<?php

namespace App\Modules\Settings\Services;

use App\Modules\Settings\Models\SchSetting;
use App\Modules\Shared\Services\SchoolContext;

/**
 * CI Schsettings::mobileapp + savemobileapp (DB fields only).
 * Envato andapp_validate / updateandappCode remain deferred (live vendor API).
 */
class SchoolMobileAppSettingService
{
    public function __construct(protected SchoolContext $school)
    {
    }

    public function current(): ?SchSetting
    {
        return SchSetting::query()->orderBy('id')->first();
    }

    /**
     * Columns written by CI Schsettings::savemobileapp only.
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

        $row->mobile_api_url = (string) ($data['mobile_api_url'] ?? '');
        $row->app_primary_color_code = (string) ($data['app_primary_color_code'] ?? '');
        $row->app_secondary_color_code = (string) ($data['app_secondary_color_code'] ?? '');
        $row->admin_mobile_api_url = (string) ($data['admin_mobile_api_url'] ?? '');
        $row->admin_app_primary_color_code = (string) ($data['admin_app_primary_color_code'] ?? '');
        $row->admin_app_secondary_color_code = (string) ($data['admin_app_secondary_color_code'] ?? '');
        $row->save();

        $this->school->clearCache();
    }
}
