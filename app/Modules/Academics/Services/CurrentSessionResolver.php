<?php

namespace App\Modules\Academics\Services;

use App\Modules\Settings\Models\SchSetting;
use Illuminate\Support\Facades\Session;

/**
 * Resolves the current academic session like CI Customlib/setting_model.
 */
class CurrentSessionResolver
{
    public function id(): int
    {
        $override = Session::get('session_array');
        if (is_array($override) && isset($override['id'])) {
            return (int) $override['id'];
        }

        $settings = SchSetting::query()->first();

        return (int) ($settings->session_id ?? 0);
    }
}
