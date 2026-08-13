<?php

namespace App\Modules\Certificates\Services;

use App\Modules\Academics\Services\CurrentSessionResolver;
use App\Modules\Certificates\Models\IdCard;
use App\Modules\Settings\Models\SchSetting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * CI admin/Generateidcard — search students & build ID card print payload.
 * Deferred: AJAX JSON print, mPDF, single-student legacy generate(), SaaS quota.
 */
class GenerateStudentIdCardService
{
    public function __construct(
        protected CurrentSessionResolver $currentSession,
        protected StudentIdCardDocumentService $documents,
        protected StudentIdCardScanCodeService $scanCodes
    ) {
    }

    /**
     * @return Collection<int, IdCard>
     */
    public function listTemplates(): Collection
    {
        return IdCard::query()->orderBy('id')->get();
    }

    public function findTemplate(int $id): IdCard
    {
        return IdCard::query()->findOrFail($id);
    }

    /**
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
            ->leftJoin('school_houses', 'school_houses.id', '=', 'students.school_house_id')
            ->whereIn('students.id', $studentIds)
            ->select([
                'students.id',
                'students.admission_no',
                'students.roll_no',
                'students.firstname',
                'students.middlename',
                'students.lastname',
                'students.image',
                'students.mobileno',
                'students.dob',
                'students.current_address',
                'students.blood_group',
                'students.father_name',
                'students.mother_name',
                'students.gender',
                'classes.class',
                'sections.section',
                DB::raw("IFNULL(school_houses.house_name, '') as house_name"),
            ])
            ->orderBy('students.id')
            ->get();
    }

    /**
     * @param  list<int>  $studentIds
     * @return array{
     *     idcard: IdCard,
     *     backgroundUrl: ?string,
     *     logoUrl: ?string,
     *     signUrl: ?string,
     *     sessionName: string,
     *     dateFormat: string,
     *     useMiddle: bool,
     *     useLast: bool,
     *     rows: list<array{student: object, fullName: string, photoUrl: string, scanUrl: ?string, dobFormatted: string}>
     * }
     */
    public function buildPrintPayload(IdCard $idcard, array $studentIds): array
    {
        $settings = SchSetting::query()->first();
        $dateFormat = (string) ($settings->date_format ?? 'm/d/Y');
        $useMiddle = (int) ($settings->middlename ?? 1) === 1;
        $useLast = (int) ($settings->lastname ?? 1) === 1;
        $scanType = (string) ($settings->scan_code_type ?? 'barcode');
        $sessionName = (string) (DB::table('sessions')->where('id', $this->currentSession->id())->value('session') ?? '');

        $students = $this->studentsForPrint($studentIds);
        $rows = [];

        foreach ($students as $student) {
            $scanRelative = null;
            if ((int) $idcard->enable_student_barcode === 1) {
                $scanRelative = $this->scanCodes->generate(
                    (string) ($student->admission_no ?? ''),
                    (int) $student->id,
                    $scanType
                );
            }

            $rows[] = [
                'student' => $student,
                'fullName' => $this->fullName(
                    (string) ($student->firstname ?? ''),
                    (string) ($student->middlename ?? ''),
                    (string) ($student->lastname ?? ''),
                    $useMiddle,
                    $useLast
                ),
                'photoUrl' => $this->photoUrl($student->image ?? null, (string) ($student->gender ?? '')),
                'scanUrl' => $this->scanCodes->url($scanRelative),
                'dobFormatted' => $this->formatDate((string) ($student->dob ?? ''), $dateFormat),
            ];
        }

        return [
            'idcard' => $idcard,
            'backgroundUrl' => $this->documents->url($idcard->background, StudentIdCardDocumentService::FOLDER_BACKGROUND),
            'logoUrl' => $this->documents->url($idcard->logo, StudentIdCardDocumentService::FOLDER_LOGO),
            'signUrl' => $this->documents->url($idcard->sign_image, StudentIdCardDocumentService::FOLDER_SIGNATURE),
            'sessionName' => $sessionName,
            'dateFormat' => $dateFormat,
            'useMiddle' => $useMiddle,
            'useLast' => $useLast,
            'rows' => $rows,
        ];
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

    protected function formatDate(string $value, string $dateFormat): string
    {
        if ($value === '' || $value === '0000-00-00') {
            return '';
        }

        $timestamp = strtotime(substr($value, 0, 10));
        if ($timestamp === false) {
            return $value;
        }

        return date($dateFormat, $timestamp);
    }

    protected function photoUrl(mixed $image, string $gender): string
    {
        $path = trim((string) $image);
        if ($path !== '') {
            if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
                return $path;
            }

            return asset(ltrim($path, '/'));
        }

        $default = strtolower($gender) === 'female'
            ? 'uploads/student_images/default_female.jpg'
            : 'uploads/student_images/default_male.jpg';

        return asset($default);
    }
}
