<?php

namespace App\Modules\Reports\Services;

use App\Modules\Academics\Models\SchoolClass;
use App\Modules\LessonPlan\Services\LessonPlanService;
use App\Modules\Shared\Services\SchoolContext;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * CI Report::lesson_plan + teachersyllabusstatus (Syllabus_model report queries).
 */
class LessonPlanReportService
{
    public function __construct(
        protected LessonPlanService $lessonPlan,
        protected SchoolContext $school,
    ) {
    }

    /**
     * @return Collection<int, SchoolClass>
     */
    public function classes(): Collection
    {
        return SchoolClass::query()->orderBy('class')->get();
    }

    public function formatDate(mixed $value): string
    {
        if ($value === null || $value === '' || $value === '0000-00-00' || $value === '0000-00-00 00:00:00') {
            return '';
        }

        return Carbon::parse((string) $value)->format($this->school->dateFormat() ?: 'd/m/Y');
    }

    /**
     * CI topic status labels for syllabus status report.
     *
     * @return array<string, string>
     */
    public function topicStatusLabels(): array
    {
        return [
            '1' => (string) __('system.complete'),
            '0' => (string) __('system.incomplete'),
        ];
    }

    /**
     * CI Syllabus_model::get_subjectstatus — $subjectGroupSubjectId is subject_group_subjects.id.
     *
     * @return object{incomplete: int|string, complete: int|string, total: int|string}
     */
    public function subjectStatus(int $subjectGroupSubjectId, int $subjectGroupClassSectionsId): object
    {
        $row = DB::table('lesson')
            ->join('topic', 'lesson.id', '=', 'topic.lesson_id')
            ->where('lesson.subject_group_class_sections_id', $subjectGroupClassSectionsId)
            ->where('lesson.subject_group_subject_id', $subjectGroupSubjectId)
            ->selectRaw("COUNT(CASE WHEN topic.status = 0 THEN 1 ELSE NULL END) as incomplete")
            ->selectRaw("COUNT(CASE WHEN topic.status = 1 THEN 1 ELSE NULL END) as complete")
            ->selectRaw('COUNT(*) as total')
            ->first();

        return $row ?: (object) ['incomplete' => 0, 'complete' => 0, 'total' => 0];
    }

    /**
     * CI Subjectgroup_model::getGroupsubjects for a subject group.
     *
     * @return list<object>
     */
    public function groupSubjects(int $subjectGroupId): array
    {
        return DB::table('subject_group_subjects')
            ->join('subjects', 'subjects.id', '=', 'subject_group_subjects.subject_id')
            ->where('subject_group_subjects.subject_group_id', $subjectGroupId)
            ->where('subject_group_subjects.session_id', $this->lessonPlan->currentSessionId())
            ->orderBy('subject_group_subjects.id')
            ->select([
                'subject_group_subjects.id',
                'subject_group_subjects.subject_id',
                'subjects.name',
                'subjects.code',
            ])
            ->get()
            ->all();
    }

    /**
     * CI Report::lesson_plan result shape keyed by subject_group_subjects.id.
     *
     * @return array<int, array{
     *     lebel: string,
     *     complete: int,
     *     incomplete: int,
     *     id: int,
     *     total: int,
     *     name: string,
     *     lesson_summary: list<array<string, mixed>>
     * }>
     */
    public function syllabusStatusReport(int $classId, int $sectionId, int $subjectGroupId): array
    {
        $sgcs = $this->lessonPlan->subjectGroupClassSectionsId($classId, $sectionId, $subjectGroupId);
        if ($sgcs === null) {
            return [];
        }

        $sgcsId = (int) $sgcs['id'];
        $subjectsData = [];

        foreach ($this->groupSubjects($subjectGroupId) as $value) {
            $sgsId = (int) $value->id;
            $label = trim((string) $value->code) === ''
                ? (string) $value->name
                : $value->name.' ('.$value->code.')';

            $details = $this->subjectStatus($sgsId, $sgcsId);
            $total = (int) $details->total;
            if ($total !== 0) {
                $complete = ((int) $details->complete / $total) * 100;
                $incomplete = ((int) $details->incomplete / $total) * 100;
                $subjectsData[$sgsId] = [
                    'lebel' => $label,
                    'complete' => (int) round($complete),
                    'incomplete' => (int) round($incomplete),
                    'id' => $sgsId,
                    'total' => $total,
                    'name' => (string) $value->name,
                    'lesson_summary' => [],
                ];
            } else {
                $subjectsData[$sgsId] = [
                    'lebel' => $label,
                    'complete' => 0,
                    'incomplete' => 0,
                    'id' => $sgsId,
                    'total' => 0,
                    'name' => (string) $value->name,
                    'lesson_summary' => [],
                ];
            }

            $lessons = DB::table('lesson')
                ->where('subject_group_subject_id', $sgsId)
                ->where('subject_group_class_sections_id', $sgcsId)
                ->orderBy('id')
                ->get(['id', 'name']);

            $lessonResult = [];
            foreach ($lessons as $lesson) {
                $topics = DB::table('topic')
                    ->where('lesson_id', (int) $lesson->id)
                    ->orderBy('id')
                    ->get(['id', 'name', 'status', 'complete_date']);

                $topicData = [];
                $topicComplete = 0;
                foreach ($topics as $topic) {
                    if ((int) $topic->status === 1) {
                        $topicComplete++;
                    }
                    $topicData[] = [
                        'name' => (string) $topic->name,
                        'status' => (string) $topic->status,
                        'complete_date' => $topic->complete_date,
                    ];
                }

                $totalTopic = count($topicData);
                if ($totalTopic > 0) {
                    $incompletePercent = (int) round((($totalTopic - $topicComplete) / $totalTopic) * 100);
                    $completePercent = (int) round(($topicComplete / $totalTopic) * 100);
                } else {
                    $incompletePercent = 0;
                    $completePercent = 0;
                }

                $lessonResult[] = [
                    'name' => (string) $lesson->name,
                    'topics' => $topicData,
                    'incomplete_percent' => $incompletePercent,
                    'complete_percent' => $completePercent,
                ];
            }

            $subjectsData[$sgsId]['lesson_summary'] = $lessonResult;
        }

        return $subjectsData;
    }

