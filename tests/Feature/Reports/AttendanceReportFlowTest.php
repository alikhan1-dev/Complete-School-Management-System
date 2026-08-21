<?php

namespace Tests\Feature\Reports;

use App\Modules\Academics\Models\AcademicSession;
use App\Modules\Academics\Models\ClassSection;
use App\Modules\Academics\Models\SchoolClass;
use App\Modules\Academics\Models\Section;
use App\Modules\Attendance\Services\StudentDayAttendanceService;
use App\Modules\Shared\Services\SchoolContext;
use App\Modules\Staff\Models\Staff;
use App\Modules\Students\Models\Student;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AttendanceReportFlowTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<int> */
    private array $cleanupStudentIds = [];

    /** @var list<int> */
    private array $cleanupClassIds = [];

    /** @var list<int> */
    private array $cleanupSectionIds = [];

    /** @var list<int> */
    private array $cleanupAttendanceIds = [];

    /** @var list<int> */
    private array $cleanupStaffAttendanceIds = [];

    protected function tearDown(): void
    {
        if ($this->cleanupAttendanceIds !== []) {
            DB::table('student_attendences')->whereIn('id', $this->cleanupAttendanceIds)->delete();
        }
        $this->cleanupAttendanceIds = [];

        if ($this->cleanupStaffAttendanceIds !== []) {
            DB::table('staff_attendance')->whereIn('id', $this->cleanupStaffAttendanceIds)->delete();
        }
        $this->cleanupStaffAttendanceIds = [];

        foreach ($this->cleanupStudentIds as $studentId) {
            DB::table('users')->where('user_id', $studentId)->where('role', 'student')->delete();
            DB::table('users')->where('childs', (string) $studentId)->where('role', 'parent')->delete();
            DB::table('student_session')->where('student_id', $studentId)->delete();
            DB::table('students')->where('id', $studentId)->delete();
        }
        $this->cleanupStudentIds = [];

        foreach ($this->cleanupClassIds as $classId) {
            DB::table('class_sections')->where('class_id', $classId)->delete();
            DB::table('classes')->where('id', $classId)->delete();
        }
        $this->cleanupClassIds = [];

        if ($this->cleanupSectionIds !== []) {
            DB::table('sections')->whereIn('id', $this->cleanupSectionIds)->delete();
        }
        $this->cleanupSectionIds = [];

        foreach ($this->createdStaffIds as $staffId) {
            DB::table('staff_roles')->where('staff_id', $staffId)->delete();
            DB::table('staff')->where('id', $staffId)->delete();
        }
        $this->createdStaffIds = [];

        parent::tearDown();
    }

    private function actingAsSuperAdmin(): int
    {
        $roleId = (int) (DB::table('roles')->where('is_superadmin', 1)->value('id')
            ?: DB::table('roles')->where('name', 'Super Admin')->value('id'));
        $this->assertGreaterThan(0, $roleId);

        $token = uniqid('arpt', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'ARPT-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'AttReport',
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

        return $staffId;
    }

    /**
     * @return array{student: Student, class: SchoolClass, section: Section, sessionId: int, staffId: int}
     */
    private function seedContext(): array
    {
        $staffId = $this->actingAsSuperAdmin();
        $suffix = uniqid();
        $session = AcademicSession::query()->first() ?: AcademicSession::query()->create(['session' => '2099-arpt']);
        DB::table('sch_settings')->limit(1)->update([
            'session_id' => $session->id,
            'attendence_type' => 0,
        ]);
        app(SchoolContext::class)->clearCache();

        $section = Section::query()->create(['section' => 'ARPTS-'.$suffix, 'is_active' => 'yes']);
        $class = SchoolClass::query()->create(['class' => 'ARPTC-'.$suffix, 'is_active' => 'yes']);
        $this->cleanupSectionIds[] = $section->id;
        $this->cleanupClassIds[] = $class->id;
        ClassSection::query()->create([
            'class_id' => $class->id,
            'section_id' => $section->id,
            'is_active' => 'yes',
        ]);

        $admissionNo = 'ARPTADM'.$suffix;
        $this->post('/student/create', [
            'admission_no' => $admissionNo,
            'firstname' => 'Att',
            'lastname' => 'Pupil',
            'gender' => 'Male',
            'dob' => '2012-01-01',
            'class_id' => $class->id,
            'section_id' => $section->id,
            'guardian_is' => 'father',
            'guardian_name' => 'Dad',
            'guardian_phone' => '03001112233',
        ])->assertRedirect();

        $student = Student::query()->where('admission_no', $admissionNo)->firstOrFail();
        $this->cleanupStudentIds[] = $student->id;

        $studentSessionId = (int) DB::table('student_session')->where('student_id', $student->id)->value('id');
        $today = now()->toDateString();
        $attId = DB::table('student_attendences')->insertGetId([
            'student_session_id' => $studentSessionId,
            'date' => $today,
            'attendence_type_id' => StudentDayAttendanceService::TYPE_PRESENT,
            'remark' => 'ok',
            'biometric_attendence' => 0,
            'qrcode_attendance' => 0,
            'user_agent' => '',
            'biometric_device_data' => '',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->cleanupAttendanceIds[] = $attId;

        $staffAttId = DB::table('staff_attendance')->insertGetId([
            'staff_id' => $staffId,
            'date' => $today,
            'staff_attendance_type_id' => 1,
            'remark' => 'staff-ok',
            'biometric_attendence' => 0,
            'qrcode_attendance' => 0,
            'user_agent' => '',
            'biometric_device_data' => '',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->cleanupStaffAttendanceIds[] = $staffAttId;

        return [
            'student' => $student,
            'class' => $class,
            'section' => $section,
            'sessionId' => (int) $session->id,
            'staffId' => $staffId,
        ];
    }

    public function test_attendance_reports_require_staff_auth(): void
    {
        $this->get('/attendencereports/attendance')->assertRedirect();
        $this->get('/attendencereports/daywiseattendancereport')->assertRedirect();
        $this->get('/attendencereports/staffdaywiseattendancereport')->assertRedirect();
        $this->get('/attendencereports/daily_attendance_report')->assertRedirect();
        $this->get('/attendencereports/attendancereport')->assertRedirect();
        $this->get('/attendencereports/classattendencereport')->assertRedirect();
        $this->get('/attendencereports/staffattendancereport')->assertRedirect();
        $this->get('/attendencereports/reportbymonth')->assertRedirect();
        $this->get('/attendencereports/reportbymonthstudent')->assertRedirect();
        $this->get('/attendencereports/biometric_attlog')->assertRedirect();
    }

    public function test_attendance_report_slice_one_flows(): void
    {
        $ctx = $this->seedContext();
        $today = now()->toDateString();
        $roleName = (string) DB::table('roles')->where('is_superadmin', 1)->value('name');

        $this->get('/attendencereports/attendance')
            ->assertOk()
            ->assertSee('Attendance Report', false)
            ->assertSee('attendencereports/daywiseattendancereport', false)
            ->assertSee('attendencereports/daily_attendance_report', false);

        $this->post('/attendencereports/daywiseattendancereport', [])
            ->assertSessionHasErrors(['class_id', 'section_id', 'date']);

        $this->post('/attendencereports/daywiseattendancereport', [
            'class_id' => $ctx['class']->id,
            'section_id' => $ctx['section']->id,
            'date' => $today,
        ])->assertOk()
            ->assertSee($ctx['student']->admission_no, false)
            ->assertSee('Att Pupil', false);

        $this->post('/attendencereports/staffdaywiseattendancereport', [
            'role' => 'select',
            'date' => $today,
        ])->assertOk()
            ->assertSee('ARPT-', false);

        $daily = $this->post('/attendencereports/daily_attendance_report', [
            'date' => $today,
        ])->assertOk();
        $this->assertStringContainsString($ctx['class']->class, $daily->getContent());
        $this->assertMatchesRegularExpression('/\b1\b/', $daily->getContent());

        $this->post('/attendencereports/attendancereport', [])
            ->assertSessionHasErrors(['attendance_type', 'class_id']);

        $this->post('/attendencereports/attendancereport', [
            'search_type' => 'this_week',
            'attendance_type' => StudentDayAttendanceService::TYPE_PRESENT,
            'class_id' => $ctx['class']->id,
            'section_id' => $ctx['section']->id,
        ])->assertOk()
            ->assertSee($ctx['student']->admission_no, false)
            ->assertSee('Att Pupil', false);

        $this->assertNotSame('', $roleName);
    }

    public function test_attendance_report_monthly_calendars(): void
    {
        $ctx = $this->seedContext();
        $today = now();
        $monthName = $today->format('F');
        $year = (int) $today->format('Y');
        $roleName = (string) DB::table('roles')->where('is_superadmin', 1)->value('name');

        $this->get('/attendencereports/classattendencereport')
            ->assertOk()
            ->assertSee('Student Attendance Report', false);

        $this->post('/attendencereports/classattendencereport', [])
            ->assertSessionHasErrors(['class_id', 'section_id', 'month']);

        $studentMonthly = $this->post('/attendencereports/classattendencereport', [
            'class_id' => $ctx['class']->id,
            'section_id' => $ctx['section']->id,
            'month' => $monthName,
            'year' => $year,
        ])->assertOk();
        $studentMonthly->assertSee($ctx['student']->admission_no, false)
            ->assertSee('Att Pupil', false);
        $this->assertMatchesRegularExpression('/\bP\b|\b100\b/', $studentMonthly->getContent());

        $this->post('/attendencereports/staffattendancereport', [])
            ->assertSessionHasErrors(['month', 'year']);

        $this->post('/attendencereports/staffattendancereport', [
            'role' => 'select',
            'month' => $monthName,
            'year' => $year,
        ])->assertOk()
            ->assertSee('ARPT-', false)
            ->assertSee('AttReport', false);
    }

    public function test_attendance_report_period_and_biometric(): void
    {
        $ctx = $this->seedContext();
        $month = now()->format('m');

        DB::table('sch_settings')->limit(1)->update([
            'attendence_type' => 1,
            'biometric' => 1,
        ]);
        // Keep session name usable for sessionMonthDetails via sessions table.
        DB::table('sessions')->where('id', $ctx['sessionId'])->update(['session' => '2025-26']);
        app(SchoolContext::class)->clearCache();

        $studentSessionId = (int) DB::table('student_session')->where('student_id', $ctx['student']->id)->value('id');
        DB::table('student_attendences')->where('student_session_id', $studentSessionId)->update([
            'biometric_attendence' => 1,
            'biometric_device_data' => json_encode([
                'user_id' => 'BIO-1',
                'serial_number' => 'SN-99',
                'ip' => '127.0.0.1',
            ]),
        ]);

        $this->get('/attendencereports/reportbymonth')
            ->assertOk()
            ->assertSee('Period Attendance', false);

        $this->post('/attendencereports/reportbymonth', [])
            ->assertSessionHasErrors(['class_id', 'section_id', 'month']);

        $this->post('/attendencereports/reportbymonth', [
            'class_id' => $ctx['class']->id,
            'section_id' => $ctx['section']->id,
            'month' => $month,
        ])->assertOk()
            ->assertSee($ctx['student']->admission_no, false);

        $this->post('/attendencereports/reportbymonthstudent', [])
            ->assertSessionHasErrors(['class_id', 'section_id', 'student_id', 'month']);

        $this->post('/attendencereports/reportbymonthstudent', [
            'class_id' => $ctx['class']->id,
            'section_id' => $ctx['section']->id,
            'student_id' => $ctx['student']->id,
            'month' => $month,
        ])->assertOk();

        $this->get('/attendencereports/biometric_attlog')
            ->assertOk()
            ->assertSee('SN-99', false)
            ->assertSee('127.0.0.1', false);
    }
}
