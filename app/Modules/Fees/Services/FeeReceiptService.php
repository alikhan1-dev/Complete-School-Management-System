<?php

namespace App\Modules\Fees\Services;

use App\Modules\Shared\Services\SchoolContext;
use Illuminate\Support\Facades\DB;

/**
 * CI Studentfee::printFeesByName + Studentfeemaster_model invoice lookups.
 * Deferred: thermal print addon, printFeesByGroup / ByGroupArray, SMS on collect.
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
}
