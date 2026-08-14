<?php

namespace App\Modules\FrontOffice\Services;

use App\Modules\Academics\Models\SchoolClass;
use App\Modules\Academics\Services\CurrentSessionResolver;
use App\Modules\FrontOffice\Models\Visitor;
use App\Modules\Shared\Services\SchoolContext;
use App\Modules\Staff\Models\Staff;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * CI visitors_model + admin/Visitors persist (SaaS quota deferred).
 */
class VisitorService
{
    public const MEETING_WITH = [
        'student' => 'Student',
        'staff' => 'Staff',
    ];

    public function __construct(
        protected SchoolContext $school,
        protected CurrentSessionResolver $session,
        protected VisitorDocumentService $documents,
    ) {
    }

    public function currentStaffId(): int
    {
        $staff = Auth::guard('staff')->user();

        return $staff ? (int) $staff->id : 0;
    }

    public function currentStaffRoleId(): int
    {
        $staff = Auth::guard('staff')->user();
        if (! $staff instanceof Staff) {
            return 0;
        }
        $role = $staff->primaryRole();

        return $role ? (int) $role->id : 0;
    }

    /**
     * @return list<object>
     */
    public function purposes(): array
    {
        return DB::table('visitors_purpose')->orderBy('id')->get()->all();
    }

    /**
     * @return list<object>
     */
    public function staffList(): array
    {
        return DB::table('staff')->where('is_active', 1)->orderBy('name')->get()->all();
    }

    /**
     * @return list<object>
     */
    public function classes(): array
    {
        return SchoolClass::query()->orderBy('id')->get()->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listAll(): array
    {
        $query = $this->baseQuery()->orderByDesc('visitors_book.id');
        if ($this->school->superadminRestriction() === 'disabled' && $this->currentStaffRoleId() !== 7) {
            $query->where(function ($q) {
                $q->where('roles.id', '!=', 7)->orWhereNull('roles.id');
            });
        }

        return $query->get()->map(fn ($row) => (array) $row)->all();
    }

    public function find(int $id): ?array
    {
        $row = $this->baseQuery()->where('visitors_book.id', $id)->first();

        return $row ? (array) $row : null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listByStaff(int $staffId): array
    {
        return Visitor::query()
            ->where('staff_id', $staffId)
            ->orderByDesc('id')
            ->get()
            ->map(fn (Visitor $row) => $row->toArray())
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listByStudentSession(int $studentSessionId): array
    {
        return Visitor::query()
            ->where('student_session_id', $studentSessionId)
            ->orderByDesc('id')
            ->get()
            ->map(fn (Visitor $row) => $row->toArray())
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function studentsByClassSection(int $classId, int $sectionId): array
    {
        return DB::table('student_session')
            ->select(
                'student_session.id',
                'students.firstname',
                'students.middlename',
                'students.lastname',
                'students.id as student_id',
                'students.admission_no',
            )
            ->leftJoin('students', 'students.id', '=', 'student_session.student_id')
            ->where('student_session.class_id', $classId)
            ->where('student_session.section_id', $sectionId)
            ->where('student_session.session_id', $this->session->id())
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function create(array $input, ?UploadedFile $file): int
    {
        $payload = $this->payload($input);
        $payload['image'] = $file ? $this->documents->store($file) : '';

        return (int) Visitor::query()->create($payload)->id;
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function update(int $id, array $input, ?UploadedFile $file): void
    {
        $existing = Visitor::query()->find($id);
        if ($existing === null) {
            return;
        }

        $payload = $this->payload($input, true);
        if ($file) {
            $this->documents->delete((string) $existing->image);
            $payload['image'] = $this->documents->store($file);
        } else {
            $payload['image'] = (string) ($existing->image ?? '');
        }

        Visitor::query()->where('id', $id)->update($payload);
    }

    public function delete(int $id): void
    {
        $row = Visitor::query()->find($id);
        if ($row === null) {
            return;
        }
        $this->documents->delete((string) $row->image);
        $row->delete();
    }

    public function parseDate(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            throw new InvalidArgumentException('Date is required.');
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1) {
            return $value;
        }

        return Carbon::createFromFormat($this->school->dateFormat() ?: 'd/m/Y', $value)->format('Y-m-d');
    }

    public function formatDate(?string $value): string
    {
        if ($value === null || $value === '' || $value === '0000-00-00') {
            return '';
        }

        return Carbon::parse($value)->format($this->school->dateFormat() ?: 'd/m/Y');
    }

    protected function baseQuery()
    {
        return DB::table('visitors_book')
            ->leftJoin('student_session', 'student_session.id', '=', 'visitors_book.student_session_id')
            ->leftJoin('students', 'students.id', '=', 'student_session.student_id')
            ->leftJoin('classes', 'student_session.class_id', '=', 'classes.id')
            ->leftJoin('sections', 'sections.id', '=', 'student_session.section_id')
            ->leftJoin('staff', 'staff.id', '=', 'visitors_book.staff_id')
            ->leftJoin('staff_roles', 'staff_roles.staff_id', '=', 'staff.id')
            ->leftJoin('roles', 'staff_roles.role_id', '=', 'roles.id')
            ->select(
                'visitors_book.*',
                'classes.class',
                'sections.section',
                'staff.name as staff_name',
                'staff.surname as staff_surname',
                'staff.employee_id as staff_employee_id',
                'student_session.class_id',
                'student_session.section_id',
                'students.id as students_id',
                'students.admission_no',
                'students.firstname as student_firstname',
                'students.middlename as student_middlename',
                'students.lastname as student_lastname',
                'roles.id as role_id',
            );
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    protected function payload(array $input, bool $edit = false): array
    {
        $meeting = (string) ($edit ? ($input['edit_meeting_with'] ?? '') : ($input['meeting_with'] ?? ''));
        $staffId = null;
        $studentSessionId = null;
        if ($meeting === 'staff') {
            $staffId = (int) ($edit ? ($input['edit_staff_id'] ?? 0) : ($input['staff_id'] ?? 0));
            $staffId = $staffId > 0 ? $staffId : null;
        } else {
            $studentSessionId = (int) ($edit ? ($input['edit_student_session_id'] ?? 0) : ($input['student_session_id'] ?? 0));
            $studentSessionId = $studentSessionId > 0 ? $studentSessionId : null;
        }

        $people = $input['pepples'] ?? 0;
        $people = $people === null || $people === '' ? 0 : (int) $people;

        return [
            'purpose' => (string) ($input['purpose'] ?? ''),
            'name' => (string) ($input['name'] ?? ''),
            'contact' => (string) ($input['contact'] ?? ''),
            'id_proof' => (string) ($input['id_proof'] ?? ''),
            'no_of_people' => $people,
            'date' => $this->parseDate((string) ($input['date'] ?? '')),
            'in_time' => (string) ($input['time'] ?? ''),
            'out_time' => (string) ($input['out_time'] ?? ''),
            'note' => (string) ($input['note'] ?? ''),
            'meeting_with' => $meeting,
            'staff_id' => $staffId,
            'student_session_id' => $studentSessionId,
        ];
    }
}
