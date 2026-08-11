<?php

namespace App\Modules\Students\Services;

use App\Modules\Academics\Services\CurrentSessionResolver;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Student search / DataTables queries scoped to current academic session
 * (CI Student_model::searchdtByClassSection / searchFullText simplified).
 */
class StudentSearchService
{
    public function __construct(protected CurrentSessionResolver $currentSession)
    {
    }

    /**
     * @return Collection<int, object>
     */
    public function searchByClassSection(?int $classId, ?int $sectionId): Collection
    {
        $sessionId = (int) $this->currentSession->id();

        $query = DB::table('students')
            ->join('student_session', function ($join) use ($sessionId) {
                $join->on('student_session.student_id', '=', 'students.id')
                    ->where('student_session.session_id', '=', $sessionId)
                    ->whereRaw("student_session.id = (
                        SELECT MIN(s2.id) FROM student_session s2
                        WHERE s2.student_id = students.id AND s2.session_id = {$sessionId}
                    )");
            })
            ->join('classes', 'student_session.class_id', '=', 'classes.id')
            ->join('sections', 'sections.id', '=', 'student_session.section_id')
            ->leftJoin('categories', 'students.category_id', '=', 'categories.id')
            ->where('students.is_active', 'yes')
            ->select([
                'students.id',
                'students.admission_no',
                'students.roll_no',
                'students.firstname',
                'students.middlename',
                'students.lastname',
                'students.father_name',
                'students.dob',
                'students.gender',
                'students.mobileno',
                'students.email',
                'student_session.id as student_session_id',
                'classes.id as class_id',
                'classes.class',
                'sections.id as section_id',
                'sections.section',
                DB::raw("IFNULL(categories.category, '') as category"),
                DB::raw("CONCAT(classes.class, '(', sections.section, ')') as class_section_list"),
            ])
            ->orderByDesc('students.id');

        if ($classId) {
            $query->where('student_session.class_id', $classId);
        }
        if ($sectionId) {
            $query->where('student_session.section_id', $sectionId);
        }

        return $query->get();
    }

    /**
     * @return Collection<int, object>
     */
    public function searchFullText(string $term): Collection
    {
        $sessionId = (int) $this->currentSession->id();
        $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $term).'%';

