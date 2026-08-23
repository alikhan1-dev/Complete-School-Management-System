<?php

namespace App\Modules\Fees\Services;

use App\Modules\Shared\Services\SchoolContext;
use Illuminate\Support\Facades\DB;

/**
 * CI Studentfee::printFeesByName + printFeesByGroup + Studentfeemaster_model lookups.
 * Deferred: thermal print addon, SMS on collect.
 */
class FeeReceiptService
{
    public function __construct(protected SchoolContext $school)
    {
    }

    /**
     * @return array{
     *     feeList:object,
     *     payment:object,
     *     student:object,
     *     sub_invoice_id:int,
     *     fee_category:string,
     *     copies:list<string>
     * }|null
     */
    public function receiptPayload(int $invoiceId, int $subInvoiceId, string $feeCategory = 'fees'): ?array
    {
        if ($invoiceId <= 0 || $subInvoiceId <= 0) {
            return null;
        }

        $feeList = $feeCategory === 'transport'
            ? $this->getTransportFeeByInvoice($invoiceId, $subInvoiceId)
            : $this->getFeeByInvoice($invoiceId, $subInvoiceId);

        if (! $feeList) {
            return null;
        }

        $payment = $this->paymentEntry($feeList->amount_detail ?? null, $subInvoiceId);
        if ($payment === null) {
            return null;
        }

        return [
            'feeList' => $feeList,
            'payment' => $payment,
            'student' => (object) [
                'father_name' => $feeList->father_name ?? '',
                'firstname' => $feeList->firstname ?? '',
                'middlename' => $feeList->middlename ?? '',
                'lastname' => $feeList->lastname ?? '',
                'admission_no' => $feeList->admission_no ?? '',
            ],
            'sub_invoice_id' => $subInvoiceId,
            'fee_category' => $feeCategory === 'transport' ? 'transport' : 'fees',
            'copies' => $this->invoiceCopies(),
        ];
    }

    public function studentDisplayName(object $feeList): string
    {
        $first = trim((string) ($feeList->firstname ?? ''));
        $middle = trim((string) ($feeList->middlename ?? ''));
        $last = trim((string) ($feeList->lastname ?? ''));
        $name = ((int) $this->school->get('middlename', 1) === 1) && $middle !== ''
            ? trim($first.' '.$middle)
            : $first;
        if (((int) $this->school->get('lastname', 1) === 1) && $last !== '') {
            $name = trim($name.' '.$last);
        }

        return $name !== '' ? $name : $first;
    }

    public function formatDate(mixed $value): string
    {
        if ($value === null || $value === '' || $value === '0000-00-00') {
            return '';
        }

        try {
            return \Carbon\Carbon::parse((string) $value)->format($this->school->dateFormat() ?: 'd/m/Y');
        } catch (\Throwable) {
            return (string) $value;
        }
    }

    public function receiptHeaderUrl(): ?string
    {
        // CI Setting_model::get_receiptheader — print_headerfooter.print_type = student_receipt
        $name = trim((string) (DB::table('print_headerfooter')
            ->where('print_type', 'student_receipt')
            ->value('header_image') ?? ''));

        if ($name === '') {
            $fallback = public_path('uploads/print_headerfooter/student_receipt');
            if (is_dir($fallback)) {
                $files = glob($fallback.DIRECTORY_SEPARATOR.'*');
                if (is_array($files) && $files !== []) {
                    return asset('uploads/print_headerfooter/student_receipt/'.basename($files[0]));
                }
            }

            return null;
        }

        return asset('uploads/print_headerfooter/student_receipt/'.ltrim($name, '/'));
    }

    public function receiptFooterHtml(): string
    {
        // CI Setting_model::get_receiptfooter
        return (string) (DB::table('print_headerfooter')
            ->where('print_type', 'student_receipt')
            ->value('footer_content') ?? '');
    }

    public function singlePagePrint(): bool
    {
        return (int) $this->school->get('single_page_print', 0) === 1;
    }

    public function currencySymbol(): string
    {
        return $this->school->currencySymbol();
    }

