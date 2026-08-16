<?php

namespace App\Modules\Settings\Services;

use App\Modules\Settings\Models\SchSetting;
use App\Modules\Shared\Services\SchoolContext;

/**
 * CI Schsettings::studentguardianpanel + studentguardian.
 */
class SchoolStudentGuardianPanelService
{
    public function __construct(protected SchoolContext $school)
    {
    }

    public function current(): ?SchSetting
    {
        return SchSetting::query()->orderBy('id')->first();
    }

    /**
     * Decode CI student_login / parent_login JSON for the form.
     *
     * @return list<string>
     */
    public function decodeLoginOptions(mixed $raw): array
    {
        if (is_array($raw)) {
            return array_values(array_map('strval', $raw));
        }

        if (! is_string($raw) || $raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            return [];
        }

        return array_values(array_map('strval', $decoded));
    }

    /**
     * Columns written by CI Schsettings::studentguardian only.
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

        $row->student_timeline = ($data['student_timeline'] ?? 'disabled') === 'enabled'
            ? 'enabled'
            : 'disabled';
        $row->student_login = $this->encodeLoginOptions($data['student_login'] ?? null);
        $row->parent_login = $this->encodeLoginOptions($data['parent_login'] ?? null);
        $row->student_panel_login = ! empty($data['student_panel_login']) ? 1 : 0;
        $row->parent_panel_login = ! empty($data['parent_panel_login']) ? 1 : 0;
        $row->save();

        $this->school->clearCache();
    }

    /**
     * CI: json_encode($this->input->post('student_login')) — missing post → false → "false".
     */
    protected function encodeLoginOptions(mixed $value): string
    {
        if (is_array($value)) {
            return (string) json_encode(array_values($value));
        }

        return 'false';
    }
}
