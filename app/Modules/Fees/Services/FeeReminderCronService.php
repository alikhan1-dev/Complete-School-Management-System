<?php

namespace App\Modules\Fees\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * CI Cron::feereminder — build fees_reminder payloads for due fee lines.
 * Live mail/SMS/WhatsApp/push delivery deferred (Communication).
 */
class FeeReminderCronService
{
    public function __construct(
        protected FeeReminderService $reminders,
        protected FeeReceiptService $receipts,
    ) {
    }

    public function cronSecretKey(): string
    {
        return (string) (DB::table('sch_settings')->limit(1)->value('cron_secret_key') ?? '');
    }

    public function assertValidKey(string $key): void
    {
        $expected = $this->cronSecretKey();
        if ($key === '' || $expected === '' || ! hash_equals($expected, $key)) {
            throw new InvalidArgumentException('Invalid Key or Direct access is not allowed');
        }
    }

    /**
     * CI Cron::feereminder — collect overdue/upcoming fee lines and queue notification persist.
     *
     * @return array{
     *     reminder_rules:int,
     *     candidates:int,
     *     queued:int,
     *     accepted:int,
     *     deferred:true,
     *     recipients:list<array<string,mixed>>
     * }
     */
    public function run(string $key, ?string $today = null): array
    {
        $this->assertValidKey($key);

        $todayDate = $today ?: now()->format('Y-m-d');
        $activeRules = $this->reminders->activeList();
        $recipients = [];

        foreach ($activeRules as $rule) {
            $days = max(0, (int) $rule->day);
            $targetDate = $rule->reminder_type === 'before'
                ? date('Y-m-d', strtotime('+'.$days.' days', strtotime($todayDate)))
                : date('Y-m-d', strtotime('-'.$days.' days', strtotime($todayDate)));

            foreach ($this->feeTypeLinesForDueDate($targetDate) as $line) {
                $recipients = array_merge($recipients, $this->studentsForFeeType($line, $targetDate));
            }

            // CI hardcoded $dt="2022-09-09" for transport — documented legacy bug.
            // Laravel uses the computed reminder target date so transport reminders work.
            foreach ($this->transportLinesForDueDate($targetDate) as $line) {
                $recipients[] = $line;
            }
        }

        $recipients = array_values(array_filter(
            $recipients,
            fn (array $row) => (float) ($row['due_amount'] ?? 0) > 0
        ));

        $queued = 0;
        $accepted = 0;
        foreach ($recipients as $recipient) {
            $result = $this->queueFeesReminder($recipient);
            $queued++;
            if ($result['accepted']) {
                $accepted++;
            }
        }

        return [
            'reminder_rules' => count($activeRules),
            'candidates' => count($recipients),
            'queued' => $queued,
            'accepted' => $accepted,
            'deferred' => true,
            'recipients' => $recipients,
        ];
    }

    /**
     * @param  array<string, mixed>  $sender
     * @return array{accepted:bool,channels:array{mail:bool,sms:bool,whatsapp:bool,notification:bool},deferred:true}
     */
    public function queueFeesReminder(array $sender): array
    {
        $flags = $this->feesReminderFlags();
        $hasTemplate = trim($flags['template']) !== '';
        $accepted = $hasTemplate && ($flags['mail'] || $flags['sms'] || $flags['whatsapp'] || $flags['notification']);

        // Live gateway delivery deferred.
        unset($sender);

        return [
            'accepted' => $accepted,
            'channels' => [
                'mail' => $flags['mail'] && $hasTemplate,
                'sms' => $flags['sms'] && $hasTemplate,
                'whatsapp' => $flags['whatsapp'] && $hasTemplate,
                'notification' => $flags['notification'] && $hasTemplate,
            ],
            'deferred' => true,
        ];
    }

