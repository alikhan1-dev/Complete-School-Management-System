<?php

namespace Tests\Feature\Hostel;

use App\Modules\Academics\Models\AcademicSession;
use App\Modules\Academics\Models\ClassSection;
use App\Modules\Academics\Models\SchoolClass;
use App\Modules\Academics\Models\Section;
use App\Modules\Hostel\Models\Hostel;
use App\Modules\Hostel\Models\HostelRoom;
use App\Modules\Hostel\Models\RoomType;
use App\Modules\Shared\Services\SchoolContext;
use App\Modules\Staff\Models\Staff;
use App\Modules\Students\Models\Student;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StudentHostelReportClassTeacherScopeTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<int> */
    private array $cleanupHostelIds = [];

    /** @var list<int> */
    private array $cleanupTypeIds = [];

    /** @var list<int> */
    private array $cleanupRoomIds = [];

    /** @var list<int> */
    private array $cleanupStudentIds = [];

    /** @var list<int> */
    private array $cleanupClassIds = [];

    /** @var list<int> */
    private array $cleanupSectionIds = [];

    /** @var list<int> */
    private array $cleanupClassTeacherIds = [];

    /** @var list<int> */
    private array $cleanupRolePermissionIds = [];

    private string $previousClassTeacherSetting = 'no';

    protected function tearDown(): void
    {
        if ($this->cleanupStudentIds !== []) {
            DB::table('student_session')->whereIn('student_id', $this->cleanupStudentIds)->delete();
            DB::table('students')->whereIn('id', $this->cleanupStudentIds)->delete();
        }
        $this->cleanupStudentIds = [];

        if ($this->cleanupClassTeacherIds !== []) {
            DB::table('class_teacher')->whereIn('id', $this->cleanupClassTeacherIds)->delete();
        }
        $this->cleanupClassTeacherIds = [];

        if ($this->cleanupRoomIds !== []) {
            DB::table('hostel_rooms')->whereIn('id', $this->cleanupRoomIds)->delete();
        }
        $this->cleanupRoomIds = [];

        if ($this->cleanupHostelIds !== []) {
            DB::table('hostel_rooms')->whereIn('hostel_id', $this->cleanupHostelIds)->delete();
            DB::table('hostel')->whereIn('id', $this->cleanupHostelIds)->delete();
        }
        $this->cleanupHostelIds = [];

        if ($this->cleanupTypeIds !== []) {
            DB::table('room_types')->whereIn('id', $this->cleanupTypeIds)->delete();
        }
        $this->cleanupTypeIds = [];

        if ($this->cleanupClassIds !== []) {
            DB::table('class_sections')->whereIn('class_id', $this->cleanupClassIds)->delete();
            DB::table('classes')->whereIn('id', $this->cleanupClassIds)->delete();
        }
        $this->cleanupClassIds = [];

        if ($this->cleanupSectionIds !== []) {
            DB::table('sections')->whereIn('id', $this->cleanupSectionIds)->delete();
        }
        $this->cleanupSectionIds = [];

        if ($this->cleanupRolePermissionIds !== []) {
            DB::table('roles_permissions')->whereIn('id', $this->cleanupRolePermissionIds)->delete();
        }
        $this->cleanupRolePermissionIds = [];

        foreach ($this->createdStaffIds as $staffId) {
            DB::table('staff_roles')->where('staff_id', $staffId)->delete();
            DB::table('staff')->where('id', $staffId)->delete();
        }
        $this->createdStaffIds = [];

        DB::table('sch_settings')->limit(1)->update(['class_teacher' => $this->previousClassTeacherSetting]);
        app(SchoolContext::class)->clearCache();

        parent::tearDown();
    }

    private function ensureTeacherPrivilege(): void
    {
        $permCatId = (int) DB::table('permission_category')->where('short_code', 'hostel_report')->value('id');
        $this->assertGreaterThan(0, $permCatId);

        $existing = DB::table('roles_permissions')
            ->where('role_id', 2)
            ->where('perm_cat_id', $permCatId)
            ->first();

        $payload = ['can_view' => 1, 'can_add' => 1, 'can_edit' => 1, 'can_delete' => 1];

        if ($existing) {
            DB::table('roles_permissions')->where('id', $existing->id)->update($payload);
        } else {
            $this->cleanupRolePermissionIds[] = DB::table('roles_permissions')->insertGetId(array_merge([
                'role_id' => 2,
                'perm_cat_id' => $permCatId,
            ], $payload));
        }
    }

    private function insertStaff(int $roleId, string $prefix): Staff
    {
        $token = uniqid($prefix, true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => strtoupper($prefix).'-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Hostel',
            'surname' => 'Teacher',
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
        DB::table('staff_roles')->insert(['staff_id' => $staffId, 'role_id' => $roleId, 'is_active' => 1]);
        $this->createdStaffIds[] = $staffId;

        return Staff::query()->findOrFail($staffId);
    }

    /**
     * @return array{session:AcademicSession,classA:SchoolClass,sectionA:Section,classB:SchoolClass,sectionB:Section,hostel:Hostel,suffix:string}
     */
    private function seedHostelStudents(): array
    {
        $session = AcademicSession::query()->first()
            ?: AcademicSession::query()->create(['session' => '2099-hrct']);
        $this->previousClassTeacherSetting = (string) (DB::table('sch_settings')->value('class_teacher') ?: 'no');
        DB::table('sch_settings')->limit(1)->update([
            'session_id' => $session->id,
            'class_teacher' => 'yes',
        ]);
        app(SchoolContext::class)->clearCache();

        $adminRoleId = (int) (DB::table('roles')->where('is_superadmin', 1)->value('id')
            ?: DB::table('roles')->where('name', 'Super Admin')->value('id'));
        $admin = $this->insertStaff($adminRoleId, 'hradm');
        $this->actingAs($admin, 'staff');

        $suffix = uniqid();
        $sectionA = Section::query()->create(['section' => 'HRSA-'.$suffix, 'is_active' => 'yes']);
        $sectionB = Section::query()->create(['section' => 'HRSB-'.$suffix, 'is_active' => 'yes']);
        $classA = SchoolClass::query()->create(['class' => 'HRCA-'.$suffix, 'is_active' => 'yes']);
        $classB = SchoolClass::query()->create(['class' => 'HRCB-'.$suffix, 'is_active' => 'yes']);
        $this->cleanupSectionIds[] = $sectionA->id;
        $this->cleanupSectionIds[] = $sectionB->id;
        $this->cleanupClassIds[] = $classA->id;
        $this->cleanupClassIds[] = $classB->id;

        ClassSection::query()->create(['class_id' => $classA->id, 'section_id' => $sectionA->id, 'is_active' => 'yes']);
        ClassSection::query()->create(['class_id' => $classB->id, 'section_id' => $sectionB->id, 'is_active' => 'yes']);

        $hostel = Hostel::query()->create([
            'hostel_name' => 'CT Hostel '.$suffix,
            'type' => 'Boys',
            'address' => '',
            'intake' => '10',
            'description' => '',
            'is_active' => 'yes',
        ]);
        $this->cleanupHostelIds[] = $hostel->id;

        $type = RoomType::query()->create(['room_type' => 'CT Type '.$suffix, 'description' => '']);
        $this->cleanupTypeIds[] = $type->id;

        $room = HostelRoom::query()->create([
            'hostel_id' => $hostel->id,
            'room_type_id' => $type->id,
            'room_no' => 'R-'.$suffix,
            'no_of_bed' => 2,
            'cost_per_bed' => 50,
            'title' => '',
            'description' => '',
        ]);
        $this->cleanupRoomIds[] = $room->id;

        foreach ([
            ['class' => $classA, 'section' => $sectionA, 'admission' => 'HRIN'.$suffix, 'name' => 'InScope Boarder'],
            ['class' => $classB, 'section' => $sectionB, 'admission' => 'HROUT'.$suffix, 'name' => 'OutScope Boarder'],
        ] as $row) {
            $this->post('/student/create', [
                'admission_no' => $row['admission'],
                'firstname' => $row['name'],
                'lastname' => 'Kid',
                'gender' => 'Male',
                'dob' => '2012-01-01',
                'class_id' => $row['class']->id,
                'section_id' => $row['section']->id,
                'guardian_is' => 'father',
                'guardian_name' => 'Dad',
                'guardian_phone' => '03001112233',
            ])->assertRedirect();

            $student = Student::query()->where('admission_no', $row['admission'])->firstOrFail();
            $this->cleanupStudentIds[] = $student->id;
            DB::table('students')->where('id', $student->id)->update(['hostel_room_id' => $room->id]);
        }

        return compact('session', 'classA', 'sectionA', 'classB', 'sectionB', 'hostel', 'suffix');
    }

    public function test_student_hostel_report_respects_class_teacher_scope(): void
    {
        $fixtures = $this->seedHostelStudents();
        $this->ensureTeacherPrivilege();

        $emptyTeacher = $this->insertStaff(2, 'hrempty');
        $this->actingAs($emptyTeacher, 'staff');

        $this->post('/admin/hostelroom/studenthosteldetails', [
            'search' => 'search_filter',
            'class_id' => $fixtures['classA']->id,
            'section_id' => $fixtures['sectionA']->id,
        ])
            ->assertOk()
            ->assertDontSee('InScope Boarder', false);

        $scopedTeacher = $this->insertStaff(2, 'hrct');
        $this->cleanupClassTeacherIds[] = DB::table('class_teacher')->insertGetId([
            'class_id' => $fixtures['classA']->id,
            'section_id' => $fixtures['sectionA']->id,
            'staff_id' => $scopedTeacher->id,
            'session_id' => $fixtures['session']->id,
        ]);
        $this->actingAs($scopedTeacher, 'staff');

        $page = $this->get('/admin/hostelroom/studenthosteldetails')->assertOk();
        $page->assertSee('HRCA-'.$fixtures['suffix'], false);
        $page->assertDontSee('HRCB-'.$fixtures['suffix'], false);

        $this->post('/admin/hostelroom/studenthosteldetails', [
            'search' => 'search_filter',
            'class_id' => $fixtures['classA']->id,
            'section_id' => $fixtures['sectionA']->id,
            'hostel_name' => $fixtures['hostel']->id,
        ])
            ->assertOk()
            ->assertSee('InScope Boarder', false)
            ->assertDontSee('OutScope Boarder', false);

        $this->post('/admin/hostelroom/studenthosteldetails', [
            'search' => 'search_filter',
            'class_id' => $fixtures['classB']->id,
            'section_id' => $fixtures['sectionB']->id,
            'hostel_name' => $fixtures['hostel']->id,
        ])
            ->assertOk()
            ->assertDontSee('OutScope Boarder', false);

        $this->get('/migration-status/hostel')
            ->assertOk()
            ->assertJsonPath('slices.student_hostel_report_class_teacher', 'done');
    }
}
