<?php

namespace App\Modules\Staff\Requests;

use App\Modules\Academics\Services\CustomFieldValueService;
use App\Modules\Settings\Models\SchSetting;
use App\Modules\Staff\Models\Staff;
use App\Modules\Staff\Services\StaffDocumentService;
use App\Modules\Staff\Services\StaffPhotoService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $settings = SchSetting::query()->orderBy('id')->first();
        $autoStaffId = $settings && (int) $settings->staffid_auto_insert === 1;
        $staffPhotoEnabled = $settings && (int) $settings->staff_photo === 1;

        $rules = [
            'name' => ['required', 'string', 'max:200'],
            'role' => ['required', 'integer', 'min:1'],
            'gender' => ['required', 'string', 'max:50'],
            'dob' => ['required', 'date'],
            'email' => [
                'required',
                'email',
                'max:200',
                Rule::unique('staff', 'email'),
            ],
            'surname' => ['nullable', 'string', 'max:200'],
            'contactno' => ['nullable', 'string', 'max:200'],
            'emergency_no' => ['nullable', 'string', 'max:200'],
            'department' => ['nullable', 'integer'],
            'designation' => ['nullable', 'integer'],
            'marital_status' => ['nullable', 'string', 'max:100'],
            'date_of_joining' => ['nullable', 'date'],
            'address' => ['nullable', 'string', 'max:300'],
            'permanent_address' => ['nullable', 'string', 'max:200'],
            'qualification' => ['nullable', 'string', 'max:200'],
            'work_exp' => ['nullable', 'string', 'max:200'],
            'basic_salary' => ['nullable', 'integer', 'min:0'],
            'contract_type' => ['nullable', 'string', 'max:100'],
            'shift' => ['nullable', 'string', 'max:100'],
            'location' => ['nullable', 'string', 'max:100'],
            'account_title' => ['nullable', 'string', 'max:200'],
            'bank_account_no' => ['nullable', 'string', 'max:200'],
            'bank_name' => ['nullable', 'string', 'max:200'],
            'ifsc_code' => ['nullable', 'string', 'max:200'],
            'bank_branch' => ['nullable', 'string', 'max:100'],
            'epf_no' => ['nullable', 'string', 'max:200'],
            'father_name' => ['nullable', 'string', 'max:200'],
            'mother_name' => ['nullable', 'string', 'max:200'],
            'note' => ['nullable', 'string', 'max:200'],
            'facebook' => ['nullable', 'string', 'max:200'],
            'twitter' => ['nullable', 'string', 'max:200'],
            'linkedin' => ['nullable', 'string', 'max:200'],
            'instagram' => ['nullable', 'string', 'max:200'],
            'leave_type' => ['nullable', 'array'],
            'leave_type.*' => ['integer', 'min:1'],
        ];

        if (! $autoStaffId) {
            $rules['employee_id'] = [
                'required',
                'string',
                'max:200',
                Rule::unique('staff', 'employee_id'),
            ];
        }

        return array_merge(
            $rules,
            app(StaffDocumentService::class)->documentValidationRules(),
            app(StaffPhotoService::class)->photoValidationRules($staffPhotoEnabled),
        );
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'employee_id.required' => __('system.staff_id_field_is_required'),
            'employee_id.unique' => 'Record already exists',
            'email.unique' => __('system.email_already_exists'),
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $posted = (array) data_get($this->all(), 'custom_fields.staff', []);
            foreach (app(CustomFieldValueService::class)->validateRequired('staff', $posted) as $key => $message) {
                $validator->errors()->add($key, $message);
            }
        });
    }
}