    /**
     * @return array{mail:bool,sms:bool,whatsapp:bool,notification:bool,template:string,subject:string}
     */
    public function feesReminderFlags(): array
    {
        $row = DB::table('notification_setting')
            ->where('type', 'fees_reminder')
            ->first();

        if (! $row) {
            return [
                'mail' => false,
                'sms' => false,
                'whatsapp' => false,
                'notification' => false,
                'template' => '',
                'subject' => '',
            ];
        }

        return [
            'mail' => (string) ($row->is_mail ?? '0') === '1',
            'sms' => (string) ($row->is_sms ?? '0') === '1',
            'whatsapp' => (int) ($row->is_whatsapp ?? 0) === 1,
            'notification' => (string) ($row->is_notification ?? '0') === '1',
            'template' => (string) ($row->template ?? ''),
            'subject' => (string) ($row->subject ?? ''),
        ];
    }

    /**
     * CI Feegrouptype_model::getFeeTypeDueDateReminder.
     *
     * @return list<object>
     */
    protected function feeTypeLinesForDueDate(string $date): array
    {
        return DB::table('fee_groups_feetype')
            ->join('feetype', 'feetype.id', '=', 'fee_groups_feetype.feetype_id')
            ->join('fee_groups', 'fee_groups.id', '=', 'fee_groups_feetype.fee_groups_id')
            ->whereDate('fee_groups_feetype.due_date', $date)
            ->select([
                'fee_groups_feetype.*',
                'feetype.type',
                'feetype.code',
                'fee_groups.name as fee_group_name',
            ])
            ->get()
            ->all();
    }

    /**
     * CI Feegrouptype_model::getFeeTypeStudents + deposit/due calc.
     *
     * @return list<array<string, mixed>>
     */
    protected function studentsForFeeType(object $feeType, string $dueDate): array
    {
        $rows = DB::table('student_fees_master')
            ->leftJoin('student_fees_deposite', function ($join) use ($feeType) {
                $join->on('student_fees_deposite.student_fees_master_id', '=', 'student_fees_master.id')
                    ->where('student_fees_deposite.fee_groups_feetype_id', (int) $feeType->id);
            })
            ->join('student_session', 'student_session.id', '=', 'student_fees_master.student_session_id')
            ->join('students', 'students.id', '=', 'student_session.student_id')
            ->join('classes', 'classes.id', '=', 'student_session.class_id')
            ->where('students.is_active', 'yes')
            ->where('student_fees_master.fee_session_group_id', (int) $feeType->fee_session_group_id)
            ->select([
                'student_fees_master.id as student_fees_master_id',
                'student_fees_master.student_session_id',
                'student_fees_deposite.amount_detail',
                'students.id as student_id',
                'students.firstname',
                'students.middlename',
                'students.lastname',
                'students.email',
                'students.mobileno',
                'students.guardian_phone',
                'students.guardian_email',
                'students.app_key',
                'students.parent_app_key',
                'classes.class',
            ])
            ->get();

        $schoolName = (string) (DB::table('sch_settings')->limit(1)->value('name') ?? '');
        $out = [];

        foreach ($rows as $row) {
            $feeAmount = (float) ($feeType->amount ?? 0);
            $depositAmount = 0.0;
            $raw = $row->amount_detail ?? null;
            if (is_string($raw) && $raw !== '' && $raw !== '0') {
                $decoded = json_decode($raw);
                if (json_last_error() === JSON_ERROR_NONE && is_object($decoded)) {
                    foreach ($decoded as $entry) {
                        $depositAmount += (float) ($entry->amount ?? 0) + (float) ($entry->amount_discount ?? 0);
                    }
                }
            }

            $dueAmount = $feeAmount - $depositAmount;
            $out[] = [
                'fee_category' => 'fees',
                'fee_group_name' => (string) ($feeType->fee_group_name ?? ''),
                'due_date' => $dueDate,
                'fee_type' => (string) ($feeType->type ?? ''),
                'fee_code' => (string) ($feeType->code ?? ''),
                'fee_amount' => number_format($feeAmount, 2, '.', ''),
                'due_amount' => number_format($dueAmount, 2, '.', ''),
                'deposit_amount' => number_format($depositAmount, 2, '.', ''),
                'student_name' => $this->receipts->studentDisplayName($row),
                'school_name' => $schoolName,
                'student_id' => (int) $row->student_id,
                'student_session_id' => (int) $row->student_session_id,
                'email' => (string) ($row->email ?? ''),
                'mobileno' => (string) ($row->mobileno ?? ''),
                'guardian_phone' => (string) ($row->guardian_phone ?? ''),
                'guardian_email' => (string) ($row->guardian_email ?? ''),
                'app_key' => (string) ($row->app_key ?? ''),
                'parent_app_key' => (string) ($row->parent_app_key ?? ''),
            ];
        }

        return $out;
    }

