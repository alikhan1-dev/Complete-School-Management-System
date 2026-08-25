<?php

namespace Tests\Feature\Library;

use App\Modules\Library\Models\Book;
use App\Modules\Library\Models\BookIssue;
use App\Modules\Library\Models\LibraryMember;
use App\Modules\Staff\Models\Staff;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LibraryAdminIssueReportTest extends TestCase
{
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

    private function actingAsSuperAdmin(): Staff
    {
        $roleId = (int) (DB::table('roles')->where('is_superadmin', 1)->value('id')
            ?: DB::table('roles')->where('name', 'Super Admin')->value('id'));
        $this->assertGreaterThan(0, $roleId);

        $token = uniqid('libir', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'LIR-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Issue',
            'surname' => 'Report',
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
        $staff = Staff::query()->findOrFail($staffId);
        $this->actingAs($staff, 'staff');

        return $staff;
    }

    public function test_admin_issue_report_lists_open_issues_and_excludes_returned(): void
    {
        $staff = $this->actingAsSuperAdmin();
        $suffix = uniqid();
        $today = now()->format('Y-m-d');
        $due = now()->addDays(3)->format('Y-m-d');

        $openBook = Book::query()->create([
            'book_title' => 'Admin Issue Report Open '.$suffix,
            'book_no' => 'AIRO-'.$suffix,
            'isbn_no' => 'ISBN-O-'.$suffix,
            'subject' => 'Lib',
            'rack_no' => 'R1',
            'publish' => 'Pub',
            'author' => 'Author',
            'qty' => 2,
            'perunitcost' => 10,
            'postdate' => $today,
            'description' => 'Open',
            'available' => 'yes',
            'is_active' => 'no',
        ]);
        $returnedBook = Book::query()->create([
            'book_title' => 'Admin Issue Report Returned '.$suffix,
            'book_no' => 'AIRR-'.$suffix,
            'isbn_no' => 'ISBN-R-'.$suffix,
            'subject' => 'Lib',
            'rack_no' => 'R2',
            'publish' => 'Pub',
            'author' => 'Author',
            'qty' => 2,
            'perunitcost' => 10,
            'postdate' => $today,
            'description' => 'Returned',
            'available' => 'yes',
            'is_active' => 'no',
        ]);
        $this->cleanupBookIds[] = $openBook->id;
        $this->cleanupBookIds[] = $returnedBook->id;

        $member = LibraryMember::query()->create([
            'library_card_no' => 'AIRCARD-'.$suffix,
            'member_type' => 'teacher',
            'member_id' => $staff->id,
            'is_active' => 'no',
        ]);
        $this->cleanupMemberIds[] = $member->id;

        $openIssue = BookIssue::query()->create([
            'book_id' => $openBook->id,
            'member_id' => $member->id,
            'duereturn_date' => $due,
            'issue_date' => $today,
            'return_date' => null,
            'is_returned' => 0,
            'is_active' => 'no',
        ]);
        $returnedIssue = BookIssue::query()->create([
            'book_id' => $returnedBook->id,
            'member_id' => $member->id,
            'duereturn_date' => $due,
            'issue_date' => $today,
            'return_date' => $today,
            'is_returned' => 1,
            'is_active' => 'no',
        ]);
        $this->cleanupIssueIds[] = $openIssue->id;
        $this->cleanupIssueIds[] = $returnedIssue->id;

        $response = $this->get('/admin/book/issue_report')->assertOk();
        $response->assertSee('Book Issue Report', false);
        $response->assertSee('Admin Issue Report Open '.$suffix, false);
        $response->assertSee('AIRCARD-'.$suffix, false);
        $response->assertSee('Issue Report', false);
        $response->assertDontSee('Admin Issue Report Returned '.$suffix, false);

        $this->get('/migration-status/library')
            ->assertOk()
            ->assertJsonPath('slices.library_admin_issue_report', 'done');
    }
}