    public function feeLineLabel(object $feeList): string
    {
        $type = (string) ($feeList->type ?? '');
        $code = (string) ($feeList->code ?? '');
        if ((int) ($feeList->is_system ?? 0) === 1) {
            $typeLabel = (string) __('system.'.$type);
            if ($typeLabel === 'system.'.$type) {
                $typeLabel = $type;
            }
            $codeLabel = (string) __('system.'.$code);
            if ($codeLabel === 'system.'.$code) {
                $codeLabel = $code;
            }

            return $typeLabel.' ('.$codeLabel.')';
        }

        return $type.($code !== '' ? ' ('.$code.')' : '');
    }

    public function paymentModeLabel(mixed $mode): string
    {
        $raw = strtolower(trim((string) $mode));
        if ($raw === '') {
            return '';
        }
        $label = (string) __('system.'.$raw);

        return $label === 'system.'.$raw ? (string) $mode : $label;
    }

    public function formatAmount(float|int|string $amount): string
    {
        return number_format((float) $amount, 2, '.', '');
    }

    /**
     * @return list<string> copy labels to print (office / student)
     */
    public function invoiceCopiesPublic(): array
    {
        return $this->invoiceCopies();
    }

    /**
     * @return list<string> copy labels to print (office / student)
     */
    protected function invoiceCopies(): array
    {
        $raw = (string) $this->school->get('is_duplicate_fees_invoice', '0');
        $parts = array_values(array_filter(array_map('trim', explode(',', $raw)), fn ($v) => $v !== ''));
        if ($parts === []) {
            $parts = ['0'];
        }

        $labels = [];
        foreach ($parts as $part) {
            $labels[] = $part === '1'
                ? (string) __('system.student_copy')
                : (string) __('system.office_copy');
        }

        return $labels !== [] ? $labels : [(string) __('system.office_copy')];
    }

    protected function getFeeByInvoice(int $invoiceId, int $subInvoiceId): ?object
    {
        $row = DB::table('student_fees_deposite')
            ->join('fee_groups_feetype', 'fee_groups_feetype.id', '=', 'student_fees_deposite.fee_groups_feetype_id')
            ->join('fee_groups', 'fee_groups.id', '=', 'fee_groups_feetype.fee_groups_id')
            ->join('feetype', 'feetype.id', '=', 'fee_groups_feetype.feetype_id')
            ->join('student_fees_master', 'student_fees_master.id', '=', 'student_fees_deposite.student_fees_master_id')
            ->join('student_session', 'student_session.id', '=', 'student_fees_master.student_session_id')
            ->join('classes', 'classes.id', '=', 'student_session.class_id')
            ->join('sections', 'sections.id', '=', 'student_session.section_id')
            ->join('students', 'students.id', '=', 'student_session.student_id')
            ->where('student_fees_deposite.id', $invoiceId)
            ->whereNull('student_fees_deposite.student_transport_fee_id')
            ->select([
                'student_fees_deposite.*',
                'students.id as std_id',
                'students.firstname',
                'students.middlename',
                'students.lastname',
                'students.admission_no',
                'students.father_name',
                'student_session.class_id',
                'classes.class',
                'sections.section',
                'student_session.section_id',
                'student_session.student_id',
                'student_session.id as student_session_id',
                'fee_groups.name',
                'feetype.type',
                'feetype.code',
                'feetype.is_system',
                'student_fees_master.amount as student_fees_master_amount',
                'fee_groups_feetype.amount',
            ])
            ->first();

        if (! $row || ! $this->hasSubInvoice($row->amount_detail ?? null, $subInvoiceId)) {
            return null;
        }

        return $row;
    }

