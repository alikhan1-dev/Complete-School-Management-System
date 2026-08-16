<?php

namespace App\Modules\Settings\Services;

use App\Modules\Settings\Models\SchSetting;
use App\Modules\Shared\Services\SchoolContext;

/**
 * CI Schsettings::fees + savefees.
 */
class SchoolFeesSettingService
{
    public function __construct(protected SchoolContext $school)
    {
    }

    public function current(): ?SchSetting
    {
        return SchSetting::query()->orderBy('id')->first();
    }

    /**
     * CI: explode(",", $setting->is_duplicate_fees_invoice)
     *
     * @return list<int>
     */
    public function duplicateInvoiceFlags(?SchSetting $row = null): array
    {
        $row ??= $this->current();
        $raw = (string) ($row->is_duplicate_fees_invoice ?? '0');
        if ($raw === '') {
            return [];
        }

        return array_values(array_map('intval', explode(',', $raw)));
    }

    public function isPartialPaymentEnabled(?SchSetting $row = null): bool
    {
        $row ??= $this->current();
        $raw = strtolower(trim((string) ($row->student_partial_payment ?? '1')));

        return in_array($raw, ['enabled', '1', 'true', 'yes'], true);
    }

    /**
     * Columns written by CI Schsettings::savefees only.
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

        $row->is_duplicate_fees_invoice = (string) ($data['is_duplicate_fees_invoice'] ?? '0');
        $row->single_page_print = (int) ($data['single_page_print'] ?? 0);
        $row->fee_due_days = (int) ($data['fee_due_days'] ?? 0);
        $row->lock_grace_period = (int) ($data['lock_grace_period'] ?? 0);
        // CI posts unchecked checkboxes as false/null → store 0 for NOT NULL ints.
        $row->collect_back_date_fees = $this->flag01($data['collect_back_date_fees'] ?? null);
        $row->display_previous_fees = $this->flag01($data['display_previous_fees'] ?? null);
        $row->is_student_feature_lock = $this->flag01($data['is_student_feature_lock'] ?? null);
        $row->is_offline_fee_payment = $this->flag01($data['is_offline_fee_payment'] ?? null);
        $row->offline_bank_payment_instruction = (string) ($data['offline_bank_payment_instruction'] ?? '');
        $row->fees_discount = $this->flag01($data['fees_discount'] ?? null);
        $row->student_partial_payment = $this->flag01($data['student_partial_payment'] ?? null);
        $row->save();

        $this->school->clearCache();
    }

    protected function flag01(mixed $value): int
    {
        if ($value === null || $value === false || $value === '') {
            return 0;
        }

        return in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'enabled'], true) ? 1 : 0;
    }
}
