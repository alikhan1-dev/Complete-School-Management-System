<?php

namespace Tests\Feature\Staff;

use App\Modules\Staff\Models\Staff;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class StaffTimelineTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<int> */
    private array $createdTimelineIds = [];

    /** @var list<string> */
    private array $createdPaths = [];

    protected function tearDown(): void
    {
        foreach ($this->createdTimelineIds as $timelineId) {
            DB::table('staff_timeline')->where('id', $timelineId)->delete();
        }
        $this->createdTimelineIds = [];

        foreach ($this->createdPaths as $path) {
            if (File::isFile($path)) {
                File::delete($path);
            }
        }
        $this->createdPaths = [];

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

        $token = uniqid('stt', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'STT-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Timeline',
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

    private function createTeacherStaff(string $suffix): Staff
    {
        $teacherRoleId = (int) (DB::table('roles')->where('name', 'Teacher')->value('id')
            ?: DB::table('roles')->where('id', '!=', DB::table('roles')->where('is_superadmin', 1)->value('id'))->value('id'));
        $this->assertGreaterThan(0, $teacherRoleId);

        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'TLN-'.$suffix,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Timeline',
            'surname' => 'Target',
            'father_name' => '',
            'mother_name' => '',
            'contact_no' => '',
            'emergency_contact_no' => '',
            'email' => 'timeline'.$suffix.'@example.test',
            'dob' => '1985-06-20',
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
            'role_id' => $teacherRoleId,
            'is_active' => 1,
        ]);
        $this->createdStaffIds[] = $staffId;

        return Staff::query()->findOrFail($staffId);
    }

    private function createTimeline(int $staffId, string $title, string $status = ''): int
    {
        $id = DB::table('staff_timeline')->insertGetId([
            'staff_id' => $staffId,
            'title' => $title,
            'timeline_date' => '2026-02-10',
            'description' => 'Timeline note',
            'document' => '',
            'status' => $status,
            'date' => '2026-02-10',
        ]);
        $this->createdTimelineIds[] = $id;

        return $id;
    }

    public function test_staff_timeline_store_and_delete(): void
    {
        $this->actingAsSuperAdmin();
        $target = $this->createTeacherStaff(uniqid());

        $this->post('/admin/timeline/add_staff_timeline', [
            'staff_id' => $target->id,
            'timeline_title' => 'Annual Review',
            'timeline_date' => '2026-03-01',
            'timeline_desc' => 'Review completed',
            'visible_check' => 'yes',
        ])
            ->assertRedirect(route('staff.profile', $target->id))
            ->assertSessionHas('success');

        $row = DB::table('staff_timeline')
            ->where('staff_id', $target->id)
            ->where('title', 'Annual Review')
            ->first();
        $this->assertNotNull($row);
        $this->createdTimelineIds[] = (int) $row->id;

        $this->get('/admin/timeline/delete_staff_timeline/'.$row->id)
            ->assertRedirect(route('staff.profile', $target->id));

        $this->assertNull(DB::table('staff_timeline')->where('id', $row->id)->first());
        $this->createdTimelineIds = array_values(array_filter(
            $this->createdTimelineIds,
            fn (int $id) => $id !== (int) $row->id
        ));
    }

    public function test_staff_timeline_json_store_matches_ci_contract(): void
    {
        $this->actingAsSuperAdmin();
        $target = $this->createTeacherStaff(uniqid());

        $this->postJson('/admin/timeline/add_staff_timeline', [
            'staff_id' => $target->id,
            'timeline_title' => 'JSON Entry',
            'timeline_date' => '2026-04-01',
            'timeline_desc' => 'Via JSON',
        ])
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $row = DB::table('staff_timeline')
            ->where('staff_id', $target->id)
            ->where('title', 'JSON Entry')
            ->first();
        $this->assertNotNull($row);
        $this->createdTimelineIds[] = (int) $row->id;
    }

    public function test_staff_timeline_download_returns_attachment(): void
    {
        $this->actingAsSuperAdmin();
        $target = $this->createTeacherStaff(uniqid());
        $fileName = 'timeline-'.uniqid().'.txt';
        $dir = public_path('uploads/staff_timeline');
        File::ensureDirectoryExists($dir);
        $path = $dir.DIRECTORY_SEPARATOR.$fileName;
        File::put($path, 'timeline attachment');
        $this->createdPaths[] = $path;

        $timelineId = DB::table('staff_timeline')->insertGetId([
            'staff_id' => $target->id,
            'title' => 'With File',
            'timeline_date' => '2026-05-01',
            'description' => '',
            'document' => $fileName,
            'status' => '',
            'date' => '2026-05-01',
        ]);
        $this->createdTimelineIds[] = $timelineId;

        $this->get('/admin/timeline/download_staff_timeline/'.$timelineId)
            ->assertOk()
            ->assertDownload($fileName);
    }

    public function test_staff_own_profile_hides_non_visible_timeline_entries(): void
    {
        $target = $this->createTeacherStaff(uniqid());
        $this->createTimeline((int) $target->id, 'Hidden Entry', '');
        $this->createTimeline((int) $target->id, 'Visible Entry', 'yes');

        $this->actingAs($target, 'staff');

        $this->get('/admin/staff/profile/'.$target->id)
            ->assertOk()
            ->assertSee('Visible Entry', false)
            ->assertDontSee('Hidden Entry', false);
    }
}
