<?php

namespace App\Modules\Reports\Services;

use App\Modules\Academics\Services\CurrentSessionResolver;
use App\Modules\Shared\Services\SchoolContext;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * CI Report student information: studentreport, classsectionreport, ratios, guardian, history, login credentials.
 */
class StudentInformationReportService
{
    public function __construct(
        protected CurrentSessionResolver $currentSession,
        protected SchoolContext $school,
    ) {
    }

    public function settingOn(string $key): bool
    {
        return (int) $this->school->get($key, 1) === 1;
    }

    public function genders(): array
    {
        return [
            'Male' => (string) __('system.male'),
            'Female' => (string) __('system.female'),
        ];
    }

    public function rteStatuses(): array
    {
        return [
            'Yes' => (string) __('system.yes'),
            'No' => (string) __('system.no'),
        ];
    }

    /**
     * @return Collection<int, object>
     */
    public function categories(): Collection
    {
        return DB::table('categories')->orderBy('id')->get();
    }

    /**
     * @return Collection<int, object>
     */
    public function classes(): Collection
    {
        return DB::table('classes')->orderBy('class')->get();
    }

    /**
     * CI Classsection_model::getClassSectionStudentCount (superadmin / non-class-teacher path).
     *
     * @return Collection<int, object>
     */
    public function classSectionCounts(): Collection
    {
        $sessionId = (int) $this->currentSession->id();

        return DB::table('class_sections')
            ->join('classes', 'classes.id', '=', 'class_sections.class_id')
            ->join('sections', 'sections.id', '=', 'class_sections.section_id')
            ->select([
                'class_sections.id',
                'class_sections.class_id',
                'class_sections.section_id',
                'classes.class',
                'sections.section',
                DB::raw("(SELECT COUNT(*) FROM student_session INNER JOIN students ON students.id = student_session.student_id WHERE student_session.class_id = classes.id AND student_session.section_id = sections.id AND students.is_active = 'yes' AND student_session.session_id = ".$sessionId.') as student_count'),
            ])
            ->orderBy('classes.class')
            ->orderBy('sections.section')
            ->get();
    }

    /**
     * CI Student_model::student_ratio (without unused custom-field joins).
     *
     * @return Collection<int, object>
     */
    public function studentRatio(): Collection
    {
        $sessionId = (int) $this->currentSession->id();

        return DB::table('students')
            ->join('student_session', 'student_session.student_id', '=', 'students.id')
            ->join('classes', 'student_session.class_id', '=', 'classes.id')
            ->join('sections', 'sections.id', '=', 'student_session.section_id')
            ->join('class_sections', function ($join) {
                $join->on('class_sections.class_id', '=', 'classes.id')
                    ->on('class_sections.section_id', '=', 'sections.id');
            })
            ->where('student_session.session_id', $sessionId)
            ->where('students.is_active', 'yes')
            ->groupBy('class_sections.id', 'classes.id', 'classes.class', 'sections.id', 'sections.section')
            ->orderBy('classes.class')
            ->orderBy('sections.section')
            ->selectRaw('COUNT(*) as total_student, SUM(CASE WHEN students.gender = "Male" THEN 1 ELSE 0 END) AS `male`, SUM(CASE WHEN students.gender = "Female" THEN 1 ELSE 0 END) AS `female`, classes.class, sections.section, classes.id as class_id, sections.id as section_id')
            ->get();
    }

    /**
     * CI Report::getRatio — 1:n style, not reduced fractions.
     */
    public function getRatio(mixed $num1, mixed $num2): string
    {
        if ($num2 > 0 && $num1 > 0) {
            $num = round($num2 / $num1, 2);
        } else {
            $num = 0;
        }

        if ($num1 == '0') {
            return '0:'.$num2;
        }

        return '1:'.$num;
    }

