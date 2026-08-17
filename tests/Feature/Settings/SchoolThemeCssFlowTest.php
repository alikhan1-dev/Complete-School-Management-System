<?php

namespace Tests\Feature\Settings;

use App\Modules\Settings\Services\SchoolThemeCssService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SchoolThemeCssFlowTest extends TestCase
{
    private ?string $frontThemeSnapshot = null;

    private ?int $frontSettingsId = null;

    protected function tearDown(): void
    {
        if ($this->frontSettingsId !== null && $this->frontThemeSnapshot !== null) {
            DB::table('front_cms_settings')->where('id', $this->frontSettingsId)->update([
                'theme' => $this->frontThemeSnapshot,
            ]);
            $this->frontSettingsId = null;
            $this->frontThemeSnapshot = null;
        }

        parent::tearDown();
    }

    public function test_theme_css_is_public_text_css(): void
    {
        $color = (string) (DB::table('sch_settings')->orderBy('id')->value('theme_color') ?: '#7367f0');

        $this->get('/theme.css')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/css; charset=UTF-8')
            ->assertSee('--bs-primary: '.$color, false)
            ->assertSee('--bs-primary-contrast:', false);
    }

    public function test_theme_css_alias_matches_pretty_url(): void
    {
        $this->get('/theme/css')
            ->assertOk()
            ->assertSee('--custom-hover-theme:', false);
    }

    public function test_theme_css_prefers_admin_session_theme(): void
    {
        $this->withSession([
            'admin' => [
                'theme' => [
                    'theme_color' => '#ff00aa',
                    'theme_font_color' => '#112233',
                ],
            ],
        ])->get('/theme.css')
            ->assertOk()
            ->assertSee('--bs-primary: #ff00aa', false)
            ->assertSee('--bs-primary-contrast: #112233', false);
    }

    public function test_fronttheme_css_is_public_text_css(): void
    {
        $this->get('/fronttheme.css')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/css; charset=UTF-8')
            ->assertSee('--bs-primary:', false)
            ->assertSee('--submit-text-color:', false);
    }

    public function test_fronttheme_css_maps_front_cms_theme_name(): void
    {
        $row = DB::table('front_cms_settings')->orderBy('id')->first();
        $this->assertNotNull($row, 'front_cms_settings row is required');
        $this->frontSettingsId = (int) $row->id;
        $this->frontThemeSnapshot = (string) $row->theme;

        DB::table('front_cms_settings')->where('id', $this->frontSettingsId)->update([
            'theme' => 'yellow',
        ]);

        $expected = SchoolThemeCssService::FRONT_THEME_COLORS['yellow']['theme_color'];

        $this->get('/fronttheme.css')
            ->assertOk()
            ->assertSee('--bs-primary: '.$expected, false)
            ->assertSee('--submit-text-color: #fff', false);

        $this->get('/FrontTheme/css')
            ->assertOk()
            ->assertSee('--bs-primary: '.$expected, false);
    }
}
