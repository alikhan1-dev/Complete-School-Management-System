<?php

namespace App\Modules\Fees\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academics\Models\SchoolClass;
use App\Modules\Fees\Services\FeeCollectService;
use App\Modules\Fees\Services\FeeReceiptService;
use App\Modules\Fees\Services\FeeSubmissionNotificationService;
use App\Modules\Fees\Services\ThermalPrintService;
use App\Modules\Roles\Services\PermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use InvalidArgumentException;

/**
 * CI Studentfee — collect fees: search, ledger, single deposit, multi-collect, delete, payment search, print receipt.
 */
class StudentFeeController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected FeeCollectService $collect,
        protected FeeReceiptService $receipts,
        protected ThermalPrintService $thermal,
        protected FeeSubmissionNotificationService $feeSubmission,
    ) {
    }

    public function index(Request $request): View
    {
        abort_unless($this->permissions->hasPrivilege('collect_fees', 'can_view'), 403);

        $resultList = null;
        if ($request->isMethod('post') || $request->filled('search_text') || $request->filled('class_id')) {
            if ($request->input('search_type') === 'keyword_search') {
                $request->validate(['search_text' => ['required', 'string', 'max:255']]);
                $resultList = $this->collect->searchStudents(
                    null,
                    null,
                    (string) $request->input('search_text')
                );
            } else {
                $request->validate([
                    'class_id' => ['nullable', 'integer', 'exists:classes,id'],
                    'section_id' => ['nullable', 'integer', 'exists:sections,id'],
                ]);
                $resultList = $this->collect->searchStudents(
                    $request->filled('class_id') ? (int) $request->input('class_id') : null,
                    $request->filled('section_id') ? (int) $request->input('section_id') : null
                );
            }
        }

        return view('shared::layouts.admin', [
            'title' => 'Collect Fees',
            'contentView' => 'fees::studentfee.search',
            'classes' => SchoolClass::query()->orderBy('id')->get(),
            'resultList' => $resultList,
            'filters' => [
                'search_type' => $request->input('search_type', 'class_search'),
                'class_id' => $request->input('class_id'),
                'section_id' => $request->input('section_id'),
                'search_text' => $request->input('search_text'),
            ],
        ]);
    }

    public function addfee(int $id): View
    {
        abort_unless($this->permissions->hasPrivilege('collect_fees', 'can_view'), 403);

        $student = $this->collect->findStudentBySession($id);
        abort_if(! $student, 404);

        $transportFees = [];
        if ($this->collect->transportModuleActive()) {
            $transportFees = $this->collect->getStudentTransportFees(
                $id,
                isset($student->route_pickup_point_id) ? (int) $student->route_pickup_point_id : null
            );
        }

        return view('shared::layouts.admin', [
            'title' => 'Collect Fees',
            'contentView' => 'fees::studentfee.addfee',
            'student' => $student,
            'ledger' => $this->collect->getStudentFees($id),
            'transportFees' => $transportFees,
            'discounts' => $this->collect->getStudentDiscounts($id),
            'canDelete' => $this->permissions->hasPrivilege('collect_fees', 'can_delete'),
        ]);
    }

    public function collectForm(Request $request): View
    {
        abort_unless($this->permissions->hasPrivilege('collect_fees', 'can_view'), 403);

        if ($request->input('fee_category') === 'transport') {
            $data = $request->validate([
                'student_session_id' => ['required', 'integer'],
                'transport_fees_id' => ['required', 'integer'],
            ]);

            $student = $this->collect->findStudentBySession((int) $data['student_session_id']);
            abort_if(! $student, 404);

            try {
                $balance = $this->collect->getTransportBalance((int) $data['transport_fees_id']);
            } catch (InvalidArgumentException $e) {
                abort(404, $e->getMessage());
            }

            return view('shared::layouts.admin', [
                'title' => 'Collect Transport Fee',
                'contentView' => 'fees::studentfee.collect',
                'student' => $student,
                'balance' => $balance,
                'studentFeesMasterId' => 0,
                'feeGroupsFeetypeId' => 0,
                'transportFeesId' => (int) $data['transport_fees_id'],
                'feeCategory' => 'transport',
                'availableDiscounts' => $this->collect->getAvailableDiscounts((int) $data['student_session_id']),
                'paymentModes' => FeeCollectService::PAYMENT_MODES,
            ]);
        }

        $data = $request->validate([
            'student_session_id' => ['required', 'integer'],
            'student_fees_master_id' => ['required', 'integer'],
            'fee_groups_feetype_id' => ['required', 'integer'],
        ]);

        $student = $this->collect->findStudentBySession((int) $data['student_session_id']);
        abort_if(! $student, 404);

        $balance = $this->collect->getBalance(
            (int) $data['student_fees_master_id'],
            (int) $data['fee_groups_feetype_id']
        );

        return view('shared::layouts.admin', [
            'title' => 'Collect Fee',
            'contentView' => 'fees::studentfee.collect',
            'student' => $student,
            'balance' => $balance,
            'studentFeesMasterId' => (int) $data['student_fees_master_id'],
            'feeGroupsFeetypeId' => (int) $data['fee_groups_feetype_id'],
            'transportFeesId' => 0,
            'feeCategory' => 'fees',
            'availableDiscounts' => $this->collect->getAvailableDiscounts((int) $data['student_session_id']),
            'paymentModes' => FeeCollectService::PAYMENT_MODES,
        ]);
    }

    public function addstudentfee(Request $request): JsonResponse|RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('collect_fees', 'can_view'), 403);

        $feeCategory = (string) $request->input('fee_category', 'fees');

        if ($feeCategory === 'transport') {
            $data = $request->validate([
                'transport_fees_id' => ['required', 'integer'],
                'student_session_id' => ['required', 'integer'],
                'date' => ['required', 'date'],
                'amount' => ['required', 'numeric', 'min:0'],
                'amount_discount' => ['required', 'numeric', 'min:0'],
                'amount_fine' => ['required', 'numeric', 'min:0'],
                'payment_mode' => ['required', 'string', 'in:'.implode(',', FeeCollectService::PAYMENT_MODES)],
                'description' => ['nullable', 'string'],
                'discounts' => ['nullable', 'array'],
                'discounts.*' => ['integer'],
            ]);

            /** @var \App\Modules\Staff\Models\Staff $staff */
            $staff = $request->user('staff');

            try {
                $result = $this->collect->depositTransport([
                    'student_transport_fee_id' => (int) $data['transport_fees_id'],
                    'student_session_id' => (int) $data['student_session_id'],
                    'date' => $data['date'],
                    'amount' => $data['amount'],
                    'amount_discount' => $data['amount_discount'],
                    'amount_fine' => $data['amount_fine'],
                    'payment_mode' => $data['payment_mode'],
                    'description' => $data['description'] ?? '',
                    'discounts' => $data['discounts'] ?? [],
                ], $staff);
            } catch (InvalidArgumentException $e) {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json(['status' => 'fail', 'error' => ['amount' => $e->getMessage()]], 422);
                }

                return back()->withInput()->withErrors(['amount' => $e->getMessage()]);
            }

            // CI Studentfee::addstudentfee → mailsmsconf::mailsms('fee_submission') — live send deferred.
            $this->feeSubmission->queueSingle([
                'invoice_id' => $result['invoice_id'],
                'sub_invoice_id' => $result['sub_invoice_id'],
                'fee_category' => 'transport',
                'student_session_id' => (int) $data['student_session_id'],
                'transport_fees_id' => (int) $data['transport_fees_id'],
                'staff_id' => (int) $staff->id,
                'guardian_phone' => $request->input('guardian_phone'),
                'guardian_email' => $request->input('guardian_email'),
                'parent_app_key' => $request->input('parent_app_key'),
            ]);

            $message = 'Transport fee collected. Payment ID: '.$result['invoice_id'].'/'.$result['sub_invoice_id'];

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'error' => '',
                    'invoice_id' => $result['invoice_id'],
                    'sub_invoice_id' => $result['sub_invoice_id'],
                    'message' => $message,
                ]);
            }

            return redirect()
                ->route('fees.studentfee.addfee', (int) $data['student_session_id'])
                ->with('success', $message);
        }

        $data = $request->validate([
            'student_fees_master_id' => ['required', 'integer'],
            'fee_groups_feetype_id' => ['required', 'integer'],
            'student_session_id' => ['required', 'integer'],
            'date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0'],
            'amount_discount' => ['required', 'numeric', 'min:0'],
            'amount_fine' => ['required', 'numeric', 'min:0'],
            'payment_mode' => ['required', 'string', 'in:'.implode(',', FeeCollectService::PAYMENT_MODES)],
            'description' => ['nullable', 'string'],
            'discounts' => ['nullable', 'array'],
            'discounts.*' => ['integer'],
        ]);

        /** @var \App\Modules\Staff\Models\Staff $staff */
        $staff = $request->user('staff');

        try {
            $result = $this->collect->deposit([
                'student_fees_master_id' => (int) $data['student_fees_master_id'],
                'fee_groups_feetype_id' => (int) $data['fee_groups_feetype_id'],
                'student_session_id' => (int) $data['student_session_id'],
                'date' => $data['date'],
                'amount' => $data['amount'],
                'amount_discount' => $data['amount_discount'],
                'amount_fine' => $data['amount_fine'],
                'payment_mode' => $data['payment_mode'],
                'description' => $data['description'] ?? '',
                'discounts' => $data['discounts'] ?? [],
            ], $staff);
        } catch (InvalidArgumentException $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['status' => 'fail', 'error' => ['amount' => $e->getMessage()]], 422);
            }

            return back()->withInput()->withErrors(['amount' => $e->getMessage()]);
        }

        // CI Studentfee::addstudentfee → mailsmsconf::mailsms('fee_submission') — live send deferred.
        $this->feeSubmission->queueSingle([
            'invoice_id' => $result['invoice_id'],
            'sub_invoice_id' => $result['sub_invoice_id'],
            'fee_category' => 'fees',
            'student_session_id' => (int) $data['student_session_id'],
            'fee_groups_feetype_id' => (int) $data['fee_groups_feetype_id'],
            'student_fees_master_id' => (int) $data['student_fees_master_id'],
            'fee_session_group_id' => (int) $request->input('fee_session_group_id', 0),
            'staff_id' => (int) $staff->id,
            'guardian_phone' => $request->input('guardian_phone'),
            'guardian_email' => $request->input('guardian_email'),
            'parent_app_key' => $request->input('parent_app_key'),
        ]);

        $message = 'Fee collected. Payment ID: '.$result['invoice_id'].'/'.$result['sub_invoice_id'];

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'success',
                'error' => '',
                'invoice_id' => $result['invoice_id'],
                'sub_invoice_id' => $result['sub_invoice_id'],
                'message' => $message,
            ]);
        }

        return redirect()
            ->route('fees.studentfee.addfee', (int) $data['student_session_id'])
            ->with('success', $message);
    }

    /**
     * CI getcollectfee — prepare multi-collect form from selected ledger lines.
     */
    public function collectGroupForm(Request $request): View|RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('collect_fees', 'can_view'), 403);

        $data = $request->validate([
            'student_session_id' => ['required', 'integer'],
            'selected' => ['required', 'array', 'min:1'],
            'selected.*' => ['string'],
        ]);

        $studentSessionId = (int) $data['student_session_id'];
        $student = $this->collect->findStudentBySession($studentSessionId);
        abort_if(! $student, 404);

        $lines = $this->collect->resolveSelectedLines($studentSessionId, $data['selected']);
        if ($lines === []) {
            return redirect()
                ->route('fees.studentfee.addfee', $studentSessionId)
                ->withErrors(['selected' => 'Select at least one unpaid fee line.']);
        }

        return view('shared::layouts.admin', [
            'title' => 'Collect Fees (Group)',
            'contentView' => 'fees::studentfee.collect_group',
            'student' => $student,
            'lines' => $lines,
            'paymentModes' => FeeCollectService::PAYMENT_MODES,
        ]);
    }

    /**
     * CI addfeegrp — deposit multiple fee lines (no payment-time discounts).
     */
    public function addfeegrp(Request $request): JsonResponse|RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('collect_fees', 'can_view'), 403);

        $data = $request->validate([
            'student_session_id' => ['required', 'integer'],
            'collected_date' => ['required', 'date'],
            'payment_mode_fee' => ['required', 'string', 'in:'.implode(',', FeeCollectService::PAYMENT_MODES)],
            'fee_gupcollected_note' => ['nullable', 'string'],
            'row_counter' => ['required', 'array', 'min:1'],
            'row_counter.*' => ['integer'],
        ]);

        $studentSessionId = (int) $data['student_session_id'];
        $lines = [];
        foreach ($data['row_counter'] as $row) {
            $amount = (float) $request->input('fee_amount_'.$row, 0);
            if ($amount <= 0) {
                continue;
            }

            $category = (string) $request->input('fee_category_'.$row, 'fees');
            $transportId = (int) $request->input('trans_fee_id_'.$row, 0);
            $fine = (float) $request->input('fee_groups_feetype_fine_amount_'.$row, 0);

            if ($category === 'transport' || $transportId > 0) {
                if ($transportId <= 0) {
                    continue;
                }
                $lines[] = [
                    'fee_category' => 'transport',
                    'student_transport_fee_id' => $transportId,
                    'student_fees_master_id' => 0,
                    'fee_groups_feetype_id' => 0,
                    'amount' => $amount,
                    'amount_fine' => $fine,
                ];

                continue;
            }

            $masterId = (int) $request->input('student_fees_master_id_'.$row);
            $feetypeId = (int) $request->input('fee_groups_feetype_id_'.$row);
            if ($masterId <= 0 || $feetypeId <= 0) {
                continue;
            }
            $lines[] = [
                'fee_category' => 'fees',
                'student_fees_master_id' => $masterId,
                'fee_groups_feetype_id' => $feetypeId,
                'student_transport_fee_id' => 0,
                'amount' => $amount,
                'amount_fine' => $fine,
            ];
        }

        /** @var \App\Modules\Staff\Models\Staff $staff */
        $staff = $request->user('staff');

        try {
            $results = $this->collect->depositCollections(
                $lines,
                $staff,
                $data['collected_date'],
                $data['payment_mode_fee'],
                (string) ($data['fee_gupcollected_note'] ?? ''),
                $studentSessionId
            );
        } catch (InvalidArgumentException $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['status' => 0, 'error' => ['row_counter' => $e->getMessage()]], 422);
            }

            return back()->withInput()->withErrors(['row_counter' => $e->getMessage()]);
        }

        // CI addfeegrp → mailsmsconf::mailsms('fee_submission', send_type=group) — live send deferred.
        $this->feeSubmission->queueGroup($studentSessionId, (int) $staff->id, $results);

        $ids = array_map(
            fn (array $r) => $r['invoice_id'].'/'.$r['sub_invoice_id'],
            $results
        );
        $message = 'Fees collected. Payment IDs: '.implode(', ', $ids);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['status' => 1, 'error' => '', 'message' => $message, 'invoices' => $results]);
        }

        return redirect()
            ->route('fees.studentfee.addfee', $studentSessionId)
            ->with('success', $message);
    }

    public function deleteFee(Request $request): JsonResponse|RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('collect_fees', 'can_delete'), 403);

        $data = $request->validate([
            'main_invoice' => ['required', 'integer'],
            'sub_invoice' => ['required', 'integer'],
            'student_session_id' => ['nullable', 'integer'],
        ]);

        $this->collect->deletePayment((int) $data['main_invoice'], (int) $data['sub_invoice']);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['status' => 'success', 'result' => 'success']);
        }

        $sessionId = (int) ($data['student_session_id'] ?? 0);
        if ($sessionId > 0) {
            return redirect()
                ->route('fees.studentfee.addfee', $sessionId)
                ->with('success', 'Payment deleted.');
        }

        return back()->with('success', 'Payment deleted.');
    }

    public function searchpayment(Request $request): View
    {
        abort_unless($this->permissions->hasPrivilege('search_fees_payment', 'can_view')
            || $this->permissions->hasPrivilege('collect_fees', 'can_view'), 403);

        $payment = null;
        if ($request->isMethod('post') || $request->filled('payment_id')) {
            $request->validate(['payment_id' => ['required', 'string', 'max:50']]);
            $payment = $this->collect->findPayment((string) $request->input('payment_id'));
        }

        return view('shared::layouts.admin', [
            'title' => 'Search Fees Payment',
            'contentView' => 'fees::studentfee.searchpayment',
            'payment' => $payment,
            'paymentId' => $request->input('payment_id'),
            'searched' => $request->isMethod('post') || $request->filled('payment_id'),
        ]);
    }

    /**
     * CI studentfee/feesearch — search due/unpaid fee lines by fee type + class/section.
     */
    public function feesearch(Request $request): View
    {
        abort_unless($this->permissions->hasPrivilege('search_due_fees', 'can_view')
            || $this->permissions->hasPrivilege('collect_fees', 'can_view'), 403);

        $feeOptions = $this->collect->feeOptionsForDueSearch();
        $results = null;
        $selected = [];

        if ($request->isMethod('post')) {
            $data = $request->validate([
                'feegroup' => ['required', 'array', 'min:1'],
                'feegroup.*' => ['string'],
                'class_id' => ['nullable', 'integer', 'exists:classes,id'],
                'section_id' => ['nullable', 'integer', 'exists:sections,id'],
            ]);

            $selected = $data['feegroup'];
            $feetypeIds = [];
            foreach ($selected as $raw) {
                $parts = explode('-', (string) $raw, 2);
                if (count($parts) === 2 && is_numeric($parts[1])) {
                    $feetypeIds[] = (int) $parts[1];
                } elseif (is_numeric($raw)) {
                    $feetypeIds[] = (int) $raw;
                }
            }

            $results = $this->collect->searchDueFees(
                $feetypeIds,
                $request->filled('class_id') ? (int) $data['class_id'] : null,
                $request->filled('section_id') ? (int) $data['section_id'] : null
            );
        }

        $groupedOptions = [];
        foreach ($feeOptions as $opt) {
            $gid = (int) $opt->fee_session_group_id;
            if (! isset($groupedOptions[$gid])) {
                $groupedOptions[$gid] = [
                    'id' => $gid,
                    'group_name' => $opt->group_name,
                    'feetypes' => [],
                ];
            }
            $groupedOptions[$gid]['feetypes'][] = $opt;
        }

        return view('shared::layouts.admin', [
            'title' => 'Search Due Fees',
            'contentView' => 'fees::studentfee.feesearch',
            'classes' => SchoolClass::query()->orderBy('id')->get(),
            'groupedOptions' => $groupedOptions,
            'results' => $results,
            'filters' => [
                'feegroup' => $selected,
                'class_id' => $request->input('class_id'),
                'section_id' => $request->input('section_id'),
            ],
        ]);
    }

    /**
     * CI Studentfee::printFeesByName — JSON {status:1, page:html} for AJAX print popup.
     * Switches to thermalPrintFeesByName when thermal module active + is_print=1.
     */
    public function printFeesByName(Request $request): JsonResponse
    {
        abort_unless($this->permissions->hasPrivilege('collect_fees', 'can_view'), 403);

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

        $view = $this->thermal->viewName('fees::print.printFeesByName');
        $page = view($view, $this->receiptViewData($payload))->render();

        return response()->json(['status' => 1, 'page' => $page]);
    }

    /**
     * Direct printable receipt page (same payload as printFeesByName).
     */
    public function printFeesByNamePage(Request $request): Response|View
    {
        abort_unless($this->permissions->hasPrivilege('collect_fees', 'can_view'), 403);

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

        $view = $this->thermal->viewName('fees::print.printFeesByName');

        return response()->view($view, $this->receiptViewData($payload));
    }

    /**
     * CI Studentfee::printFeesByGroup — JSON {status:1, page:html} fee-line ledger receipt.
     */
    public function printFeesByGroup(Request $request): JsonResponse
    {
        abort_unless($this->permissions->hasPrivilege('collect_fees', 'can_view'), 403);

        $data = $request->validate([
            'fee_category' => ['nullable', 'string', 'in:fees,transport'],
            'trans_fee_id' => ['nullable', 'integer'],
            'fee_session_group_id' => ['nullable', 'integer'],
            'fee_master_id' => ['nullable', 'integer'],
            'fee_groups_feetype_id' => ['nullable', 'integer'],
            'student_session_id' => ['nullable', 'integer'],
        ]);

        $payload = $this->receipts->groupReceiptPayload(
            (string) ($data['fee_category'] ?? 'fees'),
            $data
        );
        abort_if($payload === null, 404);

        $view = $this->thermal->viewName('fees::print.printFeesByGroup');
        $page = view($view, $this->groupReceiptViewData($payload))->render();

        return response()->json(['status' => 1, 'page' => $page]);
    }

    /**
     * Direct printable fee-line ledger (same payload as printFeesByGroup).
     */
    public function printFeesByGroupPage(Request $request): Response|View
    {
        abort_unless($this->permissions->hasPrivilege('collect_fees', 'can_view'), 403);

        $data = $request->validate([
            'fee_category' => ['nullable', 'string', 'in:fees,transport'],
            'trans_fee_id' => ['nullable', 'integer'],
            'fee_session_group_id' => ['nullable', 'integer'],
            'fee_master_id' => ['nullable', 'integer'],
            'fee_groups_feetype_id' => ['nullable', 'integer'],
        ]);

        $payload = $this->receipts->groupReceiptPayload(
            (string) ($data['fee_category'] ?? 'fees'),
            $data
        );
        abort_if($payload === null, 404);

        $view = $this->thermal->viewName('fees::print.printFeesByGroup');

        return response()->view($view, $this->groupReceiptViewData($payload));
    }

    /**
     * CI Studentfee::printFeesByGroupArray — HTML response for selected fee lines.
     */
    public function printFeesByGroupArray(Request $request): Response|View
    {
        abort_unless($this->permissions->hasPrivilege('collect_fees', 'can_view'), 403);

        $data = $request->validate([
            'data' => ['required', 'string'],
        ]);

        $decoded = json_decode($data['data'], true);
        abort_unless(is_array($decoded) && $decoded !== [], 422);

        $payloads = $this->receipts->groupReceiptPayloads($decoded);
        abort_if($payloads === [], 404);

        $view = $this->thermal->viewName('fees::print.printFeesByGroupArray');

        return response()->view($view, $this->groupArrayReceiptViewData($payloads));
    }

    /**
     * @param  array{feeList:object,fee_category:string,line:array<string,mixed>}  $payload
     * @return array<string, mixed>
     */
    protected function groupReceiptViewData(array $payload): array
    {
        return array_merge($this->sharedPrintViewData(), [
            'feeList' => $payload['feeList'],
            'line' => $payload['line'],
            'studentName' => $this->receipts->studentDisplayName($payload['feeList']),
        ]);
    }

    /**
     * @param  list<array{feeList:object,fee_category:string,line:array<string,mixed>}>  $payloads
     * @return array<string, mixed>
     */
    protected function groupArrayReceiptViewData(array $payloads): array
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
        $data = [
            'copies' => $this->receipts->invoiceCopiesPublic(),
            'printDate' => $this->receipts->formatDate(now()->format('Y-m-d')),
            'headerUrl' => $this->receipts->receiptHeaderUrl(),
            'footerHtml' => $this->receipts->receiptFooterHtml(),
            'singlePagePrint' => $this->receipts->singlePagePrint(),
            'currencySymbol' => $this->receipts->currencySymbol(),
            'formatAmount' => fn (float|int|string $amount): string => $this->receipts->formatAmount($amount),
            'groupStatusLabel' => fn (string $status): string => $this->receipts->groupStatusLabel($status),
        ];

        // CI Studentfee passes thermal_print_result into thermal views when enabled.
        if ($this->thermal->isEnabled()) {
            $data['thermal_print'] = $this->thermal->settings() ?? [
                'school_name' => '',
                'address' => '',
                'footer_text' => '',
                'is_print' => 0,
            ];
        }

        return $data;
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
    protected function receiptViewData(array $payload): array
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
}
