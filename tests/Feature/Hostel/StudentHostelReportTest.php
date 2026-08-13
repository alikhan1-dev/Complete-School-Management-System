<?php

namespace Tests\Feature\Hostel;

use App\Modules\Academics\Models\AcademicSession;
use App\Modules\Academics\Models\ClassSection;
use App\Modules\Academics\Models\SchoolClass;
use App\Modules\Academics\Models\Section;
use App\Modules\Hostel\Models\Hostel;
use App\Modules\Hostel\Models\HostelRoom;
use App\Modules\Hostel\Models\RoomType;
use App\Modules\Staff\Models\Staff;
use App\Modules\Students\Models\Student;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StudentHostelReportTest extends TestCase
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

    protected function tearDown(): void
    {
        if ($this->cleanupStudentIds !== []) {
            DB::table('student_session')->whereIn('student_id', $this->cleanupStudentIds)->delete();
            DB::table('students')->whereIn('id', $this->cleanupStudentIds)->delete();
        }
        $this->cleanupStudentIds = [];

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
            DB::table('hostel_rooms')->whereIn('room_type_id', $this->cleanupTypeIds)->delete();
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

        $token = uniqid('hsrpt', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'HSR-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Hostel',
            'surname' => 'Report',
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

    public function test_student_hostel_report_search(): void
    {
        $this->actingAsSuperAdmin();
        $suffix = uniqid();

        $session = AcademicSession::query()->first() ?: AcademicSession::query()->create(['session' => '2099-hsr']);
        DB::table('sch_settings')->limit(1)->update(['session_id' => $session->id]);

        $section = Section::query()->create(['section' => 'HSRS-'.$suffix, 'is_active' => 'yes']);
        $class = SchoolClass::query()->create(['class' => 'HSRC-'.$suffix, 'is_active' => 'yes']);
        $this->cleanupSectionIds[] = $section->id;
        $this->cleanupClassIds[] = $class->id;

        ClassSection::query()->create([
            'class_id' => $class->id,
            'section_id' => $section->id,
            'is_active' => 'yes',
        ]);

        $hostel = Hostel::query()->create([
            'hostel_name' => 'Report Hostel '.$suffix,
            'type' => 'Boys',
            'address' => '',
            'intake' => '10',
            'description' => '',
            'is_active' => 'yes',
        ]);
        $this->cleanupHostelIds[] = $hostel->id;

        $type = RoomType::query()->create([
            'room_type' => 'Report Type '.$suffix,
            'description' => '',
        ]);
        $this->cleanupTypeIds[] = $type->id;

        $room = HostelRoom::query()->create([
            'hostel_id' => $hostel->id,
            'room_type_id' => $type->id,
            'room_no' => 'HR-'.$suffix,
            'no_of_bed' => 2,
            'cost_per_bed' => 175.25,
            'title' => '',
            'description' => '',
        ]);
        $this->cleanupRoomIds[] = $room->id;

        $admissionNo = 'HSRADM'.$suffix;
        $this->post('/student/create', [
            'admission_no' => $admissionNo,
            'firstname' => 'Hostel',
            'lastname' => 'Boarder',
            'gender' => 'Male',
            'dob' => '2012-01-01',
            'class_id' => $class->id,
            'section_id' => $section->id,
            'guardian_is' => 'father',
            'guardian_name' => 'Dad',
            'guardian_phone' => '03001112233',
            'mobileno' => '03004445566',
        ])->assertRedirect();

        $student = Student::query()->where('admission_no', $admissionNo)->firstOrFail();
        $this->cleanupStudentIds[] = $student->id;

        DB::table('students')->where('id', $student->id)->update([
            'hostel_room_id' => $room->id,
            'guardian_phone' => '03001112233',
        ]);

        $this->get('/admin/hostelroom/studenthosteldetails')
            ->assertOk()
            ->assertSee('Student Hostel Report', false)
            ->assertSee('Select Criteria', false)
            ->assertDontSee('Hostel Boarder', false);

        $this->post('/admin/hostelroom/studenthosteldetails', [
            'search' => 'search_filter',
            'class_id' => $class->id,
            'section_id' => $section->id,
            'hostel_name' => $hostel->id,
        ])
            ->assertOk()
            ->assertSee('Hostel Boarder', false)
            ->assertSee('Report Hostel '.$suffix, false)
            ->assertSee('HR-'.$suffix, false)
            ->assertSee('Report Type '.$suffix, false)
            ->assertSee('175.25', false);
    }
}
