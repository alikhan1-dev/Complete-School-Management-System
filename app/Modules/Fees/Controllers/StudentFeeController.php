<?php

namespace App\Modules\Fees\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academics\Models\SchoolClass;
use App\Modules\Fees\Services\FeeCollectService;
use App\Modules\Roles\Services\PermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

/**
 * CI Studentfee — collect fees (core): search, ledger, deposit, delete, payment search.
 */
class StudentFeeController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected FeeCollectService $collect
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

        return view('shared::layouts.admin', [
            'title' => 'Collect Fees',
            'contentView' => 'fees::studentfee.addfee',
            'student' => $student,
            'ledger' => $this->collect->getStudentFees($id),
            'discounts' => $this->collect->getStudentDiscounts($id),
            'canDelete' => $this->permissions->hasPrivilege('collect_fees', 'can_delete'),
        ]);
    }

    public function collectForm(Request $request): View
    {
        abort_unless($this->permissions->hasPrivilege('collect_fees', 'can_view'), 403);

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
            'availableDiscounts' => $this->collect->getAvailableDiscounts((int) $data['student_session_id']),
            'paymentModes' => FeeCollectService::PAYMENT_MODES,
        ]);
    }

    public function addstudentfee(Request $request): JsonResponse|RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('collect_fees', 'can_view'), 403);

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
}
