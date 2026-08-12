<?php

namespace Tests\Feature\Timetable;

use App\Modules\Academics\Models\AcademicSession;
use App\Modules\Academics\Models\ClassSection;
use App\Modules\Academics\Models\SchoolClass;
use App\Modules\Academics\Models\Section;
use App\Modules\Academics\Models\Subject;
use App\Modules\Academics\Models\SubjectGroup;
use App\Modules\Academics\Models\SubjectGroupClassSection;
use App\Modules\Academics\Models\SubjectGroupSubject;
use App\Modules\Staff\Models\Staff;
use App\Modules\Timetable\Models\SubjectTimetable;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ClassTimetableTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<int> */
    private array $cleanupTimetableIds = [];

    /** @var list<int> */
    private array $cleanupSubjectGroupIds = [];

    /** @var list<int> */
    private array $cleanupSubjectIds = [];

    /** @var list<int> */
    private array $cleanupClassIds = [];

    /** @var list<int> */
    private array $cleanupSectionIds = [];

    protected function tearDown(): void
    {
        if ($this->cleanupTimetableIds !== []) {
            DB::table('subject_timetable')->whereIn('id', $this->cleanupTimetableIds)->delete();
        }
        // Also wipe by class if any leftover
        foreach ($this->cleanupClassIds as $classId) {
            DB::table('subject_timetable')->where('class_id', $classId)->delete();
            DB::table('class_sections')->where('class_id', $classId)->delete();
            DB::table('classes')->where('id', $classId)->delete();
        }
        $this->cleanupClassIds = [];
        $this->cleanupTimetableIds = [];

        foreach ($this->cleanupSectionIds as $sectionId) {
            DB::table('sections')->where('id', $sectionId)->delete();
        }
        $this->cleanupSectionIds = [];

        foreach ($this->cleanupSubjectGroupIds as $groupId) {
            DB::table('subject_group_class_sections')->where('subject_group_id', $groupId)->delete();
            DB::table('subject_group_subjects')->where('subject_group_id', $groupId)->delete();
            DB::table('subject_groups')->where('id', $groupId)->delete();
        }
        $this->cleanupSubjectGroupIds = [];

        if ($this->cleanupSubjectIds !== []) {
            DB::table('subjects')->whereIn('id', $this->cleanupSubjectIds)->delete();
        }
        $this->cleanupSubjectIds = [];

        foreach ($this->createdStaffIds as $staffId) {
            DB::table('staff_roles')->where('staff_id', $staffId)->delete();
            DB::table('staff')->where('id', $staffId)->delete();
        }
        $this->createdStaffIds = [];

        parent::tearDown();
    }

    private function createStaff(string $prefix, int $roleId, bool $actAs = false): Staff
    {
        $token = uniqid($prefix, true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => strtoupper($prefix).'-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Time',
            'surname' => $prefix,
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
        $staff = Staff::query()->findOrFail($staffId);
        if ($actAs) {
            $this->actingAs($staff, 'staff');
        }

        return $staff;
    }

    public function test_class_timetable_create_save_report_and_update_round_trip(): void
    {
        $superRoleId = (int) (DB::table('roles')->where('is_superadmin', 1)->value('id')
            ?: DB::table('roles')->where('name', 'Super Admin')->value('id'));
        $teacherRoleId = (int) DB::table('roles')->where('name', 'Teacher')->value('id');
        $this->assertGreaterThan(0, $superRoleId);
        $this->assertGreaterThan(0, $teacherRoleId);

        $this->createStaff('adm', $superRoleId, true);
        $teacher = $this->createStaff('tch', $teacherRoleId);

        $suffix = uniqid();
        $session = AcademicSession::query()->first() ?: AcademicSession::query()->create(['session' => '2099-00']);
        DB::table('sch_settings')->limit(1)->update(['session_id' => $session->id]);

        $section = Section::query()->create(['section' => 'TS-'.$suffix, 'is_active' => 'yes']);
        $class = SchoolClass::query()->create(['class' => 'TC-'.$suffix, 'is_active' => 'yes']);
        $this->cleanupSectionIds[] = $section->id;
        $this->cleanupClassIds[] = $class->id;

        $classSection = ClassSection::query()->create([
            'class_id' => $class->id,
            'section_id' => $section->id,
            'is_active' => 'yes',
        ]);

        $subject = Subject::query()->create([
            'name' => 'Physics-'.$suffix,
            'code' => 'P'.$suffix,
            'type' => 'Theory',
            'is_active' => 'yes',
        ]);
        $this->cleanupSubjectIds[] = $subject->id;

        $group = SubjectGroup::query()->create([
            'name' => 'TG-'.$suffix,
            'description' => '',
            'session_id' => $session->id,
        ]);
        $this->cleanupSubjectGroupIds[] = $group->id;

        $groupSubject = SubjectGroupSubject::query()->create([
            'subject_group_id' => $group->id,
            'subject_id' => $subject->id,
            'session_id' => $session->id,
        ]);

        SubjectGroupClassSection::query()->create([
            'subject_group_id' => $group->id,
            'class_section_id' => $classSection->id,
            'session_id' => $session->id,
            'is_active' => 1,
        ]);

        $this->get('/admin/timetable/classreport')->assertOk()->assertSee('Class Timetable', false);
        $this->get('/admin/timetable/create')->assertOk()->assertSee('Create Class Timetable', false);

        $this->post('/admin/timetable/create', [
            'search' => 'search',
            'class_id' => $class->id,
            'section_id' => $section->id,
            'subject_group_id' => $group->id,
        ])->assertOk()->assertSee('Monday', false)->assertSee($subject->name, false);

        $this->post('/admin/timetable/saveday', [
            'class_id' => $class->id,
            'section_id' => $section->id,
            'subject_group_id' => $group->id,
            'day' => 'Monday',
            'periods' => [
                [
                    'id' => 0,
                    'subject_group_subject_id' => $groupSubject->id,
                    'staff_id' => $teacher->id,
                    'time_from' => '08:00',
                    'time_to' => '08:45',
                    'room_no' => 'Lab-1',
                ],
            ],
        ])->assertRedirect();

        $row = SubjectTimetable::query()
            ->where('class_id', $class->id)
            ->where('section_id', $section->id)
            ->where('subject_group_id', $group->id)
            ->where('day', 'Monday')
            ->firstOrFail();
        $this->cleanupTimetableIds[] = $row->id;

        $this->assertSame($groupSubject->id, (int) $row->subject_group_subject_id);
        $this->assertSame($teacher->id, (int) $row->staff_id);
        $this->assertSame('Lab-1', $row->room_no);
        $this->assertSame('8:00 AM', $row->time_from);
        $this->assertSame('8:45 AM', $row->time_to);
        $this->assertSame('08:00:00', (string) $row->start_time);
        $this->assertSame('08:45:00', (string) $row->end_time);

        // Update room + replace with same id
        $this->post('/admin/timetable/saveday', [
            'class_id' => $class->id,
            'section_id' => $section->id,
            'subject_group_id' => $group->id,
            'day' => 'Monday',
            'periods' => [
                [
                    'id' => $row->id,
                    'subject_group_subject_id' => $groupSubject->id,
                    'staff_id' => $teacher->id,
                    'time_from' => '09:00',
                    'time_to' => '09:45',
                    'room_no' => 'Lab-2',
                ],
            ],
        ])->assertRedirect();

        $row->refresh();
        $this->assertSame('Lab-2', $row->room_no);
        $this->assertSame('9:00 AM', $row->time_from);
        $this->assertSame(1, SubjectTimetable::query()
            ->where('class_id', $class->id)
            ->where('day', 'Monday')
            ->count());

        $this->post('/admin/timetable/classreport', [
            'search' => '1',
            'class_id' => $class->id,
            'section_id' => $section->id,
        ])->assertOk()
            ->assertSee($subject->name, false)
            ->assertSee('Lab-2', false)
            ->assertSee($teacher->employee_id, false);

        // Clear Monday by posting no filled periods
        $this->post('/admin/timetable/saveday', [
            'class_id' => $class->id,
            'section_id' => $section->id,
            'subject_group_id' => $group->id,
            'day' => 'Monday',
            'periods' => [
                [
                    'id' => 0,
                    'subject_group_subject_id' => '',
                    'staff_id' => '',
                    'time_from' => '',
                    'time_to' => '',
                    'room_no' => '',
                ],
            ],
        ])->assertRedirect();

        $this->assertSame(0, SubjectTimetable::query()
            ->where('class_id', $class->id)
            ->where('day', 'Monday')
            ->count());
    }
}
