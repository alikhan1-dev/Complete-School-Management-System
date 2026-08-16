<?php

namespace App\Modules\Settings\Services;

use App\Modules\Settings\Models\SchSetting;
use App\Modules\Shared\Services\SchoolContext;

/**
 * CI Schsettings::whatsappsettings + savewhatsappsettings.
 */
class SchoolWhatsappSettingService
{
    public function __construct(protected SchoolContext $school)
    {
    }

    public function current(): ?SchSetting
    {
        return SchSetting::query()->orderBy('id')->first();
    }

    /**
     * Columns written by CI Schsettings::savewhatsappsettings.
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

        $row->front_side_whatsapp = ! empty($data['front_side_whatsapp']) ? 1 : 0;
        $row->front_side_whatsapp_mobile = (string) ($data['front_side_whatsapp_mobile'] ?? '');
        $row->front_side_whatsapp_from = $this->nullableTime($data['front_side_whatsapp_from'] ?? null);
        $row->front_side_whatsapp_to = $this->nullableTime($data['front_side_whatsapp_to'] ?? null);

        $row->admin_panel_whatsapp = ! empty($data['admin_panel_whatsapp']) ? 1 : 0;
        $row->admin_panel_whatsapp_mobile = (string) ($data['admin_panel_whatsapp_mobile'] ?? '');
        $row->admin_panel_whatsapp_from = $this->nullableTime($data['admin_panel_whatsapp_from'] ?? null);
        $row->admin_panel_whatsapp_to = $this->nullableTime($data['admin_panel_whatsapp_to'] ?? null);

        $row->student_panel_whatsapp = ! empty($data['student_panel_whatsapp']) ? 1 : 0;
        $row->student_panel_whatsapp_mobile = (string) ($data['student_panel_whatsapp_mobile'] ?? '');
        $row->student_panel_whatsapp_from = $this->nullableTime($data['student_panel_whatsapp_from'] ?? null);
        $row->student_panel_whatsapp_to = $this->nullableTime($data['student_panel_whatsapp_to'] ?? null);

        $row->save();
        $this->school->clearCache();
    }

    protected function nullableTime(mixed $value): ?string
    {
        if ($value === null || $value === false || $value === '') {
            return null;
        }

        return (string) $value;
    }
}
