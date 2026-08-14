<?php

namespace App\Modules\FrontCms\Services;

use App\Modules\FrontCms\Models\FrontCmsSetting;
use App\Modules\Settings\Models\SchSetting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * CI frontcms_setting_model + admin/Frontcms persist (SaaS quota deferred).
 */
class FrontCmsSettingService
{
    public const THEMES = [
        'default' => 'theme_default.jpg',
        'yellow' => 'theme_yellow.jpg',
        'darkgray' => 'theme_darkgray.jpg',
        'bold_blue' => 'theme_bold_blue.jpg',
        'shadow_white' => 'theme_shadow_white.jpg',
        'material_pink' => 'theme_material_pink.jpg',
    ];

    public function __construct(protected FrontCmsLogoService $logos)
    {
    }

    public function current(): object
    {
        $row = FrontCmsSetting::query()->orderBy('id')->first();
        if ($row) {
            return $row;
        }

        return (object) [
            'id' => 0,
            'is_active_front_cms' => 0,
            'contact_us_email' => '',
            'is_active_sidebar' => 0,
            'google_analytics' => '',
            'logo' => '',
            'fav_icon' => '',
            'sidebar_options' => json_encode([]),
            'is_active_rtl' => 0,
            'theme' => '',
            'complain_form_email' => '',
            'footer_text' => '',
            'whatsapp_url' => '',
            'fb_url' => '',
            'twitter_url' => '',
            'youtube_url' => '',
            'google_plus' => '',
            'instagram_url' => '',
            'pinterest_url' => '',
            'linkedin_url' => '',
            'cookie_consent' => '',
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function enabledLanguages(): array
    {
        $raw = SchSetting::query()->value('languages');
        $ids = json_decode((string) $raw, true);
        if (! is_array($ids) || $ids === []) {
            return [];
        }

        return DB::table('languages')->whereIn('id', $ids)->orderBy('id')->get()->map(fn ($row) => (array) $row)->all();
    }

    public function schoolLangId(): int
    {
        return (int) SchSetting::query()->value('lang_id');
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function save(array $input, ?UploadedFile $logo, ?UploadedFile $favIcon): void
    {
        $id = (int) ($input['id'] ?? 0);
        $existing = $id > 0 ? FrontCmsSetting::query()->find($id) : FrontCmsSetting::query()->orderBy('id')->first();

        $sidebar = $input['sidebar_options'] ?? null;
        if (! is_array($sidebar)) {
            $sidebar = [];
        }

        $payload = [
            'contact_us_email' => (string) ($input['contact_us_email'] ?? ''),
            'is_active_front_cms' => ! empty($input['is_active_front_cms']) ? 1 : 0,
            'is_active_rtl' => ! empty($input['is_active_rtl']) ? 1 : 0,
            'is_active_sidebar' => ! empty($input['is_active_sidebar']) ? 1 : 0,
            'theme' => (string) ($input['theme'] ?? ''),
            'complain_form_email' => (string) ($input['complain_form_email'] ?? ''),
            'sidebar_options' => json_encode(array_values($sidebar)),
            'google_analytics' => (string) ($input['google_analytics'] ?? ''),
            'footer_text' => (string) ($input['footer_text'] ?? ''),
            'whatsapp_url' => (string) ($input['whatsapp_url'] ?? ''),
            'fb_url' => (string) ($input['fb_url'] ?? ''),
            'twitter_url' => (string) ($input['twitter_url'] ?? ''),
            'youtube_url' => (string) ($input['youtube_url'] ?? ''),
            'google_plus' => (string) ($input['google_plus'] ?? ''),
            'instagram_url' => (string) ($input['instagram_url'] ?? ''),
            'pinterest_url' => (string) ($input['pinterest_url'] ?? ''),
            'linkedin_url' => (string) ($input['linkedin_url'] ?? ''),
            'cookie_consent' => (string) ($input['cookie_consent'] ?? ''),
        ];

        if ($logo) {
            if ($existing) {
                $this->logos->deleteStoredPath((string) $existing->logo);
            }
            $payload['logo'] = $this->logos->store($logo);
        } elseif ($existing) {
            $payload['logo'] = (string) ($existing->logo ?? '');
        } else {
            $payload['logo'] = '';
        }

        if ($favIcon) {
            if ($existing) {
                $this->logos->deleteStoredPath((string) $existing->fav_icon);
            }
            $payload['fav_icon'] = $this->logos->store($favIcon);
        } elseif ($existing) {
            $payload['fav_icon'] = (string) ($existing->fav_icon ?? '');
        } else {
            $payload['fav_icon'] = '';
        }

        if ($existing) {
            FrontCmsSetting::query()->where('id', $existing->id)->update($payload);
        } else {
            FrontCmsSetting::query()->create($payload);
        }

        $langId = (int) ($input['sch_lang_id'] ?? 0);
        if ($langId > 0) {
            SchSetting::query()->where('id', 1)->update(['lang_id' => $langId]);
        }
    }

    /**
     * @return list<string>
     */
    public function sidebarSelected(?string $json): array
    {
        $decoded = json_decode((string) $json, true);

        return is_array($decoded) ? array_values(array_map('strval', $decoded)) : [];
    }
}
