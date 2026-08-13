<?php

namespace App\Modules\Leave\Services;

use App\Modules\Academics\Services\CurrentSessionResolver;
use App\Modules\Leave\Models\StudentApplyLeave;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * CI apply_leave_model + admin/approve_leave — student leave applications.
 * Deferred: class-teacher scope (get_myClassSection / canApproveLeave), SaaS quota, mail/SMS.
 */
class StudentApplyLeaveService
{
    public const STATUS_PENDING = 0;

    public const STATUS_APPROVED = 1;

    public const STATUS_DISAPPROVED = 2;

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
     * CI apply_leave_model::get — list or single row for current session.
     *
     * @return list<array<string, mixed>>|array<string, mixed>|null
     */
    public function get(?int $id = null, ?int $classId = null, ?int $sectionId = null): array|null
    {
        $query = DB::table('student_applyleave')
            ->join('student_session', 'student_session.id', '=', 'student_applyleave.student_session_id')
            ->join('students', 'students.id', '=', 'student_session.student_id')
            ->leftJoin('staff', 'staff.id', '=', 'student_applyleave.approve_by')
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
        $status = (int) $input['leave_status'];
        $payload = [
            'apply_date' => (string) $input['apply_date'],
            'from_date' => (string) $input['from_date'],
            'to_date' => (string) $input['to_date'],
            'student_session_id' => (int) $input['student'],
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
}
