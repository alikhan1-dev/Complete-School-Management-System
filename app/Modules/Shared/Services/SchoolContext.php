<?php

namespace App\Modules\Shared\Services;

use App\Modules\Settings\Models\SchSetting;
use Illuminate\Support\Facades\Cache;

/**
 * School-wide runtime context (currency, theme, timezone, RTL, etc.).
 */
class SchoolContext
{
    protected ?array $settings = null;

    public function settings(): array
    {
        if ($this->settings !== null) {
            return $this->settings;
        }

        $this->settings = Cache::remember('sch_settings_row', 300, function () {
            $row = SchSetting::query()->first();

            return $row ? $row->toArray() : [];
        });

        return $this->settings;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->settings()[$key] ?? $default;
    }

    public function schoolName(): string
    {
        return (string) $this->get('name', 'Smart School');
    }

    public function timezone(): string
    {
        return (string) $this->get('timezone', config('app.timezone', 'UTC'));
    }

    public function currencySymbol(): string
    {
        return (string) $this->get('currency_symbol', '$');
    }

    public function dateFormat(): string
    {
        return (string) $this->get('date_format', 'd/m/Y');
    }

    public function theme(): string
    {
        return (string) $this->get('theme', 'default');
    }

    public function isRtl(): bool
    {
        return (string) $this->get('is_rtl', 'disabled') === 'enabled';
    }

    public function superadminRestriction(): string
    {
        return (string) $this->get('superadmin_restriction', 'disabled');
    }

    public function clearCache(): void
    {
        Cache::forget('sch_settings_row');
        $this->settings = null;
    }
}
