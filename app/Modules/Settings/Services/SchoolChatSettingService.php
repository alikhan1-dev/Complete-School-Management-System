<?php

namespace App\Modules\Settings\Services;

use App\Modules\Roles\Models\PermissionGroup;
use App\Modules\Settings\Models\SchSetting;
use App\Modules\Shared\Services\SchoolContext;

/**
 * CI Schsettings::chatsetting + savechatsetting.
 */
class SchoolChatSettingService
{
    public function __construct(protected SchoolContext $school)
    {
    }

    public function current(): ?SchSetting
    {
        return SchSetting::query()->orderBy('id')->first();
    }

    /**
     * CI Module_lib::hasActive('chat') via permission_group.short_code.
     */
    public function isChatModuleActive(): bool
    {
        return PermissionGroup::query()
            ->where('short_code', 'chat')
            ->where('is_active', 1)
            ->exists();
    }

    /**
     * Columns written by CI Schsettings::savechatsetting only.
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

        $row->student_delete_chat = ! empty($data['student_delete_chat']) ? 1 : 0;
        $row->guardian_delete_chat = ! empty($data['guardian_delete_chat']) ? 1 : 0;
        $row->staff_delete_chat = ! empty($data['staff_delete_chat']) ? 1 : 0;
        $row->save();

        $this->school->clearCache();
    }
}
