<?php

namespace App\Modules\Homework\Services;

use App\Modules\Academics\Services\CurrentSessionResolver;
use App\Modules\Homework\Models\Homework;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * CI Homework admin — list/create/edit/delete/download (first slice).
 * Deferred: evaluation, student submissions, daily assignment, reports, mail/SMS, SaaS quota.
 */
class HomeworkService
{
    public function __construct(
        protected CurrentSessionResolver $currentSession,
        protected HomeworkDocumentService $documents,
    ) {
    }

    public function sessionId(): int
    {
        $id = (int) $this->currentSession->id();
        if ($id <= 0) {
            throw ValidationException::withMessages([
                'session_id' => 'Current academic session is not configured.',
            ]);
        }

        return $id;
    }

    /**
     * @param  array{class_id?:mixed,section_id?:mixed,subject_group_id?:mixed,subject_id?:mixed}  $filters
     * @return array{upcoming:Collection<int,object>,closed:Collection<int,object>}
     */
    public function listForFilters(array $filters): array
    {
        $classId = (int) ($filters['class_id'] ?? 0);
        if ($classId <= 0) {
            return ['upcoming' => collect(), 'closed' => collect()];
        }

        $base = $this->baseListQuery($filters);
        $today = now()->format('Y-m-d');

        $upcoming = (clone $base)->where('homework.submit_date', '>=', $today)->get();
        $closed = (clone $base)->where('homework.submit_date', '<', $today)->get();

        return ['upcoming' => $upcoming, 'closed' => $closed];
    }

    public function find(int $id): Homework
    {
        $row = Homework::query()
            ->where('id', $id)
            ->where('session_id', $this->sessionId())
            ->firstOrFail();

        return $row;
    }

    /**
     * Detail row with joins for edit form.
     */
    public function findDetailed(int $id): object
    {
        $row = DB::table('homework')
            ->join('subject_group_subjects', 'subject_group_subjects.id', '=', 'homework.subject_group_subject_id')
            ->join('subject_groups', 'subject_groups.id', '=', 'subject_group_subjects.subject_group_id')
            ->join('subjects', 'subjects.id', '=', 'subject_group_subjects.subject_id')
            ->where('homework.id', $id)
            ->where('homework.session_id', $this->sessionId())
            ->select([
                'homework.*',
                'subject_groups.id as subject_group_id',
                'subjects.name as subject_name',
                'subjects.code as subject_code',
            ])
            ->first();

        abort_unless($row !== null, 404);

        return $row;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function store(array $data, ?UploadedFile $file): Homework
    {
        $staffId = (int) (Auth::guard('staff')->id() ?? 0);
        abort_unless($staffId > 0, 403);

        $document = '';
        if ($file instanceof UploadedFile && $file->isValid()) {
            $document = $this->documents->store($file);
        }

        return Homework::query()->create([
            'class_id' => (int) $data['class_id'],
            'section_id' => (int) $data['section_id'],
            'session_id' => $this->sessionId(),
            'staff_id' => $staffId,
            'subject_group_subject_id' => (int) $data['subject_group_subject_id'],
            'subject_id' => null,
            'homework_date' => $data['homework_date'],
            'submit_date' => $data['submit_date'],
            'marks' => $this->nullableMarks($data['marks'] ?? null),
            'description' => (string) $data['description'],
            'create_date' => now()->format('Y-m-d'),
            'evaluation_date' => null,
            'document' => $document,
            'created_by' => $staffId,
            'evaluated_by' => null,
        ]);
    }

    /**
     * CI edit overwrites staff_id / created_by / create_date / session_id.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Homework $homework, array $data, ?UploadedFile $file): Homework
    {
        $staffId = (int) (Auth::guard('staff')->id() ?? 0);
        abort_unless($staffId > 0, 403);

        $document = (string) ($homework->document ?? '');
        if ($file instanceof UploadedFile && $file->isValid()) {
            $previous = $document;
            $document = $this->documents->store($file);
            if ($previous !== '' && $previous !== $document) {
                $this->documents->delete($previous);
            }
        }

        $homework->fill([
            'class_id' => (int) $data['class_id'],
            'section_id' => (int) $data['section_id'],
            'session_id' => $this->sessionId(),
            'staff_id' => $staffId,
            'subject_group_subject_id' => (int) $data['subject_group_subject_id'],
            'subject_id' => null,
            'homework_date' => $data['homework_date'],
            'submit_date' => $data['submit_date'],
            'marks' => $this->nullableMarks($data['marks'] ?? null),
            'description' => (string) $data['description'],
            'create_date' => now()->format('Y-m-d'),
            'document' => $document,
            'created_by' => $staffId,
        ]);
        $homework->save();

        return $homework;
    }

    public function delete(Homework $homework): void
    {
        $document = (string) ($homework->document ?? '');
        $id = (int) $homework->id;

        DB::transaction(function () use ($id) {
            DB::table('homework_evaluation')->where('homework_id', $id)->delete();
            DB::table('submit_assignment')->where('homework_id', $id)->delete();
            DB::table('homework')->where('id', $id)->delete();
        });

        $this->documents->delete($document);
    }

    public function download(Homework $homework): BinaryFileResponse
    {
        $doc = (string) ($homework->document ?? '');
        abort_unless($doc !== '', 404);

        return $this->documents->download($doc);
    }

    /**
     * @param  array{class_id?:mixed,section_id?:mixed,subject_group_id?:mixed,subject_id?:mixed}  $filters
     */
    protected function baseListQuery(array $filters)
    {
        $sessionId = $this->sessionId();
        $classId = (int) ($filters['class_id'] ?? 0);
        $sectionId = (int) ($filters['section_id'] ?? 0);
        $subjectGroupId = (int) ($filters['subject_group_id'] ?? 0);
        // CI subject_id filter is subject_group_subjects.id
        $subjectGroupSubjectId = (int) ($filters['subject_id'] ?? 0);

        $query = DB::table('homework')
            ->join('classes', 'classes.id', '=', 'homework.class_id')
            ->join('sections', 'sections.id', '=', 'homework.section_id')
            ->join('subject_group_subjects', 'subject_group_subjects.id', '=', 'homework.subject_group_subject_id')
            ->join('subjects', 'subjects.id', '=', 'subject_group_subjects.subject_id')
            ->join('subject_groups', 'subject_groups.id', '=', 'subject_group_subjects.subject_group_id')
            ->leftJoin('staff', 'staff.id', '=', 'homework.created_by')
            ->where('subject_groups.session_id', $sessionId)
            ->where('homework.class_id', $classId)
            ->select([
                'homework.*',
                'classes.class',
                'sections.section',
                'subjects.name as subject_name',
                'subjects.code as subject_code',
                'subject_groups.name as subject_group_name',
                'staff.name as staff_name',
                'staff.surname as staff_surname',
                'staff.employee_id as staff_employee_id',
                DB::raw('(select count(*) from submit_assignment where submit_assignment.homework_id = homework.id) as assignments'),
            ])
            ->orderByDesc('homework.homework_date');

        if ($sectionId > 0) {
            $query->where('homework.section_id', $sectionId);
        }
        if ($subjectGroupId > 0) {
            $query->where('subject_groups.id', $subjectGroupId);
        }
        if ($subjectGroupSubjectId > 0) {
            $query->where('subject_group_subjects.id', $subjectGroupSubjectId);
        }

        return $query;
    }

    protected function nullableMarks(mixed $marks): ?float
    {
        if ($marks === null || $marks === '') {
            return null;
        }

        return (float) $marks;
    }
}
