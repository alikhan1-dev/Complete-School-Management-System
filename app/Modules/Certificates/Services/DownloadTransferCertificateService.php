<?php

namespace App\Modules\Certificates\Services;

use App\Modules\Academics\Services\CurrentSessionResolver;
use App\Modules\Academics\Services\CustomFieldValueService;
use App\Modules\Certificates\Models\TransferCertificateField;
use App\Modules\Certificates\Models\TransferCertificateNo;
use App\Modules\Certificates\Models\TransferCertificateSetting;
use App\Modules\Settings\Models\SchSetting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * CI Transfercertificate download + print_transfer_certificate (+ verify lookup helpers).
 * Deferred: mPDF blob download, bulk print.
 */
class DownloadTransferCertificateService
{
    public function __construct(
        protected CurrentSessionResolver $currentSession,
        protected TransferCertificateSettingsService $settingsService,
        protected TransferCertificateDocumentService $documents,
        protected CustomFieldValueService $customFields
    ) {
    }

    /**
     * CI student_model::searchByClassSection for download list (current session).
     *
     * @return Collection<int, object>
     */
    public function searchStudents(int $classId, ?int $sectionId): Collection
    {
        $sessionId = (int) $this->currentSession->id();

        $query = DB::table('students')
            ->join('student_session', function ($join) use ($sessionId) {
                $join->on('student_session.student_id', '=', 'students.id')
                    ->where('student_session.session_id', '=', $sessionId)
                    ->whereRaw("student_session.id = (
                        SELECT MIN(s2.id) FROM student_session s2
                        WHERE s2.student_id = students.id AND s2.session_id = {$sessionId}
                    )");
            })
            ->join('classes', 'student_session.class_id', '=', 'classes.id')
            ->join('sections', 'sections.id', '=', 'student_session.section_id')
            ->leftJoin('categories', 'students.category_id', '=', 'categories.id')
            ->where('students.is_active', 'yes')
            ->where('student_session.class_id', $classId)
            ->select([
                'students.id',
                'students.admission_no',
                'students.firstname',
                'students.middlename',
                'students.lastname',
                'students.dob',
                'students.gender',
                'students.mobileno',
                'student_session.id as student_session_id',
                'student_session.class_id',
                'classes.class',
                'sections.section',
                DB::raw("IFNULL(categories.category, '') as category"),
            ])
            ->orderBy('students.admission_no');

        if ($sectionId) {
            $query->where('student_session.section_id', $sectionId);
        }

        return $query->get();
    }

    /**
     * Load student + session row for print (CI get / getByStudentSession, tightened).
     */
    public function studentForPrint(int $studentId, int $studentSessionId): object
    {
        $row = DB::table('students')
            ->join('student_session', 'student_session.student_id', '=', 'students.id')
            ->join('classes', 'student_session.class_id', '=', 'classes.id')
            ->join('sections', 'sections.id', '=', 'student_session.section_id')
            ->leftJoin('categories', 'students.category_id', '=', 'categories.id')
            ->leftJoin('school_houses', 'school_houses.id', '=', 'students.school_house_id')
            ->where('students.id', $studentId)
            ->where('student_session.id', $studentSessionId)
            ->select([
                'students.id',
                'students.admission_no',
                'students.roll_no',
                'students.admission_date',
                'students.firstname',
                'students.middlename',
                'students.lastname',
                'students.image',
                'students.mobileno',
                'students.email',
                'students.religion',
                'students.cast',
                'students.dob',
                'students.gender',
                'students.current_address',
                'students.permanent_address',
                'students.blood_group',
                'students.bank_account_no',
                'students.bank_name',
                'students.ifsc_code',
                'students.guardian_is',
                'students.father_name',
                'students.father_phone',
                'students.father_occupation',
                'students.mother_name',
                'students.mother_phone',
                'students.mother_occupation',
                'students.guardian_name',
                'students.guardian_relation',
                'students.guardian_phone',
                'students.guardian_occupation',
                'students.guardian_address',
                'students.guardian_email',
                'students.height',
                'students.weight',
                'students.adhar_no',
                'students.samagra_id',
                'students.rte',
                'student_session.id as student_session_id',
                'classes.class',
                'sections.section',
                DB::raw("IFNULL(categories.category, '') as category"),
                DB::raw("IFNULL(school_houses.house_name, '') as house_name"),
            ])
            ->first();

        if (! $row) {
            throw ValidationException::withMessages([
                'student_id' => 'Student session not found for transfer certificate.',
            ]);
        }

        return $row;
    }

