<?php

namespace App\Modules\Students\Services;

use App\Modules\Academics\Services\CurrentSessionResolver;
use App\Modules\Shared\Services\SchoolContext;
use App\Modules\Students\Models\AlumniStudent;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * CI Alumni_model + Student_model alumni search — manage alumni contact details.
 * Deferred: events, mail/SMS, SaaS storage quota, custom-field columns, class-teacher scope.
 */
class AlumniService
{
    public function __construct(
        protected CurrentSessionResolver $currentSession,
        protected SchoolContext $school,
    ) {
    }

    public function settingOn(string $key): bool
    {
        return (int) $this->school->get($key, 1) === 1;
    }

    public function studentDisplayName(object $student): string
    {
        $first = trim((string) ($student->firstname ?? ''));
        $middle = trim((string) ($student->middlename ?? ''));
        $last = trim((string) ($student->lastname ?? ''));

        $name = $this->settingOn('middlename') && $middle !== ''
            ? trim($first.' '.$middle)
            : $first;
        if ($this->settingOn('lastname') && $last !== '') {
            $name = trim($name.' '.$last);
        }

        return $name !== '' ? $name : $first;
    }

    /**
     * @return array<int, object> keyed by student_id
     */
    public function alumniDetailsByStudentId(): array
    {
        $map = [];
        foreach (AlumniStudent::query()->get() as $row) {
            $map[(int) $row->student_id] = $row;
        }

        return $map;
    }

    public function findByStudentId(int $studentId): ?AlumniStudent
    {
        return AlumniStudent::query()->where('student_id', $studentId)->first();
    }

    /**
     * CI search_alumniStudent — pass-out session + is_alumni=1 + active students.
     *
     * @return Collection<int, object>
     */
    public function searchByFilter(int $sessionId, int $classId, ?int $sectionId = null): Collection
    {
        $sessionQuery = DB::table('student_session')
            ->where('is_alumni', 1)
            ->where('session_id', $sessionId)
            ->where('class_id', $classId);
        if ($sectionId !== null && $sectionId > 0) {
            $sessionQuery->where('section_id', $sectionId);
        }
        $studentIds = $sessionQuery->distinct()->pluck('student_id')->all();
        if ($studentIds === []) {
            return collect();
        }

        $students = DB::table('students')
            ->whereIn('id', $studentIds)
            ->where('is_active', 'yes')
            ->orderBy('admission_no')
            ->select([
                'id',
                'admission_no',
                'firstname',
                'middlename',
                'lastname',
                'gender',
                'dob',
                'image',
                'adhar_no',
            ])
            ->get();

        $labels = DB::table('student_session')
            ->join('classes', 'classes.id', '=', 'student_session.class_id')
            ->join('sections', 'sections.id', '=', 'student_session.section_id')
            ->where('student_session.is_alumni', 1)
            ->where('student_session.session_id', $sessionId)
            ->where('student_session.class_id', $classId)
            ->whereIn('student_session.student_id', $studentIds)
            ->select([
                'student_session.student_id',
                DB::raw('GROUP_CONCAT(classes.class, "(", sections.section, ")") as class_label'),
            ])
            ->groupBy('student_session.student_id')
            ->pluck('class_label', 'student_id');

        return $students->map(function ($student) use ($labels) {
            $student->class = (string) ($labels[$student->id] ?? '');

            return $student;
        });
    }

    /**
     * CI search_alumniStudentbyAdmissionNo — current session + admission_no LIKE.
     *
     * @return Collection<int, object>
     */
    public function searchByAdmissionNo(string $term): Collection
    {
        $sessionId = $this->currentSession->id();
        $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], trim($term)).'%';

        $studentIds = DB::table('students')
            ->join('student_session', 'student_session.student_id', '=', 'students.id')
            ->where('student_session.session_id', $sessionId)
            ->where('students.is_active', 'yes')
            ->where('student_session.is_alumni', 1)
            ->where('students.admission_no', 'like', $like)
            ->distinct()
            ->pluck('students.id')
            ->all();
        if ($studentIds === []) {
            return collect();
        }

        $students = DB::table('students')
            ->whereIn('id', $studentIds)
            ->orderBy('id')
            ->select([
                'id',
                'admission_no',
                'firstname',
                'middlename',
                'lastname',
                'gender',
                'dob',
                'image',
                'adhar_no',
            ])
            ->get();

        $labels = DB::table('student_session')
            ->join('classes', 'classes.id', '=', 'student_session.class_id')
            ->join('sections', 'sections.id', '=', 'student_session.section_id')
            ->where('student_session.session_id', $sessionId)
            ->where('student_session.is_alumni', 1)
            ->whereIn('student_session.student_id', $studentIds)
            ->select([
                'student_session.student_id',
                DB::raw('GROUP_CONCAT(classes.class, "(", sections.section, ")") as class_label'),
            ])
            ->groupBy('student_session.student_id')
            ->pluck('class_label', 'student_id');

        return $students->map(function ($student) use ($labels) {
            $student->class = (string) ($labels[$student->id] ?? '');

            return $student;
        });
    }

    public function save(int $studentId, array $data, ?UploadedFile $photo = null): AlumniStudent
    {
        $existing = $this->findByStudentId($studentId);
        $photoName = $existing?->photo ?? '';

        if ($photo !== null) {
            $photoName = $this->storePhoto($photo, $existing?->photo);
        }

        $payload = [
            'student_id' => $studentId,
            'current_email' => (string) ($data['current_email'] ?? ''),
            'current_phone' => (string) ($data['current_phone'] ?? ''),
            'occupation' => (string) ($data['occupation'] ?? ''),
            'address' => (string) ($data['address'] ?? ''),
            'photo' => (string) $photoName,
        ];

        if ($existing) {
            $existing->fill($payload);
            $existing->save();

            return $existing;
        }

        return AlumniStudent::query()->create($payload);
    }

    public function deleteByStudentId(int $studentId): void
    {
        $row = $this->findByStudentId($studentId);
        if ($row === null) {
            return;
        }

        $this->deletePhotoFile((string) $row->photo);
        $row->delete();
    }

    protected function storePhoto(UploadedFile $file, ?string $previous): string
    {
        $dir = public_path('uploads/alumni_student_images');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $name = 'alumni_'.uniqid('', true).'.'.$ext;
        $file->move($dir, $name);

        if ($previous) {
            $this->deletePhotoFile($previous);
        }

        return $name;
    }

    protected function deletePhotoFile(string $name): void
    {
        $safe = basename($name);
        if ($safe === '' || $safe !== $name) {
            return;
        }
        $path = public_path('uploads/alumni_student_images/'.$safe);
        if (is_file($path)) {
            @unlink($path);
        }
    }
}
