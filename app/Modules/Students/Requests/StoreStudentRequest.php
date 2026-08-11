<?php

namespace App\Modules\Students\Requests;

use App\Modules\Academics\Services\CustomFieldValueService;
use App\Modules\Settings\Models\SchSetting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $settings = SchSetting::query()->first();
        $admAuto = (int) ($settings->adm_auto_insert ?? 0) === 1;
        $guardianNameRequired = (int) ($settings->guardian_name ?? 0) === 1;
        $guardianPhoneRequired = (int) ($settings->guardian_phone ?? 0) === 1;

        $rules = [
            'firstname' => ['required', 'string', 'max:100'],
            'middlename' => ['nullable', 'string', 'max:255'],
            'lastname' => ['nullable', 'string', 'max:100'],
            'gender' => ['required', 'string', Rule::in(['Male', 'Female'])],
            'dob' => ['required', 'date'],
            'class_id' => ['required', 'integer', 'exists:classes,id'],
            'section_id' => ['required', 'integer', 'exists:sections,id'],
            'admission_date' => ['nullable', 'date'],
            'roll_no' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:100'],
            'mobileno' => ['nullable', 'string', 'max:100'],
            'religion' => ['nullable', 'string', 'max:100'],
            'cast' => ['nullable', 'string', 'max:50'],
            'category_id' => ['nullable'],
            'blood_group' => ['nullable', 'string', 'max:200'],
            'house' => ['nullable', 'integer'],
            'height' => ['nullable', 'string', 'max:100'],
            'weight' => ['nullable', 'string', 'max:100'],
            'father_name' => ['nullable', 'string', 'max:100'],
            'father_phone' => ['nullable', 'string', 'max:100'],
            'father_occupation' => ['nullable', 'string', 'max:100'],
            'mother_name' => ['nullable', 'string', 'max:100'],
            'mother_phone' => ['nullable', 'string', 'max:100'],
            'mother_occupation' => ['nullable', 'string', 'max:100'],
            'guardian_name' => [$guardianNameRequired ? 'required' : 'nullable', 'string', 'max:100'],
            'guardian_is' => [$guardianNameRequired ? 'required' : 'nullable', 'string', 'max:100'],
            'guardian_relation' => ['nullable', 'string', 'max:100'],
            'guardian_phone' => [$guardianPhoneRequired ? 'required' : 'nullable', 'string', 'max:100'],
            'guardian_email' => ['nullable', 'email', 'max:100'],
            'guardian_occupation' => ['nullable', 'string', 'max:100'],
            'guardian_address' => ['nullable', 'string'],
            'current_address' => ['nullable', 'string'],
            'permanent_address' => ['nullable', 'string'],
            'state' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'pincode' => ['nullable', 'string', 'max:100'],
            'previous_school' => ['nullable', 'string'],
            'adhar_no' => ['nullable', 'string', 'max:100'],
            'samagra_id' => ['nullable', 'string', 'max:100'],
            'bank_account_no' => ['nullable', 'string', 'max:100'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'ifsc_code' => ['nullable', 'string', 'max:100'],
            'rte' => ['nullable', 'string', 'max:20'],
            'note' => ['nullable', 'string'],
            'fees_discount' => ['nullable', 'numeric'],
            'sibling_id' => ['nullable', 'integer', 'exists:students,id'],
            'sibling_name' => ['nullable', 'string', 'max:255'],
            'custom_fields' => ['nullable', 'array'],
            'custom_fields.students' => ['nullable', 'array'],
        ];

        if (! $admAuto) {
            $rules['admission_no'] = ['required', 'string', 'max:100', Rule::unique('students', 'admission_no')];
        } else {
            $rules['admission_no'] = ['nullable', 'string', 'max:100'];
        }

        return $rules;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $posted = (array) data_get($this->all(), 'custom_fields.students', []);
            $errors = app(CustomFieldValueService::class)->validateRequired('students', $posted);
            foreach ($errors as $key => $message) {
                $validator->errors()->add($key, $message);
            }
        });
    }
}
