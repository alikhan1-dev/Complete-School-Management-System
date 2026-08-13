<?php

namespace Tests\Feature\Library;

use App\Modules\Library\Models\Book;
use App\Modules\Staff\Models\Staff;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BookImportTest extends TestCase
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

        $token = uniqid('libimp', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'LIBI-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Library',
            'surname' => 'Import',
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

    public function test_import_books_from_csv_and_download_sample(): void
    {
        $this->actingAsSuperAdmin();
        $suffix = uniqid();

        $this->get('/admin/book/import')
            ->assertOk()
            ->assertSee('Import Book', false)
            ->assertSee('Download Sample Import File', false);

        $sample = $this->get('/admin/book/exportformat');
        $sample->assertOk();
        $sample->assertHeader('content-disposition');
        $this->assertStringContainsString(
            'book_title,book_no,isbn_no',
            $sample->streamedContent()
        );

        $csv = implode("\n", [
            'book_title,book_no,isbn_no,subject,rack_no,publish,author,qty,perunitcost,postdate,description,available',
            'Imported Alpha '.$suffix.',BN-A-'.$suffix.',ISBN-A-'.$suffix.',Math,R1,Pub A,Author A,2,10.50,2026-08-01,Alpha desc,yes',
            'Imported Beta '.$suffix.',BN-B-'.$suffix.',ISBN-B-'.$suffix.',Science,R2,Pub B,Author B,4,20.00,2026-08-02,Beta desc,yes',
            ',,,,,,,0,,,,,,',
        ])."\n";

        $file = UploadedFile::fake()->createWithContent('import_books_'.$suffix.'.csv', $csv);

        $this->post('/admin/book/import', [
            'file' => $file,
        ])->assertRedirect('/admin/book/import')
            ->assertSessionHas('success');

        $alpha = Book::query()->where('book_title', 'Imported Alpha '.$suffix)->firstOrFail();
        $beta = Book::query()->where('book_title', 'Imported Beta '.$suffix)->firstOrFail();
        $this->cleanupBookIds[] = $alpha->id;
        $this->cleanupBookIds[] = $beta->id;

        $this->assertSame('BN-A-'.$suffix, (string) $alpha->book_no);
        $this->assertSame(2, (int) $alpha->qty);
        $this->assertEquals(10.5, (float) $alpha->perunitcost);
        $this->assertSame('yes', (string) $alpha->available);

        $this->assertSame('BN-B-'.$suffix, (string) $beta->book_no);
        $this->assertSame(4, (int) $beta->qty);
        $this->assertSame(2, Book::query()->where('book_title', 'like', 'Imported %'.$suffix)->count());
    }
}
