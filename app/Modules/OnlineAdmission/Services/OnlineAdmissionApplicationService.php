<?php

namespace App\Modules\OnlineAdmission\Services;

use App\Modules\Academics\Models\ClassSection;
use App\Modules\Academics\Models\SchoolClass;
use App\Modules\Certificates\Services\StudentIdCardScanCodeService;
use App\Modules\OnlineAdmission\Models\OnlineAdmission;
use App\Modules\Settings\Models\SchSetting;
use App\Modules\Shared\Services\SchoolContext;
use App\Modules\Students\Models\Student;
use App\Modules\Students\Models\StudentDoc;
use App\Modules\Students\Services\StudentAdmissionService;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * CI admin/Onlinestudent persist (fees/transport/mail/SMS/SaaS deferred).
 */
class OnlineAdmissionApplicationService
{
    public function __construct(
        protected SchoolContext $school,
        protected StudentAdmissionService $admission,
        protected OnlineAdmissionCustomFieldService $customFields,
        protected OnlineAdmissionFormFileService $files,
        protected StudentIdCardScanCodeService $scanCodes,
    ) {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listAll(): array
    {
        return DB::table('online_admissions')
            ->leftJoin('class_sections', 'class_sections.id', '=', 'online_admissions.class_section_id')
            ->leftJoin('classes', 'class_sections.class_id', '=', 'classes.id')
            ->leftJoin('sections', 'sections.id', '=', 'class_sections.section_id')
            ->leftJoin('categories', 'online_admissions.category_id', '=', 'categories.id')
            ->orderByDesc('online_admissions.id')
            ->select(
                'online_admissions.*',
                'classes.class',
                'sections.section',
                'categories.category',
            )
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    public function find(int $id): ?array
    {
        $row = DB::table('online_admissions')
            ->leftJoin('class_sections', 'class_sections.id', '=', 'online_admissions.class_section_id')
            ->leftJoin('classes', 'class_sections.class_id', '=', 'classes.id')
            ->leftJoin('sections', 'sections.id', '=', 'class_sections.section_id')
            ->where('online_admissions.id', $id)
            ->select(
                'online_admissions.*',
                'classes.id as class_id',
                'classes.class',
                'sections.id as section_table_id',
                'sections.section',
                'class_sections.id as class_section_id',
            )
            ->first();

        return $row ? (array) $row : null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function classes(): array
    {
        return SchoolClass::query()->orderBy('id')->get()->map(fn ($row) => $row->toArray())->all();
    }

    /**
     * CI section_model::getClassBySection — option value is class_sections.id.
     *
     * @return list<array<string, mixed>>
     */
    public function sectionsForClass(int $classId): array
    {
        return ClassSection::query()
            ->join('sections', 'sections.id', '=', 'class_sections.section_id')
            ->where('class_sections.class_id', $classId)
            ->orderBy('class_sections.id')
            ->get(['class_sections.id', 'class_sections.section_id', 'sections.section'])
            ->map(fn ($row) => $row->toArray())
            ->all();
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function update(int $id, array $input): void
    {
        OnlineAdmission::query()->where('id', $id)->update($this->payload($input));
        $this->customFields->saveFor($id, (array) data_get($input, 'custom_fields.students', []));
    }

    /**
     * CI onlinestudent_model::update action=enroll (fees/transport/mail deferred).
     *
     * @param  array<string, mixed>  $input
     * @param  array<string, UploadedFile|null>  $uploads
     */
    public function enroll(int $id, array $input, array $uploads = []): bool
    {
        $existing = OnlineAdmission::query()->find($id);
        if ($existing === null) {
            return false;
        }

        $payload = $this->payload($input);
        $classSectionId = (int) ($payload['class_section_id'] ?? 0);
        $classSection = ClassSection::query()->find($classSectionId);
        if ($classSection === null) {
            return false;
        }

        $settings = SchSetting::query()->orderBy('id')->firstOrFail();
        $admissionNo = (string) ($payload['admission_no'] ?? '');
        if ((int) $settings->adm_auto_insert === 1) {
            $admissionNo = '';
            $payload['admission_no'] = '';
        } elseif ($admissionNo !== '' && $this->admissionNoExists($admissionNo)) {
            return false;
        }

        $studentData = $this->studentDataFromPayload($payload, $existing);
        if ($admissionNo !== '') {
            $studentData['admission_no'] = $admissionNo;
        } else {
            unset($studentData['admission_no']);
        }

        $staffId = Auth::guard('staff')->id();
        if ($staffId) {
            $studentData['created_by'] = (int) $staffId;
        }

        return DB::transaction(function () use ($id, $payload, $studentData, $classSection, $input, $existing, $uploads) {
            $posted = (array) data_get($input, 'custom_fields.students', []);
            $result = $this->admission->admit(
                $studentData,
                (int) $classSection->class_id,
                (int) $classSection->section_id,
                0,
                $this->customFields->studentValueRows($posted),
            );

            $payload['is_enroll'] = 1;
            $payload['admission_no'] = (string) (Student::query()->where('id', $result['student_id'])->value('admission_no') ?? $payload['admission_no'] ?? '');
            OnlineAdmission::query()->where('id', $id)->update($payload);
            $this->copyEnrollMedia($existing, (int) $result['student_id'], $uploads);
            $this->generateEnrollScanCodes((int) $result['student_id'], (string) $payload['admission_no']);

            return true;
        });
    }

    public function admissionNoExists(string $admissionNo): bool
    {
        return Student::query()->where('admission_no', $admissionNo)->exists();
    }

    public function studentEmailExists(string $email): bool
    {
        if ($email === '') {
            return false;
        }

        return Student::query()->where('email', $email)->exists();
    }

    public function delete(int $id): void
    {
        $row = OnlineAdmission::query()->find($id);
        if ($row === null) {
            return;
        }

        foreach (['image', 'father_pic', 'mother_pic', 'guardian_pic'] as $field) {
            $path = (string) ($row->{$field} ?? '');
            if ($path !== '') {
                $full = public_path(ltrim(str_replace('./', '', $path), '/'));
                if (is_file($full)) {
                    @unlink($full);
                }
            }
        }

        $doc = (string) ($row->document ?? '');
        if ($doc !== '') {
            $docPath = public_path('uploads/student_documents/online_admission_doc/'.basename($doc));
            if (is_file($docPath)) {
                @unlink($docPath);
            }
        }

        $this->customFields->deleteFor((int) $row->id);
        $row->delete();
    }

    public function paymentStatusMessage(int $id): string
    {
        $row = OnlineAdmission::query()->find($id);
        if ($row === null) {
            return '';
        }

        $paymentOn = (string) SchSetting::query()->value('online_admission_payment') === 'yes';
        $formStatus = (int) $row->form_status;
        $paidStatus = (int) $row->paid_status;

        if ($formStatus !== 1 && $paymentOn && $paidStatus === 0) {
            return "Form Status         : Not Submitted \nPayment Status    : Unpaid \n \nDo you still want to enroll it? ";
        }
        if ($formStatus !== 1 && ! $paymentOn) {
            return "Form Status         : Not Submitted \n \n Do you still want to enroll it? ";
        }
        if ($formStatus === 1 && $paymentOn && $paidStatus === 0) {
            return "Payment Status   : Unpaid \n \n Do you still want to enroll it? ";
        }

        return '';
    }

    public function parseDate(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1) {
            return $value;
        }

        try {
            return Carbon::createFromFormat($this->school->dateFormat() ?: 'd/m/Y', $value)->format('Y-m-d');
        } catch (\Throwable) {
            throw new InvalidArgumentException('Invalid date.');
        }
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    protected function payload(array $input): array
    {
        $emptyToNull = static function ($value) {
            $value = is_string($value) ? trim($value) : $value;

            return $value === '' || $value === null ? null : $value;
        };

        return [
            'admission_no' => (string) ($input['admission_no'] ?? ''),
            'roll_no' => (string) ($input['roll_no'] ?? ''),
            'firstname' => (string) ($input['firstname'] ?? ''),
            'middlename' => (string) ($input['middlename'] ?? ''),
            'lastname' => (string) ($input['lastname'] ?? ''),
            'rte' => (string) ($input['rte'] ?? 'No'),
            'mobileno' => (string) ($input['mobileno'] ?? ''),
            'email' => (string) ($input['email'] ?? ''),
            'state' => (string) ($input['state'] ?? ''),
            'city' => (string) ($input['city'] ?? ''),
            'previous_school' => (string) ($input['previous_school'] ?? ''),
            'pincode' => (string) ($input['pincode'] ?? ''),
            'religion' => (string) ($input['religion'] ?? ''),
            'dob' => $this->parseDate((string) ($input['dob'] ?? '')),
            'admission_date' => $this->parseDate((string) ($input['admission_date'] ?? '')) ?: null,
            'current_address' => (string) ($input['current_address'] ?? ''),
            'permanent_address' => (string) ($input['permanent_address'] ?? ''),
            'category_id' => $emptyToNull($input['category_id'] ?? null),
            'adhar_no' => (string) ($input['adhar_no'] ?? ''),
            'samagra_id' => (string) ($input['samagra_id'] ?? ''),
            'bank_account_no' => (string) ($input['bank_account_no'] ?? ''),
            'bank_name' => (string) ($input['bank_name'] ?? ''),
            'ifsc_code' => (string) ($input['ifsc_code'] ?? ''),
            'cast' => (string) ($input['cast'] ?? ''),
            'father_name' => (string) ($input['father_name'] ?? ''),
            'father_phone' => (string) ($input['father_phone'] ?? ''),
            'father_occupation' => (string) ($input['father_occupation'] ?? ''),
            'mother_name' => (string) ($input['mother_name'] ?? ''),
            'mother_phone' => (string) ($input['mother_phone'] ?? ''),
            'mother_occupation' => (string) ($input['mother_occupation'] ?? ''),
            'guardian_email' => (string) ($input['guardian_email'] ?? ''),
            'gender' => (string) ($input['gender'] ?? ''),
            'guardian_name' => (string) ($input['guardian_name'] ?? ''),
            'guardian_relation' => (string) ($input['guardian_relation'] ?? ''),
            'guardian_phone' => (string) ($input['guardian_phone'] ?? ''),
            'guardian_address' => (string) ($input['guardian_address'] ?? ''),
            'guardian_is' => (string) ($input['guardian_is'] ?? ''),
            'guardian_occupation' => (string) ($input['guardian_occupation'] ?? ''),
            'school_house_id' => $emptyToNull($input['house'] ?? null),
            'blood_group' => (string) ($input['blood_group'] ?? ''),
            'height' => (string) ($input['height'] ?? ''),
            'weight' => (string) ($input['weight'] ?? ''),
            'note' => (string) ($input['note'] ?? ''),
            'class_section_id' => (int) ($input['section_id'] ?? 0) ?: null,
            'hostel_room_id' => $emptyToNull($input['hostel_room_id'] ?? null),
            'vehroute_id' => (int) ($input['vehroute_id'] ?? 0),
            'measurement_date' => $this->parseDate((string) ($input['measure_date'] ?? '')) ?: null,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function studentDataFromPayload(array $payload, OnlineAdmission $existing): array
    {
        $keys = [
            'admission_no', 'roll_no', 'firstname', 'middlename', 'lastname', 'rte',
            'mobileno', 'email', 'state', 'city', 'pincode', 'religion', 'cast', 'dob',
            'gender', 'current_address', 'permanent_address', 'category_id', 'school_house_id',
            'blood_group', 'hostel_room_id', 'adhar_no', 'samagra_id', 'bank_account_no',
            'bank_name', 'ifsc_code', 'guardian_is', 'father_name', 'father_phone',
            'father_occupation', 'mother_name', 'mother_phone', 'mother_occupation',
            'guardian_name', 'guardian_relation', 'guardian_phone', 'guardian_occupation',
            'guardian_address', 'guardian_email', 'previous_school', 'height', 'weight',
            'note', 'admission_date', 'measurement_date',
        ];

        $data = [];
        foreach ($keys as $key) {
            if (array_key_exists($key, $payload)) {
                $data[$key] = $payload[$key];
            }
        }

        foreach (['image', 'father_pic', 'mother_pic', 'guardian_pic'] as $pic) {
            $data[$pic] = (string) ($existing->{$pic} ?? '');
        }

        return $data;
    }

    /**
     * CI Onlinestudent enroll document + photo copy (SaaS deferred).
     *
     * @param  array<string, UploadedFile|null>  $uploads
     */
    protected function copyEnrollMedia(OnlineAdmission $existing, int $studentId, array $uploads): void
    {
        $docName = $this->files->copyDocumentToStudent((string) ($existing->document ?? ''), $studentId);
        if ($docName !== null) {
            StudentDoc::query()->create([
                'student_id' => $studentId,
                'title' => '',
                'doc' => $docName,
            ]);
        }

        $photos = [
            ['file', 'image', ''],
            ['father_pic', 'father_pic', 'father'],
            ['mother_pic', 'mother_pic', 'mother'],
            ['guardian_pic', 'guardian_pic', 'guardian'],
        ];
        $updates = [];
        foreach ($photos as [$inputName, $dbField, $suffix]) {
            $upload = $uploads[$inputName] ?? null;
            if ($upload instanceof UploadedFile && $upload->isValid()) {
                $updates[$dbField] = $this->files->storeStudentImage($upload, $studentId, $suffix);
                continue;
            }
            $copied = $this->files->copyImageToStudent((string) ($existing->{$dbField} ?? ''), $studentId, $suffix);
            if ($copied !== null) {
                $updates[$dbField] = $copied;
            }
        }
        if ($updates !== []) {
            Student::query()->where('id', $studentId)->update($updates);
        }
    }

    /**
     * CI Customlib::generatebarcode — writes both barcode and qrcode files; scan_code_type only selects the return path in CI.
     */
    protected function generateEnrollScanCodes(int $studentId, string $admissionNo): void
    {
        $this->scanCodes->generate($admissionNo, $studentId, 'barcode');
        $this->scanCodes->generate($admissionNo, $studentId, 'qrcode');
    }
}
