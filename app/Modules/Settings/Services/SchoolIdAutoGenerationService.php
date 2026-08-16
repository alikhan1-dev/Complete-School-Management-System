<?php

namespace App\Modules\Settings\Services;

use App\Modules\Settings\Models\SchSetting;
use App\Modules\Shared\Services\SchoolContext;

/**
 * CI Schsettings::idautogeneration + saveidautogeneration.
 */
class SchoolIdAutoGenerationService
{
    public function __construct(protected SchoolContext $school)
    {
    }

    public function current(): ?SchSetting
    {
        return SchSetting::query()->orderBy('id')->first();
    }

    /**
     * CI Customlib::getDigits() — 1..12.
     *
     * @return array<int, int>
     */
    public function digitList(): array
    {
        $digits = [];
        for ($i = 1; $i <= 12; $i++) {
            $digits[$i] = $i;
        }

        return $digits;
    }

    /**
     * Columns written by CI Schsettings::saveidautogeneration.
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

        $admAuto = (int) ($data['adm_auto_insert'] ?? 0);
        $staffAuto = (int) ($data['staffid_auto_insert'] ?? 0);

        $admUpdateStatus = 1;
        $staffUpdateStatus = 1;

        if ($admAuto) {
            if ((string) $row->adm_prefix !== (string) ($data['adm_prefix'] ?? '')
                || (string) $row->adm_start_from !== (string) ($data['adm_start_from'] ?? '')
                || (string) $row->adm_no_digit !== (string) ($data['adm_no_digit'] ?? '')
            ) {
                $admUpdateStatus = 0;
            }
        }

        if ($staffAuto) {
            if ((string) $row->staffid_prefix !== (string) ($data['staffid_prefix'] ?? '')
                || (string) $row->staffid_start_from !== (string) ($data['staffid_start_from'] ?? '')
                || (string) $row->staffid_no_digit !== (string) ($data['staffid_no_digit'] ?? '')
            ) {
                $staffUpdateStatus = 0;
            }
        }

        $row->adm_start_from = (string) ($data['adm_start_from'] ?? '');
        $row->adm_prefix = (string) ($data['adm_prefix'] ?? '');
        $row->adm_no_digit = (int) ($data['adm_no_digit'] ?? 0);
        $row->adm_auto_insert = $admAuto;
        $row->staffid_start_from = (string) ($data['staffid_start_from'] ?? '');
        $row->staffid_prefix = (string) ($data['staffid_prefix'] ?? '');
        $row->staffid_no_digit = (int) ($data['staffid_no_digit'] ?? 0);
        $row->staffid_auto_insert = $staffAuto;
        $row->adm_update_status = $admUpdateStatus;
        $row->staffid_update_status = $staffUpdateStatus;
        $row->save();

        $this->school->clearCache();
    }
}
