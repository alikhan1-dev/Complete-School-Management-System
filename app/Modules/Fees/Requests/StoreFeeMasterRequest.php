<?php

namespace App\Modules\Fees\Requests;

use App\Modules\Fees\Services\FeeMasterService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreFeeMasterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $fineType = (string) $this->input('account_type', 'none');

        $rules = [
            'fee_groups_id' => ['required', 'integer', 'exists:fee_groups,id'],
            'feetype_id' => ['required', 'integer', 'exists:feetype,id'],
            'amount' => ['required', 'numeric', 'min:0'],
            'account_type' => ['required', Rule::in(['none', 'fix', 'percentage', 'cumulative'])],
            'fine_per_day' => ['nullable'],
            'due_date' => ['nullable', 'date'],
            'fine_amount' => ['nullable', 'numeric', 'min:0'],
            'fine_percentage' => ['nullable', 'numeric', 'min:0'],
            'overdue_day' => ['nullable', 'array'],
            'overdue_day.*' => ['nullable', 'numeric', 'min:1'],
            'overdue_fine' => ['nullable', 'array'],
            'overdue_fine.*' => ['nullable', 'numeric', 'min:0'],
            'cumulative_id' => ['nullable', 'array'],
            'cumulative_id.*' => ['nullable', 'integer'],
        ];

        if (in_array($fineType, ['fix', 'percentage', 'cumulative'], true)) {
            $rules['due_date'] = ['required', 'date'];
        }
        if (in_array($fineType, ['fix', 'percentage'], true)) {
            $rules['fine_amount'] = ['required', 'numeric', 'min:0'];
        }
        if ($fineType === 'percentage') {
            $rules['fine_percentage'] = ['required', 'numeric', 'min:0'];
        }
        if ($fineType === 'cumulative') {
            $rules['overdue_day'] = ['required', 'array', 'min:1'];
            $rules['overdue_day.*'] = ['required', 'numeric', 'min:1'];
            $rules['overdue_fine'] = ['required', 'array', 'min:1'];
            $rules['overdue_fine.*'] = ['required', 'numeric', 'min:0'];
        }

        return $rules;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $groupId = (int) $this->input('fee_groups_id');
            $typeId = (int) $this->input('feetype_id');
            if ($groupId && $typeId && app(FeeMasterService::class)->combinationExists($groupId, $typeId)) {
                $validator->errors()->add('fee_groups_id', 'Fee group and fee type combination already exists.');
            }
        });
    }
}
