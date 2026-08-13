<?php

namespace App\Modules\Certificates\Services;

use App\Modules\Academics\Services\CurrentSessionResolver;
use App\Modules\Academics\Services\CustomFieldValueService;
use App\Modules\Settings\Models\SchSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * CI Transfercertificate prepare_tc + edit_custom_field / save_custom_fields.
 * Deferred: mPDF.
 */
class PrepareTransferCertificateService
{
    public const BELONG_TO = 'transfer_certificate';

    public function __construct(
        protected CurrentSessionResolver $currentSession,
        protected CustomFieldValueService $customFields,
        protected DownloadTransferCertificateService $download
    ) {
    }

    /**
     * Student profile for prepare/edit sidebar (current session when possible).
     */
    public function studentProfile(int $studentId): object
    {
        $sessionId = (int) $this->currentSession->id();

        $row = DB::table('students')
            ->leftJoin('student_session', function ($join) use ($sessionId) {
                $join->on('student_session.student_id', '=', 'students.id')
                    ->where('student_session.session_id', '=', $sessionId)
                    ->whereRaw("student_session.id = (
                        SELECT MIN(s2.id) FROM student_session s2
                        WHERE s2.student_id = students.id AND s2.session_id = {$sessionId}
                    )");
            })
            ->leftJoin('classes', 'student_session.class_id', '=', 'classes.id')
            ->leftJoin('sections', 'sections.id', '=', 'student_session.section_id')
            ->leftJoin('categories', 'students.category_id', '=', 'categories.id')
            ->leftJoin('sessions', 'sessions.id', '=', 'student_session.session_id')
            ->where('students.id', $studentId)
            ->select([
                'students.id',
                'students.admission_no',
                'students.roll_no',
                'students.firstname',
                'students.middlename',
                'students.lastname',
                'students.image',
                'students.gender',
                'students.dob',
                'students.mobileno',
                'students.email',
                'students.religion',
                'students.cast',
                'students.blood_group',
                'students.height',
                'students.weight',
                'students.father_name',
                'students.mother_name',
                'students.guardian_name',
                'students.current_address',
                'students.permanent_address',
                'students.is_active',
                'classes.class',
                'sections.section',
                'sessions.session',
                DB::raw("IFNULL(categories.category, '') as category"),
            ])
            ->first();

        if (! $row) {
            throw ValidationException::withMessages([
                'student_id' => 'Student not found.',
            ]);
        }

        return $row;
    }

    public function studentDisplayName(object $student): string
    {
        $sch = SchSetting::query()->orderBy('id')->first();
        if ($sch) {
            return $this->download->studentFullName($student, $sch);
        }

        return trim(implode(' ', array_filter([
            trim((string) ($student->firstname ?? '')),
            trim((string) ($student->middlename ?? '')),
            trim((string) ($student->lastname ?? '')),
        ])));
    }

    /**
     * @return array{fields:\Illuminate\Support\Collection,values:array<int,string>}
     */
    public function customFieldFormData(int $studentId): array
    {
        return [
            'fields' => $this->customFields->fieldsFor(self::BELONG_TO),
            'values' => $this->customFields->valuesMap(self::BELONG_TO, $studentId),
        ];
    }

    /**
     * @param  array<string|int, mixed>  $posted
     * @return array<string, string>
     */
    public function validateCustomFields(array $posted): array
    {
        return $this->customFields->validateRequired(self::BELONG_TO, $posted);
    }

    /**
     * @param  array<string|int, mixed>  $posted
     */
    public function saveCustomFields(int $studentId, array $posted): void
    {
        $rows = $this->customFields->normalizePosted(self::BELONG_TO, $posted);
        $this->customFields->upsertFor($studentId, $rows);
    }
}
