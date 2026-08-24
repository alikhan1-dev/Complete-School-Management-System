<?php

namespace Tests\Feature\Fees;

use App\Modules\Academics\Models\AcademicSession;
use App\Modules\Academics\Models\ClassSection;
use App\Modules\Academics\Models\SchoolClass;
use App\Modules\Academics\Models\Section;
use App\Modules\Fees\Models\FeeGroup;
use App\Modules\Fees\Models\FeeGroupFeetype;
use App\Modules\Fees\Models\FeeSessionGroup;
use App\Modules\Fees\Models\FeeType;
use App\Modules\Fees\Models\StudentFeesDeposite;
use App\Modules\Fees\Models\StudentFeesMaster;
use App\Modules\Fees\Models\ThermalPrint;
use App\Modules\Fees\Services\ThermalPrintService;
use App\Modules\Staff\Models\Staff;
use App\Modules\Students\Models\Student;
use App\Modules\Students\Models\StudentSession;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ThermalPrintTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<int> */
    private array $cleanupIds = [];

    private ?array $thermalSnapshot = null;

    private ?int $permissionActiveSnapshot = null;

    protected function tearDown(): void
    {
        foreach ($this->cleanupIds as $studentId) {
            $sessionIds = DB::table('student_session')->where('student_id', $studentId)->pluck('id');
            if ($sessionIds->isNotEmpty()) {
                $masterIds = DB::table('student_fees_master')->whereIn('student_session_id', $sessionIds)->pluck('id');
                if ($masterIds->isNotEmpty()) {
                    DB::table('student_fees_deposite')->whereIn('student_fees_master_id', $masterIds)->delete();
                    DB::table('student_fees_master')->whereIn('id', $masterIds)->delete();
                }
            }
            DB::table('student_session')->where('student_id', $studentId)->delete();
            DB::table('users')->where('role', 'student')->where('user_id', $studentId)->delete();
            $parentId = DB::table('students')->where('id', $studentId)->value('parent_id');
            DB::table('students')->where('id', $studentId)->delete();
            if ($parentId) {
                DB::table('users')->where('id', $parentId)->where('role', 'parent')->delete();
            }
        }
        $this->cleanupIds = [];

        foreach ($this->createdStaffIds as $staffId) {
            DB::table('staff_roles')->where('staff_id', $staffId)->delete();
            DB::table('staff')->where('id', $staffId)->delete();
        }
        $this->createdStaffIds = [];

        if (Schema::hasTable('thermal_print') && $this->thermalSnapshot !== null) {
            $row = ThermalPrint::query()->orderBy('id')->first();
            if ($row) {
                $row->fill($this->thermalSnapshot);
                $row->save();
            }
        }

        if ($this->permissionActiveSnapshot !== null) {
            DB::table('permission_group')
                ->where('short_code', ThermalPrintService::MODULE_SHORT_CODE)
                ->update(['is_active' => $this->permissionActiveSnapshot]);
        }

        parent::tearDown();
    }

    private function actingAsSuperAdmin(): void
    {
        $roleId = (int) (DB::table('roles')->where('is_superadmin', 1)->value('id')
            ?: DB::table('roles')->where('name', 'Super Admin')->value('id'));
        $this->assertGreaterThan(0, $roleId);

        $token = uniqid('thp', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'THP-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Test',
            'surname' => 'Thermal',
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

    private function enableThermal(string $schoolName, string $footer): void
    {
        $this->assertTrue(Schema::hasTable('thermal_print'), 'thermal_print migration must be applied');

        $row = ThermalPrint::query()->orderBy('id')->first();
        if (! $row) {
            $row = new ThermalPrint;
        }
        $this->thermalSnapshot = [
            'school_name' => (string) ($row->school_name ?? ''),
            'address' => (string) ($row->address ?? ''),
            'footer_text' => (string) ($row->footer_text ?? ''),
            'is_print' => (int) ($row->is_print ?? 0),
        ];
        $this->permissionActiveSnapshot = (int) (DB::table('permission_group')
            ->where('short_code', ThermalPrintService::MODULE_SHORT_CODE)
            ->value('is_active') ?? 0);

        app(ThermalPrintService::class)->save([
            'school_name' => $schoolName,
            'address' => 'Thermal Address Lane',
            'footer_text' => $footer,
            'is_print' => 1,
        ]);
    }

    /**
     * @return array{deposit:StudentFeesDeposite,admissionNo:string,suffix:string}
     */
    private function seedPaidFee(): array
    {
        $suffix = uniqid();
        $session = AcademicSession::query()->first() ?: AcademicSession::query()->create(['session' => '2099-00']);
        DB::table('sch_settings')->limit(1)->update(['session_id' => $session->id]);

        $section = Section::query()->create(['section' => 'S-'.$suffix, 'is_active' => 'yes']);
        $class = SchoolClass::query()->create(['class' => 'C-'.$suffix, 'is_active' => 'yes']);
        ClassSection::query()->create(['class_id' => $class->id, 'section_id' => $section->id, 'is_active' => 'yes']);

        $type = FeeType::query()->create([
            'type' => 'T-'.$suffix, 'code' => 'C-'.$suffix, 'description' => '', 'is_system' => 0, 'nature' => '', 'is_active' => 'no',
        ]);
        $group = FeeGroup::query()->create([
            'name' => 'G-'.$suffix, 'description' => '', 'is_system' => 0, 'nature' => '', 'is_active' => 'no',
        ]);
        $sessionGroup = FeeSessionGroup::query()->create([
            'fee_groups_id' => $group->id, 'session_id' => $session->id, 'is_active' => 'no',
        ]);
        $feeTypeRow = FeeGroupFeetype::query()->create([
            'fee_session_group_id' => $sessionGroup->id,
            'fee_groups_id' => $group->id,
            'feetype_id' => $type->id,
            'session_id' => $session->id,
            'amount' => 1000,
            'fine_type' => 'none',
            'fine_percentage' => 0,
            'fine_amount' => 0,
            'fine_per_day' => 0,
            'is_active' => 'no',
        ]);

        $admissionNo = 'ADM'.$suffix;
        $this->post('/student/create', [
            'admission_no' => $admissionNo,
            'firstname' => 'Thermal',
            'lastname' => 'Student',
            'gender' => 'Male',
            'dob' => '2012-01-01',
            'class_id' => $class->id,
            'section_id' => $section->id,
            'guardian_is' => 'father',
            'guardian_name' => 'Dad',
            'guardian_phone' => '03000000000',
            'father_name' => 'Father Thermal',
        ])->assertRedirect();

        $student = Student::query()->where('admission_no', $admissionNo)->firstOrFail();
        $this->cleanupIds[] = $student->id;
        $studentSession = StudentSession::query()
            ->where('student_id', $student->id)
            ->where('session_id', $session->id)
            ->firstOrFail();

        StudentFeesMaster::query()->create([
            'is_system' => 0,
            'student_session_id' => $studentSession->id,
            'fee_session_group_id' => $sessionGroup->id,
            'amount' => 0,
            'is_active' => 'no',
        ]);
        $master = StudentFeesMaster::query()
            ->where('student_session_id', $studentSession->id)
            ->where('fee_session_group_id', $sessionGroup->id)
            ->firstOrFail();

        $this->post('/studentfee/addstudentfee', [
            'student_fees_master_id' => $master->id,
            'fee_groups_feetype_id' => $feeTypeRow->id,
            'student_session_id' => $studentSession->id,
            'date' => '2026-08-12',
            'amount' => 500,
            'amount_discount' => 0,
            'amount_fine' => 25,
            'payment_mode' => 'Cash',
            'description' => 'Thermal receipt note',
        ])->assertRedirect('/studentfee/addfee/'.$studentSession->id);

        $deposit = StudentFeesDeposite::query()
            ->where('student_fees_master_id', $master->id)
            ->where('fee_groups_feetype_id', $feeTypeRow->id)
            ->firstOrFail();

        return [
            'deposit' => $deposit,
            'admissionNo' => $admissionNo,
            'suffix' => $suffix,
            'studentSessionId' => $studentSession->id,
            'masterId' => $master->id,
            'feeGroupsFeetypeId' => $feeTypeRow->id,
            'feeSessionGroupId' => $sessionGroup->id,
        ];
    }

    public function test_settings_page_and_save_enable_thermal_module(): void
    {
        $this->actingAsSuperAdmin();
        $this->assertTrue(Schema::hasTable('thermal_print'), 'Run thermal_print migration before this test');

        $row = ThermalPrint::query()->orderBy('id')->first();
        $this->thermalSnapshot = [
            'school_name' => (string) ($row->school_name ?? ''),
            'address' => (string) ($row->address ?? ''),
            'footer_text' => (string) ($row->footer_text ?? ''),
            'is_print' => (int) ($row->is_print ?? 0),
        ];
        $this->permissionActiveSnapshot = (int) (DB::table('permission_group')
            ->where('short_code', ThermalPrintService::MODULE_SHORT_CODE)
            ->value('is_active') ?? 0);

        $this->get('/admin/thermalprint')
            ->assertOk()
            ->assertSee('Thermal Print', false);

        $this->post('/admin/thermalprint', [
            'school_name' => 'Thermal School HQ',
            'address' => '1 Print Street',
            'footer_text' => 'Thanks for paying',
            'is_print' => 1,
        ])->assertRedirect(route('fees.thermal_print.index'));

        $this->assertTrue(app(ThermalPrintService::class)->isEnabled());
        $settings = app(ThermalPrintService::class)->settings();
        $this->assertSame('Thermal School HQ', $settings['school_name'] ?? null);
        $this->assertSame(1, (int) ($settings['is_print'] ?? 0));
    }

    public function test_print_fees_by_name_uses_thermal_layout_when_enabled(): void
    {
        $this->actingAsSuperAdmin();
        $this->enableThermal('Laravel Thermal Academy', 'Thermal footer line');

        $seed = $this->seedPaidFee();
        $deposit = $seed['deposit'];

        $json = $this->postJson('/studentfee/printFeesByName', [
            'main_invoice' => $deposit->id,
            'sub_invoice' => 1,
            'fee_category' => 'fees',
        ])->assertOk()->assertJson(['status' => 1]);

        $page = (string) $json->json('page');
        $this->assertStringContainsString('Laravel Thermal Academy', $page);
        $this->assertStringContainsString('Thermal footer line', $page);
        $this->assertStringContainsString($seed['admissionNo'], $page);
        $this->assertStringContainsString('2.9in', $page);
        $this->assertStringNotContainsString('Receipt note', $page);

        $this->get('/studentfee/printFeesByName?'.http_build_query([
            'main_invoice' => $deposit->id,
            'sub_invoice' => 1,
            'fee_category' => 'fees',
        ]))->assertOk()
            ->assertSee('Laravel Thermal Academy', false)
            ->assertSee('Thermal footer line', false);
    }

    public function test_print_fees_by_name_uses_standard_layout_when_disabled(): void
    {
        $this->actingAsSuperAdmin();
        $this->assertTrue(Schema::hasTable('thermal_print'));

        $row = ThermalPrint::query()->orderBy('id')->first() ?: new ThermalPrint;
        $this->thermalSnapshot = [
            'school_name' => (string) ($row->school_name ?? ''),
            'address' => (string) ($row->address ?? ''),
            'footer_text' => (string) ($row->footer_text ?? ''),
            'is_print' => (int) ($row->is_print ?? 0),
        ];
        $this->permissionActiveSnapshot = (int) (DB::table('permission_group')
            ->where('short_code', ThermalPrintService::MODULE_SHORT_CODE)
            ->value('is_active') ?? 0);

        app(ThermalPrintService::class)->save([
            'school_name' => 'Should Not Appear',
            'address' => 'x',
            'footer_text' => 'hidden thermal footer',
            'is_print' => 0,
        ]);

        $seed = $this->seedPaidFee();
        $deposit = $seed['deposit'];

        $page = (string) $this->postJson('/studentfee/printFeesByName', [
            'main_invoice' => $deposit->id,
            'sub_invoice' => 1,
            'fee_category' => 'fees',
        ])->assertOk()->json('page');

        $this->assertStringNotContainsString('Should Not Appear', $page);
        $this->assertStringNotContainsString('hidden thermal footer', $page);
        $this->assertStringContainsString('Thermal receipt note', $page);
        $this->assertStringNotContainsString('2.9in', $page);
    }
}
