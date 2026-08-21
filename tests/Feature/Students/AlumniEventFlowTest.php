<?php

namespace Tests\Feature\Students;

use App\Modules\Academics\Models\AcademicSession;
use App\Modules\Academics\Models\ClassSection;
use App\Modules\Academics\Models\SchoolClass;
use App\Modules\Academics\Models\Section;
use App\Modules\Staff\Models\Staff;
use App\Modules\Students\Models\AlumniEvent;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AlumniEventFlowTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<int> */
    private array $cleanupEventIds = [];

    /** @var list<int> */
    private array $cleanupClassIds = [];

    /** @var list<int> */
    private array $cleanupSectionIds = [];

    protected function tearDown(): void
    {
        if ($this->cleanupEventIds !== []) {
            DB::table('alumni_events')->whereIn('id', $this->cleanupEventIds)->delete();
            $this->cleanupEventIds = [];
        }
        foreach ($this->cleanupClassIds as $classId) {
            DB::table('class_sections')->where('class_id', $classId)->delete();
            DB::table('classes')->where('id', $classId)->delete();
        }
        $this->cleanupClassIds = [];
        if ($this->cleanupSectionIds !== []) {
            DB::table('sections')->whereIn('id', $this->cleanupSectionIds)->delete();
            $this->cleanupSectionIds = [];
        }
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

        $token = uniqid('alevt', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'AEVT-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Alumni',
            'surname' => 'Events',
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

    public function test_guest_cannot_open_alumni_events(): void
    {
        $this->get('/admin/alumni/events')->assertRedirect();
    }

    public function test_alumni_event_crud_for_all_and_class(): void
    {
        $this->actingAsSuperAdmin();
        $suffix = uniqid();

        $session = AcademicSession::query()->first() ?: AcademicSession::query()->create(['session' => '2099-evt']);
        $section = Section::query()->create(['section' => 'EVS-'.$suffix, 'is_active' => 'yes']);
        $class = SchoolClass::query()->create(['class' => 'EVC-'.$suffix, 'is_active' => 'yes']);
        $this->cleanupSectionIds[] = $section->id;
        $this->cleanupClassIds[] = $class->id;
        ClassSection::query()->create([
            'class_id' => $class->id,
            'section_id' => $section->id,
            'is_active' => 'yes',
        ]);

        $this->get('/admin/alumni/events')->assertOk();

        $titleAll = 'All Alumni Meetup '.$suffix;
        $this->post('/admin/alumni/event/create', [
            'event_for' => 'all',
            'event_title' => $titleAll,
            'from_date' => '2026-09-01',
            'to_date' => '2026-09-02',
            'note' => 'Note',
            'event_notification_message' => 'Hello alumni',
        ])->assertRedirect('/admin/alumni/events');

        $allEvent = AlumniEvent::query()->where('title', $titleAll)->firstOrFail();
        $this->cleanupEventIds[] = $allEvent->id;
        $this->assertSame('all', $allEvent->event_for);
        $this->assertSame('[]', $allEvent->section);

        $titleClass = 'Class Reunion '.$suffix;
        $this->post('/admin/alumni/event/create', [
            'event_for' => 'class',
            'session_id' => $session->id,
            'class_id' => $class->id,
            'user' => [$section->id],
            'event_title' => $titleClass,
            'from_date' => '2026-10-01',
            'to_date' => '2026-10-01',
            'note' => '',
            'event_notification_message' => '',
        ])->assertRedirect('/admin/alumni/events');

        $classEvent = AlumniEvent::query()->where('title', $titleClass)->firstOrFail();
        $this->cleanupEventIds[] = $classEvent->id;
        $this->assertSame('class', $classEvent->event_for);
        $this->assertSame((int) $class->id, (int) $classEvent->class_id);
        $this->assertSame([(int) $section->id], json_decode((string) $classEvent->section, true));

        $this->get('/admin/alumni/events')
            ->assertOk()
            ->assertSee($titleAll, false)
            ->assertSee($titleClass, false)
            ->assertSee(__('system.all_alumni'), false);

        $this->get('/admin/alumni/delete_event/'.$allEvent->id)
            ->assertRedirect('/admin/alumni/events');
        $this->assertNull(AlumniEvent::query()->find($allEvent->id));
        $this->cleanupEventIds = array_values(array_filter(
            $this->cleanupEventIds,
            fn ($id) => (int) $id !== (int) $allEvent->id
        ));
    }
}
