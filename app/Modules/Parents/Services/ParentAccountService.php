<?php

namespace App\Modules\Parents\Services;

use Illuminate\Support\Facades\DB;

/**
 * CI parent portal account helpers used from Student profile.
 * Live mail/SMS/WhatsApp for student_login_credential deferred (Communication gateways).
 */
class ParentAccountService
{
    /**
     * CI Student_model::guardian_credential — users row by parent users.id.
     */
    public function guardianCredential(int $parentId): ?object
    {
        if ($parentId <= 0) {
            return null;
        }

        return DB::table('users')
            ->where('id', $parentId)
            ->select(['id', 'user_id', 'username', 'password', 'role', 'childs', 'is_active'])
            ->first();
    }

    /**
     * CI User_model::getStudentLoginDetails — parent (via student.parent_id) UNION student user.
     *
     * @return list<object>
     */
    public function loginDetailsForStudent(int $studentId): array
    {
        if ($studentId <= 0) {
            return [];
        }

        $parentRows = DB::table('users')
            ->whereIn('id', function ($q) use ($studentId) {
                $q->select('students.parent_id')
                    ->from('students')
                    ->join('users', 'users.user_id', '=', 'students.id')
                    ->where('users.user_id', $studentId)
                    ->where('users.role', 'student')
                    ->where('students.parent_id', '>', 0);
            })
            ->select(['id', 'user_id', 'username', 'password', 'role'])
            ->get();

        $studentRows = DB::table('users')
            ->where('user_id', $studentId)
            ->where('role', 'student')
            ->select(['id', 'user_id', 'username', 'password', 'role'])
            ->get();

        $merged = collect();
        foreach ($parentRows as $row) {
            $merged->put((int) $row->id, $row);
        }
        foreach ($studentRows as $row) {
            $merged->put((int) $row->id, $row);
        }

        return $merged->values()->all();
    }

    /**
     * CI student portal user for a student id.
     */
    public function studentCredential(int $studentId): ?object
    {
        if ($studentId <= 0) {
            return null;
        }

        return DB::table('users')
            ->where('user_id', $studentId)
            ->where('role', 'student')
            ->select(['id', 'user_id', 'username', 'password', 'role'])
            ->first();
    }

    /**
     * Notification channel flags for student_login_credential (CI notification_setting).
     *
     * @return array{mail:bool,sms:bool,whatsapp:bool,template:string,subject:string}
     */
    public function loginCredentialNotificationFlags(): array
    {
        $row = DB::table('notification_setting')
            ->where('type', 'student_login_credential')
            ->first();

        if (! $row) {
            return [
                'mail' => false,
                'sms' => false,
                'whatsapp' => false,
                'template' => '',
                'subject' => '',
            ];
        }

        return [
            'mail' => (string) ($row->is_mail ?? '0') === '1',
            'sms' => (string) ($row->is_sms ?? '0') === '1',
            'whatsapp' => (int) ($row->is_whatsapp ?? 0) === 1,
            'template' => (string) ($row->template ?? ''),
            'subject' => (string) ($row->subject ?? ''),
        ];
    }

    /**
     * Persist-side acceptance for CI sendpassword / send_parent_password.
     * Live gateway delivery is deferred — returns whether channels are configured.
     *
     * @param  array{student_id:int,username?:string,password?:string,contact_no?:string,email?:string,admission_no?:string,student_session_id?:int|string,credential_for:string}  $detail
     * @return array{accepted:bool,channels:array{mail:bool,sms:bool,whatsapp:bool},deferred:true}
     */
    public function queueLoginCredentialNotification(array $detail): array
    {
        $flags = $this->loginCredentialNotificationFlags();
        $hasTemplate = trim($flags['template']) !== '';
        $accepted = $hasTemplate && ($flags['mail'] || $flags['sms'] || $flags['whatsapp']);

        // Side-effect free for now: live Mail/SMS/WhatsApp gateways deferred.
        unset($detail);

        return [
            'accepted' => $accepted,
            'channels' => [
                'mail' => $flags['mail'] && $hasTemplate,
                'sms' => $flags['sms'] && $hasTemplate,
                'whatsapp' => $flags['whatsapp'] && $hasTemplate,
            ],
            'deferred' => true,
        ];
    }
}