    /**
     * Issue next TC number and persist (CI save_tc_details). Always inserts a new row.
     *
     * @return array{tc_no:int,is_regenerte:int,setting:TransferCertificateSetting}
     */
    public function issueCertificate(int $studentSessionId, bool $isRegenerate): array
    {
        $setting = $this->settingsService->settings();
        $tcNo = $this->settingsService->nextTcNumber();

        TransferCertificateNo::query()->create([
            'student_session_id' => $studentSessionId,
            'tc_no' => $tcNo,
            'is_regenerte' => $isRegenerate ? 1 : 0,
            'create_at' => now()->format('Y-m-d H:i:s'),
        ]);

        return [
            'tc_no' => $tcNo,
            'is_regenerte' => $isRegenerate ? 1 : 0,
            'setting' => $setting->fresh(),
        ];
    }

    /**
     * Build printable field rows (CI print_transfer_certificate default fields).
     * Custom fields deferred.
     *
     * @return list<array{label:string,value:string,html?:bool}>
     */
    public function buildFieldRows(object $student, SchSetting $schSetting): array
    {
        $schArray = $schSetting->getAttributes();
        $fields = TransferCertificateField::query()
            ->where('is_active', 1)
            ->where('is_default', 1)
            ->where('status', 1)
            ->orderBy('position')
            ->orderBy('id')
            ->get();

        $rows = [];
        foreach ($fields as $field) {
            $name = (string) $field->name;
            if ($name === 'tc_no') {
                continue;
            }

            // Match settings UI gate: sch_settings key must be on when present;
            // fields without a sch_settings column still print when status=1
            // (CI print view omits the else branch — likely a bug; admission_no/dob/gender need it).
            if (array_key_exists($name, $schArray) && empty($schArray[$name])) {
                continue;
            }

            $value = $this->resolveFieldValue($name, $student);
            $rows[] = [
                'label' => $this->settingsService->fieldLabel($field),
                'value' => $value['text'],
                'html' => $value['html'],
            ];
        }

        return $rows;
    }

