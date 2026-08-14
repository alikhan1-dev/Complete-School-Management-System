<?php

namespace Tests\Feature\FrontOffice;

use App\Modules\Staff\Models\Staff;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SetupMastersFlowTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var array<string, list<int>> */
    private array $cleanup = [
        'visitors_purpose' => [],
        'complaint_type' => [],
        'source' => [],
        'reference' => [],
    ];

    protected function tearDown(): void
    {
        foreach ($this->cleanup as $table => $ids) {
            if ($ids !== []) {
                DB::table($table)->whereIn('id', $ids)->delete();
            }
        }
        $this->cleanup = [
            'visitors_purpose' => [],
            'complaint_type' => [],
            'source' => [],
            'reference' => [],
        ];

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

        $token = uniqid('set', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'SET-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Setup',
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

    public function test_setup_indexes_require_staff_auth(): void
    {
        $this->get('/admin/visitorspurpose')->assertRedirect();
        $this->get('/admin/complainttype')->assertRedirect();
        $this->get('/admin/source')->assertRedirect();
        $this->get('/admin/reference')->assertRedirect();
    }

    public function test_required_name_fields(): void
    {
        $this->actingAsSuperAdmin();
        $this->post('/admin/visitorspurpose', ['description' => 'x'])
            ->assertOk()->assertSee('The Purpose field is required.', false);
        $this->post('/admin/complainttype', ['description' => 'x'])
            ->assertOk()->assertSee('The Complaint Type field is required.', false);
        $this->post('/admin/source', ['description' => 'x'])
            ->assertOk()->assertSee('The Source field is required.', false);
        $this->post('/admin/reference', ['description' => 'x'])
            ->assertOk()->assertSee('The Reference field is required.', false);
    }

    public function test_superadmin_can_crud_all_setup_masters(): void
    {
        $this->actingAsSuperAdmin();
        $suffix = uniqid();

        $this->assertMasterCrud(
            table: 'visitors_purpose',
            field: 'visitors_purpose',
            index: '/admin/visitorspurpose',
            editPrefix: '/admin/visitorspurpose/edit',
            deletePrefix: '/admin/visitorspurpose/delete',
            name: 'Purp'.$suffix,
            listHeading: 'Purpose List',
        );
        $this->assertMasterCrud(
            table: 'complaint_type',
            field: 'complaint_type',
            index: '/admin/complainttype',
            editPrefix: '/admin/complainttype/editcomplainttype',
            deletePrefix: '/admin/complainttype/delete',
            name: 'Ctype'.$suffix,
            listHeading: 'Complaint Type List',
        );
        $this->assertMasterCrud(
            table: 'source',
            field: 'source',
            index: '/admin/source',
            editPrefix: '/admin/source/edit',
            deletePrefix: '/admin/source/delete',
            name: 'Src'.$suffix,
            listHeading: 'Source List',
        );
        $this->assertMasterCrud(
            table: 'reference',
            field: 'reference',
            index: '/admin/reference',
            editPrefix: '/admin/reference/edit',
            deletePrefix: '/admin/reference/delete',
            name: 'Ref'.$suffix,
            listHeading: 'Reference List',
        );
    }

    private function assertMasterCrud(
        string $table,
        string $field,
        string $index,
        string $editPrefix,
        string $deletePrefix,
        string $name,
        string $listHeading,
    ): void {
        $this->get($index)->assertOk()->assertSee($listHeading, false);

        $this->post($index, [
            $field => $name,
            'description' => 'Desc '.$name,
        ])->assertRedirect($index);

        $row = DB::table($table)->where($field, $name)->first();
        $this->assertNotNull($row);
        $this->cleanup[$table][] = (int) $row->id;
        $this->assertSame('Desc '.$name, $row->description);

        $this->get($index)->assertOk()->assertSee($name, false);
        $this->get($editPrefix.'/'.$row->id)->assertOk()->assertSee($name, false);

        $this->post($editPrefix.'/'.$row->id, [
            $field => $name.' Edited',
            'description' => 'Updated',
        ])->assertRedirect($index);

        $this->assertSame($name.' Edited', DB::table($table)->where('id', $row->id)->value($field));

        $this->get($deletePrefix.'/'.$row->id)->assertRedirect($index);
        $this->assertNull(DB::table($table)->where('id', $row->id)->first());
        $this->cleanup[$table] = array_values(array_filter(
            $this->cleanup[$table],
            fn ($id) => $id !== (int) $row->id
        ));
    }
}
