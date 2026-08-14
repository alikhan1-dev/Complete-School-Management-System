<?php

namespace App\Modules\OnlineAdmission\Services;

use App\Modules\OnlineAdmission\Models\OnlineAdmissionField;
use App\Modules\Settings\Models\SchSetting;
use App\Modules\Shared\Services\SchoolContext;
use Illuminate\Http\UploadedFile;

/**
 * CI admin/Onlineadmission admissionsetting + changeformfieldsetting persist.
 * SaaS quota deferred.
 */
class OnlineAdmissionSettingService
{
    public const GUARDIAN_FIELDS = [
        'guardian_relation',
        'guardian_name',
        'guardian_phone',
        'guardian_photo',
        'guardian_occupation',
        'guardian_email',
        'guardian_address',
    ];

    public const FIELD_LABELS = [
        'middlename' => 'Middle Name',
        'lastname' => 'Last Name',
        'category' => 'Category',
        'religion' => 'Religion',
        'cast' => 'Caste',
        'mobile_no' => 'Mobile Number',
        'student_email' => 'Email',
        'student_photo' => 'Student Photo',
        'is_student_house' => 'House',
        'is_blood_group' => 'Blood Group',
        'student_height' => 'Height',
        'student_weight' => 'Weight',
        'measurement_date' => 'Measurement Date',
        'father_name' => 'Father Name',
        'father_phone' => 'Father Phone',
        'father_occupation' => 'Father Occupation',
        'father_pic' => 'Father Photo',
        'mother_name' => 'Mother Name',
        'mother_phone' => 'Mother Phone',
        'mother_occupation' => 'Mother Occupation',
        'mother_pic' => 'Mother Photo',
        'if_guardian_is' => 'If Guardian Is',
        'guardian_name' => 'Guardian Name',
        'guardian_relation' => 'Guardian Relation',
        'guardian_phone' => 'Guardian Phone',
        'guardian_email' => 'Guardian Email',
        'guardian_occupation' => 'Guardian Occupation',
        'guardian_photo' => 'Guardian Photo',
        'guardian_address' => 'Guardian Address',
        'current_address' => 'If Guardian Address Is Current Address',
        'permanent_address' => 'If Permanent Address Is Current Address',
        'bank_account_no' => 'Bank Account Number',
        'bank_name' => 'Bank Name',
        'ifsc_code' => 'IFSC Code',
        'national_identification_no' => 'National Identification Number',
        'local_identification_no' => 'Local Identification Number',
        'rte' => 'RTE',
        'previous_school_details' => 'Previous School Details',
        'student_note' => 'Note',
        'upload_documents' => 'Upload Documents',
    ];

    public function __construct(
        protected OnlineAdmissionFormFileService $files,
        protected SchoolContext $school,
    ) {
    }

    public function school(): object
    {
        $row = SchSetting::query()->orderBy('id')->first();
        abort_if($row === null, 404);

        return $row;
    }

    /**
     * @return list<array{name: string, label: string, enabled: bool}>
     */
    public function formFieldRows(): array
    {
        $school = $this->school()->toArray();
        $inserted = OnlineAdmissionField::query()->get()->keyBy('name');
        $custom = \Illuminate\Support\Facades\DB::table('custom_fields')
            ->where('belong_to', 'students')
            ->orderBy('weight')
            ->pluck('name')
            ->all();

        $rows = [];
        foreach (self::FIELD_LABELS as $name => $label) {
            if (array_key_exists($name, $school) && empty($school[$name])) {
                continue;
            }
            $field = $inserted->get($name);
            $rows[] = [
                'name' => $name,
                'label' => $label,
                'enabled' => $field !== null && (int) $field->status === 1,
            ];
        }
        foreach ($custom as $name) {
            $field = $inserted->get($name);
            $rows[] = [
                'name' => (string) $name,
                'label' => (string) $name,
                'enabled' => $field !== null && (int) $field->status === 1,
            ];
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function saveSettings(array $input, ?UploadedFile $file): void
    {
        $school = $this->school();
        $paymentOn = (string) ($input['online_admission_payment'] ?? '') === 'yes';
        $payload = [
            'online_admission' => ! empty($input['online_admission']) ? 1 : 0,
            'online_admission_payment' => $paymentOn ? 'yes' : 'no',
            'online_admission_instruction' => (string) ($input['online_admission_instruction'] ?? ''),
            'online_admission_conditions' => (string) ($input['online_admission_conditions'] ?? ''),
        ];
        if ($paymentOn) {
            $payload['online_admission_amount'] = (float) trim((string) ($input['online_admission_amount'] ?? '0'));
        }

        if ($file instanceof UploadedFile) {
            $this->files->delete((string) ($school->online_admission_application_form ?? ''));
            $payload['online_admission_application_form'] = $this->files->store($file);
        }

        SchSetting::query()->where('id', $school->id)->update($payload);
        $this->school->clearCache();
    }

    public function saveFormField(string $name, int $status): void
    {
        $existing = OnlineAdmissionField::query()->where('name', $name)->first();
        if ($existing) {
            OnlineAdmissionField::query()->where('id', $existing->id)->update(['status' => $status]);
        } else {
            OnlineAdmissionField::query()->create([
                'name' => $name,
                'status' => $status,
            ]);
        }

        if ($name === 'if_guardian_is') {
            OnlineAdmissionField::query()
                ->whereIn('name', self::GUARDIAN_FIELDS)
                ->update(['status' => $status]);
        }
    }
}