    protected function getTransportFeeByInvoice(int $invoiceId, int $subInvoiceId): ?object
    {
        $row = DB::table('student_fees_deposite')
            ->join('student_transport_fees', 'student_transport_fees.id', '=', 'student_fees_deposite.student_transport_fee_id')
            ->join('transport_feemaster', 'transport_feemaster.id', '=', 'student_transport_fees.transport_feemaster_id')
            ->join('route_pickup_point', 'route_pickup_point.id', '=', 'student_transport_fees.route_pickup_point_id')
            ->join('pickup_point', 'route_pickup_point.pickup_point_id', '=', 'pickup_point.id')
            ->join('transport_route', 'route_pickup_point.transport_route_id', '=', 'transport_route.id')
            ->join('student_session', 'student_session.id', '=', 'student_transport_fees.student_session_id')
            ->join('classes', 'classes.id', '=', 'student_session.class_id')
            ->join('sections', 'sections.id', '=', 'student_session.section_id')
            ->join('students', 'students.id', '=', 'student_session.student_id')
            ->where('student_fees_deposite.id', $invoiceId)
            ->select([
                'student_fees_deposite.*',
                'students.id as std_id',
                'students.firstname',
                'students.middlename',
                'students.lastname',
                'students.admission_no',
                'students.father_name',
                'student_session.class_id',
                'classes.class',
                'sections.section',
                'student_session.section_id',
                'student_session.student_id',
                'student_session.id as student_session_id',
                'pickup_point.name as pickup_name',
                'transport_route.route_title',
                'transport_feemaster.month',
                'transport_feemaster.due_date',
                'route_pickup_point.fees',
            ])
            ->first();

        if (! $row || ! $this->hasSubInvoice($row->amount_detail ?? null, $subInvoiceId)) {
            return null;
        }

        $monthKey = strtolower((string) ($row->month ?? ''));
        $monthLabel = $monthKey !== '' ? (string) __('system.'.$monthKey) : '';
        if ($monthLabel === 'system.'.$monthKey) {
            $monthLabel = (string) ($row->month ?? '');
        }
        $row->name = (string) __('system.transport_fees');
        $row->type = $monthLabel;
        $row->code = '-';

        return $row;
    }

    protected function hasSubInvoice(mixed $raw, int $subInvoiceId): bool
    {
        return $this->paymentEntry($raw, $subInvoiceId) !== null;
    }

    protected function paymentEntry(mixed $raw, int $subInvoiceId): ?object
    {
        if ($raw === null || $raw === '' || $raw === '0') {
            return null;
        }
        $decoded = json_decode((string) $raw, true);
        if (! is_array($decoded)) {
            return null;
        }
        $key = (string) $subInvoiceId;
        if (! isset($decoded[$key]) || ! is_array($decoded[$key])) {
            return null;
        }

        return (object) $decoded[$key];
    }

    /**
     * CI Studentfee::printFeesByGroup — single fee-line ledger receipt.
     *
     * @return array{feeList:object,fee_category:string,line:array<string,mixed>}|null
     */
    public function groupReceiptPayload(string $feeCategory, array $params): ?array
    {
        if ($feeCategory === 'transport') {
            $transFeeId = (int) ($params['trans_fee_id'] ?? 0);
            if ($transFeeId <= 0) {
                return null;
            }
            $feeList = $this->getTransportFeeById($transFeeId);
            if (! $feeList) {
                return null;
            }

            return [
                'feeList' => $feeList,
                'fee_category' => 'transport',
                'line' => $this->buildGroupLine($feeList, 'transport'),
            ];
        }

        $feeSessionGroupId = (int) ($params['fee_session_group_id'] ?? 0);
        $feeMasterId = (int) ($params['fee_master_id'] ?? 0);
        $feeGroupsFeetypeId = (int) ($params['fee_groups_feetype_id'] ?? 0);
        if ($feeSessionGroupId <= 0 || $feeMasterId <= 0 || $feeGroupsFeetypeId <= 0) {
            return null;
        }

        $feeList = $this->getDueFeeByFeeSessionGroupFeetype($feeSessionGroupId, $feeMasterId, $feeGroupsFeetypeId);
        if (! $feeList) {
            return null;
        }

        return [
            'feeList' => $feeList,
            'fee_category' => 'fees',
            'line' => $this->buildGroupLine($feeList, 'fees'),
        ];
    }

