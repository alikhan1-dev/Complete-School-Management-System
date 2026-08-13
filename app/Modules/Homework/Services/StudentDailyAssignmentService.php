<?php

namespace App\Modules\Homework\Services;

use App\Modules\Homework\Models\DailyAssignment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * CI user/homework dailyassignment* — student portal CRUD.
 * Deferred: SaaS quota.
 */
class StudentDailyAssignmentService
{
    public function __construct(
        protected StudentHomeworkService $homeworkPortal,
        protected HomeworkDocumentService $documents,
    ) {
    }

    /**
     * @return Collection<int, object>
     */
    public function listForCurrentStudent(): Collection
    {
        $ctx = $this->homeworkPortal->currentContext();

        // CI model has a buggy OR student_id join — Laravel scopes only by student_session_id.
        return DB::table('daily_assignment')
            ->join('subject_group_subjects', 'subject_group_subjects.id', '=', 'daily_assignment.subject_group_subject_id')
            ->join('subjects', 'subjects.id', '=', 'subject_group_subjects.subject_id')
            ->where('daily_assignment.student_session_id', $ctx['student_session_id'])
            ->orderByDesc('daily_assignment.id')
            ->select([
                'daily_assignment.*',
                'subjects.name as subject_name',
                'subjects.code as subject_code',
            ])
            ->get();
    }

    /**
     * Subjects available for class/section (subject_group_subjects rows).
     *
     * @return Collection<int, object>
     */
    public function availableSubjects(): Collection
    {
        $ctx = $this->homeworkPortal->currentContext();

        $classSectionId = (int) DB::table('class_sections')
            ->where('class_id', $ctx['class_id'])
            ->where('section_id', $ctx['section_id'])
            ->value('id');

        if ($classSectionId <= 0) {
            return collect();
        }

        return DB::table('subject_group_class_sections')
            ->join('subject_groups', 'subject_groups.id', '=', 'subject_group_class_sections.subject_group_id')
            ->join('subject_group_subjects', 'subject_group_subjects.subject_group_id', '=', 'subject_groups.id')
            ->join('subjects', 'subjects.id', '=', 'subject_group_subjects.subject_id')
            ->where('subject_group_class_sections.class_section_id', $classSectionId)
            ->where('subject_group_class_sections.session_id', $ctx['session_id'])
            ->where('subject_group_subjects.session_id', $ctx['session_id'])
            ->orderBy('subjects.name')
            ->select([
                'subject_group_subjects.id',
                'subjects.name',
                'subjects.code',
                'subject_groups.name as subject_group_name',
            ])
            ->get();
    }

    public function findOwned(int $id): DailyAssignment
    {
        $ctx = $this->homeworkPortal->currentContext();
        $row = DailyAssignment::query()
            ->where('id', $id)
            ->where('student_session_id', $ctx['student_session_id'])
            ->firstOrFail();

        return $row;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function store(array $data, ?UploadedFile $file): DailyAssignment
    {
        $ctx = $this->homeworkPortal->currentContext();
        $this->assertSubjectAllowed((int) $data['subject_group_subject_id'], $ctx);

        $attachment = '';
        if ($file instanceof UploadedFile && $file->isValid()) {
            $attachment = $this->documents->storeDaily($file);
        }

        return DailyAssignment::query()->create([
            'student_session_id' => $ctx['student_session_id'],
            'subject_group_subject_id' => (int) $data['subject_group_subject_id'],
            'title' => (string) $data['title'],
            'description' => (string) ($data['description'] ?? ''),
            'attachment' => $attachment,
            'evaluated_by' => null,
            'date' => now()->format('Y-m-d'),
            'evaluation_date' => null,
            'remark' => '',
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(DailyAssignment $row, array $data, ?UploadedFile $file): DailyAssignment
    {
        if ($row->evaluated_by !== null && (int) $row->evaluated_by > 0) {
            throw ValidationException::withMessages([
                'id' => 'Evaluated daily assignments cannot be edited.',
            ]);
        }

        $ctx = $this->homeworkPortal->currentContext();
        $this->assertSubjectAllowed((int) $data['subject_group_subject_id'], $ctx);

        $attachment = (string) ($row->attachment ?? '');
        if ($file instanceof UploadedFile && $file->isValid()) {
            $previous = $attachment;
            $attachment = $this->documents->storeDaily($file);
            if ($previous !== '' && $previous !== $attachment) {
                $this->documents->deleteDaily($previous);
            }
        }

        $row->fill([
            'subject_group_subject_id' => (int) $data['subject_group_subject_id'],
            'title' => (string) $data['title'],
            'description' => (string) ($data['description'] ?? ''),
            'attachment' => $attachment,
            'date' => now()->format('Y-m-d'),
        ]);
        $row->save();

        return $row;
    }

    public function delete(DailyAssignment $row): void
    {
        if ($row->evaluated_by !== null && (int) $row->evaluated_by > 0) {
            throw ValidationException::withMessages([
                'id' => 'Evaluated daily assignments cannot be deleted.',
            ]);
        }

        $attachment = (string) ($row->attachment ?? '');
        $row->delete();
        $this->documents->deleteDaily($attachment);
    }

    public function download(int $id): BinaryFileResponse
    {
        $row = $this->findOwned($id);
        $attachment = (string) ($row->attachment ?? '');
        abort_unless($attachment !== '', 404);

        return $this->documents->downloadDaily($attachment);
    }

    /**
     * @param  array{session_id:int,class_id:int,section_id:int}  $ctx
     */
    protected function assertSubjectAllowed(int $subjectGroupSubjectId, array $ctx): void
    {
        $allowed = $this->availableSubjects()->contains(fn ($s) => (int) $s->id === $subjectGroupSubjectId);
        if (! $allowed) {
            throw ValidationException::withMessages([
                'subject_group_subject_id' => 'Selected subject is not available for this class.',
            ]);
        }
    }
}
