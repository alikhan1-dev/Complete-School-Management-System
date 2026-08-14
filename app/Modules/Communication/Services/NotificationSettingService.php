<?php

namespace App\Modules\Communication\Services;

use App\Modules\Communication\Models\NotificationSetting;
use App\Modules\Roles\Models\PermissionGroup;
use Illuminate\Support\Facades\DB;

/**
 * CI Notificationsetting_model + admin/notification/setting persist.
 * Runtime send still deferred to compose/Mailsms.
 */
class NotificationSettingService
{
    /**
     * @return list<NotificationSetting>
     */
    public function listAll(): array
    {
        return NotificationSetting::query()
            ->orderBy('notification_order')
            ->get()
            ->all();
    }

    public function find(int $id): ?NotificationSetting
    {
        return NotificationSetting::query()->find($id);
    }

    public function whatsappModuleActive(): bool
    {
        return PermissionGroup::query()
            ->where('short_code', 'whatsapp_messaging')
            ->where('is_active', 1)
            ->exists();
    }

    public function eventLabel(string $type): string
    {
        $path = lang_path('en/system.php');
        if (! is_file($path)) {
            return $type;
        }

        /** @var array<string, string> $lines */
        $lines = include $path;

        return $lines[$type] ?? $type;
    }

    /**
     * @param  array<string, mixed>  $input
     * @param  list<int|string>  $ids
     */
    public function saveFlags(array $ids, array $input): void
    {
        $rows = [];
        foreach ($ids as $id) {
            $id = (int) $id;
            if ($id <= 0) {
                continue;
            }

            $row = [
                'id' => $id,
                'is_mail' => 0,
                'is_sms' => 0,
                'is_whatsapp' => 0,
                'is_notification' => 0,
                'is_student_recipient' => 0,
                'is_guardian_recipient' => 0,
                'is_staff_recipient' => 0,
            ];

            if (isset($input['mail_'.$id])) {
                $row['is_mail'] = $input['mail_'.$id];
            }
            if (isset($input['sms_'.$id])) {
                $row['is_sms'] = $input['sms_'.$id];
            }
            if (isset($input['whatsapp_'.$id])) {
                $row['is_whatsapp'] = $input['whatsapp_'.$id];
            }
            if (isset($input['notification_'.$id])) {
                $row['is_notification'] = $input['notification_'.$id];
            }
            if (isset($input['student_recipient_'.$id])) {
                $row['is_student_recipient'] = $input['student_recipient_'.$id];
            }
            if (isset($input['guardian_recipient_'.$id])) {
                $row['is_guardian_recipient'] = $input['guardian_recipient_'.$id];
            }
            if (isset($input['staff_recipient_'.$id])) {
                $row['is_staff_recipient'] = $input['staff_recipient_'.$id];
            }

            $rows[] = $row;
        }

        if ($rows === []) {
            return;
        }

        DB::transaction(function () use ($rows) {
            foreach ($rows as $row) {
                $id = $row['id'];
                unset($row['id']);
                NotificationSetting::query()->where('id', $id)->update($row);
            }
        });
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function saveTemplate(NotificationSetting $row, array $input): NotificationSetting
    {
        $row->fill([
            'template_id' => (string) ($input['template_id'] ?? ''),
            'whatsapp_template_id' => (string) ($input['whatsapp_template_id'] ?? $row->whatsapp_template_id ?? ''),
            'template' => htmlspecialchars_decode((string) ($input['template_message'] ?? ''), ENT_QUOTES),
            'subject' => (string) ($input['template_subject'] ?? ''),
        ])->save();

        return $row->fresh();
    }

    /**
     * @return array{header: string, footer: string}
     */
    public function emailChrome(): array
    {
        $row = DB::table('print_headerfooter')->where('print_type', 'email')->first();

        return [
            'header' => $row ? (string) ($row->header_image ?? '') : '',
            'footer' => $row ? (string) ($row->footer_content ?? '') : '',
        ];
    }
}
