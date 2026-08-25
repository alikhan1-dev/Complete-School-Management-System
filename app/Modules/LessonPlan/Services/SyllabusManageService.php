<?php

namespace App\Modules\LessonPlan\Services;

use App\Modules\Academics\Services\CurrentSessionResolver;
use App\Modules\Homework\Services\HomeworkDocumentService;
use App\Modules\LessonPlan\Models\SubjectSyllabus;
use App\Modules\Shared\Services\ClassTeacherScopeService;
use App\Modules\Timetable\Services\ClassTimetableService;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * CI Syllabus weekly manage — subject_syllabus against teacher timetable slots.
 * Deferred: SaaS storage quota, CKEditor image modal.
 */
class SyllabusManageService
{
    public function __construct(
        protected CurrentSessionResolver $currentSession,
        protected ClassTimetableService $timetable,
        protected LessonPlanService $lessons,
        protected HomeworkDocumentService $filetypes,
        protected ClassTeacherScopeService $classTeacherScope,
    ) {
    }

    public function currentSessionId(): int
    {
        $id = $this->currentSession->id();
        if ($id <= 0) {
            throw new RuntimeException('Current academic session is not configured in sch_settings.');
        }

        return $id;
    }

    public function startWeekday(): string
    {
        return (string) (DB::table('sch_settings')->value('start_week') ?: 'Monday');
    }

    /**
     * Align date to the school week start (CI index / get_weekdates).
     * When $dateYmd is provided (prev/next nav), it is already a week-start date.
     */
    public function weekStart(?string $dateYmd = null): Carbon
    {
        if ($dateYmd) {
            return Carbon::parse($dateYmd)->startOfDay();
        }

        $startWeek = $this->startWeekday();
        $ts = strtotime('last '.$startWeek);
        if ($ts === false) {
            $ts = strtotime('last Monday');
        }
        if ((int) date('w', $ts) === (int) date('w')) {
            $ts += 7 * 86400;
        }

        return Carbon::createFromTimestamp($ts)->startOfDay();
    }

    /**
     * @return array{week_start: string, week_end: string, prev_week_start: string, next_week_start: string, days: list<string>}
     */
    public function weekMeta(?string $weekStartYmd = null): array
    {
        $start = $this->weekStart($weekStartYmd);
        $end = $start->copy()->addDays(6);
        $startWeek = $this->startWeekday();

        $prevTs = strtotime('last '.$startWeek, $start->getTimestamp());
        $nextTs = strtotime('next '.$startWeek, $start->getTimestamp());

        return [
            'week_start' => $start->toDateString(),
            'week_end' => $end->toDateString(),
            'prev_week_start' => date('Y-m-d', $prevTs !== false ? $prevTs : $start->copy()->subDays(7)->getTimestamp()),
            'next_week_start' => date('Y-m-d', $nextTs !== false ? $nextTs : $start->copy()->addDays(7)->getTimestamp()),
            'days' => $this->timetable->dayNames(),
        ];
    }

    /**
     * Teachers list (role_id 2) for admin picker.
     *
     * @return Collection<int, object>
     */
    public function teachers(): Collection
    {
        return $this->timetable->teachers();
    }

    /**
     * Weekly timetable grid keyed by day name → slots with optional syllabus id.
     *
     * @return array<string, list<array<string, mixed>>>
     */
    public function weekTimetable(int $staffId, string $weekStartYmd): array
    {
        $meta = $this->weekMeta($weekStartYmd);
        $sessionId = $this->currentSessionId();
        $grid = [];

        foreach ($meta['days'] as $index => $day) {
            $date = Carbon::parse($meta['week_start'])->addDays($index)->toDateString();
            $slots = $this->slotsForStaffDay($staffId, $day, $sessionId);
            $rows = [];
            foreach ($slots as $slot) {
                $sgcs = $this->lessons->subjectGroupClassSectionsId(
                    (int) $slot->class_id,
                    (int) $slot->section_id,
                    (int) $slot->subject_group_id
                );
                $sgcsId = $sgcs ? (int) $sgcs['id'] : 0;
                $syllabusId = 0;
                if ($sgcsId > 0) {
                    $syllabusId = $this->findSyllabusIdForSlot(
                        (int) $slot->subject_group_subject_id,
                        $date,
                        (string) $slot->time_from,
                        (string) $slot->time_to,
                        $sgcsId,
                        $staffId
                    );
                }

                $rows[] = [
                    'class' => $slot->class,
                    'section' => $slot->section,
                    'class_id' => (int) $slot->class_id,
                    'section_id' => (int) $slot->section_id,
                    'subject_group_id' => (int) $slot->subject_group_id,
                    'subject_group_subject_id' => (int) $slot->subject_group_subject_id,
                    'subject_name' => $slot->subject_name,
                    'subject_code' => $slot->subject_code,
                    'time_from' => (string) $slot->time_from,
                    'time_to' => (string) $slot->time_to,
                    'room_no' => (string) ($slot->room_no ?? ''),
                    'subject_group_class_sections_id' => $sgcsId,
                    'date' => $date,
                    'syllabus_id' => $syllabusId,
                ];
            }
            $grid[$day] = $rows;
        }

        return $grid;
    }

