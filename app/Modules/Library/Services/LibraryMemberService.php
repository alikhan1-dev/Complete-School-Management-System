<?php

namespace App\Modules\Library\Services;

use App\Modules\Academics\Services\CurrentSessionResolver;
use App\Modules\Library\Models\LibraryMember;
use App\Modules\Shared\Services\SchoolContext;
use App\Modules\Staff\Models\Staff;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * CI admin/member — list / enroll student & staff / surrender / detail for issue.
 */
class LibraryMemberService
{
    public function __construct(
        protected CurrentSessionResolver $currentSession,
        protected SchoolContext $school,
    ) {
    }

    /**
     * @return Collection<int, object>
     */
    public function listMembers(): Collection
    {
        $students = DB::table('libarary_members')
            ->join('students', 'students.id', '=', 'libarary_members.member_id')
            ->where('libarary_members.member_type', 'student')
            ->where('students.is_active', 'yes')
            ->select([
                'libarary_members.id as lib_member_id',
                'libarary_members.library_card_no',
                'libarary_members.member_type',
                'students.admission_no',
                'students.firstname',
                'students.middlename',
                'students.lastname',
                'students.guardian_phone as phone',
                DB::raw('null as employee_id'),
            ])
            ->get();

        $staff = DB::table('libarary_members')
            ->join('staff', 'staff.id', '=', 'libarary_members.member_id')
            ->where('libarary_members.member_type', 'teacher')
            ->where('staff.is_active', 1)
            ->select([
                'libarary_members.id as lib_member_id',
                'libarary_members.library_card_no',
                'libarary_members.member_type',
                DB::raw('null as admission_no'),
                'staff.name as firstname',
                DB::raw('null as middlename'),
                'staff.surname as lastname',
                'staff.contact_no as phone',
                'staff.employee_id',
                'staff.id as staff_id',
                DB::raw('(SELECT role_id FROM staff_roles WHERE staff_id = staff.id AND is_active = 1 LIMIT 1) as staff_role_id'),
            ])
            ->get()
            ->filter(fn (object $row): bool => ! $this->shouldExcludeSuperadminStaffMember((int) ($row->staff_role_id ?? 0)));

        return $students->concat($staff)->sortBy('lib_member_id')->values();
    }

    /**
     * CI Student_model::searchLibraryStudent.
     *
     * @return Collection<int, object>
     */
    public function searchStudents(?int $classId, ?int $sectionId): Collection
    {
        $sessionId = (int) $this->currentSession->id();
        abort_unless($sessionId > 0, 422, 'Current academic session is not configured.');

        $q = DB::table('students')
            ->join('student_session', 'student_session.student_id', '=', 'students.id')
            ->join('classes', 'classes.id', '=', 'student_session.class_id')
            ->join('sections', 'sections.id', '=', 'student_session.section_id')
            ->leftJoin('libarary_members', function ($join) {
                $join->on('libarary_members.member_id', '=', 'students.id')
                    ->where('libarary_members.member_type', '=', 'student');
            })
            ->where('student_session.session_id', $sessionId)
            ->where('students.is_active', 'yes')
            ->orderBy('students.id')
            ->select([
                'students.id',
                'students.admission_no',
                'students.firstname',
                'students.middlename',
                'students.lastname',
                'classes.class',
                'sections.section',
                DB::raw('IFNULL(libarary_members.id, 0) as libarary_member_id'),
                DB::raw('IFNULL(libarary_members.library_card_no, "") as library_card_no'),
            ]);

        if ($classId !== null && $classId > 0) {
            $q->where('student_session.class_id', $classId);
        }
        if ($sectionId !== null && $sectionId > 0) {
            $q->where('student_session.section_id', $sectionId);
        }

        return $q->get();
    }

    /**
     * CI Teacher_model::getLibraryTeacher.
     *
     * @return Collection<int, object>
     */
    public function listStaffCandidates(): Collection
    {
        $query = DB::table('staff')
            ->leftJoin('libarary_members', function ($join) {
                $join->on('libarary_members.member_id', '=', 'staff.id')
                    ->where('libarary_members.member_type', '=', 'teacher');
            })
            ->leftJoin('staff_roles', function ($join) {
                $join->on('staff_roles.staff_id', '=', 'staff.id')
                    ->where('staff_roles.is_active', '=', 1);
            })
            ->leftJoin('roles', 'roles.id', '=', 'staff_roles.role_id')
            ->where('staff.is_active', 1)
            ->orderBy('staff.id')
            ->select([
                'staff.id',
                'staff.employee_id',
                'staff.name',
                'staff.surname',
                'staff.email',
                'staff.contact_no',
                'staff.gender',
                DB::raw('IFNULL(libarary_members.id, 0) as libarary_member_id'),
                DB::raw('IFNULL(libarary_members.library_card_no, "") as library_card_no'),
            ]);

        $this->applySuperadminStaffQueryFilter($query);

        return $query->get();
    }

