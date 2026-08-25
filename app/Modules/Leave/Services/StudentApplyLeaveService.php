<?php

namespace App\Modules\Leave\Services;

use App\Modules\Academics\Services\CurrentSessionResolver;
use App\Modules\Leave\Models\StudentApplyLeave;
use App\Modules\Shared\Services\ClassTeacherScopeService;
use App\Modules\Shared\Services\SchoolContext;
use App\Modules\Staff\Models\Staff;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * CI apply_leave_model + admin/approve_leave — student leave applications.
 * Deferred: SaaS quota, mail/SMS.
 */
class StudentApplyLeaveService
{
    public const STATUS_PENDING = 0;

    public const STATUS_APPROVED = 1;

    public const STATUS_DISAPPROVED = 2;

    public function __construct(
        protected CurrentSessionResolver $currentSession,
        protected ClassTeacherScopeService $classTeacherScope,
        protected SchoolContext $school,
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
     * CI apply_leave_model::canApproveLeave — union class_teacher ∪ subject_timetable.
     */
    public function canApproveLeave(int $classId, int $sectionId): bool
    {
        return $this->classTeacherScope->allowsClassSection($classId, $sectionId, 'union');
    }

    /**
     * CI apply_leave_model::get — list or single row for current session.
     * Class-teacher matrix via Customlib::get_myClassSection.
     *
     * @return list<array<string, mixed>>|array<string, mixed>|null
     */
    public function get(?int $id = null, ?int $classId = null, ?int $sectionId = null): array|null
    {
        if ($this->classTeacherScope->isRestricted()) {
            $matrix = $this->classTeacherScope->myClassSectionMap();
            if ($matrix === []) {
                return $id !== null ? null : [];
            }
            if ($classId !== null && $classId > 0 && $sectionId !== null && $sectionId > 0
                && ! $this->canApproveLeave($classId, $sectionId)) {
                return $id !== null ? null : [];
            }
        }

        $query = DB::table('student_applyleave')
            ->join('student_session', 'student_session.id', '=', 'student_applyleave.student_session_id')
            ->join('students', 'students.id', '=', 'student_session.student_id')
            ->leftJoin('staff', 'staff.id', '=', 'student_applyleave.approve_by')
            ->leftJoin('staff_roles as approve_staff_roles', 'approve_staff_roles.staff_id', '=', 'staff.id')
            ->join('classes', 'student_session.class_id', '=', 'classes.id')
            ->join('sections', 'sections.id', '=', 'student_session.section_id')
            ->where('students.is_active', 'yes')
            ->where('student_session.session_id', $this->currentSessionId())
            ->select([
                'student_applyleave.*',
                'student_applyleave.status as apply_leave_status',
                'students.firstname',
                'students.middlename',
                'students.lastname',
                'staff.employee_id as staff_id',
                'staff.name as staff_name',
                'students.id as stud_id',
                'students.admission_no as admission_no',
                'staff.surname',
                'classes.id as class_id',
                'sections.id as section_id',
                'classes.class',
                'sections.section',
            ]);

        if ($this->classTeacherScope->isRestricted()) {
            $this->classTeacherScope->applyStudentSessionScope($query);
        }

        $this->applySuperadminApproveByFilter($query);

        if ($classId !== null && $classId > 0) {
            $query->where('classes.id', $classId);
        }
        if ($sectionId !== null && $sectionId > 0) {
            $query->where('sections.id', $sectionId);
        }

        if ($id !== null) {
            $row = $query->where('student_applyleave.id', $id)->first();

            return $row ? (array) $row : null;
        }

        return $query->orderByDesc('student_applyleave.id')
            ->get()
            ->map(fn ($r) => (array) $r)
            ->all();
    }

    /**
     * Students in class/section for current session (dropdown).
     *
     * @return list<array<string, mixed>>
     */
    public function studentsByClassSection(int $classId, int $sectionId): array
    {
        if (! $this->canApproveLeave($classId, $sectionId)) {
            return [];
        }

        return DB::table('student_session')
            ->join('students', 'students.id', '=', 'student_session.student_id')
            ->where('student_session.class_id', $classId)
            ->where('student_session.section_id', $sectionId)
            ->where('student_session.session_id', $this->currentSessionId())
            ->where('students.is_active', 'yes')
            ->orderBy('students.firstname')
            ->select([
                'student_session.id as student_session_id',
                'students.id as student_id',
                'students.firstname',
                'students.middlename',
                'students.lastname',
                'students.admission_no',
            ])
            ->get()
            ->map(fn ($r) => (array) $r)
            ->all();
    }

    /**
     * @param  array<string, mixed>  $input
     * @param  \Illuminate\Http\UploadedFile|null  $file
     */
    public function save(array $input, $file = null, ?int $leaveId = null): StudentApplyLeave
    {
        $classId = (int) ($input['class'] ?? 0);
        $sectionId = (int) ($input['section'] ?? 0);
        if (! $this->canApproveLeave($classId, $sectionId)) {
            throw new RuntimeException('You are not authorized for this class/section.');
        }

        $studentSessionId = (int) $input['student'];
        $belongs = DB::table('student_session')
            ->where('id', $studentSessionId)
            ->where('class_id', $classId)
            ->where('section_id', $sectionId)
            ->where('session_id', $this->currentSessionId())
            ->exists();
        if (! $belongs) {
            throw new RuntimeException('Student session does not belong to the selected class/section.');
        }

        $status = (int) $input['leave_status'];
        $payload = [
            'apply_date' => (string) $input['apply_date'],
            'from_date' => (string) $input['from_date'],
            'to_date' => (string) $input['to_date'],
            'student_session_id' => $studentSessionId,
            'reason' => (string) ($input['message'] ?? ''),
            'request_type' => 1,
            'status' => $status,
        ];

        if ($status !== self::STATUS_PENDING) {
            $payload['approve_by'] = (int) (Auth::guard('staff')->id() ?? 0) ?: null;
            $payload['approve_date'] = date('Y-m-d');
        } else {
            $payload['approve_by'] = null;
            $payload['approve_date'] = null;
        }

        $docs = '';
        if ($leaveId !== null) {
            $existing = StudentApplyLeave::query()->findOrFail($leaveId);
            $docs = (string) ($existing->docs ?? '');
        }

        if ($file !== null) {
            $dir = public_path('uploads/student_leavedocuments');
            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            if ($docs !== '' && is_file($dir.'/'.$docs)) {
                @unlink($dir.'/'.$docs);
            }
            $docs = uniqid('studleave_', true).'_'.$file->getClientOriginalName();
            $file->move($dir, $docs);
        }
        $payload['docs'] = $docs !== '' ? $docs : null;

        if ($leaveId !== null) {
            $row = StudentApplyLeave::query()->findOrFail($leaveId);
            $row->fill($payload);
            $row->save();

            return $row;
        }

        return StudentApplyLeave::query()->create($payload);
    }

    public function updateStatus(int $id, int $status): void
    {
        $row = $this->get($id);
        if ($row === null) {
            throw new RuntimeException('Leave request not found or not authorized.');
        }

        $payload = ['status' => $status];
        if ($status === self::STATUS_APPROVED) {
            $payload['approve_by'] = (int) (Auth::guard('staff')->id() ?? 0) ?: null;
            $payload['approve_date'] = date('Y-m-d');
        } else {
            $payload['approve_by'] = 0;
            $payload['approve_date'] = null;
        }
        StudentApplyLeave::query()->where('id', $id)->update($payload);
    }

    public function delete(int $id): void
    {
        $scoped = $this->get($id);
        if ($scoped === null) {
            throw new RuntimeException('Leave request not found or not authorized.');
        }

        $row = StudentApplyLeave::query()->findOrFail($id);
        if (! empty($row->docs)) {
            $path = public_path('uploads/student_leavedocuments/'.$row->docs);
            if (is_file($path)) {
                @unlink($path);
            }
        }
        $row->delete();
    }

    public function documentPath(string $filename): string
    {
        return public_path('uploads/student_leavedocuments/'.$filename);
    }

    public function statusLabel(int $status): string
    {
        return match ($status) {
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_DISAPPROVED => 'Disapproved',
            default => 'Pending',
        };
    }

    /**
     * CI apply_leave_model::get — hide rows approved by superadmin staff for non-superadmin viewers.
     */
    protected function applySuperadminApproveByFilter(Builder $query): void
    {
        /** @var Staff|null $staff */
        $staff = Auth::guard('staff')->user();
        if (! $staff) {
            return;
        }

        $roleId = (int) ($staff->roles()->value('roles.id') ?? 0);
        if ($roleId === 7) {
            return;
        }

        if ($this->school->superadminRestriction() !== 'disabled') {
            return;
        }

        $query->where(function ($q) {
            $q->whereNull('approve_staff_roles.role_id')
                ->orWhere('approve_staff_roles.role_id', '!=', 7);
        });
    }
}
