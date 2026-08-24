<?php

namespace App\Modules\Academics\Services;

use App\Modules\Academics\Models\CustomField;
use App\Modules\Academics\Models\CustomFieldValue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Load / validate / persist custom field values (CI customfield helper + model).
 */
class CustomFieldValueService
{
    /**
     * @return Collection<int, CustomField>
     */
    public function fieldsFor(string $belongTo): Collection
    {
        return CustomField::query()
            ->where('belong_to', $belongTo)
            ->orderBy('weight')
            ->orderBy('id')
            ->get();
    }

    /**
     * CI customfield_model::get_custom_fields($belongTo, 1).
     *
     * @return Collection<int, CustomField>
     */
    public function fieldsForTable(string $belongTo): Collection
    {
        return CustomField::query()
            ->where('belong_to', $belongTo)
            ->where('visible_on_table', 1)
            ->orderBy('weight')
            ->orderBy('id')
            ->get();
    }

    /**
     * Batched values for table-visible fields: student_id => [field_id => value].
     *
     * @param  list<int|string>  $belongTableIds
     * @return array<int, array<int, string>>
     */
    public function tableValuesByBelongIds(string $belongTo, array $belongTableIds): array
    {
        $belongTableIds = array_values(array_unique(array_filter(array_map('intval', $belongTableIds), fn (int $id) => $id > 0)));
        $fields = $this->fieldsForTable($belongTo);
        if ($belongTableIds === [] || $fields->isEmpty()) {
            return [];
        }

        $fieldIds = $fields->pluck('id')->map(fn ($id) => (int) $id)->all();
        $rows = CustomFieldValue::query()
            ->whereIn('belong_table_id', $belongTableIds)
            ->whereIn('custom_field_id', $fieldIds)
            ->get(['belong_table_id', 'custom_field_id', 'field_value']);

        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row->belong_table_id][(int) $row->custom_field_id] = (string) ($row->field_value ?? '');
        }

        return $map;
    }

    /**
     * Posted shape: custom_fields[students][fieldId] = scalar|array
     *
     * @param  array<string, mixed>  $posted  request()->input('custom_fields.students', [])
     * @return array<string, string> Laravel-style errors keyed by input name
     */
    public function validateRequired(string $belongTo, array $posted): array
    {
        $errors = [];
        foreach ($this->fieldsFor($belongTo) as $field) {
            if ((int) $field->validation !== 1) {
                continue;
            }
            $value = $posted[$field->id] ?? null;
            $empty = $value === null
                || $value === ''
                || (is_array($value) && count(array_filter($value, fn ($v) => $v !== '' && $v !== null)) === 0);

            if ($empty) {
                $errors['custom_fields.'.$belongTo.'.'.$field->id] = ucfirst($field->name).' is required.';
            }
        }

        return $errors;
    }

    /**
     * Normalize posted values into rows for insert/update.
     *
     * @param  array<string|int, mixed>  $posted
     * @return list<array{custom_field_id:int,field_value:string,belong_table_id?:int}>
     */
    public function normalizePosted(string $belongTo, array $posted): array
    {
        $rows = [];
        $knownIds = $this->fieldsFor($belongTo)->pluck('id')->all();

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
     * @param  list<array{custom_field_id:int,field_value:string}>  $rows
     */
    public function insertFor(int $belongTableId, array $rows): void
    {
        if ($rows === []) {
            return;
        }

        foreach ($rows as &$row) {
            $row['belong_table_id'] = $belongTableId;
        }
        unset($row);

        CustomFieldValue::query()->insert($rows);
    }

    /**
     * @param  list<array{custom_field_id:int,field_value:string}>  $rows
     */
    public function upsertFor(int $belongTableId, array $rows): void
    {
        DB::transaction(function () use ($belongTableId, $rows) {
            foreach ($rows as $row) {
                $existing = CustomFieldValue::query()
                    ->where('belong_table_id', $belongTableId)
                    ->where('custom_field_id', $row['custom_field_id'])
                    ->first();

                if ($existing) {
                    $existing->field_value = $row['field_value'];
                    $existing->save();
                } else {
                    CustomFieldValue::query()->create([
                        'belong_table_id' => $belongTableId,
                        'custom_field_id' => $row['custom_field_id'],
                        'field_value' => $row['field_value'],
                    ]);
                }
            }
        });
    }

    /**
     * @return array<int, string> field_id => value
     */
    public function valuesMap(string $belongTo, int $belongTableId): array
    {
        $fieldIds = $this->fieldsFor($belongTo)->pluck('id')->all();
        if ($fieldIds === []) {
            return [];
        }

        return CustomFieldValue::query()
            ->where('belong_table_id', $belongTableId)
            ->whereIn('custom_field_id', $fieldIds)
            ->pluck('field_value', 'custom_field_id')
            ->map(fn ($v) => (string) $v)
            ->all();
    }

    /**
     * @return list<string>
     */
    public function optionSplit(?string $fieldValues): array
    {
        if ($fieldValues === null || trim($fieldValues) === '') {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $fieldValues)), fn ($v) => $v !== ''));
    }
}
