<?php

namespace Tests\Feature\FrontCms;

use App\Modules\Staff\Models\Staff;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FrontCmsEventsFlowTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<int> */
    private array $cleanupProgramIds = [];

    protected function tearDown(): void
    {
        if ($this->cleanupProgramIds !== []) {
            DB::table('front_cms_programs')->whereIn('id', $this->cleanupProgramIds)->delete();
        }
        $this->cleanupProgramIds = [];

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

        $token = uniqid('ev', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'EV-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Event',
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

    public function test_event_index_requires_staff_auth(): void
    {
        $this->get('/admin/front/events')->assertRedirect();
    }

    public function test_create_requires_title_dates_and_description(): void
    {
        $this->actingAsSuperAdmin();
        $this->post('/admin/front/events/create', [])
            ->assertOk()
            ->assertSee('The Title field is required.', false)
            ->assertSee('The Start Date field is required.', false)
            ->assertSee('The Event Date field is required.', false)
            ->assertSee('The Description field is required.', false);
    }

    public function test_superadmin_can_create_edit_and_delete_event(): void
    {
        $this->actingAsSuperAdmin();
        $suffix = uniqid();
        $title = 'Sports Day '.$suffix;

        $this->get('/admin/front/events')->assertOk()->assertSee('Event List', false);
        $this->get('/admin/front/events/create')->assertOk()->assertSee('Add Event', false);

        $this->post('/admin/front/events/create', [
            'title' => $title,
            'venue' => 'Main Ground',
            'start_date' => '2026-08-14',
            'end_date' => '2026-08-16',
            'description' => '<p>Annual sports</p>',
            'meta_title' => 'Meta '.$suffix,
            'meta_keywords' => 'kw',
            'meta_description' => 'md',
            'sidebar' => '1',
            'image' => 'uploads/gallery/event.jpg',
        ])->assertRedirect('/admin/front/events');

        $row = DB::table('front_cms_programs')->where('title', $title)->first();
        $this->assertNotNull($row);
        $this->cleanupProgramIds[] = (int) $row->id;
        $this->assertSame('events', $row->type);
        $this->assertSame('read/'.$row->slug, $row->url);
        $this->assertSame('2026-08-14', $row->event_start);
        $this->assertSame('2026-08-16', $row->event_end);
        $this->assertSame('Main Ground', $row->event_venue);
        $this->assertSame(1, (int) $row->sidebar);
        $this->assertSame('uploads/gallery/event.jpg', $row->feature_image);

        $this->get('/admin/front/events')->assertOk()->assertSee($title, false)->assertSee('Main Ground', false);
        $this->get('/admin/front/events/edit/'.$row->slug)->assertOk()->assertSee($title, false);

        $this->post('/admin/front/events/edit/'.$row->slug, [
            'id' => (string) $row->id,
            'title' => $title.' Edited',
            'venue' => 'Indoor Hall',
            'start_date' => '2026-08-15',
            'end_date' => '2026-08-17',
            'description' => '<p>Updated</p>',
            'meta_title' => 'Meta '.$suffix,
            'meta_keywords' => 'kw',
            'meta_description' => 'md',
            'image' => '',
        ])->assertRedirect('/admin/front/events');

        $updated = DB::table('front_cms_programs')->where('id', $row->id)->first();
        $this->assertSame($title.' Edited', $updated->title);
        $this->assertSame('Indoor Hall', $updated->event_venue);
        $this->assertSame('2026-08-15', $updated->event_start);
        $this->assertSame('2026-08-17', $updated->event_end);
        $this->assertSame(0, (int) $updated->sidebar);

        $this->get('/admin/front/events/delete/'.$updated->slug)->assertRedirect('/admin/front/events');
        $this->assertNull(DB::table('front_cms_programs')->where('id', $row->id)->first());
        $this->cleanupProgramIds = [];
    }
}
