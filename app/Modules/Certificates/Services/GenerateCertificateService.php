<?php

namespace App\Modules\Certificates\Services;

use App\Modules\Academics\Services\CurrentSessionResolver;
use App\Modules\Certificates\Models\Certificate;
use App\Modules\Settings\Models\SchSetting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * CI admin/Generatecertificate — search students & merge print tokens.
 * Deferred: mPDF, custom-field placeholders, single-student legacy TC view.
 */
class GenerateCertificateService
{
    public function __construct(
        protected CurrentSessionResolver $currentSession,
        protected CertificateDocumentService $documents
    ) {
    }

    /**
     * CI Certificate_model::getstudentcertificate (created_for=2, no status filter).
     *
     * @return Collection<int, Certificate>
     */
    public function listCertificates(): Collection
    {
        return Certificate::query()
            ->where('created_for', CertificateTemplateService::CREATED_FOR_STUDENT)
            ->orderBy('id')
            ->get();
    }

    public function findCertificate(int $id): Certificate
    {
        return Certificate::query()
            ->where('created_for', CertificateTemplateService::CREATED_FOR_STUDENT)
            ->findOrFail($id);
    }

    /**
     * Search roster for generate UI (current session, active students).
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
                'students.father_name',
                'students.dob',
                'students.gender',
                'students.mobileno',
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
     * CI Student_model::getStudentsByArray — fields needed for token merge.
     *
     * @param  list<int>  $studentIds
     * @return Collection<int, object>
     */
    public function studentsForPrint(array $studentIds): Collection
    {
        $studentIds = array_values(array_unique(array_map('intval', $studentIds)));
        if ($studentIds === []) {
            return collect();
        }

        $sessionId = (int) $this->currentSession->id();

        return DB::table('students')
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
            ->whereIn('students.id', $studentIds)
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
                'students.state',
                'students.city',
                'students.pincode',
                'students.religion',
                'students.dob',
                'students.current_address',
                'students.permanent_address',
                'students.blood_group',
                'students.adhar_no',
                'students.samagra_id',
                'students.bank_account_no',
                'students.bank_name',
                'students.ifsc_code',
                'students.cast',
                'students.guardian_name',
                'students.guardian_relation',
                'students.guardian_phone',
                'students.guardian_address',
                'students.father_name',
                'students.mother_name',
                'students.rte',
                'students.gender',
                'students.created_at',
                'classes.class',
                'sections.section',
                DB::raw("IFNULL(categories.category, '') as category"),
            ])
            ->orderBy('students.id')
            ->get();
    }

    /**
     * Build print rows: merged certificate body + photo URL per student.
     *
     * @param  list<int>  $studentIds
     * @return array{certificate: Certificate, backgroundUrl: ?string, rows: list<array{body: string, photoUrl: ?string, student: object}>}
     */
    public function buildPrintPayload(Certificate $certificate, array $studentIds): array
    {
        $settings = SchSetting::query()->first();
        $dateFormat = (string) ($settings->date_format ?? 'm/d/Y');
        $useMiddle = (int) ($settings->middlename ?? 1) === 1;
        $useLast = (int) ($settings->lastname ?? 1) === 1;

        $templateText = $this->normalizeAliases((string) $certificate->certificate_text, $dateFormat);
        $students = $this->studentsForPrint($studentIds);

        $rows = [];
        foreach ($students as $student) {
            $tokens = (array) $student;
            $tokens['name'] = $this->fullName(
                (string) ($student->firstname ?? ''),
                (string) ($student->middlename ?? ''),
                (string) ($student->lastname ?? ''),
                $useMiddle,
                $useLast
            );

            $rows[] = [
                'student' => $student,
                'body' => $this->mergeTokens($templateText, $tokens, $dateFormat),
                'photoUrl' => $this->studentPhotoUrl($student->image ?? null),
            ];
        }

        return [
            'certificate' => $certificate,
            'backgroundUrl' => $this->documents->url($certificate->background_image),
            'rows' => $rows,
        ];
    }

    protected function normalizeAliases(string $text, string $dateFormat): string
    {
        $text = str_replace('[present_date]', date($dateFormat), $text);
        $text = str_replace('[present_address]', '[current_address]', $text);
        $text = str_replace('[guardian]', '[guardian_name]', $text);
        $text = str_replace('[phone]', '[mobileno]', $text);

        return $text;
    }

    /**
     * @param  array<string, mixed>  $tokens
     */
    protected function mergeTokens(string $template, array $tokens, string $dateFormat): string
    {
        $dateKeys = ['dob', 'admission_date', 'created_at'];
        $body = $template;

        foreach ($tokens as $key => $value) {
            if (! is_scalar($value) && $value !== null) {
                continue;
            }

            $stringValue = $value === null ? '' : (string) $value;
            if (in_array($key, $dateKeys, true)) {
                $stringValue = $this->formatDate($stringValue, $dateFormat);
            }

            if ($stringValue === '' || $stringValue === '0000-00-00') {
                continue;
            }

            $body = str_replace('['.$key.']', $stringValue, $body);
        }

        return $body;
    }

    protected function formatDate(string $value, string $dateFormat): string
    {
        if ($value === '' || $value === '0000-00-00') {
            return '';
        }

        $timestamp = strtotime(substr($value, 0, 19));
        if ($timestamp === false) {
            return $value;
        }

        return date($dateFormat, $timestamp);
    }

    protected function fullName(string $first, string $middle, string $last, bool $useMiddle, bool $useLast): string
    {
        $parts = [$first];
        if ($useMiddle && $middle !== '') {
            $parts[] = $middle;
        }
        if ($useLast && $last !== '') {
            $parts[] = $last;
        }

        return trim(implode(' ', array_filter($parts, fn ($p) => $p !== '')));
    }

    protected function studentPhotoUrl(mixed $image): ?string
    {
        $path = trim((string) $image);
        if ($path === '') {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return asset(ltrim($path, '/'));
    }
}
