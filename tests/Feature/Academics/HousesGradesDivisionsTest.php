<?php

namespace Tests\Feature\Academics;

use App\Modules\Academics\Models\Grade;
use App\Modules\Academics\Models\MarkDivision;
use App\Modules\Academics\Models\SchoolHouse;
use App\Modules\Staff\Models\Staff;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HousesGradesDivisionsTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    protected function tearDown(): void
    {
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

        $token = uniqid('sa', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'HG-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Test',
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
        $this->actingAs(Staff::query()->findOrFail($staffId), 'staff');
    }

    public function test_house_grade_and_division_crud(): void
    {
        $this->actingAsSuperAdmin();
        $suffix = uniqid();

        $this->get('/admin/schoolhouse')->assertOk()->assertSee('House List', false);
        $this->post('/admin/schoolhouse/create', [
            'house_name' => 'House-'.$suffix,
            'description' => 'desc',
        ])->assertRedirect(route('academics.school_houses.index'));

        $house = SchoolHouse::query()->where('house_name', 'House-'.$suffix)->firstOrFail();
        $this->post('/admin/schoolhouse/edit/'.$house->id, [
            'house_name' => 'House-'.$suffix.'-u',
            'description' => 'updated',
        ])->assertRedirect(route('academics.school_houses.index'));
        $this->get('/admin/schoolhouse/delete/'.$house->id)->assertRedirect(route('academics.school_houses.index'));
        $this->assertDatabaseMissing('school_houses', ['id' => $house->id]);

        $this->get('/admin/grade')->assertOk()->assertSee('Grade List', false);
        $this->post('/admin/grade/index', [
            'exam_type' => 'basic_system',
            'name' => 'G-'.$suffix,
            'mark_from' => 90,
            'mark_upto' => 100,
            'grade_point' => 4,
            'description' => 'A',
        ])->assertRedirect(route('academics.grades.index'));
        $grade = Grade::query()->where('name', 'G-'.$suffix)->firstOrFail();
        $this->get('/admin/grade/delete/'.$grade->id)->assertRedirect(route('academics.grades.index'));
        $this->assertDatabaseMissing('grades', ['id' => $grade->id]);

        $this->get('/admin/marksdivision')->assertOk()->assertSee('Division List', false);
        $this->post('/admin/marksdivision/index', [
            'name' => 'D-'.$suffix,
            'percentage_from' => 60,
            'percentage_to' => 100,
        ])->assertRedirect(route('academics.mark_divisions.index'));
        $division = MarkDivision::query()->where('name', 'D-'.$suffix)->firstOrFail();
        $this->get('/admin/marksdivision/delete/'.$division->id)->assertRedirect(route('academics.mark_divisions.index'));
        $this->assertDatabaseMissing('mark_divisions', ['id' => $division->id]);
    }
}