    /**
     * CI Studentfeemaster_model::getTransportFeesByDueDate + deposit/due calc.
     *
     * @return list<array<string, mixed>>
     */
    protected function transportLinesForDueDate(string $date): array
    {
        $rows = DB::table('student_transport_fees')
            ->join('transport_feemaster', 'transport_feemaster.id', '=', 'student_transport_fees.transport_feemaster_id')
            ->leftJoin('student_fees_deposite', 'student_fees_deposite.student_transport_fee_id', '=', 'student_transport_fees.id')
            ->join('student_session', 'student_session.id', '=', 'student_transport_fees.student_session_id')
            ->join('classes', 'classes.id', '=', 'student_session.class_id')
            ->join('sections', 'sections.id', '=', 'student_session.section_id')
            ->join('students', 'students.id', '=', 'student_session.student_id')
            ->join('route_pickup_point', 'route_pickup_point.id', '=', 'student_transport_fees.route_pickup_point_id')
            ->whereDate('transport_feemaster.due_date', $date)
            ->where('students.is_active', 'yes')
            ->select([
                'student_transport_fees.id as student_transport_fee_id',
                'student_transport_fees.student_session_id',
                'route_pickup_point.fees',
                'transport_feemaster.month',
                'student_fees_deposite.amount_detail',
                'students.id as student_id',
                'students.firstname',
                'students.middlename',
                'students.lastname',
                'students.email',
                'students.mobileno',
                'students.guardian_phone',
                'students.guardian_email',
                'students.app_key',
                'students.parent_app_key',
            ])
            ->get();

        $schoolName = (string) (DB::table('sch_settings')->limit(1)->value('name') ?? '');
        $out = [];

        foreach ($rows as $row) {
            $feeAmount = (float) ($row->fees ?? 0);
            $depositAmount = 0.0;
            $raw = $row->amount_detail ?? null;
            if (is_string($raw) && $raw !== '' && $raw !== '0') {
                $decoded = json_decode($raw);
                if (is_object($decoded)) {
                    foreach ($decoded as $entry) {
                        $depositAmount += (float) ($entry->amount ?? 0) + (float) ($entry->amount_discount ?? 0);
                    }
                }
            }

            // CI uses $reminder_value->amount after setting fee_amount from fees; amount may be unset.
            // Use fees-based due calc (intent parity).
            $dueAmount = $feeAmount - $depositAmount;

            $out[] = [
                'fee_category' => 'transport',
                'fee_group_name' => 'Transport',
                'due_date' => $date,
                'fee_type' => (string) ($row->month ?? ''),
                'fee_code' => '-',
                'fee_amount' => number_format($feeAmount, 2, '.', ''),
                'due_amount' => number_format($dueAmount, 2, '.', ''),
                'deposit_amount' => number_format($depositAmount, 2, '.', ''),
                'student_name' => $this->receipts->studentDisplayName($row),
                'school_name' => $schoolName,
                'student_id' => (int) $row->student_id,
                'student_session_id' => (int) $row->student_session_id,
                'email' => (string) ($row->email ?? ''),
                'mobileno' => (string) ($row->mobileno ?? ''),
                'guardian_phone' => (string) ($row->guardian_phone ?? ''),
                'guardian_email' => (string) ($row->guardian_email ?? ''),
                'app_key' => (string) ($row->app_key ?? ''),
                'parent_app_key' => (string) ($row->parent_app_key ?? ''),
            ];
        }

        return $out;
    }
}
