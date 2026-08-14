<?php

namespace Tests\Feature\FrontOffice;

use App\Modules\Staff\Models\Staff;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class DispatchReceiveFlowTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<int> */
    private array $cleanupIds = [];

    /** @var list<string> */
    private array $cleanupFiles = [];

    protected function tearDown(): void
    {
        if ($this->cleanupIds !== []) {
            DB::table('dispatch_receive')->whereIn('id', $this->cleanupIds)->delete();
        }
        $this->cleanupIds = [];

        $dir = public_path('uploads/front_office/dispatch_receive');
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

        $token = uniqid('dpr', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'DPR-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Dispatch',
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

    public function test_dispatch_and_receive_index_require_staff_auth(): void
    {
        $this->get('/admin/dispatch')->assertRedirect();
        $this->get('/admin/receive')->assertRedirect();
    }

    public function test_dispatch_requires_to_title_and_receive_requires_from_title(): void
    {
        $this->actingAsSuperAdmin();
        $this->post('/admin/dispatch', [
            'from' => 'Office',
            'ref_no' => 'X1',
        ])->assertOk()->assertSee('The To Title field is required.', false);

        $this->post('/admin/receive', [
            'to_title' => 'Office',
            'ref_no' => 'X2',
        ])->assertOk()->assertSee('The From Title field is required.', false);
    }

    public function test_superadmin_can_add_view_edit_download_and_delete_dispatch(): void
    {
        $this->actingAsSuperAdmin();
        $suffix = uniqid();
        $to = 'To '.$suffix;
        $file = UploadedFile::fake()->create('letter.txt', 10, 'text/plain');

        $this->get('/admin/dispatch')->assertOk()->assertSee('Postal Dispatch List', false);

        $this->post('/admin/dispatch', [
            'to_title' => $to,
            'ref_no' => 'D-'.$suffix,
            'address' => 'Main road',
            'note' => 'Sent',
            'from' => 'Front Office',
            'date' => date('Y-m-d'),
            'file' => $file,
        ])->assertRedirect('/admin/dispatch');

        $row = DB::table('dispatch_receive')->where('to_title', $to)->where('type', 'dispatch')->first();
        $this->assertNotNull($row);
        $this->cleanupIds[] = (int) $row->id;
        $this->assertSame('Front Office', $row->from_title);
        $this->assertSame('D-'.$suffix, $row->reference_no);
        $this->assertNotSame('', (string) $row->image);
        $this->cleanupFiles[] = (string) $row->image;

        $this->get('/admin/dispatch')->assertOk()->assertSee($to, false);
        $this->get('/admin/dispatch/details/'.$row->id.'/dispatch')->assertOk()->assertSee('Main road', false);
        $this->get('/admin/dispatch/download/'.$row->id)->assertOk();
        $this->get('/admin/dispatch/editdispatch/'.$row->id)->assertOk()->assertSee($to, false);

        $this->post('/admin/dispatch/editdispatch/'.$row->id, [
            'to_title' => $to.' Edited',
            'ref_no' => 'D-'.$suffix,
            'address' => 'Main road',
            'note' => 'Updated',
            'from' => 'Front Office',
            'date' => date('Y-m-d'),
        ])->assertRedirect('/admin/dispatch');

        $this->assertSame($to.' Edited', DB::table('dispatch_receive')->where('id', $row->id)->value('to_title'));
        $this->assertSame((string) $row->image, (string) DB::table('dispatch_receive')->where('id', $row->id)->value('image'));

        $this->get('/admin/dispatch/delete/'.$row->id)->assertRedirect('/admin/dispatch');
        $this->assertNull(DB::table('dispatch_receive')->where('id', $row->id)->first());
        $this->cleanupIds = array_values(array_filter($this->cleanupIds, fn ($id) => $id !== (int) $row->id));
    }

    public function test_superadmin_can_add_view_edit_and_delete_receive(): void
    {
        $this->actingAsSuperAdmin();
        $suffix = uniqid();
        $from = 'From '.$suffix;

        $this->get('/admin/receive')->assertOk()->assertSee('Postal Receive List', false);

        $this->post('/admin/receive', [
            'from_title' => $from,
            'to_title' => 'Principal',
            'ref_no' => 'R-'.$suffix,
            'address' => 'Board office',
            'note' => 'Received',
            'date' => date('Y-m-d'),
        ])->assertRedirect('/admin/receive');

        $row = DB::table('dispatch_receive')->where('from_title', $from)->where('type', 'receive')->first();
        $this->assertNotNull($row);
        $this->cleanupIds[] = (int) $row->id;
        $this->assertSame('Principal', $row->to_title);
        $this->assertSame('', (string) ($row->image ?? ''));

        $this->get('/admin/receive')->assertOk()->assertSee($from, false);
        $this->get('/admin/dispatch/details/'.$row->id.'/receive')->assertOk()->assertSee('Board office', false);
        $this->get('/admin/receive/editreceive/'.$row->id)->assertOk()->assertSee($from, false);

        $this->post('/admin/receive/editreceive/'.$row->id, [
            'from_title' => $from.' Edited',
            'to_title' => 'Principal',
            'ref_no' => 'R-'.$suffix,
            'address' => 'Board office',
            'note' => 'Filed',
            'date' => date('Y-m-d'),
        ])->assertRedirect('/admin/receive');

        $this->assertSame($from.' Edited', DB::table('dispatch_receive')->where('id', $row->id)->value('from_title'));

        $this->get('/admin/receive/delete/'.$row->id)->assertRedirect('/admin/receive');
        $this->assertNull(DB::table('dispatch_receive')->where('id', $row->id)->first());
        $this->cleanupIds = array_values(array_filter($this->cleanupIds, fn ($id) => $id !== (int) $row->id));
    }
}
