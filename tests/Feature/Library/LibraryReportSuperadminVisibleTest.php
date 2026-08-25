<?php

namespace Tests\Feature\Library;

use App\Modules\Library\Models\Book;
use App\Modules\Library\Models\BookIssue;
use App\Modules\Library\Models\LibraryMember;
use App\Modules\Shared\Services\SchoolContext;
use App\Modules\Staff\Models\Staff;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LibraryReportSuperadminVisibleTest extends TestCase
{
    private ?string $savedRestriction = null;

    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<int> */
    private array $cleanupBookIds = [];

    /** @var list<int> */
    private array $cleanupMemberIds = [];

    /** @var list<int> */
    private array $cleanupIssueIds = [];

    protected function tearDown(): void
    {
        if ($this->savedRestriction !== null) {
            DB::table('sch_settings')->limit(1)->update(['superadmin_restriction' => $this->savedRestriction]);
            app(SchoolContext::class)->clearCache();
            $this->savedRestriction = null;
        }

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

        foreach ($this->createdStaffIds as $staffId) {
            DB::table('libarary_members')->where('member_type', 'teacher')->where('member_id', $staffId)->delete();
            DB::table('staff_roles')->where('staff_id', $staffId)->delete();
            DB::table('staff')->where('id', $staffId)->delete();
        }
        $this->createdStaffIds = [];

        parent::tearDown();
    }

    private function setSuperadminRestriction(string $value): void
    {
        if ($this->savedRestriction === null) {
            $this->savedRestriction = (string) DB::table('sch_settings')->value('superadmin_restriction');
        }
        DB::table('sch_settings')->limit(1)->update(['superadmin_restriction' => $value]);
        app(SchoolContext::class)->clearCache();
    }

    private function createStaff(int $roleId, string $prefix): Staff
    {
        $token = uniqid($prefix, true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => strtoupper($prefix).'-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => ucfirst($prefix),
            'surname' => 'Member',
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
            'basic_salary' => 0,
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

        return Staff::query()->findOrFail($staffId);
    }

    /**
     * @return array{bookTitle:string,issueByLabel:string,employeeId:string}
     */
    private function seedSuperadminBookIssue(int $superadminStaffId): array
    {
        $suffix = uniqid();
        $today = now()->format('Y-m-d');
        $due = now()->addDays(7)->format('Y-m-d');
        $bookTitle = 'Superadmin Mask Book '.$suffix;
        $employeeId = 'SAEMP-'.$suffix;
        DB::table('staff')->where('id', $superadminStaffId)->update(['employee_id' => $employeeId]);

        $book = Book::query()->create([
            'book_title' => $bookTitle,
            'book_no' => 'SMB-'.$suffix,
            'isbn_no' => 'ISBN-SA-'.$suffix,
            'subject' => 'Lib',
            'rack_no' => 'R1',
            'publish' => 'Pub',
            'author' => 'Author',
            'qty' => 1,
            'perunitcost' => 10,
            'postdate' => $today,
            'description' => 'Mask test',
            'available' => 'yes',
            'is_active' => 'no',
        ]);
        $this->cleanupBookIds[] = $book->id;

        $member = LibraryMember::query()->create([
            'library_card_no' => 'SACARD-'.$suffix,
            'member_type' => 'teacher',
            'member_id' => $superadminStaffId,
            'is_active' => 'no',
        ]);
        $this->cleanupMemberIds[] = $member->id;

        $issue = BookIssue::query()->create([
            'book_id' => $book->id,
            'member_id' => $member->id,
            'duereturn_date' => $due,
            'issue_date' => $today,
            'return_date' => null,
            'is_returned' => 0,
            'is_active' => 'no',
        ]);
        $this->cleanupIssueIds[] = $issue->id;

        $superadminStaff = Staff::query()->findOrFail($superadminStaffId);
        $issueByLabel = trim($superadminStaff->name.' '.$superadminStaff->surname).' ('.$employeeId.')';

        return [
            'bookTitle' => $bookTitle,
            'issueByLabel' => $issueByLabel,
            'employeeId' => $employeeId,
        ];
    }

    public function test_book_issue_and_due_reports_mask_superadmin_staff_for_non_superadmin_viewer(): void
    {
        $superadminRoleId = (int) (DB::table('roles')->where('id', 7)->value('id')
            ?: DB::table('roles')->where('is_superadmin', 1)->value('id'));
        $this->assertSame(7, $superadminRoleId, 'CI parity expects superadmin role id 7.');

        $teacherRoleId = (int) (DB::table('roles')->where('id', '!=', 7)->orderBy('id')->value('id'));
        $this->assertGreaterThan(0, $teacherRoleId);

        $this->setSuperadminRestriction('disabled');

        $hiddenSuperadmin = $this->createStaff($superadminRoleId, 'hidden');
        $payload = $this->seedSuperadminBookIssue($hiddenSuperadmin->id);

        $viewer = $this->createStaff($teacherRoleId, 'viewer');
        $this->actingAs($viewer, 'staff');

        $issueResponse = $this->get('/report/studentbookissuereport?search=1&search_type=this_year&members_type=teacher')
            ->assertOk();
        $issueResponse->assertSee($payload['bookTitle'], false);
        $issueResponse->assertDontSee($payload['issueByLabel'], false);
        $issueResponse->assertDontSee($payload['employeeId'], false);

        $dueResponse = $this->get('/report/bookduereport?search=1&search_type=this_year&members_type=teacher')
            ->assertOk();
        $dueResponse->assertSee($payload['bookTitle'], false);
        $dueResponse->assertDontSee($payload['issueByLabel'], false);
        $dueResponse->assertDontSee($payload['employeeId'], false);
    }

    public function test_book_issue_report_shows_superadmin_staff_name_to_superadmin_viewer(): void
    {
        $superadminRoleId = (int) (DB::table('roles')->where('id', 7)->value('id')
            ?: DB::table('roles')->where('is_superadmin', 1)->value('id'));
        if ($superadminRoleId !== 7) {
            $this->markTestSkipped('CI parity expects superadmin role id 7.');
        }

        $this->setSuperadminRestriction('disabled');

        $hiddenSuperadmin = $this->createStaff($superadminRoleId, 'visible');
        $payload = $this->seedSuperadminBookIssue($hiddenSuperadmin->id);

        $viewer = $this->createStaff($superadminRoleId, 'saadmin');
        $this->actingAs($viewer, 'staff');

        $this->get('/report/studentbookissuereport?search=1&search_type=this_year&members_type=teacher')
            ->assertOk()
            ->assertSee($payload['bookTitle'], false)
            ->assertSee($payload['issueByLabel'], false);

        $this->get('/migration-status/library')
            ->assertOk()
            ->assertJsonPath('slices.library_reports_superadmin_visible', 'done');
    }
}
