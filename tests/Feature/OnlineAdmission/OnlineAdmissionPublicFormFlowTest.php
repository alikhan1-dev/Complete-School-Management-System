<?php

namespace Tests\Feature\OnlineAdmission;

use App\Modules\Shared\Services\SchoolContext;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OnlineAdmissionPublicFormFlowTest extends TestCase
{
    private mixed $originalAdmission = null;

    private mixed $originalCms = null;

    private mixed $originalPayment = null;

    private mixed $originalAdmissionCaptcha = null;

    /** @var list<int> */
    private array $cleanupIds = [];

    /** @var list<string> */
    private array $cleanupFiles = [];

    /** @var list<int> */
    private array $cleanupCustomFieldIds = [];

    /** @var list<string> */
    private array $cleanupCustomFieldNames = [];

    /** @var array<string, mixed>|null */
    private ?array $fieldSnapshot = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalAdmission = DB::table('sch_settings')->orderBy('id')->value('online_admission');
        $this->originalCms = DB::table('front_cms_settings')->orderBy('id')->value('is_active_front_cms');
        $this->originalPayment = DB::table('sch_settings')->orderBy('id')->value('online_admission_payment');
        DB::table('sch_settings')->orderBy('id')->limit(1)->update(['online_admission_payment' => 'no']);
        $this->originalAdmissionCaptcha = DB::table('captcha')->where('name', 'admission')->value('status');
        DB::table('captcha')->where('name', 'admission')->update(['status' => 0]);
        app(SchoolContext::class)->clearCache();
    }

    protected function tearDown(): void
    {
        if ($this->cleanupIds !== []) {
            DB::table('online_admission_custom_field_value')->whereIn('belong_table_id', $this->cleanupIds)->delete();
            DB::table('online_admissions')->whereIn('id', $this->cleanupIds)->delete();
        }
        $this->cleanupIds = [];
        foreach ($this->cleanupFiles as $path) {
            if (is_file($path)) {
                @unlink($path);
            }
        }
        $this->cleanupFiles = [];
        foreach ($this->cleanupCustomFieldIds as $fieldId) {
            DB::table('online_admission_custom_field_value')->where('custom_field_id', $fieldId)->delete();
            DB::table('custom_fields')->where('id', $fieldId)->delete();
        }
        $this->cleanupCustomFieldIds = [];
        if ($this->cleanupCustomFieldNames !== []) {
            DB::table('online_admission_fields')->whereIn('name', $this->cleanupCustomFieldNames)->delete();
            $this->cleanupCustomFieldNames = [];
        }
        if ($this->fieldSnapshot !== null) {
            foreach ($this->fieldSnapshot as $name => $status) {
                DB::table('online_admission_fields')->where('name', $name)->update(['status' => $status]);
            }
            $this->fieldSnapshot = null;
        }

        if ($this->originalAdmission !== null) {
            DB::table('sch_settings')->orderBy('id')->limit(1)->update([
                'online_admission' => $this->originalAdmission,
            ]);
            app(SchoolContext::class)->clearCache();
        }
        if ($this->originalCms !== null) {
            DB::table('front_cms_settings')->orderBy('id')->limit(1)->update([
                'is_active_front_cms' => $this->originalCms,
            ]);
        }
        if ($this->originalPayment !== null) {
            DB::table('sch_settings')->orderBy('id')->limit(1)->update([
                'online_admission_payment' => $this->originalPayment,
            ]);
            app(SchoolContext::class)->clearCache();
        }

        if ($this->originalAdmissionCaptcha !== null) {
            DB::table('captcha')->where('name', 'admission')->update(['status' => $this->originalAdmissionCaptcha]);
        }

        parent::tearDown();
    }

    private function setAdmission(int $value): void
    {
        DB::table('sch_settings')->orderBy('id')->limit(1)->update(['online_admission' => $value]);
        app(SchoolContext::class)->clearCache();
    }

    public function test_form_redirects_when_admission_disabled_and_cms_off(): void
    {
        $this->setAdmission(0);
        DB::table('front_cms_settings')->orderBy('id')->limit(1)->update(['is_active_front_cms' => 0]);

        $this->get('/online_admission')->assertRedirect('/site/userlogin');
    }

    public function test_submit_requires_core_fields(): void
    {
        $this->setAdmission(1);
        $this->get('/online_admission')->assertOk()->assertSee('Online Admission Form', false);
        $this->post('/online_admission', [])
            ->assertOk()
            ->assertSee('The First Name field is required.', false)
            ->assertSee('The Date Of Birth field is required.', false)
            ->assertSee('The Class field is required.', false)
            ->assertSee('The Section field is required.', false)
            ->assertSee('The Gender field is required.', false)
            ->assertSee('The Email field is required.', false);
    }

    public function test_submit_review_status_and_form_submit(): void
    {
        $this->setAdmission(1);
        $section = DB::table('class_sections')->orderBy('id')->first();
        $this->assertNotNull($section);
        $token = uniqid('pub', false);

        $response = $this->post('/online_admission', [
            'class_id' => (string) $section->class_id,
            'section_id' => (string) $section->id,
            'firstname' => 'Public '.$token,
            'lastname' => 'Applicant',
            'dob' => '2012-05-01',
            'gender' => 'Male',
            'email' => $token.'@example.test',
            'guardian_is' => 'father',
            'guardian_name' => 'Father '.$token,
            'guardian_relation' => 'Father',
        ]);
        $response->assertRedirect();
        $location = (string) $response->headers->get('Location');
        $this->assertMatchesRegularExpression('#/welcome/online_admission_review/\d{6}$#', $location);
        $reference = basename($location);

        $row = DB::table('online_admissions')->where('reference_no', $reference)->first();
        $this->assertNotNull($row);
        $this->cleanupIds[] = (int) $row->id;
        $this->assertSame('Public '.$token, $row->firstname);
        $this->assertSame(0, (int) $row->form_status);
        $this->assertSame((int) $section->id, (int) $row->class_section_id);

        $this->get('/welcome/online_admission_review/'.$reference)
            ->assertOk()
            ->assertSee($reference, false)
            ->assertSee('Public '.$token, false);

        $this->flushSession();
        $this->get('/welcome/online_admission_review/'.$reference)->assertForbidden();

        $this->postJson('/welcome/checkadmissionstatus', [
            'refno' => $reference,
            'student_dob' => '2012-05-01',
        ])->assertOk()->assertJson(['status' => '1', 'refno' => $reference]);

        $this->get('/welcome/online_admission_review/'.$reference)->assertOk();

        $this->postJson('/welcome/submitadmission', [
            'admission_id' => $row->id,
            'checkterm' => '1',
        ])->assertOk()->assertJson(['status' => '1', 'reference_no' => $reference]);

        $this->assertSame(1, (int) DB::table('online_admissions')->where('id', $row->id)->value('form_status'));

        $this->post('/welcome/getSections', ['class_id' => $section->class_id])
            ->assertOk()
            ->assertJsonFragment(['id' => $section->id]);
    }

    public function test_public_edit_updates_application_and_returns_to_review(): void
    {
        $this->setAdmission(1);
        $section = DB::table('class_sections')->orderBy('id')->first();
        $this->assertNotNull($section);
        $token = uniqid('edt', false);

        $create = $this->post('/online_admission', [
            'class_id' => (string) $section->class_id,
            'section_id' => (string) $section->id,
            'firstname' => 'Edit '.$token,
            'lastname' => 'Applicant',
            'dob' => '2012-05-01',
            'gender' => 'Male',
            'email' => $token.'@example.test',
            'guardian_is' => 'father',
            'guardian_name' => 'Father '.$token,
            'guardian_relation' => 'Father',
        ]);
        $create->assertRedirect();
        $reference = basename((string) $create->headers->get('Location'));
        $row = DB::table('online_admissions')->where('reference_no', $reference)->first();
        $this->assertNotNull($row);
        $this->cleanupIds[] = (int) $row->id;

        $this->get('/welcome/editonlineadmission/'.$reference)
            ->assertOk()
            ->assertSee('Edit '.$token, false);

        $this->post('/welcome/editonlineadmission/'.$reference, [
            'admission_id' => (string) $row->id,
            'class_id' => (string) $section->class_id,
            'section_id' => (string) $section->id,
            'firstname' => 'Edited '.$token,
            'lastname' => 'Applicant',
            'dob' => '2012-05-01',
            'gender' => 'Female',
            'email' => $token.'@example.test',
            'guardian_is' => 'father',
            'guardian_name' => 'Father '.$token,
            'guardian_relation' => 'Father',
        ])->assertRedirect('/welcome/online_admission_review/'.$reference);

        $updated = DB::table('online_admissions')->where('id', $row->id)->first();
        $this->assertSame('Edited '.$token, $updated->firstname);
        $this->assertSame('Female', $updated->gender);
        $this->assertSame($reference, $updated->reference_no);
        $this->assertSame(0, (int) $updated->form_status);

        $this->get('/welcome/online_admission_review/'.$reference)
            ->assertOk()
            ->assertSee('Edited '.$token, false);
    }

    public function test_public_edit_unknown_reference_is_not_found(): void
    {
        $this->setAdmission(1);
        $this->get('/welcome/editonlineadmission/000000')->assertNotFound();
    }

    public function test_public_edit_requires_core_fields_and_does_not_need_session(): void
    {
        $this->setAdmission(1);
        $section = DB::table('class_sections')->orderBy('id')->first();
        $this->assertNotNull($section);
        $token = uniqid('eds', false);

        $create = $this->post('/online_admission', [
            'class_id' => (string) $section->class_id,
            'section_id' => (string) $section->id,
            'firstname' => 'Session '.$token,
            'lastname' => 'Applicant',
            'dob' => '2012-05-01',
            'gender' => 'Male',
            'email' => $token.'@example.test',
            'guardian_is' => 'father',
            'guardian_name' => 'Father '.$token,
            'guardian_relation' => 'Father',
        ]);
        $create->assertRedirect();
        $reference = basename((string) $create->headers->get('Location'));
        $row = DB::table('online_admissions')->where('reference_no', $reference)->first();
        $this->assertNotNull($row);
        $this->cleanupIds[] = (int) $row->id;

        $this->flushSession();
        $this->get('/welcome/editonlineadmission/'.$reference)
            ->assertOk()
            ->assertSee('Session '.$token, false);

        $this->post('/welcome/editonlineadmission/'.$reference, [
            'admission_id' => (string) $row->id,
        ])->assertOk()->assertSee('The First Name field is required.', false);

        $this->assertSame('Session '.$token, DB::table('online_admissions')->where('id', $row->id)->value('firstname'));
    }

    public function test_public_form_persists_photo_and_document_uploads(): void
    {
        $this->setAdmission(1);
        $this->enableFields(['student_photo', 'upload_documents']);
        $section = DB::table('class_sections')->orderBy('id')->first();
        $this->assertNotNull($section);
        $token = uniqid('upl', false);

        $create = $this->post('/online_admission', [
            'class_id' => (string) $section->class_id,
            'section_id' => (string) $section->id,
            'firstname' => 'Upload '.$token,
            'lastname' => 'Applicant',
            'dob' => '2012-05-01',
            'gender' => 'Male',
            'email' => $token.'@example.test',
            'guardian_is' => 'father',
            'guardian_name' => 'Father '.$token,
            'guardian_relation' => 'Father',
            'file' => UploadedFile::fake()->image('student.jpg', 20, 20),
            'document' => UploadedFile::fake()->create('notes.pdf', 20, 'application/pdf'),
        ]);
        $create->assertRedirect();
        $reference = basename((string) $create->headers->get('Location'));
        $row = DB::table('online_admissions')->where('reference_no', $reference)->first();
        $this->assertNotNull($row);
        $this->cleanupIds[] = (int) $row->id;
        $this->assertNotSame('', (string) $row->image);
        $this->assertNotSame('', (string) $row->document);
        $this->assertStringContainsString('uploads/student_images/online_admission_image/', (string) $row->image);
        $imagePath = public_path(str_replace('/', DIRECTORY_SEPARATOR, (string) $row->image));
        $docPath = public_path('uploads/student_documents/online_admission_doc/'.basename((string) $row->document));
        $this->assertFileExists($imagePath);
        $this->assertFileExists($docPath);
        $this->cleanupFiles[] = $imagePath;
        $this->cleanupFiles[] = $docPath;
    }

    public function test_public_form_rejects_disallowed_photo_type(): void
    {
        $this->setAdmission(1);
        $this->enableFields(['student_photo']);
        $section = DB::table('class_sections')->orderBy('id')->first();
        $this->assertNotNull($section);
        $token = uniqid('bad', false);

        $response = $this->post('/online_admission', [
            'class_id' => (string) $section->class_id,
            'section_id' => (string) $section->id,
            'firstname' => 'Bad '.$token,
            'lastname' => 'Applicant',
            'dob' => '2012-05-01',
            'gender' => 'Male',
            'email' => $token.'@example.test',
            'guardian_is' => 'father',
            'guardian_name' => 'Father '.$token,
            'guardian_relation' => 'Father',
            'file' => UploadedFile::fake()->create('virus.exe', 20, 'application/x-msdownload'),
        ]);
        $response->assertOk();
        $this->assertTrue(
            str_contains($response->getContent(), 'File Type Not Allowed')
            || str_contains($response->getContent(), 'Extension Not Allowed')
            || DB::table('online_admissions')->where('email', $token.'@example.test')->doesntExist()
        );
    }

    public function test_public_form_requires_and_persists_enabled_custom_fields(): void
    {
        $this->setAdmission(1);
        $section = DB::table('class_sections')->orderBy('id')->first();
        $this->assertNotNull($section);
        $field = $this->createEnabledRequiredStudentField();
        $token = uniqid('cf', false);
        $base = [
            'class_id' => (string) $section->class_id,
            'section_id' => (string) $section->id,
            'firstname' => 'Custom '.$token,
            'lastname' => 'Applicant',
            'dob' => '2012-05-01',
            'gender' => 'Male',
            'email' => $token.'@example.test',
            'guardian_is' => 'father',
            'guardian_name' => 'Father '.$token,
            'guardian_relation' => 'Father',
        ];

        $this->get('/online_admission')
            ->assertOk()
            ->assertSee('Custom Fields', false)
            ->assertSee($field->name, false);

        $this->post('/online_admission', $base)
            ->assertOk()
            ->assertSee('The '.$field->name.' field is required.', false);

        $create = $this->post('/online_admission', array_merge($base, [
            'custom_fields' => [
                'students' => [
                    $field->id => 'Alpha',
                ],
            ],
        ]));
        $create->assertRedirect();
        $reference = basename((string) $create->headers->get('Location'));
        $row = DB::table('online_admissions')->where('reference_no', $reference)->first();
        $this->assertNotNull($row);
        $this->cleanupIds[] = (int) $row->id;
        $this->assertDatabaseHas('online_admission_custom_field_value', [
            'belong_table_id' => $row->id,
            'custom_field_id' => $field->id,
            'field_value' => 'Alpha',
        ]);

        $this->get('/welcome/editonlineadmission/'.$reference)
            ->assertOk()
            ->assertSee('Alpha', false);

        $this->post('/welcome/editonlineadmission/'.$reference, array_merge($base, [
            'admission_id' => (string) $row->id,
            'firstname' => 'Custom Edited '.$token,
            'custom_fields' => [
                'students' => [
                    $field->id => 'Beta',
                ],
            ],
        ]))->assertRedirect('/welcome/online_admission_review/'.$reference);

        $this->assertDatabaseHas('online_admission_custom_field_value', [
            'belong_table_id' => $row->id,
            'custom_field_id' => $field->id,
            'field_value' => 'Beta',
        ]);
        $this->assertSame(
            1,
            (int) DB::table('online_admission_custom_field_value')
                ->where('belong_table_id', $row->id)
                ->where('custom_field_id', $field->id)
                ->count()
        );
    }

    /**
     * @return object{id:int,name:string}
     */
    private function createEnabledRequiredStudentField(): object
    {
        $name = 'OA note '.uniqid();
        $id = DB::table('custom_fields')->insertGetId([
            'name' => $name,
            'belong_to' => 'students',
            'type' => 'input',
            'bs_column' => 6,
            'validation' => 1,
            'field_values' => '',
            'visible_on_table' => 0,
            'weight' => 0,
            'is_active' => 1,
        ]);
        $this->cleanupCustomFieldIds[] = $id;
        $this->cleanupCustomFieldNames[] = $name;
        DB::table('online_admission_fields')->insert(['name' => $name, 'status' => 1]);

        return (object) ['id' => $id, 'name' => $name];
    }

    public function test_admission_captcha_required_and_accepts_session_word(): void
    {
        $this->setAdmission(1);
        DB::table('captcha')->where('name', 'admission')->update(['status' => 1]);
        $section = DB::table('class_sections')->orderBy('id')->first();
        $this->assertNotNull($section);
        $token = uniqid('cap', false);
        $base = [
            'class_id' => (string) $section->class_id,
            'section_id' => (string) $section->id,
            'firstname' => 'Captcha '.$token,
            'lastname' => 'Applicant',
            'dob' => '2012-05-01',
            'gender' => 'Male',
            'email' => $token.'@example.test',
            'guardian_is' => 'father',
            'guardian_name' => 'Father '.$token,
            'guardian_relation' => 'Father',
        ];

        $this->withSession(['captchaCode' => 'TEST12'])
            ->post('/online_admission', $base)
            ->assertOk()
            ->assertSee('The Captcha field is required.', false);

        $this->withSession(['captchaCode' => 'TEST12'])
            ->post('/online_admission', array_merge($base, ['captcha' => 'NOPE']))
            ->assertOk()
            ->assertSee('Incorrect Captcha', false);

        $create = $this->withSession(['captchaCode' => 'TEST12'])
            ->post('/online_admission', array_merge($base, ['captcha' => 'TEST12']));
        $create->assertRedirect();
        $reference = basename((string) $create->headers->get('Location'));
        $row = DB::table('online_admissions')->where('reference_no', $reference)->first();
        $this->assertNotNull($row);
        $this->cleanupIds[] = (int) $row->id;
    }

    public function test_refresh_captcha_sets_session_word(): void
    {
        $this->setAdmission(1);
        $this->post('/site/refreshCaptcha')
            ->assertOk();
        $this->assertNotSame('', (string) session('captchaCode'));
    }

    private function enableFields(array $names): void
    {
        $this->fieldSnapshot = [];
        foreach ($names as $name) {
            $this->fieldSnapshot[$name] = DB::table('online_admission_fields')->where('name', $name)->value('status');
            $existing = DB::table('online_admission_fields')->where('name', $name)->first();
            if ($existing) {
                DB::table('online_admission_fields')->where('name', $name)->update(['status' => 1]);
            } else {
                DB::table('online_admission_fields')->insert(['name' => $name, 'status' => 1]);
            }
        }
    }
}
