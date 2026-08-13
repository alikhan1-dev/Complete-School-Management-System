<?php

namespace App\Modules\Exams\Services;

use App\Modules\Exams\Models\TemplateAdmitcard;
use App\Modules\Exams\Models\TemplateMarksheet;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * CI Marksheet_model / Admitcard_model — design templates + print student search.
 * Deferred: mPDF binary download, email PDF marksheet, saas storage quota.
 */
class ExamPrintTemplateService
{
    public const MARKSHEET_FOLDER = 'marksheet';

    public const ADMITCARD_FOLDER = 'admit_card';

    public function __construct(protected ExamDocumentService $documents)
    {
    }

    /**
     * @return list<string>
     */
    public function marksheetFlagFields(): array
    {
        return [
            'exam_session', 'is_name', 'is_father_name', 'is_mother_name', 'is_dob',
            'is_admission_no', 'is_roll_no', 'is_photo', 'is_division', 'is_rank',
            'is_class', 'is_section', 'is_teacher_remark',
        ];
    }

    /**
     * @return list<string>
     */
    public function admitcardFlagFields(): array
    {
        return [
            'is_name', 'is_father_name', 'is_mother_name', 'is_dob', 'is_admission_no',
            'is_roll_no', 'is_address', 'is_gender', 'is_photo', 'is_class', 'is_section',
        ];
    }

    /**
     * @return list<string>
     */
    public function marksheetFileFields(): array
    {
        return ['header_image', 'left_logo', 'right_logo', 'left_sign', 'middle_sign', 'right_sign', 'background_img'];
    }

    /**
     * @return list<string>
     */
    public function admitcardFileFields(): array
    {
        return ['left_logo', 'right_logo', 'sign', 'background_img'];
    }

    /**
     * @return Collection<int, TemplateMarksheet>
     */
    public function listMarksheets(): Collection
    {
        return TemplateMarksheet::query()->orderBy('id')->get();
    }

    /**
     * @return Collection<int, TemplateAdmitcard>
     */
    public function listAdmitcards(): Collection
    {
        return TemplateAdmitcard::query()->orderBy('id')->get();
    }

    public function findMarksheet(int $id): TemplateMarksheet
    {
        return TemplateMarksheet::query()->findOrFail($id);
    }

    public function findAdmitcard(int $id): TemplateAdmitcard
    {
        return TemplateAdmitcard::query()->findOrFail($id);
    }

    public function activeAdmitcard(): ?TemplateAdmitcard
    {
        return TemplateAdmitcard::query()->where('is_active', 1)->orderBy('id')->first();
    }