    /**
     * CI Subjecttimetable_model::getSyllabussubject.
     * When the logged-in viewer is a restricted class teacher, filter slots to their
     * class/section matrix (even when viewing another staff member's week).
     * Empty matrix → no filter (CI !empty quirk).
     *
     * @return Collection<int, object>
     */
    public function slotsForStaffDay(int $staffId, string $day, ?int $sessionId = null): Collection
    {
        $sessionId = $sessionId ?: $this->currentSessionId();

        $query = DB::table('subject_timetable')
            ->join('classes', 'classes.id', '=', 'subject_timetable.class_id')
            ->join('sections', 'sections.id', '=', 'subject_timetable.section_id')
            ->join('subject_group_subjects', 'subject_group_subjects.id', '=', 'subject_timetable.subject_group_subject_id')
            ->join('subjects as sub', 'sub.id', '=', 'subject_group_subjects.subject_id')
            ->where('subject_timetable.session_id', $sessionId)
            ->where('subject_group_subjects.session_id', $sessionId)
            ->where('subject_timetable.day', $day)
            ->where('subject_timetable.staff_id', $staffId);

        if ($this->classTeacherScope->isRestricted()) {
            $matrix = $this->classTeacherScope->myClassSectionMap();
            if ($matrix !== []) {
                $query->where(function ($outer) use ($matrix) {
                    foreach ($matrix as $classId => $sectionIds) {
                        foreach ($sectionIds as $sectionId) {
                            $outer->orWhere(function ($inner) use ($classId, $sectionId) {
                                $inner->where('subject_timetable.class_id', (int) $classId)
                                    ->where('subject_timetable.section_id', (int) $sectionId);
                            });
                        }
                    }
                });
            }
        }

        return $query
            ->orderBy('subject_timetable.start_time')
            ->select([
                'subject_timetable.*',
                'classes.class',
                'sections.section',
                'sub.name as subject_name',
                'sub.code as subject_code',
            ])
            ->get();
    }