    /**
     * CI Report::teachersyllabusstatus.
     *
     * @return array{
     *     subjects_data: array<int|string, array<string, mixed>>,
     *     subject_name: string,
     *     subject_complete: int
     * }
     */
    public function teacherSyllabusStatusReport(
        int $classId,
        int $sectionId,
        int $subjectGroupId,
        int $subjectGroupSubjectId,
    ): array {
        $sgcs = $this->lessonPlan->subjectGroupClassSectionsId($classId, $sectionId, $subjectGroupId);
        $subjectsData = [];
        $subjectName = '';
        $subjectComplete = 0;
        $teacherSummary = [];

        if ($sgcs === null) {
            return [
                'subjects_data' => $subjectsData,
                'subject_name' => $subjectName,
                'subject_complete' => $subjectComplete,
            ];
        }

        $sgcsId = (int) $sgcs['id'];
        $subjectGroupSubject = DB::table('subject_group_subjects')
            ->where('id', $subjectGroupSubjectId)
            ->first();
        $subjectdata = null;
        if ($subjectGroupSubject) {
            $subjectdata = DB::table('subjects')
                ->where('id', (int) $subjectGroupSubject->subject_id)
                ->first();
        }

        $details = $this->subjectStatus($subjectGroupSubjectId, $sgcsId);
        $total = (int) $details->total;

        if ($subjectdata && $total !== 0) {
            $complete = ((int) $details->complete / $total) * 100;
            $incomplete = ((int) $details->incomplete / $total) * 100;
            // Preserve CI inverted code-label branch.
            $label = ! empty($subjectdata->code)
                ? (string) $subjectdata->name
                : $subjectdata->name.' ('.$subjectdata->code.')';
            $subjectsData[(int) $subjectdata->id] = [
                'lebel' => $label,
                'complete' => (int) round($complete),
                'incomplete' => (int) round($incomplete),
                'id' => $subjectdata->id.'_'.$subjectdata->code,
                'teachers_summary' => [],
            ];
            $subjectComplete = (int) round($complete);
        } else {
            $subjectsData[0] = [
                'lebel' => 0,
                'complete' => 0,
                'incomplete' => 0,
                'id' => 0,
                'teachers_summary' => [],
            ];
            $subjectComplete = 0;
        }

        $teachersReport = DB::table('subject_syllabus')
            ->join('topic', 'topic.id', '=', 'subject_syllabus.topic_id')
            ->join('lesson', 'lesson.id', '=', 'topic.lesson_id')
            ->join('staff', 'staff.id', '=', 'subject_syllabus.created_for')
            ->join('subject_group_subjects', 'subject_group_subjects.id', '=', 'lesson.subject_group_subject_id')
            ->join('subject_groups', 'subject_groups.id', '=', 'subject_group_subjects.subject_group_id')
            ->join('subjects', 'subjects.id', '=', 'subject_group_subjects.subject_id')
            ->where('lesson.subject_group_subject_id', $subjectGroupSubjectId)
            ->where('lesson.subject_group_class_sections_id', $sgcsId)
            ->groupBy('subject_syllabus.created_for')
            ->selectRaw('GROUP_CONCAT(subject_syllabus.id) as subject_syllabus_id')
            ->selectRaw('CONCAT_WS(" ", MAX(staff.name), MAX(staff.surname), "(", MAX(staff.employee_id), ")") as name')
            ->selectRaw('COUNT(subject_syllabus.id) as total_priodes')
            ->selectRaw('MAX(subjects.name) as subject_name')
            ->selectRaw('MAX(subjects.code) as code')
            ->get();

        foreach ($teachersReport as $row) {
            $subjectName = trim((string) ($row->code ?? '')) === ''
                ? (string) $row->subject_name
                : $row->subject_name.' ('.$row->code.')';

            $syllabusIds = array_filter(explode(',', (string) $row->subject_syllabus_id), fn ($id) => $id !== '');
            $staffPeriodsData = [];
            foreach ($syllabusIds as $syllabusId) {
                $period = DB::table('subject_syllabus')
                    ->join('topic', 'topic.id', '=', 'subject_syllabus.topic_id')
                    ->join('lesson', 'lesson.id', '=', 'topic.lesson_id')
                    ->where('subject_syllabus.id', (int) $syllabusId)
                    ->select([
                        'subject_syllabus.*',
                        'lesson.name as lesson_name',
                        'topic.name as topic_name',
                    ])
                    ->first();
                if ($period) {
                    $staffPeriodsData[] = (array) $period;
                }
            }

            $teacherSummary[] = [
                'name' => (string) $row->name,
                'total_periods' => (int) $row->total_priodes,
                'summary_report' => $staffPeriodsData,
            ];
        }

        if ($subjectdata) {
            if (! isset($subjectsData[(int) $subjectdata->id])) {
                $subjectsData[(int) $subjectdata->id] = [
                    'lebel' => 0,
                    'complete' => 0,
                    'incomplete' => 0,
                    'id' => 0,
                    'teachers_summary' => [],
                ];
            }
            $subjectsData[(int) $subjectdata->id]['teachers_summary'] = $teacherSummary;
        }

        return [
            'subjects_data' => $subjectsData,
            'subject_name' => $subjectName,
            'subject_complete' => $subjectComplete,
        ];
    }
}
