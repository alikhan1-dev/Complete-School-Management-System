<?php

namespace Tests\Feature\FrontCms;

use App\Modules\Staff\Models\Staff;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FrontCmsMenusFlowTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<int> */
    private array $cleanupMenuIds = [];

    /** @var list<int> */
    private array $cleanupItemIds = [];

    protected function tearDown(): void
    {
        if ($this->cleanupItemIds !== []) {
            DB::table('front_cms_menu_items')->whereIn('id', $this->cleanupItemIds)->delete();
        }
        $this->cleanupItemIds = [];

        if ($this->cleanupMenuIds !== []) {
            DB::table('front_cms_menu_items')->whereIn('menu_id', $this->cleanupMenuIds)->delete();
            DB::table('front_cms_menus')->whereIn('id', $this->cleanupMenuIds)->delete();
        }
        $this->cleanupMenuIds = [];

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

        $token = uniqid('mn', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'MN-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Menu',
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

    public function test_menu_index_requires_staff_auth(): void
    {
        $this->get('/admin/front/menus')->assertRedirect();
    }

    public function test_create_menu_requires_name_and_rejects_duplicate(): void
    {
        $this->actingAsSuperAdmin();
        $this->post('/admin/front/menus', [])
            ->assertOk()
            ->assertSee('The Menu Item field is required.', false);

        $this->post('/admin/front/menus', ['menu' => 'Main Menu'])
            ->assertOk()
            ->assertSee('Menu already exists', false);
    }

    public function test_superadmin_can_manage_menu_and_items(): void
    {
        $this->actingAsSuperAdmin();
        $suffix = uniqid();
        $menuName = 'Extra '.$suffix;
        $pageId = (int) DB::table('front_cms_pages')->orderBy('id')->value('id');
        $this->assertGreaterThan(0, $pageId);

        $this->get('/admin/front/menus')->assertOk()->assertSee('Menu List', false);
        $this->post('/admin/front/menus', [
            'menu' => $menuName,
            'description' => 'Extra nav',
        ])->assertRedirect('/admin/front/menus');

        $menu = DB::table('front_cms_menus')->where('menu', $menuName)->first();
        $this->assertNotNull($menu);
        $this->cleanupMenuIds[] = (int) $menu->id;
        $this->assertSame('manual', $menu->content_type);

        $this->get('/admin/front/menus/additem/'.$menu->slug)->assertOk()->assertSee('Add Menu Item', false);

        $this->post('/admin/front/menus/additem/'.$menu->slug, [
            'menu_id' => (string) $menu->id,
            'menu' => 'Page Link '.$suffix,
            'page_id' => (string) $pageId,
        ])->assertRedirect('/admin/front/menus/additem/'.$menu->slug);

        $this->post('/admin/front/menus/additem/'.$menu->slug, [
            'menu_id' => (string) $menu->id,
            'menu' => 'Ext Link '.$suffix,
            'ext_url' => '1',
            'ext_url_link' => '',
        ])->assertOk()->assertSee('The Field Can Not Be Blank', false);

        $this->post('/admin/front/menus/additem/'.$menu->slug, [
            'menu_id' => (string) $menu->id,
            'menu' => 'Ext Link '.$suffix,
            'ext_url' => '1',
            'open_new_tab' => '1',
            'ext_url_link' => 'https://example.test/'.$suffix,
        ])->assertRedirect('/admin/front/menus/additem/'.$menu->slug);

        $pageItem = DB::table('front_cms_menu_items')->where('menu_id', $menu->id)->where('menu', 'Page Link '.$suffix)->first();
        $extItem = DB::table('front_cms_menu_items')->where('menu_id', $menu->id)->where('menu', 'Ext Link '.$suffix)->first();
        $this->assertNotNull($pageItem);
        $this->assertNotNull($extItem);
        $this->cleanupItemIds[] = (int) $pageItem->id;
        $this->cleanupItemIds[] = (int) $extItem->id;
        $this->assertSame($pageId, (int) $pageItem->page_id);
        $this->assertSame('1', (string) $extItem->ext_url);
        $this->assertSame(1, (int) $extItem->open_new_tab);

        $this->get('/admin/front/menus/edititem/'.$pageItem->slug.'/'.$menu->slug)
            ->assertOk()
            ->assertSee('Edit Menu Item', false);

        $this->post('/admin/front/menus/edititem/'.$pageItem->slug.'/'.$menu->slug, [
            'id' => (string) $pageItem->id,
            'top_menu' => $menu->slug,
            'menu' => 'Page Link Edited '.$suffix,
            'page_id' => (string) $pageId,
        ])->assertRedirect('/admin/front/menus/additem/'.$menu->slug);

        $this->assertSame(
            'Page Link Edited '.$suffix,
            DB::table('front_cms_menu_items')->where('id', $pageItem->id)->value('menu')
        );

        $this->post('/admin/front/menus/updateMenu', [
            'order' => [
                [
                    'id' => (string) $extItem->id,
                    'children' => [
                        ['id' => (string) $pageItem->id],
                    ],
                ],
            ],
        ])->assertOk();

        $nested = DB::table('front_cms_menu_items')->where('id', $pageItem->id)->first();
        $this->assertSame((int) $extItem->id, (int) $nested->parent_id);

        $this->post('/admin/front/menus/deleteMenuItem', ['id' => (string) $pageItem->id])
            ->assertOk()
            ->assertJson(['status' => 1]);
        $this->assertNull(DB::table('front_cms_menu_items')->where('id', $pageItem->id)->first());
        $this->cleanupItemIds = array_values(array_filter($this->cleanupItemIds, fn ($id) => $id !== (int) $pageItem->id));

        $this->get('/admin/front/menus/delete/'.$menu->slug)->assertRedirect('/admin/front/menus');
        $this->assertNull(DB::table('front_cms_menus')->where('id', $menu->id)->first());
        $this->cleanupMenuIds = [];
        $this->cleanupItemIds = array_values(array_filter(
            $this->cleanupItemIds,
            fn ($id) => DB::table('front_cms_menu_items')->where('id', $id)->exists()
        ));
    }
}
