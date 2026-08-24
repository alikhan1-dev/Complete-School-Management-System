<?php

namespace App\Modules\Staff\Services;

use Illuminate\Support\Facades\DB;

/**
 * CI admin/Staff create → mailsms('staff_login_credential')
 * CI admin/Staff import row → mailsms('login_credential').
 * Live mail/SMS/WhatsApp gateways deferred (Communication module).
 */
class StaffCredentialNotificationService
{
    /**
     * @return array{
     *     mail:bool,
     *     sms:bool,
     *     whatsapp:bool,
     *     notification:bool,
     *     staff_recipient:bool,
     *     template:string,
     *     subject:string,
     *     template_id:string,
     *     whatsapp_template_id:string
     * }
     */
    public function flagsForType(string $type): array
    {
        $row = DB::table('notification_setting')
            ->where('type', $type)
            ->first();

        if ($row === null) {
            return [
                'mail' => false,
                'sms' => false,
                'whatsapp' => false,
                'notification' => false,
                'staff_recipient' => false,
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
            'staff_recipient' => (int) ($row->is_staff_recipient ?? 0) === 1,
            'template' => (string) ($row->template ?? ''),
            'subject' => (string) ($row->subject ?? ''),
            'template_id' => (string) ($row->template_id ?? ''),
            'whatsapp_template_id' => (string) ($row->whatsapp_template_id ?? ''),
        ];
    }

    /**
     * CI staff create credential payload.
     *
     * @param  array{
     *     staff_id:int,
     *     first_name:string,
     *     last_name?:string,
     *     username:string,
     *     password:string,
     *     contact_no?:string,
     *     email?:string,
     *     employee_id?:string
     * }  $detail
     * @return array{
     *     accepted:bool,
     *     channels:array{mail:bool,sms:bool,whatsapp:bool,notification:bool},
     *     payload:array<string,mixed>,
     *     deferred:true
     * }
     */
    public function queueStaffCreateCredential(array $detail): array
    {
        return $this->queue('staff_login_credential', array_merge($detail, [
            'credential_for' => 'staff',
            'id' => (int) ($detail['staff_id'] ?? 0),
        ]));
    }

    /**
     * CI staff import row credential payload.
     *
     * @param  array{
     *     staff_id:int,
     *     username:string,
     *     password:string,
     *     contact_no?:string,
     *     email?:string
     * }  $detail
     * @return array{
     *     accepted:bool,
     *     channels:array{mail:bool,sms:bool,whatsapp:bool,notification:bool},
     *     payload:array<string,mixed>,
     *     deferred:true
     * }
     */
    public function queueImportCredential(array $detail): array
    {
        return $this->queue('login_credential', array_merge($detail, [
            'credential_for' => 'staff',
            'id' => (int) ($detail['staff_id'] ?? 0),
        ]));
    }

    /**
     * @param  array<string, mixed>  $detail
     * @return array{
     *     accepted:bool,
     *     channels:array{mail:bool,sms:bool,whatsapp:bool,notification:bool},
     *     payload:array<string,mixed>,
     *     deferred:true
     * }
     */
    protected function queue(string $notificationType, array $detail): array
    {
        $flags = $this->flagsForType($notificationType);
        $hasTemplate = trim($flags['template']) !== '';
        $staffRecipient = (int) ($flags['staff_recipient'] ?? 0) === 1;

        $channelReady = static fn (bool $enabled): bool => $enabled && $hasTemplate && (
            $notificationType !== 'staff_login_credential' || $staffRecipient
        );

        $accepted = $hasTemplate && (
            $notificationType !== 'staff_login_credential' || $staffRecipient
        ) && (
            $flags['mail'] || $flags['sms'] || $flags['whatsapp'] || $flags['notification']
        );

        return [
            'accepted' => $accepted,
            'channels' => [
                'mail' => $channelReady($flags['mail']),
                'sms' => $channelReady($flags['sms']),
                'whatsapp' => $channelReady($flags['whatsapp']),
                'notification' => $channelReady($flags['notification']),
            ],
            'payload' => $detail,
            'deferred' => true,
        ];
    }
}