    /**
     * @return array<string, mixed>
     */
    public function marksheetPayload(Request $request, ?TemplateMarksheet $existing = null): array
    {
        $payload = [
            'template' => (string) $request->input('template'),
            'heading' => (string) $request->input('heading', ''),
            'title' => (string) $request->input('title', ''),
            'exam_name' => (string) $request->input('exam_name', ''),
            'school_name' => (string) $request->input('school_name', ''),
            'exam_center' => (string) $request->input('exam_center', ''),
            'date' => (string) $request->input('date', ''),
            'content' => (string) $request->input('content', ''),
            'content_footer' => (string) $request->input('content_footer', ''),
            'is_customfield' => $existing ? (int) $existing->is_customfield : 0,
        ];

        foreach ($this->marksheetFlagFields() as $flag) {
            $payload[$flag] = $request->boolean($flag) ? 1 : 0;
        }

        foreach ($this->marksheetFileFields() as $fileField) {
            $payload[$fileField] = $existing ? (string) ($existing->{$fileField} ?? '') : '';
            if ($request->hasFile($fileField)) {
                if ($existing && $existing->{$fileField}) {
                    $this->documents->delete((string) $existing->{$fileField}, self::MARKSHEET_FOLDER);
                }
                $payload[$fileField] = $this->documents->store($request->file($fileField), self::MARKSHEET_FOLDER);
            }
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    public function admitcardPayload(Request $request, ?TemplateAdmitcard $existing = null): array
    {
        $payload = [
            'template' => (string) $request->input('template'),
            'heading' => (string) $request->input('heading', ''),
            'title' => (string) $request->input('title', ''),
            'exam_name' => (string) $request->input('exam_name', ''),
            'school_name' => (string) $request->input('school_name', ''),
            'exam_center' => (string) $request->input('exam_center', ''),
            'content_footer' => nl2br((string) $request->input('content_footer', '')),
            'is_active' => $existing ? (int) $existing->is_active : 0,
        ];

        foreach ($this->admitcardFlagFields() as $flag) {
            $payload[$flag] = $request->boolean($flag) ? 1 : 0;
        }

        foreach ($this->admitcardFileFields() as $fileField) {
            $payload[$fileField] = $existing ? (string) ($existing->{$fileField} ?? '') : '';
            if ($request->hasFile($fileField)) {
                if ($existing && $existing->{$fileField}) {
                    $this->documents->delete((string) $existing->{$fileField}, self::ADMITCARD_FOLDER);
                }
                $payload[$fileField] = $this->documents->store($request->file($fileField), self::ADMITCARD_FOLDER);
            }
        }

        return $payload;
    }

    public function createMarksheet(array $payload): TemplateMarksheet
    {
        return TemplateMarksheet::query()->create($payload);
    }

    public function updateMarksheet(TemplateMarksheet $row, array $payload): TemplateMarksheet
    {
        $row->fill($payload);
        $row->save();

        return $row;
    }

    public function deleteMarksheet(TemplateMarksheet $row): void
    {
        foreach ($this->marksheetFileFields() as $fileField) {
            $this->documents->delete((string) ($row->{$fileField} ?? ''), self::MARKSHEET_FOLDER);
        }
        $row->delete();
    }

    public function createAdmitcard(array $payload): TemplateAdmitcard
    {
        return TemplateAdmitcard::query()->create($payload);
    }

    public function updateAdmitcard(TemplateAdmitcard $row, array $payload): TemplateAdmitcard
    {
        $row->fill($payload);
        $row->save();

        return $row;
    }

    public function deleteAdmitcard(TemplateAdmitcard $row): void
    {
        foreach ($this->admitcardFileFields() as $fileField) {
            $this->documents->delete((string) ($row->{$fileField} ?? ''), self::ADMITCARD_FOLDER);
        }
        $row->delete();
    }

    public function activateAdmitcard(int $id): void
    {
        DB::transaction(function () use ($id) {
            TemplateAdmitcard::query()->update(['is_active' => 0]);
            TemplateAdmitcard::query()->where('id', $id)->update(['is_active' => 1]);
        });
    }

    /**
     * CI Examgroupstudent_model::searchExamStudents — students assigned to an exam.
     *
     * @return Collection<int, object>
     */
    public function searchExamStudentsForPrint(
        int $examId,
        int $classId,
        int $sectionId,
        int $sessionId
    ): Collection {
        return DB::table('exam_group_class_batch_exam_students')
            ->join('student_session', 'student_session.id', '=', 'exam_group_class_batch_exam_students.student_session_id')
            ->join('students', 'students.id', '=', 'student_session.student_id')
            ->join('classes', 'student_session.class_id', '=', 'classes.id')
            ->join('sections', 'student_session.section_id', '=', 'sections.id')
            ->leftJoin('categories', 'students.category_id', '=', 'categories.id')
            ->where('exam_group_class_batch_exam_students.exam_group_class_batch_exam_id', $examId)
            ->where('student_session.class_id', $classId)
            ->where('student_session.section_id', $sectionId)
            ->where('student_session.session_id', $sessionId)
            ->where('students.is_active', 'yes')
            ->orderBy('students.firstname')
            ->select([
                'exam_group_class_batch_exam_students.id as exam_student_id',
                'exam_group_class_batch_exam_students.roll_no as exam_roll_no',
                'exam_group_class_batch_exam_students.teacher_remark',
                'students.id as student_id',
                'students.admission_no',
                'students.roll_no',
                'students.firstname',
                'students.middlename',
                'students.lastname',
                'students.father_name',
                'students.mother_name',
                'students.dob',
                'students.gender',
                'students.current_address',
                'students.image',
                'classes.class',
                'sections.section',
                DB::raw("IFNULL(categories.category, '') as category"),
            ])
            ->get();
    }

    /**
     * Subject schedule for admit card print.
     *
     * @return Collection<int, object>
     */
    public function examSubjectsForPrint(int $examId): Collection
    {
        return DB::table('exam_group_class_batch_exam_subjects')
            ->join('subjects', 'subjects.id', '=', 'exam_group_class_batch_exam_subjects.subject_id')
            ->where('exam_group_class_batch_exam_subjects.exam_group_class_batch_exams_id', $examId)
            ->orderBy('exam_group_class_batch_exam_subjects.date_from')
            ->orderBy('exam_group_class_batch_exam_subjects.time_from')
            ->select([
                'exam_group_class_batch_exam_subjects.*',
                'subjects.name as subject_name',
                'subjects.code as subject_code',
            ])
            ->get();
    }

    /**
     * Subject marks for one exam student (print marksheet HTML).
     *
     * @return Collection<int, object>
     */
    public function studentSubjectMarks(int $examId, int $examStudentId): Collection
    {
        return DB::table('exam_group_class_batch_exam_subjects')
            ->join('subjects', 'subjects.id', '=', 'exam_group_class_batch_exam_subjects.subject_id')
            ->leftJoin('exam_group_exam_results', function ($join) use ($examStudentId) {
                $join->on(
                    'exam_group_exam_results.exam_group_class_batch_exam_subject_id',
                    '=',
                    'exam_group_class_batch_exam_subjects.id'
                )->where('exam_group_exam_results.exam_group_class_batch_exam_student_id', '=', $examStudentId);
            })
            ->where('exam_group_class_batch_exam_subjects.exam_group_class_batch_exams_id', $examId)
            ->orderBy('exam_group_class_batch_exam_subjects.id')
            ->select([
                'subjects.name as subject_name',
                'subjects.code as subject_code',
                'exam_group_class_batch_exam_subjects.max_marks',
                'exam_group_class_batch_exam_subjects.min_marks',
                DB::raw("IFNULL(exam_group_exam_results.get_marks, '') as get_marks"),
                DB::raw("IFNULL(exam_group_exam_results.attendence, 'present') as attendence"),
                DB::raw("IFNULL(exam_group_exam_results.note, '') as note"),
            ])
            ->get();
    }
}
