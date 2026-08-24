<?php

namespace App\Modules\Students\Requests;

use App\Modules\Academics\Services\CustomFieldValueService;
use App\Modules\Settings\Models\SchSetting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $settings = SchSetting::query()->first();
        $guardianNameRequired = (int) ($settings->guardian_name ?? 0) === 1;
        $guardianPhoneRequired = (int) ($settings->guardian_phone ?? 0) === 1;
        $studentId = (int) $this->route('id');

        return [
            'firstname' => ['required', 'string', 'max:100'],
            'middlename' => ['nullable', 'string', 'max:255'],
            'lastname' => ['nullable', 'string', 'max:100'],
            'gender' => ['required', 'string', Rule::in(['Male', 'Female'])],
            'dob' => ['required', 'date'],
            'class_id' => ['required', 'integer', 'exists:classes,id'],
            'section_id' => ['required', 'integer', 'exists:sections,id'],
            'admission_no' => ['required', 'string', 'max:100', Rule::unique('students', 'admission_no')->ignore($studentId)],
            'admission_date' => ['nullable', 'date'],
            'roll_no' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:100'],
            'mobileno' => ['nullable', 'string', 'max:100'],
            'religion' => ['nullable', 'string', 'max:100'],
            'cast' => ['nullable', 'string', 'max:50'],
            'category_id' => ['nullable'],
            'blood_group' => ['nullable', 'string', 'max:200'],
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
            'fee_session_group_id' => ['nullable', 'array'],
            'fee_session_group_id.*' => ['integer', 'exists:fee_session_groups,id'],
            'discount_id' => ['nullable', 'array'],
            'discount_id.*' => ['integer', 'exists:fees_discounts,id'],
            'transport_feemaster_id' => ['nullable', 'array'],
            'transport_feemaster_id.*' => ['integer', 'exists:transport_feemaster,id'],
            'route_pickup_point_id' => ['nullable', 'integer', 'exists:route_pickup_point,id'],
            'vehroute_id' => ['nullable', 'integer'],
            'multiclass' => ['nullable', 'array'],
            'multiclass.*.class' => ['nullable', 'integer', 'exists:classes,id'],
            'multiclass.*.section' => ['nullable', 'integer', 'exists:sections,id'],
            'custom_fields' => ['nullable', 'array'],
            'custom_fields.students' => ['nullable', 'array'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $posted = (array) data_get($this->all(), 'custom_fields.students', []);
            $errors = app(CustomFieldValueService::class)->validateRequired('students', $posted);
            foreach ($errors as $key => $message) {
                $validator->errors()->add($key, $message);
            }

            $transportMonths = array_filter((array) $this->input('transport_feemaster_id', []));
            if ($transportMonths !== []) {
                if (! $this->filled('vehroute_id')) {
                    $validator->errors()->add('vehroute_id', 'The '.__('system.route_list').' field is required.');
                }
                if (! $this->filled('route_pickup_point_id')) {
                    $validator->errors()->add('route_pickup_point_id', 'The '.__('system.pickup_point').' field is required.');
                }
            }

            $seen = [];
            foreach ((array) $this->input('multiclass', []) as $index => $row) {
                $classId = (int) ($row['class'] ?? 0);
                $sectionId = (int) ($row['section'] ?? 0);
                if ($classId <= 0 && $sectionId <= 0) {
                    continue;
                }
                if ($classId <= 0 || $sectionId <= 0) {
                    $validator->errors()->add("multiclass.$index.class", 'The '.__('system.class').' / '.__('system.section').' combination is required.');
                    continue;
                }
                $key = $classId.'-'.$sectionId;
                if (isset($seen[$key])) {
                    $validator->errors()->add("multiclass.$index.class", (string) __('system.duplicate_entry'));
                }
                $seen[$key] = true;
            }
        });
    }
}
