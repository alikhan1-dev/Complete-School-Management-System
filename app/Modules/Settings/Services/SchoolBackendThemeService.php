<?php

namespace App\Modules\Settings\Services;

use App\Modules\Settings\Models\SchSetting;
use App\Modules\Shared\Services\SchoolContext;

/**
 * CI Schsettings::backendtheme + savebackendtheme.
 */
class SchoolBackendThemeService
{
    /** @var list<string> */
    public const PRESET_COLORS = [
        '#7367f0',
        '#2092EC',
        '#FFAB1D',
        '#0D9394',
        '#EB3D63',
    ];

    public function __construct(protected SchoolContext $school)
    {
    }

    public function current(): ?SchSetting
    {
        return SchSetting::query()->orderBy('id')->first();
    }

    /**
     * @return array{theme_color:string,theme_shadow:?string,theme_background:string,theme_content:string,theme_type:string,theme_navigation:string,theme_font_color:string}
     */
    public function themeArray(?SchSetting $row = null): array
    {
        $row ??= $this->current();

        return [
            'theme_color' => (string) ($row->theme_color ?? '#7367f0'),
            'theme_shadow' => $row->theme_shadow !== null && $row->theme_shadow !== ''
                ? (string) $row->theme_shadow
                : null,
            'theme_background' => (string) ($row->theme_background ?: 'light-mode'),
            'theme_content' => (string) ($row->theme_content ?: 'container-fluid'),
            'theme_type' => (string) ($row->theme_type ?: 'default'),
            'theme_navigation' => (string) ($row->theme_navigation ?: 'expanded'),
            'theme_font_color' => (string) ($row->theme_font_color ?: '#fff'),
        ];
    }

    /**
     * Columns written by CI Schsettings::savebackendtheme only.
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

        $shadow = $data['theme_shadow'] ?? null;
        // CI empty2null(); column is NOT NULL — store empty string when cleared.
        $row->theme_color = (string) ($data['theme_color'] ?? $row->theme_color);
        $row->theme_shadow = ($shadow === null || $shadow === '') ? '' : (string) $shadow;
        $row->theme_background = (string) ($data['theme_background'] ?? $row->theme_background);
        $row->theme_content = (string) ($data['theme_content'] ?? $row->theme_content);
        $row->theme_type = (string) ($data['theme_type'] ?? $row->theme_type);
        $row->theme_navigation = (string) ($data['theme_navigation'] ?? $row->theme_navigation);
        $row->theme_font_color = (string) ($data['theme_font_color'] ?? $row->theme_font_color);
        $row->save();

        $this->school->clearCache();
    }
}
