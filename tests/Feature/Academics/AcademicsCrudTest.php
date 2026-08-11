<?php

namespace Tests\Feature\Academics;

use App\Modules\Academics\Models\AcademicSession;
use App\Modules\Academics\Models\ClassSection;
use App\Modules\Academics\Models\SchoolClass;
use App\Modules\Academics\Models\Section;
use App\Modules\Academics\Models\Subject;
use App\Modules\Academics\Models\SubjectGroup;
use App\Modules\Staff\Models\Staff;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AcademicsCrudTest extends TestCase
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

    private function actingAsSuperAdmin(): Staff
    {
        $staff = Staff::query()
            ->whereHas('roles', function ($q) {
                $q->where('is_superadmin', 1)->orWhere('name', 'Super Admin');
            })
            ->where('is_active', 1)
            ->orderBy('id')
            ->first();

        if (! $staff) {
            $roleId = (int) (DB::table('roles')->where('is_superadmin', 1)->value('id')
                ?: DB::table('roles')->where('name', 'Super Admin')->value('id'));
            $this->assertGreaterThan(0, $roleId, 'Super Admin role must exist.');

            $token = uniqid('sa', true);
            $staffId = DB::table('staff')->insertGetId([
                'employee_id' => 'TEST-'.$token,
                'lang_id' => 1,
                'currency_id' => 0,
                'department' => null,
                'designation' => null,
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
                'date_of_joining' => null,
                'date_of_leaving' => null,
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
                'basic_salary' => null,
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
                'disable_at' => null,
            ]);

            DB::table('staff_roles')->insert([
                'staff_id' => $staffId,
                'role_id' => $roleId,
                'is_active' => 1,
            ]);

            $this->createdStaffIds[] = $staffId;
            $staff = Staff::query()->findOrFail($staffId);
        }

        $this->actingAs($staff, 'staff');

        return $staff;
    }

    public function test_guest_is_redirected_from_academics_pages(): void
    {
        $this->get('/sessions')->assertRedirect('/site/login');
        $this->get('/classes')->assertRedirect('/site/login');
        $this->get('/sections')->assertRedirect('/site/login');
        $this->get('/admin/subject')->assertRedirect('/site/login');
        $this->get('/admin/subjectgroup')->assertRedirect('/site/login');
    }

    public function test_get_by_class_returns_class_section_contract(): void
    {
        $this->actingAsSuperAdmin();

        $section = Section::query()->create([
            'section' => 'T-SEC-'.uniqid(),
            'is_active' => 'yes',
        ]);
        $class = SchoolClass::query()->create([
            'class' => 'T-CLS-'.uniqid(),
            'is_active' => 'yes',
        ]);
        $link = ClassSection::query()->create([
            'class_id' => $class->id,
            'section_id' => $section->id,
            'is_active' => 'yes',
        ]);

        try {
            $response = $this->getJson('/sections/getByClass?class_id='.$class->id);
            $response->assertOk();
            $payload = $response->json();
            $this->assertIsArray($payload);
            $this->assertNotEmpty($payload);
            $row = collect($payload)->firstWhere('id', (string) $link->id);
            $this->assertNotNull($row);
            $this->assertSame((string) $link->id, $row['id']);
            $this->assertSame((string) $section->id, $row['section_id']);
            $this->assertSame($section->section, $row['section']);
        } finally {
            $link->delete();
            $class->delete();
            $section->delete();
        }
    }

    public function test_session_section_subject_and_class_crud_round_trip(): void
    {
        $this->actingAsSuperAdmin();
        $suffix = uniqid('t');

        $sessionName = '2099-'.$suffix;
        $this->post('/sessions/create', ['session' => $sessionName])
            ->assertRedirect(route('academics.sessions.index'));

        $session = AcademicSession::query()->where('session', $sessionName)->first();
        $this->assertNotNull($session);

        $sectionName = 'Sec-'.$suffix;
        $this->post('/sections', ['section' => $sectionName])
            ->assertRedirect(route('academics.sections.index'));
        $section = Section::query()->where('section', $sectionName)->firstOrFail();

        $className = 'Class-'.$suffix;
        $this->post('/classes', [
            'class' => $className,
            'sections' => [$section->id],
        ])->assertRedirect(route('academics.classes.index'));

        $class = SchoolClass::query()->where('class', $className)->firstOrFail();
        $this->assertDatabaseHas('class_sections', [
            'class_id' => $class->id,
            'section_id' => $section->id,
        ]);

        $subjectName = 'Subj-'.$suffix;
        $this->post('/admin/subject', [
            'name' => $subjectName,
            'type' => 'theory',
            'code' => 'C'.$suffix,
        ])->assertRedirect(route('academics.subjects.index'));
        $subject = Subject::query()->where('name', $subjectName)->firstOrFail();

        $classSectionId = ClassSection::query()
            ->where('class_id', $class->id)
            ->where('section_id', $section->id)
            ->value('id');

        // Ensure sch_settings has a session so CurrentSessionResolver works.
        $settingsSessionId = (int) (DB::table('sch_settings')->value('session_id') ?? 0);
        if ($settingsSessionId === 0) {
            DB::table('sch_settings')->limit(1)->update(['session_id' => $session->id]);
            $settingsSessionId = $session->id;
        }

        $groupName = 'SG-'.$suffix;
        $this->post('/admin/subjectgroup', [
            'name' => $groupName,
            'class_id' => $class->id,
            'sections' => [$classSectionId],
            'subject' => [$subject->id],
            'description' => 'test',
        ])->assertRedirect(route('academics.subject_groups.index'));

        $group = SubjectGroup::query()
            ->where('name', $groupName)
            ->where('session_id', $settingsSessionId)
            ->first();
        $this->assertNotNull($group);

        // Cleanup in FK-safe order
        $group->delete();
        $subject->delete();
        $class->delete();
        $section->delete();
        if ($session->id !== $settingsSessionId) {
            $session->delete();
        } else {
            // Created session became current — leave name-marked but do not delete active setting session via cascade risk.
            // Prefer delete only when unused; if unique, rename then leave or remove if no FKs.
            try {
                $session->delete();
            } catch (\Throwable) {
                // keep row if referenced
            }
        }
    }

    public function test_sessions_index_renders_for_super_admin(): void
    {
        $this->actingAsSuperAdmin();
        $this->get('/sessions')->assertOk()->assertSee('Session List', false);
        $this->get('/classes')->assertOk()->assertSee('Class List', false);
        $this->get('/sections')->assertOk()->assertSee('Section List', false);
        $this->get('/admin/subject')->assertOk()->assertSee('Subject List', false);
        $this->get('/admin/subjectgroup')->assertOk()->assertSee('Subject Group List', false);
    }
}
