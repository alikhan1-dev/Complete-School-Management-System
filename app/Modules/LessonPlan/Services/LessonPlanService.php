<?php

namespace App\Modules\LessonPlan\Services;

use App\Modules\Academics\Services\CurrentSessionResolver;
use App\Modules\LessonPlan\Models\Lesson;
use App\Modules\LessonPlan\Models\Topic;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * CI Lessonplan_model — lesson + topic CRUD helpers.
 * Deferred: copy old lesson, weekly syllabus manage, forum, class-teacher auth, DataTables AJAX.
 */
class LessonPlanService
{
    public function __construct(
        protected CurrentSessionResolver $currentSession,
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

    /**
     * CI getsubject_group_class_sectionsId.
     *
     * @return array<string, mixed>|null
     */
    public function subjectGroupClassSectionsId(int $classId, int $sectionId, int $subjectGroupId, ?int $sessionId = null): ?array
    {
        $sessionId = $sessionId ?: $this->currentSessionId();

        $row = DB::table('subject_group_class_sections')
            ->join('class_sections', 'class_sections.id', '=', 'subject_group_class_sections.class_section_id')
            ->join('subject_groups', 'subject_groups.id', '=', 'subject_group_class_sections.subject_group_id')
            ->where('class_sections.class_id', $classId)
            ->where('class_sections.section_id', $sectionId)
            ->where('subject_groups.id', $subjectGroupId)
            ->where('subject_groups.session_id', $sessionId)
            ->orderByDesc('subject_groups.id')
            ->select('subject_groups.name', 'subject_group_class_sections.*')
            ->first();

        return $row ? (array) $row : null;
    }

    /**
     * Grouped lesson list for current session (CI get without id).
     *
     * @return list<array<string, mixed>>
     */
    public function listLessonGroups(?int $sessionId = null): array
    {
        $sessionId = $sessionId ?: $this->currentSessionId();

        return DB::table('lesson')
            ->join('subject_group_subjects', 'subject_group_subjects.id', '=', 'lesson.subject_group_subject_id')
            ->join('subject_groups', 'subject_groups.id', '=', 'subject_group_subjects.subject_group_id')
            ->join('subjects', 'subjects.id', '=', 'subject_group_subjects.subject_id')
            ->join('subject_group_class_sections', 'subject_group_class_sections.id', '=', 'lesson.subject_group_class_sections_id')
            ->join('class_sections', 'class_sections.id', '=', 'subject_group_class_sections.class_section_id')
            ->join('sections', 'sections.id', '=', 'class_sections.section_id')
            ->join('classes', 'classes.id', '=', 'class_sections.class_id')
            ->where('lesson.session_id', $sessionId)
            ->groupBy([
                'lesson.subject_group_subject_id',
                'lesson.subject_group_class_sections_id',
                'subject_groups.name',
                'subjects.name',
                'subjects.code',
                'sections.section',
                'sections.id',
                'subject_groups.id',
                'subjects.id',
                'class_sections.id',
                'classes.class',
                'classes.id',
            ])
            ->orderByDesc(DB::raw('MAX(lesson.id)'))
            ->select([
                'lesson.subject_group_subject_id',
                'lesson.subject_group_class_sections_id',
                'subject_groups.name as sgname',
                'subjects.name as subname',
                'subjects.code as subjects_code',
                'sections.section as sname',
                'sections.id as sectionid',
                'subject_groups.id as subjectgroupsid',
                'subjects.id as subjectid',
                'class_sections.id as csectionid',
                'classes.class as cname',
                'classes.id as classid',
            ])
            ->get()
            ->map(fn ($r) => (array) $r)
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function lessonsForSubject(int $subjectGroupSubjectId, int $subjectGroupClassSectionsId, ?int $sessionId = null): array
    {
        $sessionId = $sessionId ?: $this->currentSessionId();

        return Lesson::query()
            ->where('subject_group_subject_id', $subjectGroupSubjectId)
            ->where('subject_group_class_sections_id', $subjectGroupClassSectionsId)
            ->where('session_id', $sessionId)
            ->orderBy('id')
            ->get()
            ->map(fn ($r) => $r->toArray())
            ->all();
    }

    /**
     * @param  list<string>  $names
     */
    public function createLessons(int $classId, int $sectionId, int $subjectGroupId, int $subjectGroupSubjectId, array $names): void
    {
        $sgcs = $this->subjectGroupClassSectionsId($classId, $sectionId, $subjectGroupId);
        if ($sgcs === null) {
            throw ValidationException::withMessages([
                'subject_group_id' => 'Subject group is not assigned to this class section.',
            ]);
        }

        $names = array_values(array_filter(array_map('trim', $names), fn ($n) => $n !== ''));
        if ($names === []) {
            throw ValidationException::withMessages([
                'lessons' => 'Lesson name field is required',
            ]);
        }

        $sessionId = $this->currentSessionId();
        foreach ($names as $name) {
            Lesson::query()->create([
                'subject_group_subject_id' => $subjectGroupSubjectId,
                'name' => $name,
                'subject_group_class_sections_id' => (int) $sgcs['id'],
                'session_id' => $sessionId,
            ]);
        }
    }

    /**
     * @param  array<int, string>  $updates  lesson_id => name
     * @param  list<int>  $deleteIds
     * @param  list<string>  $newNames
     */
    public function updateLessons(
        int $classId,
        int $sectionId,
        int $subjectGroupId,
        int $subjectGroupSubjectId,
        array $updates,
        array $deleteIds,
        array $newNames,
    ): void {
        $sgcs = $this->subjectGroupClassSectionsId($classId, $sectionId, $subjectGroupId);
        if ($sgcs === null) {
            throw ValidationException::withMessages([
                'subject_group_id' => 'Subject group is not assigned to this class section.',
            ]);
        }

        $sessionId = $this->currentSessionId();
        $sgcsId = (int) $sgcs['id'];

        foreach ($deleteIds as $deleteId) {
            $this->deleteLesson((int) $deleteId);
        }

        foreach ($updates as $lessonId => $name) {
            $name = trim((string) $name);
            if ($name === '') {
                continue;
            }
            Lesson::query()
                ->where('id', (int) $lessonId)
                ->where('session_id', $sessionId)
                ->update([
                    'name' => $name,
                    'subject_group_subject_id' => $subjectGroupSubjectId,
                    'subject_group_class_sections_id' => $sgcsId,
                ]);
        }

        foreach (array_filter(array_map('trim', $newNames)) as $name) {
            Lesson::query()->create([
                'subject_group_subject_id' => $subjectGroupSubjectId,
                'name' => $name,
                'subject_group_class_sections_id' => $sgcsId,
                'session_id' => $sessionId,
            ]);
        }
    }

    public function deleteLesson(int $id): void
    {
        $sessionId = $this->currentSessionId();
        Topic::query()->where('lesson_id', $id)->where('session_id', $sessionId)->delete();
        Lesson::query()->where('id', $id)->where('session_id', $sessionId)->delete();
    }

    public function deleteLessonBulk(int $subjectGroupClassSectionsId, int $subjectGroupSubjectId): void
    {
        $sessionId = $this->currentSessionId();
        $lessonIds = Lesson::query()
            ->where('subject_group_class_sections_id', $subjectGroupClassSectionsId)
            ->where('subject_group_subject_id', $subjectGroupSubjectId)
            ->where('session_id', $sessionId)
            ->pluck('id')
            ->all();

        if ($lessonIds !== []) {
            Topic::query()->whereIn('lesson_id', $lessonIds)->where('session_id', $sessionId)->delete();
            Lesson::query()->whereIn('id', $lessonIds)->delete();
        }
    }

    /**
     * Grouped topic list (CI gettopic).
     *
     * @return list<array<string, mixed>>
     */
    public function listTopicGroups(?int $sessionId = null): array
    {
        $sessionId = $sessionId ?: $this->currentSessionId();

        return DB::table('topic')
            ->join('lesson', 'lesson.id', '=', 'topic.lesson_id')
            ->join('subject_group_subjects', 'subject_group_subjects.id', '=', 'lesson.subject_group_subject_id')
            ->join('subject_groups', 'subject_groups.id', '=', 'subject_group_subjects.subject_group_id')
            ->join('subjects', 'subjects.id', '=', 'subject_group_subjects.subject_id')
            ->join('subject_group_class_sections', 'subject_group_class_sections.id', '=', 'lesson.subject_group_class_sections_id')
            ->join('class_sections', 'class_sections.id', '=', 'subject_group_class_sections.class_section_id')
            ->join('sections', 'sections.id', '=', 'class_sections.section_id')
            ->join('classes', 'classes.id', '=', 'class_sections.class_id')
            ->where('topic.session_id', $sessionId)
            ->groupBy([
                'topic.lesson_id',
                'lesson.name',
                'lesson.subject_group_class_sections_id',
                'lesson.subject_group_subject_id',
                'subject_groups.name',
                'subjects.name',
                'sections.section',
                'sections.id',
                'subject_groups.id',
                'subjects.id',
                'classes.class',
                'classes.id',
            ])
            ->orderByDesc(DB::raw('MAX(topic.id)'))
            ->select([
                'topic.lesson_id',
                'lesson.name as lessonname',
                'lesson.subject_group_class_sections_id',
                'lesson.subject_group_subject_id',
                'subject_groups.name as sgname',
                'subjects.name as subname',
                'sections.section as sname',
                'sections.id as sectionid',
                'subject_groups.id as subjectgroupsid',
                'subjects.id as subjectid',
                'classes.class as cname',
                'classes.id as classid',
            ])
            ->get()
            ->map(fn ($r) => (array) $r)
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function topicsByLesson(int $lessonId, ?int $sessionId = null): array
    {
        $sessionId = $sessionId ?: $this->currentSessionId();

        return Topic::query()
            ->where('lesson_id', $lessonId)
            ->where('session_id', $sessionId)
            ->orderBy('id')
            ->get()
            ->map(fn ($r) => $r->toArray())
            ->all();
    }

    /**
     * @param  list<string>  $names
     */
    public function createTopics(int $lessonId, array $names): void
    {
        $names = array_values(array_filter(array_map('trim', $names), fn ($n) => $n !== ''));
        if ($names === []) {
            throw ValidationException::withMessages([
                'topic' => 'Topic name field is required',
            ]);
        }

        $sessionId = $this->currentSessionId();
        foreach ($names as $name) {
            Topic::query()->create([
                'lesson_id' => $lessonId,
                'name' => $name,
                'session_id' => $sessionId,
                'status' => 0,
                'complete_date' => null,
            ]);
        }
    }

    /**
     * @param  array<int, string>  $updates
     * @param  list<int>  $deleteIds
     * @param  list<string>  $newNames
     */
    public function updateTopics(int $lessonId, array $updates, array $deleteIds, array $newNames): void
    {
        $sessionId = $this->currentSessionId();

        foreach ($deleteIds as $deleteId) {
            Topic::query()->where('id', (int) $deleteId)->where('session_id', $sessionId)->delete();
        }

        foreach ($updates as $topicId => $name) {
            $name = trim((string) $name);
            if ($name === '') {
                continue;
            }
            Topic::query()
                ->where('id', (int) $topicId)
                ->where('session_id', $sessionId)
                ->update(['name' => $name, 'lesson_id' => $lessonId]);
        }

        foreach (array_filter(array_map('trim', $newNames)) as $name) {
            Topic::query()->create([
                'lesson_id' => $lessonId,
                'name' => $name,
                'session_id' => $sessionId,
                'status' => 0,
                'complete_date' => null,
            ]);
        }
    }

    public function deleteTopicsByLesson(int $lessonId): void
    {
        Topic::query()
            ->where('lesson_id', $lessonId)
            ->where('session_id', $this->currentSessionId())
            ->delete();
    }

    public function changeTopicStatus(int $id, int $status, ?string $completeDate = null): void
    {
        $payload = [
            'status' => $status,
            'complete_date' => $status === 1 ? ($completeDate ?: null) : null,
        ];
        Topic::query()->where('id', $id)->update($payload);
    }

    /**
     * Syllabus status tree: lessons with topics for class/section/group/subject.
     *
     * @return array{subject_name: string, lessons: array<int, array<string, mixed>>}
     */
    public function syllabusStatus(int $classId, int $sectionId, int $subjectGroupId, int $subjectGroupSubjectId): array
    {
        $subject = DB::table('subject_group_subjects')
            ->join('subjects', 'subjects.id', '=', 'subject_group_subjects.subject_id')
            ->where('subject_group_subjects.id', $subjectGroupSubjectId)
            ->select('subjects.name', 'subjects.code')
            ->first();

        $sgcs = $this->subjectGroupClassSectionsId($classId, $sectionId, $subjectGroupId);
        $lessons = [];
        if ($sgcs !== null) {
            $lessonRows = $this->lessonsForSubject($subjectGroupSubjectId, (int) $sgcs['id']);
            foreach ($lessonRows as $lesson) {
                $lesson['topic'] = $this->topicsByLesson((int) $lesson['id']);
                $lessons[(int) $lesson['id']] = $lesson;
            }
        }

        $subjectName = $subject
            ? (trim((string) $subject->name).(! empty($subject->code) ? ' ('.$subject->code.')' : ''))
            : '';

        return [
            'subject_name' => $subjectName,
            'lessons' => $lessons,
        ];
    }

    /**
     * Load lesson/topic tree from a past session (CI copylesson search).
     *
     * @return array{subject_name: string, lessons: array<int, array<string, mixed>>, no_record: string}
     */
    public function loadOldSyllabus(
        int $oldSessionId,
        int $classId,
        int $sectionId,
        int $subjectGroupId,
        int $subjectGroupSubjectId,
    ): array {
        $subject = DB::table('subject_group_subjects')
            ->join('subjects', 'subjects.id', '=', 'subject_group_subjects.subject_id')
            ->where('subject_group_subjects.id', $subjectGroupSubjectId)
            ->select('subjects.name', 'subjects.code')
            ->first();

        $sgcs = $this->subjectGroupClassSectionsId($classId, $sectionId, $subjectGroupId, $oldSessionId);
        $lessons = [];
        $noRecord = '1';

        if ($sgcs !== null) {
            $lessonRows = $this->lessonsForSubject($subjectGroupSubjectId, (int) $sgcs['id'], $oldSessionId);
            foreach ($lessonRows as $lesson) {
                $noRecord = '2';
                $lesson['topic'] = $this->topicsByLesson((int) $lesson['id'], $oldSessionId);
                $lessons[(int) $lesson['id']] = $lesson;
            }
        }

        $subjectName = '';
        if ($subject) {
            $subjectName = trim((string) $subject->name);
            if (! empty($subject->code)) {
                $subjectName .= ' ('.$subject->code.')';
            }
        }

        return [
            'subject_name' => $subjectName,
            'lessons' => $lessons,
            'no_record' => $noRecord,
        ];
    }

    /**
     * CI saveCopyLesson / add_copy_lesson — copy checked topics into current session.
     *
     * @param  array<int, list<int>>  $topicIdsByLessonId  old lesson_id => topic ids
     */
    public function copySelectedTopics(
        array $topicIdsByLessonId,
        int $classId,
        int $sectionId,
        int $subjectGroupId,
        int $subjectGroupSubjectId,
    ): void {
        if ($topicIdsByLessonId === []) {
            throw ValidationException::withMessages([
                'topic_id' => 'The topic field is required.',
            ]);
        }

        $sgcs = $this->subjectGroupClassSectionsId($classId, $sectionId, $subjectGroupId);
        if ($sgcs === null) {
            throw ValidationException::withMessages([
                'subject_group_id' => 'Subject group is not assigned to this class section.',
            ]);
        }

        $sessionId = $this->currentSessionId();
        $sgcsId = (int) $sgcs['id'];

        /** @var array<int, array{name: string, topics: list<string>}> $payload */
        $payload = [];

        foreach ($topicIdsByLessonId as $oldLessonId => $topicIds) {
            foreach ($topicIds as $topicId) {
                $topicId = (int) $topicId;
                if ($topicId <= 0) {
                    continue;
                }

                $row = DB::table('topic')
                    ->join('lesson', 'lesson.id', '=', 'topic.lesson_id')
                    ->where('topic.id', $topicId)
                    ->select('topic.name as topic_name', 'lesson.name as lessonname', 'lesson.id as lesson_id')
                    ->first();

                if ($row === null) {
                    continue;
                }

                $oldLessonKey = (int) ($oldLessonId ?: $row->lesson_id);
                if (! isset($payload[$oldLessonKey])) {
                    $payload[$oldLessonKey] = [
                        'name' => (string) $row->lessonname,
                        'topics' => [],
                    ];
                }
                $payload[$oldLessonKey]['topics'][] = (string) $row->topic_name;
            }
        }

        if ($payload === []) {
            throw ValidationException::withMessages([
                'topic_id' => 'The topic field is required.',
            ]);
        }

        DB::transaction(function () use ($payload, $subjectGroupSubjectId, $sgcsId, $sessionId) {
            foreach ($payload as $lessonData) {
                $lesson = Lesson::query()->create([
                    'subject_group_subject_id' => $subjectGroupSubjectId,
                    'name' => $lessonData['name'],
                    'subject_group_class_sections_id' => $sgcsId,
                    'session_id' => $sessionId,
                ]);

                foreach ($lessonData['topics'] as $topicName) {
                    Topic::query()->create([
                        'lesson_id' => $lesson->id,
                        'name' => $topicName,
                        'session_id' => $sessionId,
                        'status' => 0,
                        'complete_date' => null,
                    ]);
                }
            }
        });
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findLessonGroup(int $subjectGroupClassSectionsId, int $subjectGroupSubjectId): ?array
    {
        $rows = array_values(array_filter(
            $this->listLessonGroups(),
            fn ($r) => (int) $r['subject_group_class_sections_id'] === $subjectGroupClassSectionsId
                && (int) $r['subject_group_subject_id'] === $subjectGroupSubjectId
        ));

        return $rows[0] ?? null;
    }
}
