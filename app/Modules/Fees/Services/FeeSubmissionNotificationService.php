<?php

namespace App\Modules\Fees\Services;

use Illuminate\Support\Facades\DB;

/**
 * CI Studentfee collect → mailsmsconf::mailsms('fee_submission', …).
 * Live mail/SMS/WhatsApp/push delivery deferred (Communication gateways).
 */
class FeeSubmissionNotificationService
{
    public function __construct(
        protected FeeReceiptTokenService $tokens,
    ) {
    }

    /**
     * CI Customlib::sendMailSMS('fee_submission') channel flags.
     *
     * @return array{
     *     mail:bool,
     *     sms:bool,
     *     whatsapp:bool,
     *     notification:bool,
     *     template:string,
     *     subject:string,
     *     template_id:string,
     *     whatsapp_template_id:string
     * }
     */
    public function flags(): array
    {
        $row = DB::table('notification_setting')
            ->where('type', 'fee_submission')
            ->first();

        if (! $row) {
            return [
                'mail' => false,
                'sms' => false,
                'whatsapp' => false,
                'notification' => false,
                'template' => '',
                'subject' => '',
                'template_id' => '',
                'whatsapp_template_id' => '',
            ];
        }

        return [
            'mail' => (string) ($row->is_mail ?? '0') === '1',
            'sms' => (string) ($row->is_sms ?? '0') === '1',
            'whatsapp' => (int) ($row->is_whatsapp ?? 0) === 1,
            'notification' => (string) ($row->is_notification ?? '0') === '1',
            'template' => (string) ($row->template ?? ''),
            'subject' => (string) ($row->subject ?? ''),
            'template_id' => (string) ($row->template_id ?? ''),
            'whatsapp_template_id' => (string) ($row->whatsapp_template_id ?? ''),
        ];
    }

    /**
     * Single-line collect (CI addstudentfee after fee_deposit).
     *
     * @param  array{
     *     invoice_id:int,
     *     sub_invoice_id:int,
     *     fee_category:string,
     *     student_session_id:int,
     *     transport_fees_id?:int,
     *     fee_groups_feetype_id?:int,
     *     student_fees_master_id?:int,
     *     fee_session_group_id?:int,
     *     staff_id:int,
     *     guardian_phone?:string|null,
     *     guardian_email?:string|null,
     *     parent_app_key?:string|null
     * }  $context
     * @return array{
     *     accepted:bool,
     *     channels:array{mail:bool,sms:bool,whatsapp:bool,notification:bool},
     *     payload:array<string,mixed>,
     *     deferred:true
     * }
     */
    public function queueSingle(array $context): array
    {
        $flags = $this->flags();
        $payload = $this->buildSinglePayload($context);
        $hasTemplate = trim($flags['template']) !== '';
        $accepted = $hasTemplate && ($flags['mail'] || $flags['sms'] || $flags['whatsapp'] || $flags['notification']);

        // Live Mail/SMS/WhatsApp/push gateways deferred.
        return [
            'accepted' => $accepted,
            'channels' => [
                'mail' => $flags['mail'] && $hasTemplate,
                'sms' => $flags['sms'] && $hasTemplate,
                'whatsapp' => $flags['whatsapp'] && $hasTemplate,
                'notification' => $flags['notification'] && $hasTemplate,
            ],
            'payload' => $payload,
            'deferred' => true,
        ];
    }

