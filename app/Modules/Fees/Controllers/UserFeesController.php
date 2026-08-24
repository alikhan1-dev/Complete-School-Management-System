<?php

namespace App\Modules\Fees\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Fees\Services\FeeCollectService;
use App\Modules\Fees\Services\FeeReceiptService;
use App\Modules\Fees\Services\PortalOnlinePayService;
use App\Modules\Fees\Services\StudentFeesPortalService;
use App\Modules\Shared\Services\SchoolContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use InvalidArgumentException;
use RuntimeException;

/**
 * CI user/User::getfees + portal fee receipt print + processing-fees + online pay modal.
 */
class UserFeesController extends Controller
{
    public function __construct(
        protected StudentFeesPortalService $portal,
        protected FeeReceiptService $receipts,
        protected PortalOnlinePayService $onlinePay,
        protected FeeCollectService $collect,
        protected SchoolContext $school,
    ) {
    }

    public function getfees(): View
    {
        try {
            $data = $this->portal->pageData();
        } catch (RuntimeException $e) {
            abort(403, $e->getMessage());
        }

        return view('shared::layouts.student_parent', [
            'title' => (string) __('system.fees'),
            'contentView' => 'fees::user.getfees',
            'student' => $data['student'],
            'sessionFees' => $data['sessionFees'],
            'offlineEnabled' => $data['offlineEnabled'],
            'hasProcessingFees' => $data['hasProcessingFees'],
            'paymentMethodActive' => $data['paymentMethodActive'],
            'allowPartialPayment' => $data['allowPartialPayment'],
            'currencySymbol' => $this->school->currencySymbol(),
        ]);
    }

