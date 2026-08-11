<?php

namespace App\Modules\Students\Services;

use App\Modules\Academics\Services\CurrentSessionResolver;
use App\Modules\Students\Models\StudentSession;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Mirrors CI Stdtransfer + Student_model::searchNonPromotedStudents / add_student_session
 * and Studentsession_model::updatePromote.
 */
class StudentPromotionService
{
    public function __construct(protected CurrentSessionResolver $currentSession)
    {
    }

    /**
     * Non-promoted students in current session class/section who do not yet have
     * a row in the target promote session/class/section.
     *
     * @return Collection<int, object>
     */
    public function searchNonPromoted(
        int $classId,
        int $sectionId,
        int $promoteSessionId,
        int $promoteClassId,
        int $promoteSectionId
    ): Collection {
        $currentSessionId = (int) $this->currentSession->id();

        return DB::table('students')
            ->join('student_session', 'student_session.student_id', '=', 'students.id')
            ->join('classes', 'student_session.class_id', '=', 'classes.id')
            ->join('sections', 'sections.id', '=', 'student_session.section_id')
            ->leftJoin('categories', 'students.category_id', '=', 'categories.id')
            ->leftJoin('student_session as promoted_students', function ($join) use ($promoteSessionId, $promoteClassId, $promoteSectionId) {
                $join->on('promoted_students.student_id', '=', 'students.id')
                    ->where('promoted_students.session_id', '=', $promoteSessionId)
                    ->where('promoted_students.class_id', '=', $promoteClassId)
                    ->where('promoted_students.section_id', '=', $promoteSectionId);
            })
            ->where('student_session.is_leave', 0)
            ->where('student_session.session_id', $currentSessionId)
            ->where('students.is_active', 'yes')
            ->where('student_session.class_id', $classId)
            ->where('student_session.section_id', $sectionId)
            ->whereNull('promoted_students.id')
            ->select([
                'promoted_students.id as promoted_student_id',
                'classes.id as class_id',
                'student_session.id as student_session_id',
                'students.id',
                'classes.class',
                'sections.id as section_id',
                'sections.section',
                'students.admission_no',
                'students.roll_no',
                'students.firstname',
                'students.middlename',
                'students.lastname',
                'students.father_name',
                'students.dob',
                'students.gender',
                DB::raw("IFNULL(categories.category, '') as category"),
            ])
            ->orderBy('students.id')
            ->get();
    }

    /**
     * @param  list<int|string>  $studentIds
     * @param  array<int|string, string>  $results  result_{id} => pass|fail
     * @param  array<int|string, string>  $nextStatuses  next_working_{id} => countinue|leave (CI typo preserved)
     */
    public function promote(
        array $studentIds,
        array $results,
        array $nextStatuses,
        int $promoteSessionId,
        int $promoteClassId,
        int $promoteSectionId,
        int $currentClassId,
        int $currentSectionId
    ): void {
        $currentSessionId = (int) $this->currentSession->id();

        DB::transaction(function () use (
            $studentIds,
            $results,
            $nextStatuses,
            $promoteSessionId,
            $promoteClassId,
            $promoteSectionId,
            $currentClassId,
            $currentSectionId,
            $currentSessionId
        ) {
            foreach ($studentIds as $studentId) {
                $studentId = (int) $studentId;
                $result = $results[$studentId] ?? ($results[(string) $studentId] ?? null);
                $sessionStatus = $nextStatuses[$studentId] ?? ($nextStatuses[(string) $studentId] ?? null);

                // CI typo: "countinue"
                if ($result === 'pass' && $sessionStatus === 'countinue') {
                    $this->upsertStudentSession([
                        'student_id' => $studentId,
                        'class_id' => $promoteClassId,
                        'section_id' => $promoteSectionId,
                        'session_id' => $promoteSessionId,
                        'transport_fees' => 0,
                        'fees_discount' => 0,
                    ]);
                } elseif ($result === 'fail' && $sessionStatus === 'countinue') {
                    $this->upsertStudentSession([
                        'student_id' => $studentId,
                        'class_id' => $currentClassId,
                        'section_id' => $currentSectionId,
                        'session_id' => $promoteSessionId,
                        'transport_fees' => 0,
                        'fees_discount' => 0,
                    ]);
                } elseif ($sessionStatus === 'leave') {
                    StudentSession::query()
                        ->where('session_id', $currentSessionId)
                        ->where('student_id', $studentId)
                        ->where('class_id', $currentClassId)
                        ->where('section_id', $currentSectionId)
                        ->update([
                            'is_leave' => 1,
                            'session_id' => $currentSessionId,
                            'student_id' => $studentId,
                            'class_id' => $currentClassId,
                            'section_id' => $currentSectionId,
                        ]);

                    StudentSession::query()
                        ->where('student_id', $studentId)
                        ->where('session_id', $currentSessionId)
                        ->update(['is_alumni' => 1]);
                }
            }
        });
    }

    /**
     * Mirrors Student_model::add_student_session — update if same session+student exists, else insert.
     *
     * @param  array<string, mixed>  $data
     */
    public function upsertStudentSession(array $data): int
    {
        $existing = StudentSession::query()
            ->where('session_id', $data['session_id'])
            ->where('student_id', $data['student_id'])
            ->first();

        if ($existing) {
            $existing->fill($data);
            $existing->save();

            return (int) $existing->id;
        }

        $data['is_alumni'] = $data['is_alumni'] ?? 0;
        $data['is_leave'] = $data['is_leave'] ?? 0;
        $data['is_active'] = $data['is_active'] ?? 'yes';
        $data['default_login'] = $data['default_login'] ?? 0;

        return (int) StudentSession::query()->create($data)->id;
    }
}
