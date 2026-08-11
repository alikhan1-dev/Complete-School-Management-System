<?php

namespace App\Modules\Students\Services;

use App\Modules\Academics\Services\CurrentSessionResolver;
use App\Modules\Students\Models\Student;
use App\Modules\Students\Models\StudentSession;
use Illuminate\Support\Facades\DB;

/**
 * Hard delete mirrors CI Student_model::remove; disable mirrors disableStudent / disable_reason.
 */
class StudentLifecycleService
{
    public function __construct(
        protected CurrentSessionResolver $currentSession,
        protected StudentDocumentService $documents,
        protected StudentTimelineService $timeline
    ) {
    }

    public function delete(int $studentId): void
    {
        DB::transaction(function () use ($studentId) {
            $student = Student::query()->findOrFail($studentId);

            // Update parent childs list or delete parent user (CI remove()).
            $parentUsers = DB::table('users')
                ->where('role', 'parent')
                ->where(function ($q) use ($studentId) {
                    $q->where('childs', $studentId)
                        ->orWhere('childs', 'like', $studentId.',%')
                        ->orWhere('childs', 'like', '%,'.$studentId)
                        ->orWhere('childs', 'like', '%,'.$studentId.',%');
                })
                ->get();

            foreach ($parentUsers as $parent) {
                $childs = array_values(array_filter(explode(',', (string) $parent->childs), fn ($c) => (string) $c !== (string) $studentId && $c !== ''));
                if (count($childs) > 0) {
                    DB::table('users')->where('id', $parent->id)->update(['childs' => implode(',', $childs)]);
                } else {
                    DB::table('users')->where('id', $parent->id)->delete();
                }
            }

            $this->documents->deleteAllForStudent($studentId);
            $this->timeline->deleteAllForStudent($studentId);

            DB::table('student_session')->where('student_id', $studentId)->delete();
            DB::table('users')->where('role', 'student')->where('user_id', $studentId)->delete();
            $student->delete();
        });
    }

    public function disable(int $studentId, ?string $reason = null, ?string $note = null, ?string $disableAt = null): void
    {
        $student = Student::query()->findOrFail($studentId);
        $student->is_active = 'no';
        $student->disable_at = $disableAt ?: now()->toDateString();
        if ($reason !== null) {
            $student->dis_reason = $reason;
        }
        if ($note !== null) {
            $student->dis_note = $note;
        }
        $student->save();
    }

    public function enable(int $studentId): void
    {
        $student = Student::query()->findOrFail($studentId);
        $student->is_active = 'yes';
        $student->save();
    }

    public function syncCurrentSessionClassSection(int $studentId, int $classId, int $sectionId): void
    {
        $sessionId = $this->currentSession->id();
        $row = StudentSession::query()
            ->where('student_id', $studentId)
            ->where('session_id', $sessionId)
            ->orderBy('id')
            ->first();

        if ($row) {
            $row->class_id = $classId;
            $row->section_id = $sectionId;
            $row->save();

            return;
        }

        StudentSession::query()->create([
            'student_id' => $studentId,
            'session_id' => $sessionId,
            'class_id' => $classId,
            'section_id' => $sectionId,
            'is_alumni' => 0,
            'is_active' => 'yes',
            'is_leave' => 0,
            'default_login' => 0,
            'transport_fees' => 0,
            'fees_discount' => 0,
        ]);
    }
}