    /**
     * CI Student_model::count_classteachers — distinct active staff on subject_timetable.
     */
    public function countClassTeachers(int $classId, int $sectionId): int
    {
        $sessionId = (int) $this->currentSession->id();
        $ids = DB::table('subject_timetable')
            ->join('subject_group_subjects', 'subject_timetable.subject_group_subject_id', '=', 'subject_group_subjects.id')
            ->join('subjects', 'subject_group_subjects.subject_id', '=', 'subjects.id')
            ->join('staff', 'staff.id', '=', 'subject_timetable.staff_id')
            ->where('staff.is_active', '1')
            ->where('subject_timetable.class_id', $classId)
            ->where('subject_timetable.section_id', $sectionId)
            ->where('subject_timetable.session_id', $sessionId)
            ->pluck('staff.id');

        return $ids->unique()->count();
    }

    /**
     * @return array{rows: list<array<string, mixed>>, total_boys: int, total_girls: int, total_students: int, all_ratio: string}
     */
    public function genderRatioReport(): array
    {
        $rows = [];
        $totalBoys = 0;
        $totalGirls = 0;
        foreach ($this->studentRatio() as $value) {
            $male = (int) $value->male;
            $female = (int) $value->female;
            $totalBoys += $male;
            $totalGirls += $female;
            $rows[] = [
                'class' => $value->class,
                'section' => $value->section,
                'class_id' => (int) $value->class_id,
                'section_id' => (int) $value->section_id,
                'male' => $male,
                'female' => $female,
                'total_student' => (int) $value->total_student,
                'boys_girls_ratio' => $this->getRatio($male, $female),
            ];
        }

        return [
            'rows' => $rows,
            'total_boys' => $totalBoys,
            'total_girls' => $totalGirls,
            'total_students' => $totalBoys + $totalGirls,
            'all_ratio' => $this->getRatio($totalBoys, $totalGirls),
        ];
    }

    /**
     * @return array{rows: list<array<string, mixed>>, total_students: int, all_teacher: int, all_ratio: string}
     */
    public function teacherRatioReport(): array
    {
        $rows = [];
        $allStudent = 0;
        $allTeacher = 0;
        foreach ($this->studentRatio() as $value) {
            $teachers = $this->countClassTeachers((int) $value->class_id, (int) $value->section_id);
            $total = (int) $value->total_student;
            $allStudent += $total;
            $allTeacher += $teachers;
            $rows[] = [
                'class' => $value->class,
                'section' => $value->section,
                'class_id' => (int) $value->class_id,
                'section_id' => (int) $value->section_id,
                'total_student' => $total,
                'total_teacher' => $teachers,
                'teacher_ratio' => $this->getRatio($total, $teachers),
            ];
        }

        return [
            'rows' => $rows,
            'total_students' => $allStudent,
            'all_teacher' => $allTeacher,
            'all_ratio' => $this->getRatio($allStudent, $allTeacher),
        ];
    }

