<?php

namespace App\Modules\Students\Services;

use App\Modules\Academics\Services\CurrentSessionResolver;
use App\Modules\Shared\Services\ClassTeacherScopeService;
use App\Modules\Shared\Services\SchoolContext;
use App\Modules\Students\Models\StudentSession;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * CI Student::multiclass / savemulticlass + Studentsession_model multi-class helpers.
 */
class MultiClassStudentService
{
    public function __construct(
        protected CurrentSessionResolver $currentSession,
        protected SchoolContext $school,
        protected ClassTeacherScopeService $classTeacherScope,
    ) {
    }

    public function formMultiClassEnabled(): bool
    {
        return (string) $this->school->get('student_form_multi_class', 'disabled') === 'enabled';
    }

    /**
     * CI Studentsession_model::searchMultiStudentByClassSection
     * via Student_model::searchByClassSectionWithSession (+ teacher matrix filter).
     *
     * @return list<array<string, mixed>>
     */
    public function searchByClassSection(int $classId, int $sectionId): array
    {
        $sessionId = (int) $this->currentSession->id();
        if ($sessionId <= 0 || $classId <= 0 || $sectionId <= 0) {
            return [];
        }

        $students = DB::table('students')
            ->join('student_session', 'student_session.student_id', '=', 'students.id')
            ->join('classes', 'student_session.class_id', '=', 'classes.id')
            ->join('sections', 'sections.id', '=', 'student_session.section_id')
            ->where('student_session.session_id', $sessionId)
            ->where('students.is_active', 'yes')
            ->where('student_session.class_id', $classId)
            ->where('student_session.section_id', $sectionId)
            ->orderBy('students.id')
            ->select([
                'students.id',
                'students.admission_no',
                'students.firstname',
                'students.middlename',
                'students.lastname',
                'student_session.id as student_session_id',
                'classes.id as class_id',
                'classes.class',
                'sections.id as section_id',
                'sections.section',
            ])
            ->get()
            ->map(fn ($student) => (array) $student)
            ->all();

        $students = $this->classTeacherScope->filterRowsByMatrix($students);

        return array_map(function (array $student) use ($sessionId) {
            $sessions = DB::table('student_session')
                ->where('student_id', $student['id'])
                ->where('session_id', $sessionId)
                ->orderBy('id')
                ->get();

            $student['student_sessions'] = $sessions->all();

            return $student;
        }, $students);
    }

    /**
     * @return Collection<int, object>
     */
    public function sessionsForStudent(int $studentId): Collection
    {
        $sessionId = (int) $this->currentSession->id();

        return DB::table('student_session')
            ->join('classes', 'student_session.class_id', '=', 'classes.id')
            ->join('sections', 'sections.id', '=', 'student_session.section_id')
            ->where('student_session.student_id', $studentId)
            ->where('student_session.session_id', $sessionId)
            ->orderByDesc('student_session.default_login')
            ->orderBy('student_session.id')
            ->select([
                'student_session.*',
                'classes.class',
                'sections.section',
                'student_session.id as student_session_id',
            ])
            ->get();
    }

    public function sessionCount(int $studentId): int
    {
        return (int) StudentSession::query()
            ->where('student_id', $studentId)
            ->where('session_id', $this->currentSession->id())
            ->count();
    }

    /**
     * CI Studentsession_model::add — keep posted class/section rows; delete others in current session.
     *
     * @param  list<array{class_id:int,section_id:int}>  $rows
     */
    public function syncSessions(int $studentId, array $rows): bool
    {
        $sessionId = (int) $this->currentSession->id();
        if ($sessionId <= 0 || $studentId <= 0) {
            return false;
        }

        $normalized = [];
        $seen = [];
        foreach ($rows as $row) {
            $classId = (int) ($row['class_id'] ?? 0);
            $sectionId = (int) ($row['section_id'] ?? 0);
            if ($classId <= 0 || $sectionId <= 0) {
                continue;
            }
            $key = $classId.'-'.$sectionId;
            if (isset($seen[$key])) {
                return false; // duplicate
            }
            $seen[$key] = true;
            $normalized[] = [
                'class_id' => $classId,
                'section_id' => $sectionId,
                'session_id' => $sessionId,
                'student_id' => $studentId,
            ];
        }

        return DB::transaction(function () use ($normalized, $studentId, $sessionId) {
            $keepIds = [];
            foreach ($normalized as $row) {
                $existing = StudentSession::query()
                    ->where('session_id', $row['session_id'])
                    ->where('student_id', $row['student_id'])
                    ->where('class_id', $row['class_id'])
                    ->where('section_id', $row['section_id'])
                    ->first();

                if ($existing) {
                    $keepIds[] = (int) $existing->id;
                } else {
                    $created = StudentSession::query()->create([
                        'student_id' => $row['student_id'],
                        'class_id' => $row['class_id'],
                        'section_id' => $row['section_id'],
                        'session_id' => $row['session_id'],
                        'is_alumni' => 0,
                        'is_active' => 'yes',
                        'is_leave' => 0,
                        'default_login' => 0,
                        'transport_fees' => 0,
                        'fees_discount' => 0,
                    ]);
                    $keepIds[] = (int) $created->id;
                }
            }

            $deleteQuery = StudentSession::query()
                ->where('session_id', $sessionId)
                ->where('student_id', $studentId);

            if ($keepIds !== []) {
                $deleteQuery->whereNotIn('id', $keepIds);
            }

            $deleteQuery->delete();

            return true;
        });
    }

    /**
     * CI addNewMethod multiclass_data — extra student_session rows on admit.
     *
     * @param  list<array{class?:int|string,section?:int|string}>  $multiclassRows
     */
    public function insertExtraSessionsOnAdmit(int $studentId, array $multiclassRows): void
    {
        $sessionId = (int) $this->currentSession->id();
        foreach ($multiclassRows as $row) {
            $classId = (int) ($row['class'] ?? 0);
            $sectionId = (int) ($row['section'] ?? 0);
            if ($classId <= 0 || $sectionId <= 0) {
                continue;
            }

            $exists = StudentSession::query()
                ->where('session_id', $sessionId)
                ->where('student_id', $studentId)
                ->where('class_id', $classId)
                ->where('section_id', $sectionId)
                ->exists();

            if ($exists) {
                continue;
            }

            StudentSession::query()->create([
                'student_id' => $studentId,
                'class_id' => $classId,
                'section_id' => $sectionId,
                'session_id' => $sessionId,
                'is_alumni' => 0,
                'is_active' => 'yes',
                'is_leave' => 0,
                'default_login' => 0,
                'transport_fees' => 0,
                'fees_discount' => 0,
            ]);
        }
    }

    /**
     * CI Studentsession_model::addMultiClassWithTeacher — sync extras relative to primary class/section.
     *
     * @param  list<array{class?:int|string,section?:int|string}>  $multiclassRows
     */
    public function syncFromAdmissionForm(int $studentId, int $primaryClassId, int $primarySectionId, array $multiclassRows): void
    {
        $rows = [
            ['class_id' => $primaryClassId, 'section_id' => $primarySectionId],
        ];
        foreach ($multiclassRows as $row) {
            $classId = (int) ($row['class'] ?? 0);
            $sectionId = (int) ($row['section'] ?? 0);
            if ($classId <= 0 || $sectionId <= 0) {
                continue;
            }
            if ($classId === $primaryClassId && $sectionId === $primarySectionId) {
                continue;
            }
            $rows[] = ['class_id' => $classId, 'section_id' => $sectionId];
        }

        $this->syncSessions($studentId, $rows);
    }
}