    public function enrollStudent(int $studentId, string $libraryCardNo): LibraryMember
    {
        return $this->enroll('student', $studentId, $libraryCardNo);
    }

    public function enrollStaff(int $staffId, string $libraryCardNo): LibraryMember
    {
        return $this->enroll('teacher', $staffId, $libraryCardNo);
    }

    public function surrender(int $libraryMemberId): void
    {
        $member = LibraryMember::query()->findOrFail($libraryMemberId);

        DB::transaction(function () use ($member) {
            DB::table('book_issues')->where('member_id', $member->id)->delete();
            $member->delete();
        });
    }

    /**
     * CI Librarymember_model::getByMemberID detail payload.
     */
    public function findDetailed(int $libraryMemberId): object
    {
        $member = LibraryMember::query()->findOrFail($libraryMemberId);

        if ($member->member_type === 'student') {
            $row = DB::table('libarary_members')
                ->join('students', 'students.id', '=', 'libarary_members.member_id')
                ->leftJoin('student_session', 'student_session.student_id', '=', 'students.id')
                ->leftJoin('sessions', 'sessions.id', '=', 'student_session.session_id')
                ->where('libarary_members.id', $libraryMemberId)
                ->orderByDesc('student_session.id')
                ->select([
                    'libarary_members.id as lib_member_id',
                    'libarary_members.library_card_no',
                    'libarary_members.member_type',
                    'students.admission_no',
                    'students.firstname',
                    'students.middlename',
                    'students.lastname',
                    'students.gender',
                    'students.mobileno',
                    'sessions.session as session_year',
                ])
                ->first();
        } else {
            $row = DB::table('libarary_members')
                ->join('staff', 'staff.id', '=', 'libarary_members.member_id')
                ->where('libarary_members.id', $libraryMemberId)
                ->select([
                    'libarary_members.id as lib_member_id',
                    'libarary_members.library_card_no',
                    'libarary_members.member_type',
                    'staff.employee_id as admission_no',
                    'staff.name as firstname',
                    DB::raw('null as middlename'),
                    'staff.surname as lastname',
                    'staff.gender',
                    'staff.contact_no as mobileno',
                    DB::raw('null as session_year'),
                ])
                ->first();
        }

        abort_unless($row !== null, 404);

        return $row;
    }

    protected function enroll(string $memberType, int $memberId, string $libraryCardNo): LibraryMember
    {
        $card = trim($libraryCardNo);
        if ($card === '') {
            throw ValidationException::withMessages([
                'library_card_no' => 'Library card number is required.',
            ]);
        }

        $cardTaken = LibraryMember::query()->where('library_card_no', $card)->exists();
        if ($cardTaken) {
            throw ValidationException::withMessages([
                'library_card_no' => 'Card no already exists.',
            ]);
        }

        $already = LibraryMember::query()
            ->where('member_type', $memberType)
            ->where('member_id', $memberId)
            ->exists();
        if ($already) {
            throw ValidationException::withMessages([
                'member_id' => 'This person is already a library member.',
            ]);
        }

        if ($memberType === 'student') {
            $exists = DB::table('students')->where('id', $memberId)->where('is_active', 'yes')->exists();
        } else {
            $exists = DB::table('staff')->where('id', $memberId)->where('is_active', 1)->exists();
        }
        abort_unless($exists, 404);

        return LibraryMember::query()->create([
            'member_type' => $memberType,
            'member_id' => $memberId,
            'library_card_no' => $card,
            'is_active' => 'no',
        ]);
    }

    /**
     * CI Member::index — omit staff library members with role 7 when restriction is disabled.
     */
    protected function shouldExcludeSuperadminStaffMember(int $memberStaffRoleId): bool
    {
        if ($memberStaffRoleId !== 7) {
            return false;
        }

        if ($this->school->superadminRestriction() !== 'disabled') {
            return false;
        }

        return $this->viewerRoleId() !== 7;
    }

    /**
     * CI Teacher_model::getLibraryTeacher — roles.id != 7 for non-superadmin viewers.
     */
    protected function applySuperadminStaffQueryFilter(Builder $query): void
    {
        if ($this->school->superadminRestriction() !== 'disabled') {
            return;
        }

        if ($this->viewerRoleId() === 7) {
            return;
        }

        $query->where('roles.id', '!=', 7);
    }

    protected function viewerRoleId(): int
    {
        /** @var Staff|null $staff */
        $staff = Auth::guard('staff')->user();
        if (! $staff) {
            return 0;
        }

        return (int) ($staff->roles()->value('roles.id') ?? 0);
    }
}