        return DB::table('students')
            ->join('student_session', function ($join) use ($sessionId) {
                $join->on('student_session.student_id', '=', 'students.id')
                    ->where('student_session.session_id', '=', $sessionId)
                    ->whereRaw("student_session.id = (
                        SELECT MIN(s2.id) FROM student_session s2
                        WHERE s2.student_id = students.id AND s2.session_id = {$sessionId}
                    )");
            })
            ->join('classes', 'student_session.class_id', '=', 'classes.id')
            ->join('sections', 'sections.id', '=', 'student_session.section_id')
            ->leftJoin('categories', 'students.category_id', '=', 'categories.id')
            ->where('students.is_active', 'yes')
            ->where(function ($q) use ($like) {
                foreach ([
                    'students.firstname', 'students.middlename', 'students.lastname',
                    'students.guardian_name', 'students.adhar_no', 'students.samagra_id',
                    'students.roll_no', 'students.admission_no', 'students.mobileno',
                    'students.email', 'students.religion', 'students.gender',
                    'students.current_address', 'students.permanent_address',
                    'students.bank_name', 'students.ifsc_code', 'students.father_name',
                    'students.guardian_relation', 'students.guardian_phone', 'students.guardian_address',
                ] as $col) {
                    $q->orWhere($col, 'like', $like);
                }
            })
            ->select([
                'students.id',
                'students.admission_no',
                'students.roll_no',
                'students.firstname',
                'students.middlename',
                'students.lastname',
                'students.father_name',
                'students.dob',
                'students.gender',
                'students.mobileno',
                'students.email',
                'student_session.id as student_session_id',
                'classes.id as class_id',
                'classes.class',
                'sections.id as section_id',
                'sections.section',
                DB::raw("IFNULL(categories.category, '') as category"),
                DB::raw("CONCAT(classes.class, '(', sections.section, ')') as class_section_list"),
            ])
            ->orderByDesc('students.id')
            ->get();
    }

    /**
     * Profile payload similar to CI Student_model::get($id).
     */
    public function findForView(int $studentId): ?object
    {
        $sessionId = $this->currentSession->id();

        return DB::table('students')
            ->join('student_session', function ($join) use ($sessionId) {
                $join->on('student_session.student_id', '=', 'students.id')
                    ->where('student_session.session_id', '=', $sessionId);
            })
            ->join('classes', 'student_session.class_id', '=', 'classes.id')
            ->join('sections', 'sections.id', '=', 'student_session.section_id')
            ->leftJoin('users', function ($join) {
                $join->on('users.user_id', '=', 'students.id')
                    ->where('users.role', '=', 'student');
            })
            ->leftJoin('categories', 'students.category_id', '=', 'categories.id')
            ->where('students.id', $studentId)
            ->orderBy('student_session.id')
            ->select([
                'students.*',
                'student_session.id as student_session_id',
                'student_session.class_id',
                'student_session.section_id',
                'student_session.session_id',
                'classes.class',
                'sections.section',
                'users.username',
                'users.id as user_tbl_id',
                DB::raw("IFNULL(categories.category, '') as category"),
            ])
            ->first();
    }

    /**
     * CI Student_model::getMySiblings — other active students sharing parent_id in current session.
     *
     * @return Collection<int, object>
     */
    public function siblingsOf(int $parentId, int $excludeStudentId): Collection
    {
        if ($parentId <= 0) {
            return collect();
        }

        $sessionId = (int) $this->currentSession->id();

        return DB::table('students')
            ->join('student_session', function ($join) use ($sessionId) {
                $join->on('student_session.student_id', '=', 'students.id')
                    ->where('student_session.session_id', '=', $sessionId);
            })
            ->join('classes', 'student_session.class_id', '=', 'classes.id')
            ->join('sections', 'sections.id', '=', 'student_session.section_id')
            ->where('students.parent_id', $parentId)
            ->where('students.id', '!=', $excludeStudentId)
            ->where('students.is_active', 'yes')
            ->select([
                'students.id',
                'students.admission_no',
                'students.firstname',
                'students.middlename',
                'students.lastname',
                'students.parent_id',
                'classes.id as class_id',
                'classes.class',
                'sections.id as section_id',
                'sections.section',
                'student_session.session_id',
            ])
            ->orderBy('students.id')
            ->get();
    }

    /**
     * CI Student::getStudentRecordByID payload for sibling picker autofill.
     */
    public function findRecordById(int $studentId): ?object
    {
        $sessionId = (int) $this->currentSession->id();

        $row = DB::table('students')
            ->leftJoin('student_session', function ($join) use ($sessionId) {
                $join->on('student_session.student_id', '=', 'students.id')
                    ->where('student_session.session_id', '=', $sessionId);
            })
            ->leftJoin('classes', 'student_session.class_id', '=', 'classes.id')
            ->leftJoin('sections', 'sections.id', '=', 'student_session.section_id')
            ->where('students.id', $studentId)
            ->select([
                'students.*',
                'classes.class',
                'sections.section',
                'student_session.class_id',
                'student_session.section_id',
            ])
            ->first();

        if (! $row) {
            return null;
        }

        $settings = DB::table('sch_settings')->first();
        $parts = [trim((string) $row->firstname)];
        if ((int) ($settings->middlename ?? 1) === 1 && filled($row->middlename)) {
            $parts[] = trim((string) $row->middlename);
        }
        if ((int) ($settings->lastname ?? 1) === 1 && filled($row->lastname)) {
            $parts[] = trim((string) $row->lastname);
        }
        $row->full_name = trim(implode(' ', array_filter($parts)));

        return $row;
    }
}
