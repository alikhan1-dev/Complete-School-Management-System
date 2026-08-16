<?php

namespace App\Modules\Settings\Services;

use App\Modules\Settings\Models\SchSetting;
use App\Modules\Shared\Services\SchoolContext;

/**
 * CI Schsettings::maintenance + save_maintenance.
 */
class SchoolMaintenanceSettingService
{
    public function __construct(protected SchoolContext $school)
    {
    }

    public function current(): ?SchSetting
    {
        return SchSetting::query()->orderBy('id')->first();
    }

    /**
     * Columns written by CI Schsettings::save_maintenance only.
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

        // Unchecked checkbox → CI posts empty/false → store 0.
        $row->maintenance_mode = ! empty($data['maintenance_mode']) ? 1 : 0;
        $row->save();

        $this->school->clearCache();
    }
}
