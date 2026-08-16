<?php

namespace App\Modules\Settings\Services;

use App\Modules\Settings\Models\GoogleDriveSetting;

/**
 * CI Schsettings::googledrivesetting + savegoogledrive.
 */
class SchoolGoogleDriveSettingService
{
    /**
     * CI Student_model::getgoogledrivepickersetting — row id = 1.
     */
    public function current(): ?GoogleDriveSetting
    {
        return GoogleDriveSetting::query()->where('id', 1)->first()
            ?? GoogleDriveSetting::query()->orderBy('id')->first();
    }

    /**
     * CI Student_model::savegoogledrive.
     *
     * @param  array<string, mixed>  $data
     */
    public function save(array $data): void
    {
        $id = (int) ($data['id'] ?? 0);
        $row = $id > 0
            ? GoogleDriveSetting::query()->where('id', $id)->first()
            : null;

        if ($row === null) {
            $row = $this->current() ?? new GoogleDriveSetting();
            if (! $row->exists && $id > 0) {
                $row->id = $id;
            }
        }

        $row->client_id = (string) ($data['client_id'] ?? '');
        $row->api_key = (string) ($data['api_key'] ?? '');
        $row->project_number = (string) ($data['project_number'] ?? '');
        $row->is_enable = $this->enabledOrDisabled($data['is_enable'] ?? null);
        $row->is_student = $this->enabledOrDisabled($data['is_student'] ?? null);
        $row->is_parent = $this->enabledOrDisabled($data['is_parent'] ?? null);
        $row->is_staff = $this->enabledOrDisabled($data['is_staff'] ?? null);
        $row->save();
    }

    protected function enabledOrDisabled(mixed $value): string
    {
        return strtolower(trim((string) $value)) === 'enabled' ? 'enabled' : 'disabled';
    }
}
