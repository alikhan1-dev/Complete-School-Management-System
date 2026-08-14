<?php

namespace App\Modules\LessonPlan\Services;

use App\Modules\LessonPlan\Models\LessonPlanForum;
use App\Modules\Shared\Services\SchoolContext;
use App\Modules\Staff\Models\Staff;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * CI syllabus_model addmessage / getmessage / deletemessage.
 * Student portal posting deferred; student comments still display on admin show.
 */
class LessonPlanForumService
{
    public function __construct(
        protected SchoolContext $school,
    ) {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function messagesForSyllabus(int $subjectSyllabusId, ?Staff $viewer = null): array
    {
        $rows = DB::table('lesson_plan_forum')
            ->leftJoin('staff', 'staff.id', '=', 'lesson_plan_forum.staff_id')
            ->leftJoin('students', 'students.id', '=', 'lesson_plan_forum.student_id')
            ->where('lesson_plan_forum.subject_syllabus_id', $subjectSyllabusId)
            ->orderByDesc('lesson_plan_forum.id')
            ->select([
                'lesson_plan_forum.id as fourm_id',
                'lesson_plan_forum.message',
                'lesson_plan_forum.created_date',
                'lesson_plan_forum.type',
                'lesson_plan_forum.staff_id',
                'lesson_plan_forum.student_id',
                'staff.name as staff_name',
                'staff.surname as staff_surname',
                'staff.employee_id as staff_employee_id',
                'staff.image as staff_image',
                'staff.gender as staff_gender',
                'students.firstname',
                'students.middlename',
                'students.lastname',
                'students.image as student_image',
                'students.admission_no',
                'students.gender as students_gender',
            ])
            ->get();

        $hideSuperadmin = $this->school->superadminRestriction() === 'disabled'
            && $viewer !== null
            && ! $viewer->isSuperAdmin();

        $out = [];
        foreach ($rows as $row) {
            $item = (array) $row;
            if ($hideSuperadmin && ($item['type'] ?? '') === 'staff') {
                $author = Staff::query()->find((int) ($item['staff_id'] ?? 0));
                if ($author && $author->isSuperAdmin()) {
                    continue;
                }
            }
            $out[] = $item;
        }

        return $out;
    }

    public function addStaffMessage(int $subjectSyllabusId, int $staffId, string $message): LessonPlanForum
    {
        $message = trim($message);
        if ($message === '') {
            throw ValidationException::withMessages([
                'message' => 'The comment field is required.',
            ]);
        }

        return LessonPlanForum::query()->create([
            'subject_syllabus_id' => $subjectSyllabusId,
            'type' => 'staff',
            'staff_id' => $staffId,
            'student_id' => null,
            'message' => $message,
            'created_date' => now()->format('Y-m-d H:i:s'),
        ]);
    }

    public function deleteOwnStaffMessage(int $id, int $staffId): void
    {
        $row = LessonPlanForum::query()->find($id);
        if ($row === null) {
            return;
        }

        if ($row->type !== 'staff' || (int) $row->staff_id !== $staffId) {
            abort(403);
        }

        $row->delete();
    }
}
