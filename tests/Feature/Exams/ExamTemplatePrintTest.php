<?php

namespace Tests\Feature\Exams;

use App\Modules\Exams\Models\TemplateAdmitcard;
use App\Modules\Exams\Models\TemplateMarksheet;
use App\Modules\Staff\Models\Staff;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ExamTemplatePrintTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<int> */
    private array $cleanupMarksheetIds = [];

    /** @var list<int> */
    private array $cleanupAdmitcardIds = [];

    protected function tearDown(): void
    {
        if ($this->cleanupMarksheetIds !== []) {
            DB::table('template_marksheets')->whereIn('id', $this->cleanupMarksheetIds)->delete();
        }
        $this->cleanupMarksheetIds = [];

        if ($this->cleanupAdmitcardIds !== []) {
            DB::table('template_admitcards')->whereIn('id', $this->cleanupAdmitcardIds)->delete();
        }
        $this->cleanupAdmitcardIds = [];

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

        $token = uniqid('extpl', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'EXTPL-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Exam',
            'surname' => 'Templates',
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

    public function test_marksheet_and_admitcard_template_crud_and_print_pages(): void
    {
        $this->actingAsSuperAdmin();
        $suffix = uniqid();

        $this->get('/admin/marksheet')->assertOk()->assertSee('Design Marksheet', false);
        $this->post('/admin/marksheet', [
            'template' => 'MS-'.$suffix,
            'heading' => 'Board',
            'title' => 'Title',
            'exam_name' => 'Annual',
            'school_name' => 'Test School',
            'exam_center' => 'Center A',
            'is_name' => '1',
            'is_admission_no' => '1',
            'is_class' => '1',
        ])->assertRedirect('/admin/marksheet');

        $ms = TemplateMarksheet::query()->where('template', 'MS-'.$suffix)->firstOrFail();
        $this->cleanupMarksheetIds[] = $ms->id;
        $this->assertSame(1, (int) $ms->is_name);
        $this->assertSame(0, (int) $ms->is_photo);

        $this->post('/admin/marksheet/edit/'.$ms->id, [
            'template' => 'MS-Updated-'.$suffix,
            'heading' => 'Board',
            'exam_name' => 'Annual Updated',
            'school_name' => 'Test School',
            'is_name' => '1',
            'is_roll_no' => '1',
        ])->assertRedirect('/admin/marksheet');
        $ms->refresh();
        $this->assertSame('MS-Updated-'.$suffix, $ms->template);
        $this->assertSame(1, (int) $ms->is_roll_no);

        $this->get('/admin/admitcard')->assertOk()->assertSee('Design Admit Card', false);
        $this->post('/admin/admitcard', [
            'template' => 'AC-'.$suffix,
            'exam_name' => 'Term Exam',
            'school_name' => 'Test School',
            'is_name' => '1',
            'is_class' => '1',
        ])->assertRedirect('/admin/admitcard');

        $ac = TemplateAdmitcard::query()->where('template', 'AC-'.$suffix)->firstOrFail();
        $this->cleanupAdmitcardIds[] = $ac->id;

        $this->get('/admin/admitcard/activate/'.$ac->id)->assertRedirect('/admin/admitcard');
        $ac->refresh();
        $this->assertSame(1, (int) $ac->is_active);

        $this->get('/admin/examresult/marksheet')->assertOk()->assertSee('Print Marksheet', false);
        $this->get('/admin/examresult/admitcard')->assertOk()->assertSee('Print Admit Card', false);

        $this->get('/admin/marksheet/delete/'.$ms->id)->assertRedirect('/admin/marksheet');
        $this->assertNull(TemplateMarksheet::query()->find($ms->id));
        $this->cleanupMarksheetIds = [];

        $this->get('/admin/admitcard/delete/'.$ac->id)->assertRedirect('/admin/admitcard');
        $this->assertNull(TemplateAdmitcard::query()->find($ac->id));
        $this->cleanupAdmitcardIds = [];
    }
}
