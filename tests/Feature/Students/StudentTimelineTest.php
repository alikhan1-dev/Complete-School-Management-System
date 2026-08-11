<?php

namespace Tests\Feature\Students;

use App\Modules\Academics\Models\AcademicSession;
use App\Modules\Academics\Models\ClassSection;
use App\Modules\Academics\Models\SchoolClass;
use App\Modules\Academics\Models\Section;
use App\Modules\Staff\Models\Staff;
use App\Modules\Students\Models\Student;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class StudentTimelineTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<int> */
    private array $createdStudentIds = [];

    /** @var list<array{class:\Illuminate\Database\Eloquent\Model,section:\Illuminate\Database\Eloquent\Model}> */
    private array $createdClassSections = [];

    /** @var list<int> */
    private array $createdTimelineIds = [];

    protected function tearDown(): void
    {
        foreach ($this->createdTimelineIds as $timelineId) {
            $doc = DB::table('student_timeline')->where('id', $timelineId)->value('document');
            DB::table('student_timeline')->where('id', $timelineId)->delete();
            if ($doc) {
                $path = public_path('uploads/student_timeline/'.$doc);
                if (File::isFile($path)) {
                    File::delete($path);
                }
            }
        }
        $this->createdTimelineIds = [];

        foreach ($this->createdStudentIds as $studentId) {
            DB::table('student_timeline')->where('student_id', $studentId)->delete();
            DB::table('student_session')->where('student_id', $studentId)->delete();
            DB::table('users')->where('role', 'student')->where('user_id', $studentId)->delete();
            $parentId = DB::table('students')->where('id', $studentId)->value('parent_id');
            DB::table('students')->where('id', $studentId)->delete();
            if ($parentId) {
                DB::table('users')->where('id', $parentId)->where('role', 'parent')->delete();
            }
        }
        $this->createdStudentIds = [];

        foreach ($this->createdClassSections as $pair) {
            ClassSection::query()->where('class_id', $pair['class']->id)->delete();
            $pair['class']->delete();
            $pair['section']->delete();
        }
        $this->createdClassSections = [];

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

        $token = uniqid('stl', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'STL-'.$token,
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

    private function admitStudent(): Student
    {
        $session = AcademicSession::query()->first();
        if (! $session) {
            $session = AcademicSession::query()->create(['session' => '2098-99']);
        }
        DB::table('sch_settings')->limit(1)->update(['session_id' => $session->id]);

        $section = Section::query()->create(['section' => 'S-'.uniqid(), 'is_active' => 'yes']);
        $class = SchoolClass::query()->create(['class' => 'C-'.uniqid(), 'is_active' => 'yes']);
        ClassSection::query()->create([
            'class_id' => $class->id,
            'section_id' => $section->id,
            'is_active' => 'yes',
        ]);
        $this->createdClassSections[] = compact('class', 'section');

        $admissionNo = 'ADM'.uniqid();
        $this->post('/student/create', [
            'admission_no' => $admissionNo,
            'firstname' => 'Timeline',
            'lastname' => 'Kid',
            'gender' => 'Female',
            'dob' => '2013-05-01',
            'class_id' => $class->id,
            'section_id' => $section->id,
            'guardian_is' => 'mother',
            'guardian_name' => 'Guardian',
            'guardian_phone' => '03007654321',
        ])->assertRedirect();

        $student = Student::query()->where('admission_no', $admissionNo)->firstOrFail();
        $this->createdStudentIds[] = $student->id;

        return $student;
    }

    public function test_student_timeline_add_edit_download_delete_round_trip(): void
    {
        $this->actingAsSuperAdmin();
        $student = $this->admitStudent();

        $this->get('/student/view/'.$student->id)
            ->assertOk()
            ->assertSee('Timeline', false)
            ->assertSee('Add Timeline', false);

        $file = UploadedFile::fake()->create('note.pdf', 80, 'application/pdf');

        $this->post('/admin/timeline/add', [
            'student_id' => $student->id,
            'timeline_title' => 'First Meeting',
            'timeline_date' => '2024-06-15',
            'timeline_desc' => 'Discussed admission',
            'visible_check' => 'yes',
            'timeline_doc' => $file,
        ])->assertRedirect(route('students.view', $student->id));

        $row = DB::table('student_timeline')->where('student_id', $student->id)->first();
        $this->assertNotNull($row);
        $this->createdTimelineIds[] = $row->id;
        $this->assertSame('First Meeting', $row->title);
        $this->assertSame('yes', $row->status);
        $this->assertNotEmpty($row->document);
        $path = public_path('uploads/student_timeline/'.$row->document);
        $this->assertFileExists($path);

        $this->get('/student/view/'.$student->id)
            ->assertOk()
            ->assertSee('First Meeting', false)
            ->assertSee('Discussed admission', false);

        $this->get('/admin/timeline/download/'.$row->id)->assertOk();

        $this->get('/student/view/'.$student->id.'?edit_timeline='.$row->id)
            ->assertOk()
            ->assertSee('Update Timeline', false);

        $this->post('/admin/timeline/editstudenttimeline', [
            'id' => $row->id,
            'student_id' => $student->id,
            'timeline_title' => 'First Meeting Updated',
            'timeline_date' => '2024-06-16',
            'timeline_desc' => 'Updated notes',
        ])->assertRedirect(route('students.view', $student->id));

        $this->assertDatabaseHas('student_timeline', [
            'id' => $row->id,
            'title' => 'First Meeting Updated',
            'status' => '',
        ]);

        $this->post('/admin/timeline/delete_timeline', [
            'id' => $row->id,
        ])->assertRedirect(route('students.view', $student->id));

        $this->assertDatabaseMissing('student_timeline', ['id' => $row->id]);
        $this->assertFileDoesNotExist($path);
        $this->createdTimelineIds = [];
    }
}