    /**
     * CI user/User::geBalanceFee — JSON balance/fine/discounts for single online pay modal.
     */
    public function geBalanceFee(Request $request): JsonResponse
    {
        $data = $request->validate([
            'fee_groups_feetype_id' => ['required'],
            'student_fees_master_id' => ['required'],
            'student_session_id' => ['required', 'integer'],
            'fee_category' => ['nullable', 'string', 'in:fees,transport'],
            'trans_fee_id' => ['nullable', 'integer'],
            'student_transport_fee_id' => ['nullable', 'integer'],
        ]);

        $sessionId = (int) $data['student_session_id'];
        $portalSession = $this->portal->currentStudentSessionId();
        if ($portalSession > 0 && $portalSession !== $sessionId) {
            // Prefer authenticated portal class session for ownership.
            $sessionId = $portalSession;
        }

        try {
            return response()->json($this->onlinePay->balanceFee($data, $sessionId));
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'status' => 'fail',
                'error' => ['fee_groups_feetype_id' => $e->getMessage()],
            ]);
        } catch (RuntimeException $e) {
            abort(403, $e->getMessage());
        }
    }

    /**
     * CI user/User::getcollectfee — JSON {view: html} for Pay Selected modal.
     */
    public function getcollectfee(Request $request): JsonResponse
    {
        abort_unless($this->onlinePay->hasActivePaymentMethod(), 403);

        $data = $request->validate([
            'data' => ['required', 'string'],
        ]);

        $decoded = json_decode($data['data'], true);
        abort_unless(is_array($decoded) && $decoded !== [], 422);

        $sessionId = $this->portal->currentStudentSessionId();
        abort_if($sessionId <= 0, 403);

        try {
            $lines = $this->onlinePay->collectFeeLines($decoded, $sessionId);
            $student = $this->collect->findStudentBySession($sessionId);
            abort_if(! $student, 404);
        } catch (RuntimeException $e) {
            abort(403, $e->getMessage());
        }

        $view = view('fees::user.getcollectfee', [
            'lines' => $lines,
            'student' => $student,
            'allowPartialPayment' => $this->onlinePay->allowPartialPayment(),
            'currencySymbol' => $this->school->currencySymbol(),
        ])->render();

        return response()->json(['view' => $view]);
    }

    /**
     * CI user/User::getProcessingfees — JSON {view: html} for processing fees modal.
     */
    public function getProcessingfees(): JsonResponse
    {
        try {
            $data = $this->portal->processingModalData();
        } catch (RuntimeException $e) {
            abort(403, $e->getMessage());
        }

        $view = view('fees::user.getProcessingfees', [
            'student' => $data['student'],
            'student_due_fee' => $data['student_due_fee'],
            'transport_fees' => $data['transport_fees'],
            'currencySymbol' => $this->school->currencySymbol(),
        ])->render();

        return response()->json(['view' => $view]);
    }

    /**
     * CI user/User::printFeesByName — JSON {status:1, page:html}.
     */
    public function printFeesByName(Request $request): JsonResponse
    {
        $data = $request->validate([
            'main_invoice' => ['required', 'integer'],
            'sub_invoice' => ['required', 'integer'],
            'fee_category' => ['nullable', 'string', 'in:fees,transport'],
            'student_session_id' => ['nullable', 'integer'],
        ]);

        $payload = $this->receipts->receiptPayload(
            (int) $data['main_invoice'],
            (int) $data['sub_invoice'],
            (string) ($data['fee_category'] ?? 'fees')
        );
        abort_if($payload === null, 404);

        try {
            $this->portal->assertOwnsFeeList($payload['feeList']);
        } catch (RuntimeException $e) {
            abort(403, $e->getMessage());
        }

        $page = view('fees::print.printFeesByName', $this->nameViewData($payload))->render();

        return response()->json(['status' => 1, 'page' => $page]);
    }

    /**
     * Direct printable receipt for portal (same payload as printFeesByName).
     */
    public function printFeesByNamePage(Request $request): Response|View
    {
        $data = $request->validate([
            'main_invoice' => ['required', 'integer'],
            'sub_invoice' => ['required', 'integer'],
            'fee_category' => ['nullable', 'string', 'in:fees,transport'],
        ]);

        $payload = $this->receipts->receiptPayload(
            (int) $data['main_invoice'],
            (int) $data['sub_invoice'],
            (string) ($data['fee_category'] ?? 'fees')
        );
        abort_if($payload === null, 404);

        try {
            $this->portal->assertOwnsFeeList($payload['feeList']);
        } catch (RuntimeException $e) {
            abort(403, $e->getMessage());
        }

        return response()->view('fees::print.printFeesByName', $this->nameViewData($payload));
    }

    /**
     * CI user/User::printFeesByGroupArray — HTML for selected fee lines.
     */
    public function printFeesByGroupArray(Request $request): Response|View
    {
        $data = $request->validate([
            'data' => ['required', 'string'],
        ]);

        $decoded = json_decode($data['data'], true);
        abort_unless(is_array($decoded) && $decoded !== [], 422);

        $payloads = $this->receipts->groupReceiptPayloads($decoded);
        abort_if($payloads === [], 404);

        try {
            foreach ($payloads as $payload) {
                $this->portal->assertOwnsFeeList($payload['feeList']);
            }
        } catch (RuntimeException $e) {
            abort(403, $e->getMessage());
        }

        return response()->view(
            'fees::print.printFeesByGroupArray',
            $this->groupArrayViewData($payloads)
        );
    }

    /**
     * @param  array{
     *     feeList:object,
     *     payment:object,
     *     student:object,
     *     sub_invoice_id:int,
     *     fee_category:string,
     *     copies:list<string>
     * }  $payload
     * @return array<string, mixed>
     */
    protected function nameViewData(array $payload): array
    {
        return array_merge($this->sharedPrintViewData(), [
            'feeList' => $payload['feeList'],
            'payment' => $payload['payment'],
            'student' => $payload['student'],
            'sub_invoice_id' => $payload['sub_invoice_id'],
            'fee_category' => $payload['fee_category'],
            'studentName' => $this->receipts->studentDisplayName($payload['feeList']),
            'feeLineLabel' => $this->receipts->feeLineLabel($payload['feeList']),
            'paymentModeLabel' => $this->receipts->paymentModeLabel($payload['payment']->payment_mode ?? ''),
            'paymentDate' => $this->receipts->formatDate($payload['payment']->date ?? ''),
        ]);
    }

    /**
     * @param  list<array{feeList:object,fee_category:string,line:array<string,mixed>}>  $payloads
     * @return array<string, mixed>
     */
    protected function groupArrayViewData(array $payloads): array
    {
        $lines = array_map(fn (array $payload) => $payload['line'], $payloads);
        $headerFeeList = $payloads[0]['feeList'];

        return array_merge($this->sharedPrintViewData(), [
            'headerFeeList' => $headerFeeList,
            'lines' => $lines,
            'studentName' => $this->receipts->studentDisplayName($headerFeeList),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function sharedPrintViewData(): array
    {
        return [
            'copies' => $this->receipts->invoiceCopiesPublic(),
            'printDate' => $this->receipts->formatDate(now()->format('Y-m-d')),
            'headerUrl' => $this->receipts->receiptHeaderUrl(),
            'footerHtml' => $this->receipts->receiptFooterHtml(),
            'singlePagePrint' => $this->receipts->singlePagePrint(),
            'currencySymbol' => $this->receipts->currencySymbol(),
            'formatAmount' => fn (float|int|string $amount): string => $this->receipts->formatAmount($amount),
            'groupStatusLabel' => fn (string $status): string => $this->receipts->groupStatusLabel($status),
        ];
    }
}
