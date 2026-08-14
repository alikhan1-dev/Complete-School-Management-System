<?php

namespace Tests\Feature\FrontOffice;

use App\Modules\Staff\Models\Staff;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ComplaintFlowTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<int> */
    private array $cleanupComplaintIds = [];

    /** @var list<int> */
    private array $cleanupTypeIds = [];

    /** @var list<int> */
    private array $cleanupSourceIds = [];

    /** @var list<string> */
    private array $cleanupFiles = [];

    protected function tearDown(): void
    {
        if ($this->cleanupComplaintIds !== []) {
            DB::table('complaint')->whereIn('id', $this->cleanupComplaintIds)->delete();
        }
        $this->cleanupComplaintIds = [];

        if ($this->cleanupTypeIds !== []) {
            DB::table('complaint_type')->whereIn('id', $this->cleanupTypeIds)->delete();
        }
        $this->cleanupTypeIds = [];

        if ($this->cleanupSourceIds !== []) {
            DB::table('source')->whereIn('id', $this->cleanupSourceIds)->delete();
        }
        $this->cleanupSourceIds = [];

        $dir = public_path('uploads/front_office/complaints');
        foreach ($this->cleanupFiles as $file) {
            $path = $dir.DIRECTORY_SEPARATOR.basename($file);
            if (is_file($path)) {
                File::delete($path);
            }
        }
        $this->cleanupFiles = [];

        foreach ($this->createdStaffIds as $staffId) {
            DB::table('staff_roles')->where('staff_id', $staffId)->delete();
            DB::table('staff')->where('id', $staffId)->delete();
        }
        $this->createdStaffIds = [];

        parent::tearDown();
    }

    private function actingAsSuperAdmin(): int
    {
        $roleId = (int) (DB::table('roles')->where('is_superadmin', 1)->value('id')
            ?: DB::table('roles')->where('name', 'Super Admin')->value('id'));
        $this->assertGreaterThan(0, $roleId);

        $token = uniqid('cmp', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'CMP-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Complaint',
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
        $this->actingAs(Staff::query()->findOrFail($staffId), 'staff');

        return $staffId;
    }

    public function test_complaint_index_requires_staff_auth(): void
    {
        $this->get('/admin/complaint')->assertRedirect();
    }

    public function test_create_requires_complain_by_name(): void
    {
        $this->actingAsSuperAdmin();
        $this->post('/admin/complaint', [
            'complaint' => 'Noise',
            'contact' => '0300111222',
        ])->assertOk()->assertSee('The Complain By field is required.', false);

        $this->assertNull(DB::table('complaint')->where('contact', '0300111222')->first());
    }

    public function test_superadmin_can_add_view_edit_download_and_delete_complaint(): void
    {
        $this->actingAsSuperAdmin();
        $suffix = uniqid();
        $type = 'Type'.$suffix;
        $source = 'Src'.$suffix;
        $name = 'By '.$suffix;

        $this->cleanupTypeIds[] = DB::table('complaint_type')->insertGetId([
            'complaint_type' => $type,
            'description' => 'test',
        ]);
        $this->cleanupSourceIds[] = DB::table('source')->insertGetId([
            'source' => $source,
            'description' => 'test',
        ]);

        $this->get('/admin/complaint')->assertOk()->assertSee('Complaint List', false)->assertSee($type, false);

        $file = UploadedFile::fake()->create('note.txt', 10, 'text/plain');
        $this->post('/admin/complaint', [
            'complaint' => $type,
            'source' => $source,
            'name' => $name,
            'contact' => '0300111222',
            'date' => date('Y-m-d'),
            'description' => 'Hall noise',
            'action_taken' => 'Logged',
            'assigned' => 'Office',
            'note' => 'First note',
            'file' => $file,
        ])->assertRedirect('/admin/complaint');

        $row = DB::table('complaint')->where('name', $name)->first();
        $this->assertNotNull($row);
        $this->cleanupComplaintIds[] = (int) $row->id;
        $this->assertSame('', (string) $row->email);
        $this->assertSame($type, $row->complaint_type);
        $this->assertSame($source, $row->source);
        $this->assertNotSame('', (string) $row->image);
        $this->cleanupFiles[] = (string) $row->image;

        $this->get('/admin/complaint')->assertOk()->assertSee($name, false);
        $this->get('/admin/complaint/details/'.$row->id)->assertOk()->assertSee('Hall noise', false);
        $this->get('/admin/complaint/download/'.$row->id)->assertOk();

        $this->get('/admin/complaint/edit/'.$row->id)->assertOk()->assertSee($name, false);

        $this->post('/admin/complaint/edit/'.$row->id, [
            'complaint' => $type,
            'source' => $source,
            'name' => $name.' Edited',
            'contact' => '0300111222',
            'date' => date('Y-m-d'),
            'description' => 'Updated desc',
            'action_taken' => 'Resolved',
            'assigned' => 'Office',
            'note' => 'Second note',
        ])->assertRedirect('/admin/complaint');

        $updated = DB::table('complaint')->where('id', $row->id)->first();
        $this->assertSame($name.' Edited', $updated->name);
        $this->assertSame((string) $row->image, (string) $updated->image);

        $this->get('/admin/complaint/delete/'.$row->id)->assertRedirect('/admin/complaint');
        $this->assertNull(DB::table('complaint')->where('id', $row->id)->first());
        $this->cleanupComplaintIds = [];
    }
}