    public function findSyllabusIdForSlot(
        int $subjectGroupSubjectId,
        string $date,
        string $timeFrom,
        string $timeTo,
        int $subjectGroupClassSectionsId,
        int $staffId,
    ): int {
        $row = DB::table('subject_syllabus')
            ->join('topic', 'topic.id', '=', 'subject_syllabus.topic_id')
            ->join('lesson', 'lesson.id', '=', 'topic.lesson_id')
            ->where('lesson.subject_group_subject_id', $subjectGroupSubjectId)
            ->where('lesson.subject_group_class_sections_id', $subjectGroupClassSectionsId)
            ->where('subject_syllabus.date', $date)
            ->where('subject_syllabus.time_from', $timeFrom)
            ->where('subject_syllabus.time_to', $timeTo)
            ->where('subject_syllabus.created_for', $staffId)
            ->select('subject_syllabus.id')
            ->first();

        return $row ? (int) $row->id : 0;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findDetailed(int $id): ?array
    {
        $row = DB::table('subject_syllabus')
            ->join('topic', 'topic.id', '=', 'subject_syllabus.topic_id')
            ->join('lesson', 'lesson.id', '=', 'topic.lesson_id')
            ->join('subject_group_subjects', 'subject_group_subjects.id', '=', 'lesson.subject_group_subject_id')
            ->join('subject_groups', 'subject_groups.id', '=', 'subject_group_subjects.subject_group_id')
            ->join('subjects', 'subjects.id', '=', 'subject_group_subjects.subject_id')
            ->join('subject_group_class_sections', 'subject_group_class_sections.id', '=', 'lesson.subject_group_class_sections_id')
            ->join('class_sections', 'class_sections.id', '=', 'subject_group_class_sections.class_section_id')
            ->join('sections', 'sections.id', '=', 'class_sections.section_id')
            ->join('classes', 'classes.id', '=', 'class_sections.class_id')
            ->where('subject_syllabus.id', $id)
            ->where('subject_syllabus.session_id', $this->currentSessionId())
            ->select([
                'subject_syllabus.*',
                'topic.name as topic_name',
                'lesson.name as lessonname',
                'lesson.id as lesson_id',
                'lesson.subject_group_subject_id',
                'lesson.subject_group_class_sections_id',
                'subject_groups.name as sgname',
                'subjects.name as subname',
                'subjects.code as scode',
                'sections.section as sname',
                'classes.class as cname',
            ])
            ->first();

        return $row ? (array) $row : null;
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function save(
        array $input,
        int $staffId,
        ?UploadedFile $attachment = null,
        ?UploadedFile $lectureVideo = null,
    ): SubjectSyllabus {
        $topicId = (int) ($input['topic_id'] ?? 0);
        $topic = DB::table('topic')->where('id', $topicId)->first();
        if ($topic === null) {
            throw ValidationException::withMessages(['topic_id' => 'Topic is required.']);
        }

        $id = (int) ($input['subject_syllabusid'] ?? $input['id'] ?? 0);
        $payload = [
            'session_id' => $this->currentSessionId(),
            'topic_id' => $topicId,
            'date' => Carbon::parse((string) $input['date'])->toDateString(),
            'time_from' => (string) $input['time_from'],
            'time_to' => (string) $input['time_to'],
            'presentation' => (string) ($input['presentation'] ?? ''),
            'sub_topic' => (string) ($input['sub_topic'] ?? ''),
            'teaching_method' => (string) ($input['teaching_method'] ?? ''),
            'general_objectives' => (string) ($input['general_objectives'] ?? ''),
            'previous_knowledge' => (string) ($input['previous_knowledge'] ?? ''),
            'comprehensive_questions' => (string) ($input['comprehensive_questions'] ?? ''),
            'lacture_youtube_url' => (string) ($input['lacture_youtube_url'] ?? ''),
            'created_for' => (int) ($input['created_for'] ?? $staffId),
            'created_by' => $staffId,
        ];

        if ($attachment !== null) {
            $payload['attachment'] = $this->storeAttachment($attachment);
        }
        if ($lectureVideo !== null) {
            $payload['lacture_video'] = $this->storeLectureVideo($lectureVideo);
        }

        if ($id > 0) {
            $row = SubjectSyllabus::query()->findOrFail($id);
            if (isset($payload['attachment']) && $row->attachment) {
                $this->deleteAttachment((string) $row->attachment);
            }
            if (isset($payload['lacture_video']) && $row->lacture_video) {
                $this->deleteLectureVideo((string) $row->lacture_video);
            }
            unset($payload['created_by']);
            $row->fill($payload)->save();

            return $row->fresh();
        }

        $payload['attachment'] = $payload['attachment'] ?? '';
        $payload['lacture_video'] = $payload['lacture_video'] ?? '';
        $payload['status'] = 0;

        return SubjectSyllabus::query()->create($payload);
    }

    public function delete(int $id): void
    {
        $row = SubjectSyllabus::query()->find($id);
        if ($row === null) {
            return;
        }
        if ($row->attachment) {
            $this->deleteAttachment((string) $row->attachment);
        }
        if ($row->lacture_video) {
            $this->deleteLectureVideo((string) $row->lacture_video);
        }
        $row->delete();
    }

    public function attachmentDirectory(): string
    {
        return public_path('uploads/syllabus_attachment');
    }

    public function lectureVideoDirectory(): string
    {
        return public_path('uploads/syllabus_attachment/lacture_video');
    }

    public function storeAttachment(UploadedFile $file): string
    {
        $dir = $this->attachmentDirectory();
        File::ensureDirectoryExists($dir);
        $original = basename((string) $file->getClientOriginalName());
        $saved = time().'-'.uniqid((string) random_int(1000, 9999), false).'!'.$original;
        $file->move($dir, $saved);

        return $saved;
    }

    public function storeLectureVideo(UploadedFile $file): string
    {
        $dir = $this->lectureVideoDirectory();
        File::ensureDirectoryExists($dir);
        $original = basename((string) $file->getClientOriginalName());
        $saved = time().'-'.uniqid((string) random_int(1000, 9999), false).'!'.$original;
        $file->move($dir, $saved);

        return $saved;
    }

    public function deleteAttachment(?string $filename): void
    {
        if ($filename === null || $filename === '') {
            return;
        }
        $safe = basename($filename);
        $path = $this->attachmentDirectory().DIRECTORY_SEPARATOR.$safe;
        if (File::isFile($path)) {
            File::delete($path);
        }
    }

    public function deleteLectureVideo(?string $filename): void
    {
        if ($filename === null || $filename === '') {
            return;
        }
        $safe = basename($filename);
        $path = $this->lectureVideoDirectory().DIRECTORY_SEPARATOR.$safe;
        if (File::isFile($path)) {
            File::delete($path);
        }
    }

    public function downloadAttachment(string $filename): BinaryFileResponse
    {
        $safe = basename($filename);
        abort_unless($safe !== '' && $safe === $filename && ! str_contains($safe, '..'), 404);
        $path = $this->attachmentDirectory().DIRECTORY_SEPARATOR.$safe;
        abort_unless(File::isFile($path), 404);

        return response()->download($path, $this->displayName($safe));
    }

    public function downloadLectureVideo(string $filename): BinaryFileResponse
    {
        $safe = basename($filename);
        abort_unless($safe !== '' && $safe === $filename && ! str_contains($safe, '..'), 404);
        $path = $this->lectureVideoDirectory().DIRECTORY_SEPARATOR.$safe;
        abort_unless(File::isFile($path), 404);

        return response()->download($path, $this->displayName($safe));
    }

    private function displayName(string $stored): string
    {
        $pos = strpos($stored, '!');

        return $pos === false ? $stored : substr($stored, $pos + 1);
    }

    /**
     * @return array{extensions: list<string>, max_kb: int}
     */
    public function uploadMeta(): array
    {
        return $this->filetypes->uploadRulesFromFiletypes();
    }
}