    /**
     * CI Student_model::searchdatatableByClassSectionCategoryGenderRte.
     *
     * @return Collection<int, object>
     */
    public function studentReportRows(?int $classId, ?int $sectionId, ?int $categoryId, ?string $gender, ?string $rte): Collection
    {
        $sessionId = (int) $this->currentSession->id();
        $query = DB::table('students')
            ->join('student_session', 'student_session.student_id', '=', 'students.id')
            ->join('classes', 'student_session.class_id', '=', 'classes.id')
            ->join('sections', 'sections.id', '=', 'student_session.section_id')
            ->leftJoin('categories', 'students.category_id', '=', 'categories.id')
            ->where('student_session.session_id', $sessionId)
            ->where('students.is_active', 'yes')
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
                'students.samagra_id',
                'students.adhar_no',
                'students.rte',
                'sections.section',
                'categories.category',
            ])
            ->orderBy('students.id');

        if ($classId) {
            $query->where('student_session.class_id', $classId);
        }
        if ($sectionId) {
            $query->where('student_session.section_id', $sectionId);
        }
        if ($categoryId) {
            $query->where('students.category_id', $categoryId);
        }
        if ($gender !== null && $gender !== '') {
            $query->where('students.gender', $gender);
        }
        if ($rte !== null && $rte !== '') {
            $query->where('students.rte', $rte);
        }

        return $query->get();
    }

    /**
     * @return array{draw: int, recordsTotal: int, recordsFiltered: int, data: list<list<string>>}
     */
    public function studentReportDataTable(Request $request): array
    {
        $classId = $this->nullableInt($request->input('class_id'));
        $sectionId = $this->nullableInt($request->input('section_id'));
        $categoryId = $this->nullableInt($request->input('category_id'));
        $gender = trim((string) $request->input('gender', ''));
        $rte = trim((string) $request->input('rte', ''));
        $rows = $this->studentReportRows($classId, $sectionId, $categoryId, $gender !== '' ? $gender : null, $rte !== '' ? $rte : null);
        $data = [];
        foreach ($rows as $student) {
            $data[] = $this->studentReportCells($student);
        }

        $draw = (int) $request->input('draw', 1);

        return [
            'draw' => $draw,
            'recordsTotal' => count($data),
            'recordsFiltered' => count($data),
            'data' => $data,
        ];
    }

    /**
     * @return list<string>
     */
    public function studentReportCells(object $student): array
    {
        $name = $this->fullName($student);
        $row = [
            (string) $student->section,
            (string) $student->admission_no,
            "<a href='".url('student/view/'.$student->id)."'>".e($name).'</a>',
        ];
        if ($this->settingOn('father_name')) {
            $row[] = (string) ($student->father_name ?? '');
        }
        $row[] = $this->formatDate($student->dob ?? null);
        $gender = (string) ($student->gender ?? '');
        $row[] = $gender !== '' ? (string) __('system.'.strtolower($gender)) : '';
        if ($this->settingOn('category')) {
            $row[] = (string) ($student->category ?? '');
        }
        if ($this->settingOn('mobile_no')) {
            $row[] = (string) ($student->mobileno ?? '');
        }
        if ($this->settingOn('local_identification_no')) {
            $row[] = (string) ($student->adhar_no ?? '');
        }
        if ($this->settingOn('national_identification_no')) {
            $row[] = (string) ($student->samagra_id ?? '');
        }
        if ($this->settingOn('rte')) {
            $row[] = (string) ($student->rte ?? '');
        }

        return $row;
    }

    public function fullName(object $student): string
    {
        $first = trim((string) ($student->firstname ?? ''));
        $middle = trim((string) ($student->middlename ?? ''));
        $last = trim((string) ($student->lastname ?? ''));
        $name = $this->settingOn('middlename')
            ? ($middle === '' ? $first : $first.' '.$middle)
            : $first;
        if ($this->settingOn('lastname') && $last !== '') {
            $name .= ' '.$last;
        }

        return $name;
    }

    public function formatDate(mixed $value): string
    {
        if ($value === null || $value === '' || $value === '0000-00-00') {
            return '';
        }

        return Carbon::parse((string) $value)->format($this->school->dateFormat() ?: 'd/m/Y');
    }

    /**
     * CI Student_model::searchGuardianDetails.
     *
     * @return Collection<int, object>
     */
    public function guardianRows(int $classId, int $sectionId): Collection
    {
        $sessionId = (int) $this->currentSession->id();

        return DB::table('students')
            ->join('student_session', 'student_session.student_id', '=', 'students.id')
            ->join('classes', 'student_session.class_id', '=', 'classes.id')
            ->join('sections', 'student_session.section_id', '=', 'sections.id')
            ->where('students.is_active', 'yes')
            ->where('student_session.session_id', $sessionId)
            ->where('student_session.class_id', $classId)
            ->where('student_session.section_id', $sectionId)
            ->select([
                'students.id',
                'students.admission_no',
                'students.firstname',
                'students.middlename',
                'students.lastname',
                'students.mobileno',
                'students.father_phone',
                'students.mother_phone',
                'students.father_name',
                'students.mother_name',
                'students.guardian_name',
                'students.guardian_relation',
                'students.guardian_phone',
                'classes.class',
                'sections.section',
            ])
            ->orderBy('students.id')
            ->get();
    }

    /**
     * CI Student_model::admissionYear.
     *
     * @return Collection<int, object>
     */
    public function admissionYears(): Collection
    {
        return DB::table('students')
            ->selectRaw('DISTINCT YEAR(admission_date) as year')
            ->whereNotIn('admission_date', ['0000-00-00', '1970-01-01'])
            ->whereNotNull('admission_date')
            ->orderBy('year')
            ->get();
    }

    /**
     * CI Student_model::searchdatatablebyAdmissionDetails + studentSessionDetails.
     *
     * @return Collection<int, object>
     */
    public function historyRows(int $classId, ?int $year): Collection
    {
        $query = DB::table('students')
            ->join('student_session', 'students.id', '=', 'student_session.student_id')
            ->join('classes', 'student_session.class_id', '=', 'classes.id')
            ->join('sections', 'student_session.section_id', '=', 'sections.id')
            ->join('sessions', 'student_session.session_id', '=', 'sessions.id')
            ->where('student_session.class_id', $classId)
            ->select([
                'students.firstname',
                'students.middlename',
                'students.lastname',
                'students.is_active',
                'students.mobileno',
                'students.id as sid',
                'students.admission_no',
                'students.admission_date',
                'students.guardian_name',
                'students.guardian_relation',
                'students.guardian_phone',
                'classes.class',
                'sessions.id',
                'sections.section',
            ])
            ->orderBy('students.id');

        if ($year !== null) {
            $query->whereRaw('YEAR(students.admission_date) = ?', [$year]);
        }

        // CI group_by students.id. Unique in PHP so older MySQL/MariaDB without ANY_VALUE still returns one row per student.
        return $query->get()->unique('sid')->values();
    }

    /**
     * CI Student_model::studentSessionDetails.
     *
     * @return array{start: string, end: string, startclass: string, endclass: string}
     */
    public function studentSessionDetails(int $studentId): array
    {
        $row = DB::table('sessions')
            ->join('student_session', 'sessions.id', '=', 'student_session.session_id')
            ->join('classes', 'classes.id', '=', 'student_session.class_id')
            ->where('student_session.student_id', $studentId)
            ->selectRaw('MIN(sessions.session) as start, MAX(sessions.session) as end, MIN(classes.class) as startclass, MAX(classes.class) as endclass')
            ->first();

        return [
            'start' => (string) ($row->start ?? ''),
            'end' => (string) ($row->end ?? ''),
            'startclass' => (string) ($row->startclass ?? ''),
            'endclass' => (string) ($row->endclass ?? ''),
        ];
    }

    /**
     * @return list<string>
     */
    public function historyCells(object $student): array
    {
        $id = (int) ($student->sid ?? $student->id ?? 0);
        $sessions = $this->studentSessionDetails($id);
        $startYear = (int) explode('-', $sessions['start'])[0];
        $endYear = (int) explode('-', $sessions['end'])[0];
        $row = [
            (string) $student->admission_no,
            "<a href='".url('student/view/'.$id)."'>".e($this->fullName($student)).'</a>',
            $this->formatDate($student->admission_date ?? null),
            $sessions['startclass'].'  -  '.$sessions['endclass'],
            $sessions['start'].'  -  '.$sessions['end'],
            (string) (($endYear - $startYear) + 1),
        ];
        if ($this->settingOn('mobile_no')) {
            $row[] = (string) ($student->mobileno ?? '');
        }
        if ($this->settingOn('guardian_name')) {
            $row[] = (string) ($student->guardian_name ?? '');
        }
        if ($this->settingOn('guardian_phone')) {
            $row[] = (string) ($student->guardian_phone ?? '');
        }

        return $row;
    }

    /**
     * @return array{draw: int, recordsTotal: int, recordsFiltered: int, data: list<list<string>>}
     */
    public function historyDataTable(Request $request): array
    {
        $classId = (int) $request->input('class_id');
        $yearRaw = $request->input('year');
        $year = ($yearRaw === null || $yearRaw === '') ? null : (int) $yearRaw;
        $rows = $classId > 0 ? $this->historyRows($classId, $year) : collect();
        $data = [];
        foreach ($rows as $student) {
            $data[] = $this->historyCells($student);
        }

        return [
            'draw' => (int) $request->input('draw', 1),
            'recordsTotal' => count($data),
            'recordsFiltered' => count($data),
            'data' => $data,
        ];
    }

    /**
     * CI Student_model::getdtforlogincredential (without unused custom-field joins).
     *
     * @return Collection<int, object>
     */
    public function loginCredentialStudents(?int $classId, ?int $sectionId): Collection
    {
        $sessionId = (int) $this->currentSession->id();
        $query = DB::table('students')
            ->join('student_session', 'student_session.student_id', '=', 'students.id')
            ->join('classes', 'student_session.class_id', '=', 'classes.id')
            ->join('sections', 'sections.id', '=', 'student_session.section_id')
            ->where('student_session.session_id', $sessionId)
            ->where('students.is_active', 'yes')
            ->select([
                'students.id',
                'students.admission_no',
                'students.firstname',
                'students.middlename',
                'students.lastname',
            ])
            ->orderBy('students.admission_no');

        if ($classId) {
            $query->where('student_session.class_id', $classId);
        }
        if ($sectionId) {
            $query->where('student_session.section_id', $sectionId);
        }

        return $query->get();
    }

    /**
     * CI User_model::getUserLoginDetails.
     */
    public function studentLoginDetails(int $studentId): ?object
    {
        return DB::table('users')
            ->where('user_id', $studentId)
            ->where('role', 'student')
            ->first();
    }

    /**
     * CI User_model::getParentLoginDetails.
     */
    public function parentLoginDetails(int $studentId): ?object
    {
        return DB::table('users')
            ->join('students', 'students.parent_id', '=', 'users.id')
            ->where('students.id', $studentId)
            ->where('users.role', 'parent')
            ->select('users.*')
            ->first();
    }

    /**
     * @return array{draw: int, recordsTotal: int, recordsFiltered: int, data: list<list<string>>}
     */
    public function studentCredentialDataTable(Request $request): array
    {
        $classId = $this->nullableInt($request->input('class_id'));
        $sectionId = $this->nullableInt($request->input('section_id'));
        $data = [];
        foreach ($this->loginCredentialStudents($classId, $sectionId) as $student) {
            $login = $this->studentLoginDetails((int) $student->id);
            $data[] = [
                "<span class='pull-left'>".e((string) $student->admission_no).'</span>',
                "<a href='".url('student/view/'.$student->id)."'>".e($this->fullName($student)).'</a>',
                $login !== null ? (string) $login->username : '',
                $login !== null ? (string) $login->password : '',
            ];
        }

        return [
            'draw' => (int) $request->input('draw', 1),
            'recordsTotal' => count($data),
            'recordsFiltered' => count($data),
            'data' => $data,
        ];
    }

    /**
     * @return array{draw: int, recordsTotal: int, recordsFiltered: int, data: list<list<string>>}
     */
    public function parentCredentialDataTable(Request $request): array
    {
        $classId = $this->nullableInt($request->input('class_id'));
        $sectionId = $this->nullableInt($request->input('section_id'));
        $data = [];
        foreach ($this->loginCredentialStudents($classId, $sectionId) as $student) {
            $login = $this->parentLoginDetails((int) $student->id);
            $data[] = [
                (string) $student->admission_no,
                "<a href='".url('student/view/'.$student->id)."'>".e($this->fullName($student)).'</a>',
                $login !== null ? (string) $login->username : '',
                $login !== null ? (string) $login->password : '',
            ];
        }

        return [
            'draw' => (int) $request->input('draw', 1),
            'recordsTotal' => count($data),
            'recordsFiltered' => count($data),
            'data' => $data,
        ];
    }

    protected function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }
}
