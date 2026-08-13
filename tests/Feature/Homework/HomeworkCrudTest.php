<?php

namespace Tests\Feature\Homework;

use App\Modules\Academics\Models\AcademicSession;
use App\Modules\Academics\Models\ClassSection;
use App\Modules\Academics\Models\SchoolClass;
use App\Modules\Academics\Models\Section;
use App\Modules\Academics\Models\Subject;
use App\Modules\Academics\Models\SubjectGroup;
use App\Modules\Academics\Models\SubjectGroupClassSection;
use App\Modules\Academics\Models\SubjectGroupSubject;
use App\Modules\Homework\Models\Homework;
use App\Modules\Staff\Models\Staff;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class HomeworkCrudTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<int> */
    private array $cleanupHomeworkIds = [];

    /** @var list<int> */
    private array $cleanupSubjectGroupIds = [];

    /** @var list<int> */
    private array $cleanupSubjectIds = [];

    /** @var list<int> */
    private array $cleanupClassIds = [];

    /** @var list<int> */
    private array $cleanupSectionIds = [];

    /** @var list<string> */
    private array $cleanupDocuments = [];

    protected function tearDown(): void
    {
        if ($this->cleanupHomeworkIds !== []) {
            DB::table('homework_evaluation')->whereIn('homework_id', $this->cleanupHomeworkIds)->delete();
            DB::table('submit_assignment')->whereIn('homework_id', $this->cleanupHomeworkIds)->delete();
            DB::table('homework')->whereIn('id', $this->cleanupHomeworkIds)->delete();
        }
        $this->cleanupHomeworkIds = [];

        foreach ($this->cleanupDocuments as $name) {
            $path = public_path('uploads/homework/'.basename($name));
            if (is_file($path)) {
                File::delete($path);
            }
        }
        $this->cleanupDocuments = [];

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

        foreach ($this->cleanupClassIds as $classId) {
            DB::table('class_sections')->where('class_id', $classId)->delete();
            DB::table('classes')->where('id', $classId)->delete();
        }
        $this->cleanupClassIds = [];

        foreach ($this->cleanupSectionIds as $sectionId) {
            DB::table('sections')->where('id', $sectionId)->delete();
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

        $token = uniqid('hwstf', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'HW-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Home',
            'surname' => 'Work',
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

    /**
     * @return array{class:SchoolClass,section:Section,group:SubjectGroup,groupSubject:SubjectGroupSubject,session:AcademicSession}
     */
    private function seedClassSubjectGraph(): array
    {
        $suffix = uniqid();
        $session = AcademicSession::query()->first() ?: AcademicSession::query()->create(['session' => '2099-hw']);
        DB::table('sch_settings')->limit(1)->update(['session_id' => $session->id]);

        $section = Section::query()->create(['section' => 'HWS-'.$suffix, 'is_active' => 'yes']);
        $class = SchoolClass::query()->create(['class' => 'HWC-'.$suffix, 'is_active' => 'yes']);
        $this->cleanupSectionIds[] = $section->id;
        $this->cleanupClassIds[] = $class->id;

        $classSection = ClassSection::query()->create([
            'class_id' => $class->id,
            'section_id' => $section->id,
            'is_active' => 'yes',
        ]);

        $subject = Subject::query()->create([
            'name' => 'HW Subject '.$suffix,
            'code' => 'HW'.$suffix,
            'type' => 'Theory',
            'is_active' => 'yes',
        ]);
        $this->cleanupSubjectIds[] = $subject->id;

        $group = SubjectGroup::query()->create([
            'name' => 'HWG-'.$suffix,
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

        return [
            'class' => $class,
            'section' => $section,
            'group' => $group,
            'groupSubject' => $groupSubject,
            'session' => $session,
        ];
    }

    public function test_homework_crud_list_create_edit_download_delete(): void
    {
        $this->actingAsSuperAdmin();
        $ctx = $this->seedClassSubjectGraph();
        $suffix = uniqid();

        $this->get('/homework')->assertOk()->assertSee('Homework', false);

        $file = UploadedFile::fake()->create('hw-notes.txt', 10, 'text/plain');

        $this->post('/homework/create', [
            'class_id' => $ctx['class']->id,
            'section_id' => $ctx['section']->id,
            'subject_group_id' => $ctx['group']->id,
            'subject_group_subject_id' => $ctx['groupSubject']->id,
            'homework_date' => now()->format('Y-m-d'),
            'submit_date' => now()->addDays(3)->format('Y-m-d'),
            'marks' => 20,
            'description' => 'Complete chapter '.$suffix,
            'userfile' => $file,
        ])->assertRedirect();

        $row = Homework::query()->where('description', 'Complete chapter '.$suffix)->firstOrFail();
        $this->cleanupHomeworkIds[] = $row->id;
        $this->assertSame((int) $ctx['groupSubject']->id, (int) $row->subject_group_subject_id);
        $this->assertNotSame('', (string) $row->document);
        $this->assertFileExists(public_path('uploads/homework/'.$row->document));
        $this->cleanupDocuments[] = (string) $row->document;

        $this->get('/homework?class_id='.$ctx['class']->id.'&section_id='.$ctx['section']->id)
            ->assertOk()
            ->assertSee('Upcoming Homework', false)
            ->assertSee($ctx['class']->class, false)
            ->assertSee('HW Subject', false);

        $this->get('/homework/edit/'.$row->id)
            ->assertOk()
            ->assertSee('Edit Homework', false)
            ->assertSee('Complete chapter '.$suffix, false);

        $this->post('/homework/edit/'.$row->id, [
            'class_id' => $ctx['class']->id,
            'section_id' => $ctx['section']->id,
            'subject_group_id' => $ctx['group']->id,
            'subject_group_subject_id' => $ctx['groupSubject']->id,
            'homework_date' => now()->format('Y-m-d'),
            'submit_date' => now()->addDays(5)->format('Y-m-d'),
            'marks' => 25,
            'description' => 'Updated chapter '.$suffix,
        ])->assertRedirect();

        $row->refresh();
        $this->assertSame('Updated chapter '.$suffix, $row->description);
        $this->assertSame('25.00', number_format((float) $row->marks, 2));

        $this->get('/homework/download/'.$row->id)->assertOk();

        $this->get('/homework/delete/'.$row->id)->assertRedirect();
        $this->assertNull(Homework::query()->find($row->id));
        $this->cleanupHomeworkIds = [];
    }
}
