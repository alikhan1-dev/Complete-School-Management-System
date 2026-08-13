<?php

namespace Tests\Feature\Library;

use App\Modules\Library\Models\Book;
use App\Modules\Library\Models\BookIssue;
use App\Modules\Library\Models\LibraryMember;
use App\Modules\Staff\Models\Staff;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BookIssueReturnTest extends TestCase
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

        $token = uniqid('libiss', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'LI-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Issue',
            'surname' => 'Admin',
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

    public function test_issue_and_return_book_for_member(): void
    {
        $staff = $this->actingAsSuperAdmin();
        $suffix = uniqid();

        $book = Book::query()->create([
            'book_title' => 'Issue Book '.$suffix,
            'book_no' => 'IB-'.$suffix,
            'isbn_no' => 'ISBN-'.$suffix,
            'subject' => 'Science',
            'rack_no' => 'R9',
            'publish' => 'Pub',
            'author' => 'Author',
            'qty' => 2,
            'perunitcost' => 10,
            'postdate' => now()->format('Y-m-d'),
            'description' => 'Test',
            'available' => 'yes',
            'is_active' => 'no',
        ]);
        $this->cleanupBookIds[] = $book->id;

        $member = LibraryMember::query()->create([
            'library_card_no' => 'ICARD-'.$suffix,
            'member_type' => 'teacher',
            'member_id' => $staff->id,
            'is_active' => 'no',
        ]);
        $this->cleanupMemberIds[] = $member->id;

        $this->get('/admin/member/issue/'.$member->id)
            ->assertOk()
            ->assertSee('Issue Book', false)
            ->assertSee('Issue Book '.$suffix, false);

        $due = now()->addDays(7)->format('Y-m-d');
        $this->post('/admin/member/issue/'.$member->id, [
            'member_id' => $member->id,
            'book_id' => $book->id,
            'return_date' => $due,
        ])->assertRedirect('/admin/member/issue/'.$member->id);

        $issue = BookIssue::query()
            ->where('member_id', $member->id)
            ->where('book_id', $book->id)
            ->where('is_returned', 0)
            ->firstOrFail();
        $this->cleanupIssueIds[] = $issue->id;
        $this->assertSame($due, (string) $issue->duereturn_date);

        // Already issued to same member should fail.
        $this->from('/admin/member/issue/'.$member->id)
            ->post('/admin/member/issue/'.$member->id, [
                'member_id' => $member->id,
                'book_id' => $book->id,
                'return_date' => $due,
            ])
            ->assertSessionHasErrors('book_id');

        $returnDate = now()->format('Y-m-d');
        $this->post('/admin/member/bookreturn', [
            'id' => $issue->id,
            'member_id' => $member->id,
            'date' => $returnDate,
        ])->assertRedirect('/admin/member/issue/'.$member->id);

        $issue->refresh();
        $this->assertSame(1, (int) $issue->is_returned);
        $this->assertSame($returnDate, (string) $issue->return_date);
    }
}
