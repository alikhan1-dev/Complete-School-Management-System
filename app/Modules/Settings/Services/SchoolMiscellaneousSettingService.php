<?php

namespace App\Modules\Settings\Services;

use App\Modules\Settings\Models\SchSetting;
use App\Modules\Shared\Services\SchoolContext;

/**
 * CI Schsettings::miscellaneous + savemiscellaneous.
 */
class SchoolMiscellaneousSettingService
{
    public function __construct(protected SchoolContext $school)
    {
    }

    public function current(): ?SchSetting
    {
        return SchSetting::query()->orderBy('id')->first();
    }

    /**
     * Columns written by CI Schsettings::savemiscellaneous only.
     *
     * @param  array<string, mixed>  $data
     */
    public function save(array $data): void
    {
        $id = (int) ($data['id'] ?? 0);
        $row = SchSetting::query()->where('id', $id)->first()
            ?? SchSetting::query()->orderBy('id')->first();

        if ($row === null) {
            throw new \RuntimeException('School settings row was not found.');
        }

        $row->my_question = ! empty($data['my_question']) ? 1 : 0;
        $row->exam_result = ! empty($data['exam_result']) ? 1 : 0;
        $row->class_teacher = ($data['class_teacher'] ?? 'no') === 'yes' ? 'yes' : 'no';
        $row->superadmin_restriction = ($data['superadmin_restriction'] ?? 'disabled') === 'enabled'
            ? 'enabled'
            : 'disabled';
        $row->calendar_event_reminder = $data['calendar_event_reminder'] ?? '0';
        $row->event_reminder = ($data['event_reminder'] ?? 'disabled') === 'enabled'
            ? 'enabled'
            : 'disabled';
        $row->staff_notification_email = (string) ($data['staff_notification_email'] ?? '');
        $row->scan_code_type = (string) ($data['scan_code_type'] ?? 'barcode');
        $row->download_admit_card = ! empty($data['download_admit_card']) ? 1 : 0;
        $row->student_form_multi_class = ($data['student_form_multi_class'] ?? 'disabled') === 'enabled'
            ? 'enabled'
            : 'disabled';
        $row->save();

        $this->school->clearCache();
    }
}
