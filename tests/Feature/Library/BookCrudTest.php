<?php

namespace Tests\Feature\Library;

use App\Modules\Library\Models\Book;
use App\Modules\Staff\Models\Staff;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BookCrudTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<int> */
    private array $cleanupBookIds = [];

    protected function tearDown(): void
    {
        if ($this->cleanupBookIds !== []) {
            DB::table('book_issues')->whereIn('book_id', $this->cleanupBookIds)->delete();
            DB::table('books')->whereIn('id', $this->cleanupBookIds)->delete();
        }
        $this->cleanupBookIds = [];

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

        $token = uniqid('libbk', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'LIB-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Library',
            'surname' => 'Staff',
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

    public function test_book_list_create_edit_delete(): void
    {
        $this->actingAsSuperAdmin();
        $suffix = uniqid();

        $this->get('/admin/book/getall')
            ->assertOk()
            ->assertSee('Book List', false)
            ->assertSee('Add Book', false);

        $this->post('/admin/book/create', [
            'book_title' => 'Laravel Library '.$suffix,
            'book_no' => 'BN-'.$suffix,
            'isbn_no' => 'ISBN-'.$suffix,
            'subject' => 'Computing',
            'rack_no' => 'R1',
            'publish' => 'Publisher '.$suffix,
            'author' => 'Author '.$suffix,
            'qty' => 3,
            'perunitcost' => '12.50',
            'postdate' => now()->format('Y-m-d'),
            'description' => 'Desc '.$suffix,
        ])->assertRedirect('/admin/book/getall');

        $book = Book::query()->where('book_title', 'Laravel Library '.$suffix)->firstOrFail();
        $this->cleanupBookIds[] = $book->id;
        $this->assertSame('BN-'.$suffix, (string) $book->book_no);
        $this->assertSame(3, (int) $book->qty);
        $this->assertEquals(12.5, (float) $book->perunitcost);

        $this->get('/admin/book/edit/'.$book->id)
            ->assertOk()
            ->assertSee('Edit Book', false)
            ->assertSee('Laravel Library '.$suffix, false);

        $this->post('/admin/book/edit/'.$book->id, [
            'book_title' => 'Updated Library '.$suffix,
            'book_no' => 'BN-'.$suffix,
            'isbn_no' => 'ISBN-'.$suffix,
            'subject' => 'Computing',
            'rack_no' => 'R2',
            'publish' => 'Publisher '.$suffix,
            'author' => 'Author '.$suffix,
            'qty' => 5,
            'perunitcost' => '15.00',
            'postdate' => now()->format('Y-m-d'),
            'description' => 'Updated '.$suffix,
        ])->assertRedirect('/admin/book/getall');

        $book->refresh();
        $this->assertSame('Updated Library '.$suffix, $book->book_title);
        $this->assertSame(5, (int) $book->qty);

        DB::table('book_issues')->insert([
            'book_id' => $book->id,
            'member_id' => null,
            'duereturn_date' => null,
            'return_date' => null,
            'issue_date' => now()->format('Y-m-d'),
            'is_returned' => 0,
            'is_active' => 'no',
        ]);

        $this->get('/admin/book/delete/'.$book->id)->assertRedirect('/admin/book/getall');
        $this->assertNull(Book::query()->find($book->id));
        $this->assertSame(0, DB::table('book_issues')->where('book_id', $book->id)->count());
        $this->cleanupBookIds = [];
    }
}
