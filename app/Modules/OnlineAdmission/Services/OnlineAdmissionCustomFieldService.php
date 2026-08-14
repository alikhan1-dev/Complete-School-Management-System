<?php

namespace App\Modules\OnlineAdmission\Services;

use App\Modules\Academics\Models\CustomField;
use App\Modules\Academics\Services\CustomFieldValueService;
use App\Modules\OnlineAdmission\Models\OnlineAdmissionCustomFieldValue;
use Illuminate\Support\Collection;

/**
 * CI customfield_model onlineadmissioninsertRecord / onlineadmissionupdateRecord.
 * Values live on online_admission_custom_field_value (not custom_field_values).
 */
class OnlineAdmissionCustomFieldService
{
    public function __construct(
        protected OnlineAdmissionSettingService $settings,
        protected CustomFieldValueService $fields,
    ) {
    }

    /**
     * Student custom fields enabled on the online admission form.
     *
     * @return Collection<int, CustomField>
     */
    public function visibleFields(): Collection
    {
        return $this->fields->fieldsFor('students')
            ->filter(fn (CustomField $field) => $this->settings->fieldEnabled((string) $field->name))
            ->values();
    }

    /**
     * @param  array<string|int, mixed>  $posted
     * @return array<string, string>
     */
    public function validate(array $posted): array
    {
        $errors = [];
        foreach ($this->visibleFields() as $field) {
            if ((int) $field->validation !== 1) {
                continue;
            }
            $value = $posted[$field->id] ?? null;
            $empty = $value === null
                || $value === ''
                || (is_array($value) && count(array_filter($value, fn ($v) => $v !== '' && $v !== null)) === 0);
            if ($empty) {
                $errors['custom_fields.students.'.$field->id] = 'The '.$field->name.' field is required.';
            }
        }

        return $errors;
    }

    /**
     * @param  array<string|int, mixed>  $posted
     * @return array<int, string>
     */
    public function postedValues(array $posted): array
    {
        $values = [];
        foreach ($posted as $fieldId => $value) {
            $values[(int) $fieldId] = is_array($value) ? implode(',', $value) : (string) ($value ?? '');
        }

        return $values;
    }

    /**
     * CI customfield_model::updateRecord rows for enroll (belong_table_id = student id).
     *
     * @param  array<string|int, mixed>  $posted
     * @return list<array{custom_field_id:int,field_value:string}>
     */
    public function studentValueRows(array $posted): array
    {
        $knownIds = $this->visibleFields()->pluck('id')->all();
        $rows = [];
        foreach ($posted as $fieldId => $value) {
            $fieldId = (int) $fieldId;
            if (! in_array($fieldId, $knownIds, true)) {
                continue;
            }
            $rows[] = [
                'custom_field_id' => $fieldId,
                'field_value' => is_array($value) ? implode(',', $value) : (string) ($value ?? ''),
            ];
        }

        return $rows;
    }

    /**
     * @param  array<string|int, mixed>  $posted
     */
    public function saveFor(int $admissionId, array $posted): void
    {
        $knownIds = $this->visibleFields()->pluck('id')->all();
        foreach ($posted as $fieldId => $value) {
            $fieldId = (int) $fieldId;
            if (! in_array($fieldId, $knownIds, true)) {
                continue;
            }
            $fieldValue = is_array($value) ? implode(',', $value) : (string) ($value ?? '');
            $existing = OnlineAdmissionCustomFieldValue::query()
                ->where('belong_table_id', $admissionId)
                ->where('custom_field_id', $fieldId)
                ->first();
            if ($existing) {
                $existing->field_value = $fieldValue;
                $existing->save();
            } else {
                OnlineAdmissionCustomFieldValue::query()->create([
                    'belong_table_id' => $admissionId,
                    'custom_field_id' => $fieldId,
                    'field_value' => $fieldValue,
                ]);
            }
        }
    }

    /**
     * @return array<int, string>
     */
    public function valuesMap(int $admissionId): array
    {
        return OnlineAdmissionCustomFieldValue::query()
            ->where('belong_table_id', $admissionId)
            ->pluck('field_value', 'custom_field_id')
            ->map(fn ($v) => (string) $v)
            ->all();
    }

    public function deleteFor(int $admissionId): void
    {
        OnlineAdmissionCustomFieldValue::query()->where('belong_table_id', $admissionId)->delete();
    }
}
