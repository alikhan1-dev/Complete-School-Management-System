<?php

namespace Tests\Feature\FrontOffice;

use App\Modules\Staff\Models\Staff;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdmissionEnquiryFlowTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<int> */
    private array $cleanupEnquiryIds = [];

    /** @var list<int> */
    private array $cleanupFollowUpIds = [];

    /** @var list<int> */
    private array $cleanupSourceIds = [];

    protected function tearDown(): void
    {
        if ($this->cleanupFollowUpIds !== []) {
            DB::table('follow_up')->whereIn('id', $this->cleanupFollowUpIds)->delete();
        }
        $this->cleanupFollowUpIds = [];

        if ($this->cleanupEnquiryIds !== []) {
            DB::table('follow_up')->whereIn('enquiry_id', $this->cleanupEnquiryIds)->delete();
            DB::table('enquiry')->whereIn('id', $this->cleanupEnquiryIds)->delete();
        }
        $this->cleanupEnquiryIds = [];

        if ($this->cleanupSourceIds !== []) {
            DB::table('source')->whereIn('id', $this->cleanupSourceIds)->delete();
        }
        $this->cleanupSourceIds = [];

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

        $token = uniqid('enq', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'ENQ-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Enquiry',
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

    public function test_enquiry_index_requires_staff_auth(): void
    {
        $this->get('/admin/enquiry')->assertRedirect();
    }

    public function test_superadmin_can_add_search_edit_follow_up_and_delete_enquiry(): void
    {
        $staffId = $this->actingAsSuperAdmin();
        $suffix = uniqid();
        $sourceName = 'Src'.$suffix;
        $sourceId = DB::table('source')->insertGetId([
            'source' => $sourceName,
            'description' => 'test',
        ]);
        $this->cleanupSourceIds[] = $sourceId;

        $name = 'Parent '.$suffix;
        $phone = '03'.substr(preg_replace('/\D/', '', $suffix).'00000000', 0, 9);
        $today = date('Y-m-d');

        $this->get('/admin/enquiry')->assertOk()->assertSee('Admission Enquiry', false);

        $add = $this->post('/admin/enquiry/add', [
            'name' => $name,
            'contact' => $phone,
            'address' => 'Street 1',
            'reference' => '',
            'date' => $today,
            'description' => 'Need admission',
            'follow_up_date' => $today,
            'note' => 'First note',
            'source' => $sourceName,
            'email' => $suffix.'@example.test',
            'assigned' => '',
            'class' => '',
            'no_of_child' => '2',
        ])->assertOk()->assertJsonPath('status', 'success');

        $enquiry = DB::table('enquiry')->where('contact', $phone)->first();
        $this->assertNotNull($enquiry);
        $this->cleanupEnquiryIds[] = (int) $enquiry->id;
        $this->assertSame('active', $enquiry->status);
        $this->assertSame($staffId, (int) $enquiry->created_by);

        $this->get('/admin/enquiry')->assertOk()->assertSee($name, false);

        $this->post('/admin/enquiry', [
            'from_date' => $today,
            'to_date' => $today,
            'source' => $sourceName,
            'status' => 'active',
            'class' => '',
        ])->assertOk()->assertSee($name, false);

        $this->post('/admin/enquiry/check_number', [
            'phone_number' => $phone,
        ])->assertOk()->assertJsonPath('status', 'success');

        $this->post('/admin/enquiry/editpost/'.$enquiry->id, [
            'name' => $name.' Edited',
            'contact' => $phone,
            'address' => 'Street 2',
            'reference' => '',
            'date' => $today,
            'description' => 'Updated',
            'follow_up_date' => $today,
            'note' => 'Edited note',
            'source' => $sourceName,
            'email' => $suffix.'@example.test',
            'assigned' => '',
            'class' => '',
            'no_of_child' => '3',
        ])->assertOk()->assertJsonPath('status', 'success');

        $this->assertSame($name.' Edited', DB::table('enquiry')->where('id', $enquiry->id)->value('name'));

        $this->get('/admin/enquiry/details/'.$enquiry->id.'/active')
            ->assertOk()
            ->assertSee($name.' Edited', false);

        $this->get('/admin/enquiry/follow_up/'.$enquiry->id.'/active/'.$staffId)
            ->assertOk()
            ->assertSee('Follow Up', false);

        $this->post('/admin/enquiry/follow_up_insert', [
            'enquiry_id' => (string) $enquiry->id,
            'date' => $today,
            'follow_up_date' => $today,
            'response' => 'Called parent',
            'note' => 'Will visit',
        ])->assertOk()->assertJsonPath('status', 'success');

        $follow = DB::table('follow_up')->where('enquiry_id', $enquiry->id)->first();
        $this->assertNotNull($follow);
        $this->cleanupFollowUpIds[] = (int) $follow->id;

        $this->get('/admin/enquiry/follow_up_list/'.$enquiry->id)
            ->assertOk()
            ->assertSee('Called parent', false);

        $this->post('/admin/enquiry/change_status', [
            'id' => (string) $enquiry->id,
            'status' => 'won',
        ])->assertOk()->assertJsonPath('status', 'success');
        $this->assertSame('won', DB::table('enquiry')->where('id', $enquiry->id)->value('status'));

        $this->post('/admin/enquiry/delete/'.$enquiry->id)
            ->assertOk()
            ->assertJsonPath('status', 'success');
        $this->assertNull(DB::table('enquiry')->where('id', $enquiry->id)->first());
    }

    public function test_add_enquiry_requires_name_phone_source_and_dates(): void
    {
        $this->actingAsSuperAdmin();

        $this->post('/admin/enquiry/add', [])
            ->assertOk()
            ->assertJsonPath('status', 'fail');
    }
}