    /**
     * @return array{text:string,html:bool}
     */
    protected function resolveFieldValue(string $name, object $student): array
    {
        $text = '';
        $html = false;

        switch ($name) {
            case 'admission_no':
                $text = (string) ($student->admission_no ?? '');
                break;
            case 'roll_no':
                $text = (string) ($student->roll_no ?? '');
                break;
            case 'admission_date':
                $text = $this->formatDate($student->admission_date ?? null);
                break;
            case 'middlename':
                $text = (string) ($student->middlename ?? '');
                break;
            case 'lastname':
                $text = (string) ($student->lastname ?? '');
                break;
            case 'rte':
                $text = (string) ($student->rte ?? '');
                break;
            case 'student_photo':
                $image = (string) ($student->image ?? '');
                if ($image !== '') {
                    $url = str_starts_with($image, 'http') ? $image : asset(ltrim($image, '/'));
                    $text = '<img src="'.e($url).'" height="150" width="150" alt="">';
                    $html = true;
                }
                break;
            case 'mobileno':
                $text = (string) ($student->mobileno ?? '');
                break;
            case 'student_email':
                $text = (string) ($student->email ?? '');
                break;
            case 'religion':
                $text = (string) ($student->religion ?? '');
                break;
            case 'cast':
                $text = (string) ($student->cast ?? '');
                break;
            case 'dob':
                $text = $this->formatDate($student->dob ?? null);
                break;
            case 'gender':
                $text = (string) ($student->gender ?? '');
                break;
            case 'current_address':
                $text = (string) ($student->current_address ?? '');
                break;
            case 'permanent_address':
                $text = (string) ($student->permanent_address ?? '');
                break;
            case 'category':
                $text = (string) ($student->category ?? '');
                break;
            case 'is_blood_group':
                $text = (string) ($student->blood_group ?? '');
                break;
            case 'bank_account_no':
                $text = (string) ($student->bank_account_no ?? '');
                break;
            case 'bank_name':
                $text = (string) ($student->bank_name ?? '');
                break;
            case 'ifsc_code':
                $text = (string) ($student->ifsc_code ?? '');
                break;
            case 'guardian_is':
            case 'if_guardian_is':
                $text = (string) ($student->guardian_is ?? '');
                break;
            case 'father_name':
                $text = (string) ($student->father_name ?? '');
                break;
            case 'father_phone':
                $text = (string) ($student->father_phone ?? '');
                break;
            case 'father_occupation':
                $text = (string) ($student->father_occupation ?? '');
                break;
            case 'mother_name':
                $text = (string) ($student->mother_name ?? '');
                break;
            case 'mother_phone':
                $text = (string) ($student->mother_phone ?? '');
                break;
            case 'mother_occupation':
                $text = (string) ($student->mother_occupation ?? '');
                break;
            case 'guardian_name':
                $text = (string) ($student->guardian_name ?? '');
                break;
            case 'guardian_relation':
                $text = (string) ($student->guardian_relation ?? '');
                break;
            case 'guardian_phone':
                $text = (string) ($student->guardian_phone ?? '');
                break;
            case 'guardian_occupation':
                $text = (string) ($student->guardian_occupation ?? '');
                break;
            case 'guardian_address':
                $text = (string) ($student->guardian_address ?? '');
                break;
            case 'guardian_email':
                $text = (string) ($student->guardian_email ?? '');
                break;
            case 'student_height':
                $text = (string) ($student->height ?? '');
                break;
            case 'student_weight':
                $text = (string) ($student->weight ?? '');
                break;
            case 'national_identification_no':
                $text = (string) ($student->adhar_no ?? '');
                break;
            case 'local_identification_no':
                $text = (string) ($student->samagra_id ?? '');
                break;
            case 'is_student_house':
                $text = (string) ($student->house_name ?? '');
                break;
            case 'firstname':
                $text = trim(implode(' ', array_filter([
                    trim((string) ($student->firstname ?? '')),
                    trim((string) ($student->middlename ?? '')),
                    trim((string) ($student->lastname ?? '')),
                ])));
                break;
            default:
                $text = '';
        }

        return ['text' => $text, 'html' => $html];
    }

    public function studentFullName(object $student, SchSetting $schSetting): string
    {
        $parts = [trim((string) ($student->firstname ?? ''))];
        if (! empty($schSetting->middlename) && trim((string) ($student->middlename ?? '')) !== '') {
            $parts[] = trim((string) $student->middlename);
        }
        if (! empty($schSetting->lastname) && trim((string) ($student->lastname ?? '')) !== '') {
            $parts[] = trim((string) $student->lastname);
        }

        return trim(implode(' ', array_filter($parts)));
    }

    public function showTcNumberHeader(): bool
    {
        $status = TransferCertificateField::query()
            ->where('name', 'tc_no')
            ->where('is_active', 1)
            ->where('is_default', 1)
            ->value('status');

        return (int) $status === 1;
    }

    /**
     * @return array<string, mixed>
     */
    public function buildPrintPayload(int $studentId, int $studentSessionId, bool $isRegenerate): array
    {
        $student = $this->studentForPrint($studentId, $studentSessionId);
        $issued = $this->issueCertificate($studentSessionId, $isRegenerate);
        $setting = $issued['setting'];
        $schSetting = SchSetting::query()->orderBy('id')->firstOrFail();

        return [
            'student' => $student,
            'studentName' => $this->studentFullName($student, $schSetting),
            'tcNo' => $issued['tc_no'],
            'isRegenerate' => $issued['is_regenerte'] === 1,
            'showTcNo' => $this->showTcNumberHeader(),
            'affiliationNo' => (string) ($setting->affiliation_no ?? ''),
            'fieldRows' => array_merge(
                $this->buildFieldRows($student, $schSetting),
                $this->customFieldRows((int) $student->id)
            ),
            'footerContent' => (string) ($setting->footer_content ?? ''),
            'headerUrl' => $this->documents->url($setting->header_image ?? null),
            'classTeacherSignatureUrl' => $this->documents->url($setting->class_teacher_signature ?? null),
            'checkedByUrl' => $this->documents->url($setting->checked_by ?? null),
            'principalSignatureUrl' => $this->documents->url($setting->signature_of_principle ?? null),
        ];
    }

