<?php

namespace Tests\Feature\Library;

use App\Modules\Academics\Models\AcademicSession;
use App\Modules\Academics\Models\ClassSection;
use App\Modules\Academics\Models\SchoolClass;
use App\Modules\Academics\Models\Section;
use App\Modules\Auth\Models\PortalUser;
use App\Modules\Library\Models\Book;
use App\Modules\Library\Models\BookIssue;
use App\Modules\Library\Models\LibraryMember;
use App\Modules\Staff\Models\Staff;
use App\Modules\Students\Models\Student;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StudentBookPortalTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<int> */
    private array $cleanupStudentIds = [];

    /** @var list<int> */
    private array $cleanupUserIds = [];

    /** @var list<int> */
    private array $cleanupClassIds = [];

    /** @var list<int> */
    private array $cleanupSectionIds = [];

    /** @var list<int> */
    private array $cleanupBookIds = [];

    /** @var list<int> */
    private array $cleanupMemberIds = [];

    /** @var list<int> */
    private array $cleanupIssueIds = [];

    protected function tearDown(): void
    {
        if ($this->cleanupIssueIds !== []) {
            DB::table('book_issues')->whereIn('id', $this->cleanupIssueIds)->delete();
        }
        $this->cleanupIssueIds = [];

        if ($this->cleanupMemberIds !== []) {
            DB::table('book_issues')->whereIn('member_id', $this->cleanupMemberIds)->delete();
            DB::table('libarary_members')->whereIn('id', $this->cleanupMemberIds)->delete();
        }
        $this->cleanupMemberIds = [];

        if ($this->cleanupBookIds !== []) {
            DB::table('book_issues')->whereIn('book_id', $this->cleanupBookIds)->delete();
            DB::table('books')->whereIn('id', $this->cleanupBookIds)->delete();
        }
        $this->cleanupBookIds = [];

        foreach ($this->cleanupStudentIds as $studentId) {
            DB::table('libarary_members')->where('member_type', 'student')->where('member_id', $studentId)->delete();
            DB::table('users')->where('user_id', $studentId)->where('role', 'student')->delete();
            DB::table('student_session')->where('student_id', $studentId)->delete();
            DB::table('students')->where('id', $studentId)->delete();
        }
        $this->cleanupStudentIds = [];

        if ($this->cleanupUserIds !== []) {
            DB::table('users')->whereIn('id', $this->cleanupUserIds)->delete();
        }
        $this->cleanupUserIds = [];

        foreach ($this->cleanupClassIds as $classId) {
            DB::table('class_sections')->where('class_id', $classId)->delete();
            DB::table('classes')->where('id', $classId)->delete();
        }
        $this->cleanupClassIds = [];

        if ($this->cleanupSectionIds !== []) {
            DB::table('sections')->whereIn('id', $this->cleanupSectionIds)->delete();
        }
        $this->cleanupSectionIds = [];

        foreach ($this->createdStaffIds as $staffId) {
            DB::table('staff_roles')->where('staff_id', $staffId)->delete();
            DB::table('staff')->where('id', $staffId)->delete();
        }
        $this->createdStaffIds = [];

        parent::tearDown();
    }

    private function actingAsSuperAdmin(): void
    {
        $roleId = (int) (DB::table('roles')->where('is_superadmin', 1)->value('id')
            ?: DB::table('roles')->where('name', 'Super Admin')->value('id'));
        $this->assertGreaterThan(0, $roleId);

        $token = uniqid('libprt', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'LBP-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Portal',
            'surname' => 'Lib',
            'father_name' => '',
            'mother_name' => '',
            'contact_no' => '',
            'emergency_contact_no' => '',
            'email' => $token.'@example.test',
            'dob' => '1990-01-01',
            'marital_status' => '',
            'local_address' => '',
            'permanent_address' => '',
            'note' => '',
            'image' => '',
            'password' => bcrypt('secret'),
            'gender' => 'Male',
            'account_title' => '',
            'bank_account_no' => '',
            'bank_name' => '',
            'ifsc_code' => '',
            'bank_branch' => '',
            'payscale' => '',
            'epf_no' => '',
            'contract_type' => '',
            'shift' => '',
            'location' => '',
            'facebook' => '',
            'twitter' => '',
            'linkedin' => '',
            'instagram' => '',
            'resume' => '',
            'joining_letter' => '',
            'resignation_letter' => '',
            'other_document_name' => '',
            'other_document_file' => '',
            'user_id' => 0,
            'is_active' => 1,
            'verification_code' => '',
        ]);
        DB::table('staff_roles')->insert([
            'staff_id' => $staffId,
            'role_id' => $roleId,
            'is_active' => 1,
        ]);
        $this->createdStaffIds[] = $staffId;
        $this->actingAs(Staff::query()->findOrFail($staffId), 'staff');
    }

    /**
     * @return array{student:Student,sessionId:int}
     */
    private function seedStudentPortalContext(): array
    {
        $this->actingAsSuperAdmin();
        $suffix = uniqid();

        $session = AcademicSession::query()->first() ?: AcademicSession::query()->create(['session' => '2099-libp']);
        DB::table('sch_settings')->limit(1)->update(['session_id' => $session->id]);

        $section = Section::query()->create(['section' => 'LBPS-'.$suffix, 'is_active' => 'yes']);
        $class = SchoolClass::query()->create(['class' => 'LBPC-'.$suffix, 'is_active' => 'yes']);
        $this->cleanupSectionIds[] = $section->id;
        $this->cleanupClassIds[] = $class->id;

        ClassSection::query()->create([
            'class_id' => $class->id,
            'section_id' => $section->id,
            'is_active' => 'yes',
        ]);

        $admissionNo = 'LIBPADM'.$suffix;
        $this->post('/student/create', [
            'admission_no' => $admissionNo,
            'firstname' => 'Lib',
            'lastname' => 'Pupil',
            'gender' => 'Male',
            'dob' => '2012-01-01',
            'class_id' => $class->id,
            'section_id' => $section->id,
            'guardian_is' => 'father',
            'guardian_name' => 'Dad',
            'guardian_phone' => '03001112233',
        ])->assertRedirect();

        $student = Student::query()->where('admission_no', $admissionNo)->firstOrFail();
        $this->cleanupStudentIds[] = $student->id;

        $studentSessionId = (int) DB::table('student_session')
            ->where('student_id', $student->id)
            ->where('session_id', $session->id)
            ->value('id');
        $this->assertGreaterThan(0, $studentSessionId);

        $user = PortalUser::query()
            ->where('user_id', $student->id)
            ->where('role', 'student')
            ->firstOrFail();
        $user->login_token = 'tok'.$suffix;
        $user->save();
        $this->cleanupUserIds[] = (int) $user->id;

        $this->actingAs($user, 'student_parent');
        session(['current_class' => ['student_session_id' => $studentSessionId]]);

        return [
            'student' => $student,
            'sessionId' => $studentSessionId,
        ];
    }

    public function test_student_can_view_books_and_issued_list(): void
    {
        $ctx = $this->seedStudentPortalContext();
        $suffix = uniqid();
        $today = now()->format('Y-m-d');

        $book = Book::query()->create([
            'book_title' => 'Portal Catalog Book '.$suffix,
            'book_no' => 'PCB-'.$suffix,
            'isbn_no' => 'ISBN-P-'.$suffix,
            'subject' => 'History',
            'rack_no' => 'R7',
            'publish' => 'Pub',
            'author' => 'Author P',
            'qty' => 2,
            'perunitcost' => 8.25,
            'postdate' => $today,
            'description' => 'Portal desc '.$suffix,
            'available' => 'yes',
            'is_active' => 'no',
        ]);
        $this->cleanupBookIds[] = $book->id;

        $this->get('/user/book')
            ->assertOk()
            ->assertSee('Books', false)
            ->assertSee('Portal Catalog Book '.$suffix, false)
            ->assertSee('Book Issued', false);

        $this->get('/user/book/issue')
            ->assertOk()
            ->assertSee('Book Issued', false)
            ->assertSee('You are not enrolled as a library member', false);

        $member = LibraryMember::query()->create([
            'library_card_no' => 'PCARD-'.$suffix,
            'member_type' => 'student',
            'member_id' => $ctx['student']->id,
            'is_active' => 'no',
        ]);
        $this->cleanupMemberIds[] = $member->id;

        $issue = BookIssue::query()->create([
            'book_id' => $book->id,
            'member_id' => $member->id,
            'duereturn_date' => now()->addDays(7)->format('Y-m-d'),
            'issue_date' => $today,
            'return_date' => null,
            'is_returned' => 0,
            'is_active' => 'no',
        ]);
        $this->cleanupIssueIds[] = $issue->id;

        $this->get('/user/book/issue')
            ->assertOk()
            ->assertSee('Portal Catalog Book '.$suffix, false)
            ->assertSee('PCB-'.$suffix, false)
            ->assertDontSee('You are not enrolled as a library member', false);
    }
}
