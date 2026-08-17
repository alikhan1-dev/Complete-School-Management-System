<?php

namespace App\Modules\Settings\Services;

use App\Modules\FrontCms\Models\FrontCmsSetting;

/**
 * CI Theme::css + FrontTheme::css + custom_helper::front_theme_color.
 */
class SchoolThemeCssService
{
    /**
     * CI custom_helper::front_theme_color.
     *
     * @var array<string, array{theme_color:string,theme_font_color:string}>
     */
    public const FRONT_THEME_COLORS = [
        'material_pink' => [
            'theme_color' => '#bd0745',
            'theme_font_color' => '#fff',
        ],
        'default' => [
            'theme_color' => '#1ea0e0',
            'theme_font_color' => '#fff',
        ],
        'yellow' => [
            'theme_color' => '#f48000',
            'theme_font_color' => '#fff',
        ],
        'darkgray' => [
            'theme_color' => '#f58000',
            'theme_font_color' => '#fff',
        ],
        'bold_blue' => [
            'theme_color' => '#f58001',
            'theme_font_color' => '#fff',
        ],
        'shadow_white' => [
            'theme_color' => '#1583c9',
            'theme_font_color' => '#fff',
        ],
    ];

    public function __construct(protected SchoolBackendThemeService $backendThemes)
    {
    }

    /**
     * CI Customlib::getCurrentThemeSetting then Theme.php fallbacks.
     * Session admin/student theme when present (CI); otherwise sch_settings
     * (Laravel login does not yet hydrate admin.theme).
     *
     * @return array{theme_color:string,theme_font_color:string}
     */
    public function backendColors(): array
    {
        $sessionTheme = $this->sessionTheme();
        if ($sessionTheme !== null) {
            return [
                'theme_color' => (string) ($sessionTheme['theme_color'] ?? ''),
                'theme_font_color' => (string) ($sessionTheme['theme_font_color'] ?? ''),
            ];
        }

        $fromDb = $this->backendThemes->themeArray();

        return [
            'theme_color' => $fromDb['theme_color'] !== '' ? $fromDb['theme_color'] : '#7367f0',
            'theme_font_color' => $fromDb['theme_font_color'] !== '' ? $fromDb['theme_font_color'] : '#fff',
        ];
    }

    /**
     * CI Customlib::getFrontCurrentThemeSetting + FrontTheme.php fallbacks.
     *
     * @return array{theme_color:string,theme_font_color:string}
     */
    public function frontColors(): array
    {
        $themeKey = (string) (FrontCmsSetting::query()->orderBy('id')->value('theme') ?? '');
        if ($themeKey !== '' && isset(self::FRONT_THEME_COLORS[$themeKey])) {
            return self::FRONT_THEME_COLORS[$themeKey];
        }

        return [
            'theme_color' => '#7367f0',
            'theme_font_color' => '#7367f0',
        ];
    }

    public function backendCss(): string
    {
        $colors = $this->backendColors();
        $themeColor = $colors['theme_color'];
        $themeFontColor = $colors['theme_font_color'];

        return "
            :root {
                --bs-primary: {$themeColor};
                --bs-btn-border-color: {$themeColor};
                --bs-primary-hover: {$themeColor};
                --bs-hover-color: {$themeColor};
                --bs-alert-bg: {$themeColor};
                --custom-hover-theme: {$themeColor};
                --bs-primary-contrast: {$themeFontColor};
            }
        ";
    }

    public function frontCss(): string
    {
        $colors = $this->frontColors();
        $themeColor = $colors['theme_color'];
        $themeFontColor = $colors['theme_font_color'];

        return "
            :root {
                --bs-primary: {$themeColor};
                --submit-text-color: {$themeFontColor};
              
            }
        ";
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function sessionTheme(): ?array
    {
        $admin = session('admin');
        if (is_array($admin) && isset($admin['theme']) && is_array($admin['theme'])) {
            return $admin['theme'];
        }

        $student = session('student');
        if (is_array($student) && isset($student['theme']) && is_array($student['theme'])) {
            return $student['theme'];
        }

        return null;
    }
}
