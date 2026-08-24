<?php

namespace App\Modules\Fees\Services;

use App\Modules\Fees\Models\ThermalPrint;
use App\Modules\Roles\Models\PermissionGroup;
use Illuminate\Support\Facades\Schema;

/**
 * CI Studentfee thermal_print_module + thermal_print_enable + thermal_print_model::get.
 */
class ThermalPrintService
{
    public const MODULE_SHORT_CODE = 'thermal_print';

    /**
     * CI Module_lib::hasModule('thermal_print') — permission_group row exists.
     */
    public function hasModule(): bool
    {
        return PermissionGroup::query()
            ->where('short_code', self::MODULE_SHORT_CODE)
            ->exists();
    }

    /**
     * CI Module_lib::hasActive('thermal_print').
     */
    public function hasActiveModule(): bool
    {
        $row = PermissionGroup::query()
            ->where('short_code', self::MODULE_SHORT_CODE)
            ->first();

        if (! $row) {
            return false;
        }

        return (int) $row->is_active === 1;
    }

    public function settingsTableReady(): bool
    {
        return Schema::hasTable('thermal_print');
    }

    /**
     * CI thermal_print_result array for views.
     *
     * @return array{school_name:string,address:string,footer_text:string,is_print:int}|null
     */
    public function settings(): ?array
    {
        if (! $this->settingsTableReady()) {
            return null;
        }

        $row = ThermalPrint::query()->orderBy('id')->first();
        if (! $row) {
            return null;
        }

        return [
            'school_name' => (string) ($row->school_name ?? ''),
            'address' => (string) ($row->address ?? ''),
            'footer_text' => (string) ($row->footer_text ?? ''),
            'is_print' => (int) ($row->is_print ?? 0),
        ];
    }

    /**
     * CI: thermal_print_module == 1 && thermal_print_enable == 1.
     */
    public function isEnabled(): bool
    {
        if (! $this->hasModule() || ! $this->hasActiveModule()) {
            return false;
        }

        $settings = $this->settings();
        if ($settings === null) {
            return false;
        }

        return (int) $settings['is_print'] === 1;
    }

    /**
     * @param  array{school_name?:string,address?:string,footer_text?:string,is_print?:int|bool|string}  $input
     */
    public function save(array $input): ThermalPrint
    {
        $isPrint = ! empty($input['is_print']) && (string) $input['is_print'] !== '0';

        $row = ThermalPrint::query()->orderBy('id')->first();
        if (! $row) {
            $row = new ThermalPrint;
        }

        $row->school_name = (string) ($input['school_name'] ?? '');
        $row->address = (string) ($input['address'] ?? '');
        $row->footer_text = (string) ($input['footer_text'] ?? '');
        $row->is_print = $isPrint ? 1 : 0;
        $row->save();

        // Enabling thermal print activates the module group (addon installed + active parity).
        $group = PermissionGroup::query()->firstOrCreate(
            ['short_code' => self::MODULE_SHORT_CODE],
            ['name' => 'Thermal Print', 'is_active' => 0, 'system' => 0]
        );
        $group->is_active = $isPrint ? 1 : (int) $group->is_active;
        // When disabling print, keep module row but mark inactive only if print off:
        if (! $isPrint) {
            $group->is_active = 0;
        }
        $group->save();

        return $row;
    }

    /**
     * Resolve Blade view name for a receipt style when thermal is enabled.
     */
    public function viewName(string $standardView): string
    {
        if (! $this->isEnabled()) {
            return $standardView;
        }

        return match ($standardView) {
            'fees::print.printFeesByName' => 'fees::print.thermalPrintFeesByName',
            'fees::print.printFeesByGroup' => 'fees::print.thermalPrintFeesByGroup',
            'fees::print.printFeesByGroupArray' => 'fees::print.thermalPrintFeesByGroupArray',
            default => $standardView,
        };
    }
}