    /**
     * CI Studentfee::printFeesByGroupArray — multiple fee lines for one student.
     *
     * @param  list<array<string, mixed>>  $selections
     * @return list<array{feeList:object,fee_category:string,line:array<string,mixed>}>
     */
    public function groupReceiptPayloads(array $selections): array
    {
        $items = [];
        foreach ($selections as $selection) {
            if (! is_array($selection)) {
                continue;
            }
            $category = (string) ($selection['fee_category'] ?? 'fees');
            $payload = $this->groupReceiptPayload($category, $selection);
            if ($payload !== null) {
                $items[] = $payload;
            }
        }

        return $items;
    }

    public function groupStatusLabel(string $status): string
    {
        return match ($status) {
            'paid' => (string) __('system.paid'),
            'partial' => (string) __('system.partial'),
            default => (string) __('system.unpaid'),
        };
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildGroupLine(object $feeList, string $feeCategory): array
    {
        $due = $feeCategory === 'fees' && (int) ($feeList->is_system ?? 0) === 1
            ? (float) ($feeList->student_fees_master_amount ?? 0)
            : (float) ($feeList->amount ?? $feeList->fees ?? 0);

        $totals = ['amount' => 0.0, 'amount_discount' => 0.0, 'amount_fine' => 0.0];
        $payments = [];
        $depositeId = (int) ($feeList->student_fees_deposite_id ?? 0);
        $rawDetail = $feeList->amount_detail ?? null;

        if ($rawDetail !== null && $rawDetail !== '' && $rawDetail !== '0') {
            $decoded = json_decode((string) $rawDetail, true);
            if (is_array($decoded)) {
                foreach ($decoded as $sub => $entry) {
                    if (! is_array($entry)) {
                        continue;
                    }
                    $subId = (int) ($entry['inv_no'] ?? $sub);
                    $totals['amount'] += (float) ($entry['amount'] ?? 0);
                    $totals['amount_discount'] += (float) ($entry['amount_discount'] ?? 0);
                    $totals['amount_fine'] += (float) ($entry['amount_fine'] ?? 0);
                    $payments[] = [
                        'sub_invoice_id' => $subId,
                        'payment_id' => $depositeId.'/'.$subId,
                        'date' => $this->formatDate($entry['date'] ?? ''),
                        'payment_mode' => $this->paymentModeLabel($entry['payment_mode'] ?? ''),
                        'amount' => (float) ($entry['amount'] ?? 0),
                        'amount_fine' => (float) ($entry['amount_fine'] ?? 0),
                        'amount_discount' => (float) ($entry['amount_discount'] ?? 0),
                    ];
                }
            }
        }

        $balance = round($due - ($totals['amount'] + $totals['amount_discount']), 2);
        $status = $balance <= 0 ? 'paid' : ($totals['amount'] > 0 || $totals['amount_discount'] > 0 ? 'partial' : 'unpaid');

        if ($feeCategory === 'transport') {
            $monthKey = strtolower((string) ($feeList->month ?? ''));
            $monthLabel = $monthKey !== '' ? (string) __('system.'.$monthKey) : '';
            if ($monthLabel === 'system.'.$monthKey) {
                $monthLabel = (string) ($feeList->month ?? '');
            }
            $feeList->name = (string) __('system.transport_fees');
            $feeList->type = $monthLabel;
            $feeList->code = '-';
            $feeList->is_system = 0;
        }

        return [
            'fee_line_label' => $this->feeLineLabel($feeList),
            'due_date' => $this->formatDate($feeList->due_date ?? ''),
            'status' => $status,
            'due_amount' => $due,
            'paid_amount' => $totals['amount'],
            'paid_discount' => $totals['amount_discount'],
            'paid_fine' => $totals['amount_fine'],
            'balance' => max(0, $balance),
            'fine_amount' => (float) ($feeList->fine_amount ?? 0),
            'payments' => $payments,
            'student_fees_deposite_id' => $depositeId,
        ];
    }

    protected function getDueFeeByFeeSessionGroupFeetype(int $feeSessionGroupId, int $studentFeesMasterId, int $feeGroupsFeetypeId): ?object
    {
        return DB::table('student_fees_master')
            ->join('fee_session_groups', 'fee_session_groups.id', '=', 'student_fees_master.fee_session_group_id')
            ->join('fee_groups_feetype', 'fee_groups_feetype.fee_session_group_id', '=', 'fee_session_groups.id')
            ->join('fee_groups', 'fee_groups.id', '=', 'fee_groups_feetype.fee_groups_id')
            ->join('feetype', 'feetype.id', '=', 'fee_groups_feetype.feetype_id')
            ->leftJoin('student_fees_deposite', function ($join) {
                $join->on('student_fees_deposite.student_fees_master_id', '=', 'student_fees_master.id')
                    ->on('student_fees_deposite.fee_groups_feetype_id', '=', 'fee_groups_feetype.id');
            })
            ->join('student_session', 'student_session.id', '=', 'student_fees_master.student_session_id')
            ->join('classes', 'classes.id', '=', 'student_session.class_id')
            ->join('sections', 'sections.id', '=', 'student_session.section_id')
            ->join('students', 'students.id', '=', 'student_session.student_id')
            ->where('student_fees_master.fee_session_group_id', $feeSessionGroupId)
            ->where('student_fees_master.id', $studentFeesMasterId)
            ->where('fee_groups_feetype.id', $feeGroupsFeetypeId)
            ->select([
                'student_fees_master.id',
                'student_fees_master.is_system',
                'student_fees_master.student_session_id',
                'student_fees_master.fee_session_group_id',
                'student_fees_master.amount as student_fees_master_amount',
                'fee_groups_feetype.id as fee_groups_feetype_id',
                'students.id as student_id',
                'students.firstname',
                'students.middlename',
                'students.admission_no',
                'students.lastname',
                'students.father_name',
                'student_session.class_id',
                'classes.class',
                'sections.section',
                'student_session.section_id',
                'fee_groups_feetype.amount',
                'fee_groups_feetype.due_date',
                'fee_groups_feetype.fine_amount',
                'fee_groups_feetype.fine_type',
                'fee_groups.name',
                'feetype.code',
                'feetype.type',
                'feetype.is_system',
                DB::raw('IFNULL(student_fees_deposite.id, 0) as student_fees_deposite_id'),
                DB::raw('IFNULL(student_fees_deposite.amount_detail, 0) as amount_detail'),
            ])
            ->first();
    }

    protected function getTransportFeeById(int $transFeeId): ?object
    {
        $row = DB::table('student_transport_fees')
            ->join('transport_feemaster', 'transport_feemaster.id', '=', 'student_transport_fees.transport_feemaster_id')
            ->leftJoin('student_fees_deposite', 'student_fees_deposite.student_transport_fee_id', '=', 'student_transport_fees.id')
            ->join('student_session', 'student_session.id', '=', 'student_transport_fees.student_session_id')
            ->join('classes', 'classes.id', '=', 'student_session.class_id')
            ->join('sections', 'sections.id', '=', 'student_session.section_id')
            ->join('students', 'students.id', '=', 'student_session.student_id')
            ->join('route_pickup_point', 'route_pickup_point.id', '=', 'student_transport_fees.route_pickup_point_id')
            ->where('student_transport_fees.id', $transFeeId)
            ->select([
                'student_transport_fees.*',
                'route_pickup_point.fees',
                'transport_feemaster.month',
                'transport_feemaster.due_date',
                'transport_feemaster.fine_amount',
                'transport_feemaster.fine_type',
                'transport_feemaster.fine_percentage',
                'students.id as student_id',
                'students.firstname',
                'students.middlename',
                'students.admission_no',
                'students.lastname',
                'students.father_name',
                'student_session.class_id',
                'classes.class',
                'sections.section',
                'student_session.section_id',
                'student_session.student_id',
                DB::raw('IFNULL(student_fees_deposite.id, 0) as student_fees_deposite_id'),
                DB::raw('IFNULL(student_fees_deposite.amount_detail, 0) as amount_detail'),
            ])
            ->first();

        return $row ?: null;
    }
}
