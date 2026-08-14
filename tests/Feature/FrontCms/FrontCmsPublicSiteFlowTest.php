<?php

namespace Tests\Feature\FrontCms;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FrontCmsPublicSiteFlowTest extends TestCase
{
    private ?int $cmsSettingId = null;

    private mixed $originalCmsActive = null;

    /** @var array<string, string|null> */
    private array $originalPageDescriptions = [];

    /** @var list<int> */
    private array $cleanupProgramIds = [];

    /** @var list<int> */
    private array $cleanupVisitorIds = [];

    /** @var list<int> */
    private array $cleanupComplaintIds = [];

    protected function setUp(): void
    {
        parent::setUp();
        $row = DB::table('front_cms_settings')->orderBy('id')->first();
        $this->assertNotNull($row);
        $this->cmsSettingId = (int) $row->id;
        $this->originalCmsActive = $row->is_active_front_cms;
    }

    protected function tearDown(): void
    {
        if ($this->cleanupVisitorIds !== []) {
            DB::table('visitors_book')->whereIn('id', $this->cleanupVisitorIds)->delete();
        }
        if ($this->cleanupComplaintIds !== []) {
            DB::table('complaint')->whereIn('id', $this->cleanupComplaintIds)->delete();
        }
        if ($this->cleanupProgramIds !== []) {
            DB::table('front_cms_programs')->whereIn('id', $this->cleanupProgramIds)->delete();
        }
        foreach ($this->originalPageDescriptions as $slug => $description) {
            DB::table('front_cms_pages')->where('slug', $slug)->update(['description' => $description]);
        }
        if ($this->cmsSettingId) {
            DB::table('front_cms_settings')->where('id', $this->cmsSettingId)->update([
                'is_active_front_cms' => $this->originalCmsActive,
            ]);
        }

        parent::tearDown();
    }

    private function enablePublicCms(): void
    {
        DB::table('front_cms_settings')->where('id', $this->cmsSettingId)->update([
            'is_active_front_cms' => 1,
        ]);
    }

    public function test_public_routes_redirect_to_userlogin_when_cms_inactive(): void
    {
        DB::table('front_cms_settings')->where('id', $this->cmsSettingId)->update([
            'is_active_front_cms' => 0,
        ]);

        $this->get('/frontend')->assertRedirect('/site/userlogin');
        $this->get('/page/contact-us')->assertRedirect('/site/userlogin');
        $this->get('/')->assertRedirect('/site/login');
    }

    public function test_frontend_and_pages_render_when_cms_active(): void
    {
        $this->enablePublicCms();

        $home = DB::table('front_cms_pages')->where('slug', 'home')->first();
        $this->assertNotNull($home);

        $this->get('/frontend')
            ->assertOk()
            ->assertSee((string) $home->title, false);

        $this->get('/page/contact-us')->assertOk();
        $this->get('/page/home')->assertRedirect('/frontend');
        $this->get('/page/missing-slug-'.uniqid())->assertOk()->assertSee('404', false);
    }

    public function test_read_program_and_ajax_pagination(): void
    {
        $this->enablePublicCms();
        $suffix = uniqid();
        $title = 'Public notice '.$suffix;
        $id = DB::table('front_cms_programs')->insertGetId([
            'type' => 'notice',
            'slug' => 'public-notice-'.$suffix,
            'url' => 'read/public-notice-'.$suffix,
            'title' => $title,
            'date' => '2026-08-14',
            'description' => '<p>Public body</p>',
            'sidebar' => 0,
            'feature_image' => '',
            'meta_title' => '',
            'meta_description' => '',
            'meta_keyword' => '',
        ]);
        $this->cleanupProgramIds[] = $id;

        $this->get('/read/public-notice-'.$suffix)
            ->assertOk()
            ->assertSee($title, false)
            ->assertSee('Public body', false);

        $this->post('/welcome/ajaxPaginationData', [
            'page' => 0,
            'page_content_type' => 'notice',
        ])->assertOk()->assertSee($title, false);
    }

    public function test_contact_and_complain_forms_persist_without_mail(): void
    {
        $this->enablePublicCms();
        $this->withFormBuilder('contact-us', 'contact_us');
        $this->withFormBuilder('complain', 'complain');

        $token = uniqid('cms', true);

        $this->get('/page/contact-us')->assertOk()->assertSee('name="form_name"', false);

        $this->post('/page/contact-us', [
            'form_name' => 'contact_us',
            'name' => 'Ali '.$token,
            'email' => $token.'@example.test',
            'subject' => 'Hello '.$token,
            'description' => 'From site',
        ])->assertRedirect('/page/contact-us');

        $visitor = DB::table('visitors_book')->where('email', $token.'@example.test')->first();
        $this->assertNotNull($visitor);
        $this->cleanupVisitorIds[] = (int) $visitor->id;
        $this->assertSame('Online', $visitor->source);
        $this->assertSame('Hello '.$token, $visitor->purpose);
        $this->assertStringContainsString('(Sent from online front site)', (string) $visitor->note);

        $this->post('/page/complain', [
            'form_name' => 'complain',
            'name' => 'Ali '.$token,
            'email' => $token.'@example.test',
            'contact_no' => '03001234567',
            'description' => 'Issue '.$token,
        ])->assertRedirect('/page/complain');

        $complaint = DB::table('complaint')->where('email', $token.'@example.test')->first();
        $this->assertNotNull($complaint);
        $this->cleanupComplaintIds[] = (int) $complaint->id;
        $this->assertSame('General', $complaint->complaint_type);
        $this->assertSame('Online', $complaint->source);
        $this->assertSame('03001234567', $complaint->contact);
    }

    public function test_setsitecookies(): void
    {
        $this->post('/welcome/setsitecookies')->assertOk()->assertCookie('sitecookies', '1');
    }

    private function withFormBuilder(string $slug, string $formName): void
    {
        $page = DB::table('front_cms_pages')->where('slug', $slug)->first();
        $this->assertNotNull($page);
        $this->originalPageDescriptions[$slug] = $page->description;
        DB::table('front_cms_pages')->where('id', $page->id)->update([
            'description' => '<p>Form</p>[form-builder:'.$formName.']',
        ]);
    }
}