    /**
     * CI check_is_tc_exist — latest row for a TC serial (if duplicates).
     */
    public function findIssuedByTcNo(int|string $tcNo): ?TransferCertificateNo
    {
        return TransferCertificateNo::query()
            ->where('tc_no', $tcNo)
            ->orderByDesc('id')
            ->first();
    }

    /**
     * CI verify_tc preview — uses stored TC no; does not issue a new serial.
     *
     * @return array<string, mixed>|null  null when TC or student session missing
     */
    public function buildVerifyPayload(int|string $tcNo): ?array
    {
        $issued = $this->findIssuedByTcNo($tcNo);
        if (! $issued || empty($issued->student_session_id)) {
            return null;
        }

        $studentSessionId = (int) $issued->student_session_id;
        $studentId = (int) (DB::table('student_session')->where('id', $studentSessionId)->value('student_id') ?? 0);
        if ($studentId <= 0) {
            return null;
        }

        try {
            $student = $this->studentForPrint($studentId, $studentSessionId);
        } catch (ValidationException) {
            return null;
        }

        $setting = $this->settingsService->settings();
        $schSetting = SchSetting::query()->orderBy('id')->firstOrFail();

        return [
            'student' => $student,
            'studentName' => $this->studentFullName($student, $schSetting),
            'tcNo' => (int) $issued->tc_no,
            'isRegenerate' => (int) $issued->is_regenerte === 1,
            'showTcNo' => $this->showTcNumberHeader(),
            'affiliationNo' => (string) ($setting->affiliation_no ?? ''),
            'fieldRows' => array_merge(
                $this->buildFieldRows($student, $schSetting),
                $this->customFieldRows((int) $student->id)
            ),
            'footerContent' => (string) ($setting->footer_content ?? ''),
            'headerUrl' => $this->documents->url($setting->header_image ?? null),
            'classTeacherSignatureUrl' => $this->documents->url($setting->class_teacher_signature ?? null),
            'checkedByUrl' => $this->documents->url($setting->checked_by ?? null),
            'principalSignatureUrl' => $this->documents->url($setting->signature_of_principle ?? null),
        ];
    }

    /**
     * CI get_custom_table_values(..., 'transfer_certificate') when TC field status is on.
     *
     * @return list<array{label:string,value:string,html:bool}>
     */
    public function customFieldRows(int $studentId): array
    {
        $fields = $this->customFields->fieldsFor('transfer_certificate');
        if ($fields->isEmpty()) {
            return [];
        }

        $values = $this->customFields->valuesMap('transfer_certificate', $studentId);
        $enabledNames = TransferCertificateField::query()
            ->where('is_active', 1)
            ->where('status', 1)
            ->pluck('name')
            ->map(fn ($n) => (string) $n)
            ->all();

        $rows = [];
        foreach ($fields as $field) {
            $name = (string) $field->name;
            if (! in_array($name, $enabledNames, true)) {
                continue;
            }

            $raw = (string) ($values[$field->id] ?? '');
            $html = false;
            $text = $raw;

            if ($raw !== '' && $this->looksLikeJsonList($raw)) {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    $items = array_map(fn ($v) => '<li>'.e((string) $v).'</li>', $decoded);
                    $text = '<ul class="student_custom_field">'.implode('', $items).'</ul>';
                    $html = true;
                }
            } elseif ((string) $field->type === 'link' && $raw !== '') {
                $text = '<a href="'.e($raw).'" target="_blank" rel="noopener">'.e($raw).'</a>';
                $html = true;
            }

            $rows[] = [
                'label' => $name,
                'value' => $text,
                'html' => $html,
            ];
        }

        return $rows;
    }

    protected function looksLikeJsonList(string $raw): bool
    {
        $trim = ltrim($raw);
        if ($trim === '' || ($trim[0] !== '[' && $trim[0] !== '{')) {
            return false;
        }

        $decoded = json_decode($raw, true);

        return json_last_error() === JSON_ERROR_NONE && is_array($decoded);
    }

    protected function formatDate(mixed $value): string
    {
        if ($value === null || $value === '' || $value === '0000-00-00') {
            return '';
        }

        try {
            return \Carbon\Carbon::parse((string) $value)->format('d-m-Y');
        } catch (\Throwable) {
            return (string) $value;
        }
    }
}
