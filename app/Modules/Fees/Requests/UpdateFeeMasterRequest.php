<?php

namespace App\Modules\Fees\Requests;

use App\Modules\Fees\Services\FeeMasterService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateFeeMasterRequest extends FormRequest
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
            'account_type' => ['required', Rule::in(['none', 'fix', 'percentage'])],
            'fine_per_day' => ['nullable'],
            'due_date' => ['nullable', 'date'],
            'fine_amount' => ['nullable', 'numeric', 'min:0'],
            'fine_percentage' => ['nullable', 'numeric', 'min:0'],
        ];

        if (in_array($fineType, ['fix', 'percentage'], true)) {
            $rules['due_date'] = ['required', 'date'];
            $rules['fine_amount'] = ['required', 'numeric', 'min:0'];
        }
        if ($fineType === 'percentage') {
            $rules['fine_percentage'] = ['required', 'numeric', 'min:0'];
        }

        return $rules;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $groupId = (int) $this->input('fee_groups_id');
            $typeId = (int) $this->input('feetype_id');
            $rowId = (int) $this->route('id');
            if ($groupId && $typeId && app(FeeMasterService::class)->combinationExists($groupId, $typeId, $rowId)) {
                $validator->errors()->add('fee_groups_id', 'Fee group and fee type combination already exists.');
            }
        });
    }
}
