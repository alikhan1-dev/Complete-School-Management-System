<?php

namespace Tests\Feature\Settings;

use App\Modules\Staff\Models\Staff;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SchoolModuleSettingFlowTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var array<string, mixed>|null */
    private ?array $groupSnapshot = null;

    /** @var list<array<string, mixed>> */
    private array $studentSnapshots = [];

    protected function setUp(): void
    {
        parent::setUp();
        $group = DB::table('permission_group')->where('system', 0)->orderBy('id')->first();
        $this->assertNotNull($group, 'permission_group system=0 row is required');
        $this->groupSnapshot = (array) $group;

        $students = DB::table('permission_student')->where('group_id', $group->id)->get();
        $this->studentSnapshots = $students->map(fn ($row) => (array) $row)->all();
    }

    protected function tearDown(): void
    {
        if ($this->groupSnapshot !== null) {
            $id = $this->groupSnapshot['id'];
            $payload = $this->groupSnapshot;
            unset($payload['id']);
            DB::table('permission_group')->where('id', $id)->update($payload);
            $this->groupSnapshot = null;
        }

        foreach ($this->studentSnapshots as $row) {
            $id = $row['id'];
            unset($row['id']);
            DB::table('permission_student')->where('id', $id)->update($row);
        }
        $this->studentSnapshots = [];

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

        $token = uniqid('schmod', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'MD-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Module',
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
    }

    public function test_admin_module_requires_staff_auth(): void
    {
        $this->get('/admin/module')->assertRedirect();
    }

    public function test_superadmin_can_view_module_toggles(): void
    {
        $this->actingAsSuperAdmin();
        $name = (string) ($this->groupSnapshot['name'] ?? '');

        $this->get('/admin/module')
            ->assertOk()
            ->assertSee('Modules', false)
            ->assertSee($name, false);
    }

    public function test_change_status_updates_group_and_cascades_student_parent(): void
    {
        $this->actingAsSuperAdmin();
        $id = (int) $this->groupSnapshot['id'];
        $next = ((int) $this->groupSnapshot['is_active'] === 1) ? 0 : 1;

        $this->postJson('/admin/module/changeStatus', [
            'id' => $id,
            'status' => $next,
            'role' => 'system',
        ])
            ->assertOk()
            ->assertJsonPath('status', 1);

        $this->assertSame($next, (int) DB::table('permission_group')->where('id', $id)->value('is_active'));

        foreach (DB::table('permission_student')->where('group_id', $id)->get() as $row) {
            $this->assertSame($next, (int) $row->student);
            $this->assertSame($next, (int) $row->parent);
        }
    }

    public function test_change_student_status_updates_student_column_only(): void
    {
        $this->actingAsSuperAdmin();
        $row = DB::table('permission_student')->where('system', 0)->orderBy('id')->first();
        $this->assertNotNull($row);
        $next = ((int) $row->student === 1) ? 0 : 1;
        $parentBefore = (int) $row->parent;

        $this->postJson('/admin/module/changeStudentStatus', [
            'id' => $row->id,
            'status' => $next,
            'role' => 'student',
        ])
            ->assertOk()
            ->assertJsonPath('status', 1);

        $updated = DB::table('permission_student')->where('id', $row->id)->first();
        $this->assertSame($next, (int) $updated->student);
        $this->assertSame($parentBefore, (int) $updated->parent);

        DB::table('permission_student')->where('id', $row->id)->update([
            'student' => $row->student,
            'parent' => $row->parent,
        ]);
    }

    public function test_parent_toggle_posts_to_change_student_status(): void
    {
        $this->actingAsSuperAdmin();
        $row = DB::table('permission_student')->where('system', 0)->orderBy('id')->first();
        $this->assertNotNull($row);
        $next = ((int) $row->parent === 1) ? 0 : 1;
        $studentBefore = (int) $row->student;

        $this->postJson('/admin/module/changeStudentStatus', [
            'id' => $row->id,
            'status' => $next,
            'role' => 'parent',
        ])
            ->assertOk()
            ->assertJsonPath('status', 1);

        $updated = DB::table('permission_student')->where('id', $row->id)->first();
        $this->assertSame($next, (int) $updated->parent);
        $this->assertSame($studentBefore, (int) $updated->student);

        DB::table('permission_student')->where('id', $row->id)->update([
            'student' => $row->student,
            'parent' => $row->parent,
        ]);
    }
}