    /**
     * Multi-collect (CI addfeegrp → send_type=group).
     *
     * @param  list<array{
     *     invoice_id:int,
     *     sub_invoice_id:int,
     *     fee_category:string,
     *     student_transport_fee_id?:int|null,
     *     fee_groups_feetype_id?:int|null,
     *     student_fees_master_id?:int|null,
     *     fee_session_group_id?:int|null
     * }>  $deposits
     * @return array{
     *     accepted:bool,
     *     channels:array{mail:bool,sms:bool,whatsapp:bool,notification:bool},
     *     payload:array<string,mixed>,
     *     deferred:true
     * }
     */
    public function queueGroup(int $studentSessionId, int $staffId, array $deposits): array
    {
        $flags = $this->flags();
        $payload = $this->buildGroupPayload($studentSessionId, $staffId, $deposits);
        $hasTemplate = trim($flags['template']) !== '';
        $accepted = $hasTemplate && ($flags['mail'] || $flags['sms'] || $flags['whatsapp'] || $flags['notification']);

        return [
            'accepted' => $accepted,
            'channels' => [
                'mail' => $flags['mail'] && $hasTemplate,
                'sms' => $flags['sms'] && $hasTemplate,
                'whatsapp' => $flags['whatsapp'] && $hasTemplate,
                'notification' => $flags['notification'] && $hasTemplate,
            ],
            'payload' => $payload,
            'deferred' => true,
        ];
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function buildSinglePayload(array $context): array
    {
        $studentSessionId = (int) ($context['student_session_id'] ?? 0);
        $feeCategory = (string) ($context['fee_category'] ?? 'fees');
        $transportFeesId = (int) ($context['transport_fees_id'] ?? 0);
        $feeGroupsFeetypeId = (int) ($context['fee_groups_feetype_id'] ?? 0);
        $studentFeesMasterId = (int) ($context['student_fees_master_id'] ?? 0);
        $feeSessionGroupId = (int) ($context['fee_session_group_id'] ?? 0);
        $staffId = (int) ($context['staff_id'] ?? 0);
        $invoiceId = (int) ($context['invoice_id'] ?? 0);
        $subInvoiceId = (int) ($context['sub_invoice_id'] ?? 0);

        if ($feeSessionGroupId <= 0 && $studentFeesMasterId > 0) {
            $feeSessionGroupId = (int) (DB::table('student_fees_master')
                ->where('id', $studentFeesMasterId)
                ->value('fee_session_group_id') ?? 0);
        }

        $contacts = $this->studentContacts($studentSessionId);
        $feeMeta = $feeCategory === 'transport'
            ? $this->transportFeeMeta($transportFeesId)
            : $this->feesFeeMeta($feeGroupsFeetypeId, $studentSessionId);

        $token = $this->tokens->encode([
            'invoice_id' => $invoiceId,
            'fee_category' => $feeCategory,
            'transport_fees_id' => $transportFeesId,
            'fee_groups_feetype_id' => $feeGroupsFeetypeId,
            'student_fees_master_id' => $studentFeesMasterId,
            'fee_session_group_id' => $feeSessionGroupId,
            'type' => 'staff',
            'created_by' => $staffId,
        ]);

        return array_merge($feeMeta, [
            'invoice' => json_encode([
                'invoice_id' => $invoiceId,
                'sub_invoice_id' => $subInvoiceId,
            ]),
            'student_session_id' => $studentSessionId,
            'student_id' => $contacts['student_id'],
            'contact_no' => (string) ($context['guardian_phone'] ?? $contacts['guardian_phone']),
            'email' => (string) ($context['guardian_email'] ?? $contacts['guardian_email']),
            'parent_app_key' => (string) ($context['parent_app_key'] ?? $contacts['parent_app_key']),
            'fee_category' => $feeCategory,
            'fee_receipt_url' => $this->tokens->absoluteUrl($token),
            'send_type' => 'single',
        ]);
    }

    /**
     * @param  list<array<string, mixed>>  $deposits
     * @return array<string, mixed>
     */
    public function buildGroupPayload(int $studentSessionId, int $staffId, array $deposits): array
    {
        $contacts = $this->studentContacts($studentSessionId);
        $invoices = [];
        $receiptUrls = [];
        $feeGroupName = [];
        $type = [];
        $code = [];
        $fineType = [];
        $dueDate = [];
        $finePercentage = [];
        $fineAmount = [];
        $amount = [];
        $lastCategory = 'fees';

        foreach ($deposits as $deposit) {
            $feeCategory = (string) ($deposit['fee_category'] ?? 'fees');
            $lastCategory = $feeCategory;
            $transportId = (int) ($deposit['student_transport_fee_id'] ?? 0);
            $feeGroupsFeetypeId = (int) ($deposit['fee_groups_feetype_id'] ?? 0);
            $studentFeesMasterId = (int) ($deposit['student_fees_master_id'] ?? 0);
            $feeSessionGroupId = (int) ($deposit['fee_session_group_id'] ?? 0);
            $invoiceId = (int) ($deposit['invoice_id'] ?? 0);
            $subInvoiceId = (int) ($deposit['sub_invoice_id'] ?? 0);

            if ($feeSessionGroupId <= 0 && $studentFeesMasterId > 0) {
                $feeSessionGroupId = (int) (DB::table('student_fees_master')
                    ->where('id', $studentFeesMasterId)
                    ->value('fee_session_group_id') ?? 0);
            }

            $invoices[] = [
                'invoice_id' => $invoiceId,
                'sub_invoice_id' => $subInvoiceId,
                'fee_category' => $feeCategory,
            ];

            $token = $this->tokens->encode([
                'invoice_id' => $invoiceId,
                'fee_category' => $feeCategory,
                'transport_fees_id' => $transportId,
                'fee_groups_feetype_id' => $feeGroupsFeetypeId,
                'student_fees_master_id' => $studentFeesMasterId,
                'fee_session_group_id' => $feeSessionGroupId,
                'type' => 'staff',
                'created_by' => $staffId,
            ]);
            $receiptUrls[] = $this->tokens->absoluteUrl($token);

            $meta = $feeCategory === 'transport'
                ? $this->transportFeeMeta($transportId)
                : $this->feesFeeMeta($feeGroupsFeetypeId, $studentSessionId);

            $feeGroupName[] = (string) ($meta['fee_group_name'] ?? '');
            $type[] = (string) ($meta['type'] ?? '');
            $code[] = (string) ($meta['code'] ?? '-');
            $fineType[] = (string) ($meta['fine_type'] ?? '');
            $dueDate[] = (string) ($meta['due_date'] ?? '');
            $finePercentage[] = (string) ($meta['fine_percentage'] ?? '');
            $fineAmount[] = (string) ($meta['fine_amount'] ?? '');
            $amount[] = (string) ($meta['amount'] ?? '');
        }

        return [
            'student_id' => $contacts['student_id'],
            'student_session_id' => $studentSessionId,
            'invoice' => $invoices,
            'contact_no' => $contacts['guardian_phone'],
            'email' => $contacts['email'] !== '' ? $contacts['email'] : $contacts['guardian_email'],
            'parent_app_key' => $contacts['parent_app_key'],
            'amount' => '(' . implode(',', $amount) . ')',
            'fine_type' => '(' . implode(',', $fineType) . ')',
            'due_date' => '(' . implode(',', $dueDate) . ')',
            'fine_percentage' => '(' . implode(',', $finePercentage) . ')',
            'fine_amount' => '(' . implode(',', $fineAmount) . ')',
            'fee_group_name' => '(' . implode(',', $feeGroupName) . ')',
            'type' => '(' . implode(',', $type) . ')',
            'code' => '(' . implode(',', $code) . ')',
            'fee_receipt_url' => '(' . implode(', ', $receiptUrls) . ')',
            'fee_category' => $lastCategory,
            'send_type' => 'group',
        ];
    }

    /**
     * @return array{student_id:int,guardian_phone:string,guardian_email:string,email:string,parent_app_key:string}
     */
    protected function studentContacts(int $studentSessionId): array
    {
        $row = DB::table('student_session')
            ->join('students', 'students.id', '=', 'student_session.student_id')
            ->where('student_session.id', $studentSessionId)
            ->select([
                'students.id as student_id',
                'students.guardian_phone',
                'students.guardian_email',
                'students.email',
                'students.parent_app_key',
            ])
            ->first();

        return [
            'student_id' => (int) ($row->student_id ?? 0),
            'guardian_phone' => (string) ($row->guardian_phone ?? ''),
            'guardian_email' => (string) ($row->guardian_email ?? ''),
            'email' => (string) ($row->email ?? ''),
            'parent_app_key' => (string) ($row->parent_app_key ?? ''),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function feesFeeMeta(int $feeGroupsFeetypeId, int $studentSessionId): array
    {
        if ($feeGroupsFeetypeId <= 0) {
            return [
                'fee_group_name' => '',
                'type' => '',
                'code' => '',
                'amount' => '0',
                'fine_type' => '',
                'due_date' => '',
                'fine_percentage' => '0',
                'fine_amount' => '0',
            ];
        }

        $row = DB::table('fee_groups_feetype')
            ->join('fee_groups', 'fee_groups.id', '=', 'fee_groups_feetype.fee_groups_id')
            ->join('feetype', 'feetype.id', '=', 'fee_groups_feetype.feetype_id')
            ->join('student_fees_master', 'student_fees_master.fee_session_group_id', '=', 'fee_groups_feetype.fee_session_group_id')
            ->where('fee_groups_feetype.id', $feeGroupsFeetypeId)
            ->where('student_fees_master.student_session_id', $studentSessionId)
            ->select([
                'fee_groups.name as fee_group_name',
                'feetype.type',
                'feetype.code',
                'fee_groups_feetype.amount',
                'fee_groups_feetype.due_date',
                'fee_groups_feetype.fine_type',
                'fee_groups_feetype.fine_percentage',
                'fee_groups_feetype.fine_amount',
                'student_fees_master.is_system',
                'student_fees_master.amount as balance_fee_master_amount',
            ])
            ->first();

        $amount = (float) ($row->amount ?? 0);
        if ($row && (int) ($row->is_system ?? 0) === 1) {
            $amount = (float) ($row->balance_fee_master_amount ?? 0);
        }

        return [
            'fee_group_name' => (string) ($row->fee_group_name ?? ''),
            'type' => (string) ($row->type ?? ''),
            'code' => (string) ($row->code ?? ''),
            'amount' => number_format($amount, 2, '.', ''),
            'fine_type' => (string) ($row->fine_type ?? ''),
            'due_date' => (string) ($row->due_date ?? ''),
            'fine_percentage' => number_format((float) ($row->fine_percentage ?? 0), 2, '.', ''),
            'fine_amount' => number_format((float) ($row->fine_amount ?? 0), 2, '.', ''),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function transportFeeMeta(int $transportFeesId): array
    {
        $row = DB::table('student_transport_fees')
            ->join('transport_feemaster', 'transport_feemaster.id', '=', 'student_transport_fees.transport_feemaster_id')
            ->join('route_pickup_point', 'route_pickup_point.id', '=', 'student_transport_fees.route_pickup_point_id')
            ->where('student_transport_fees.id', $transportFeesId)
            ->select([
                'transport_feemaster.month',
                'transport_feemaster.due_date',
                'transport_feemaster.fine_type',
                'transport_feemaster.fine_percentage',
                'transport_feemaster.fine_amount',
                'route_pickup_point.fees',
            ])
            ->first();

        return [
            'fee_group_name' => (string) __('system.transport_fees'),
            'type' => (string) ($row->month ?? ''),
            'code' => '',
            'amount' => number_format((float) ($row->fees ?? 0), 2, '.', ''),
            'fine_type' => (string) ($row->fine_type ?? ''),
            'due_date' => (string) ($row->due_date ?? ''),
            'fine_percentage' => number_format((float) ($row->fine_percentage ?? 0), 2, '.', ''),
            'fine_amount' => number_format((float) ($row->fine_amount ?? 0), 2, '.', ''),
        ];
    }
}
