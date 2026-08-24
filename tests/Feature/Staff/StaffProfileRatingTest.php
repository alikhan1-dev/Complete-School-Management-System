<?php

namespace Tests\Feature\Staff;

use App\Modules\Staff\Models\Staff;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StaffProfileRatingTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<int> */
    private array $cleanupRatingIds = [];

    /** @var list<int> */
    private array $cleanupUserIds = [];

    /** @var list<int> */
    private array $cleanupStudentIds = [];

    protected function tearDown(): void
    {
        if ($this->cleanupRatingIds !== []) {
            DB::table('staff_rating')->whereIn('id', $this->cleanupRatingIds)->delete();
        }
        $this->cleanupRatingIds = [];

        foreach ($this->cleanupUserIds as $userId) {
            DB::table('users')->where('id', $userId)->delete();
        }
        $this->cleanupUserIds = [];

        foreach ($this->cleanupStudentIds as $studentId) {
            DB::table('students')->where('id', $studentId)->delete();
        }
        $this->cleanupStudentIds = [];

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

        $token = uniqid('spr', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'SPR-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Rating',
            'surname' => 'Viewer',
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

    private function createTeacherStaff(): int
    {
        $teacherRoleId = (int) (DB::table('roles')->where('name', 'Teacher')->value('id')
            ?: DB::table('roles')->where('id', '!=', DB::table('roles')->where('is_superadmin', 1)->value('id'))->value('id'));
        $this->assertGreaterThan(0, $teacherRoleId);

        $token = uniqid('tpr', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'TPR-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Rated',
            'surname' => 'Teacher',
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
            'role_id' => $teacherRoleId,
            'is_active' => 1,
        ]);
        $this->createdStaffIds[] = $staffId;

        return $staffId;
    }

    private function createStudentReviewer(string $suffix): int
    {
        $studentId = (int) DB::table('students')->insertGetId([
            'parent_id' => 0,
            'admission_no' => 'ADM-'.$suffix,
            'roll_no' => 'R-'.$suffix,
            'admission_date' => date('Y-m-d'),
            'firstname' => 'Review',
            'middlename' => '',
            'lastname' => 'Student',
            'rte' => '',
            'image' => '',
            'mobileno' => '',
            'email' => 'review'.$suffix.'@example.test',
            'state' => '',
            'city' => '',
            'pincode' => '',
            'religion' => '',
            'cast' => '',
            'dob' => '2010-01-01',
            'gender' => 'Male',
            'current_address' => '',
            'permanent_address' => '',
            'category_id' => 0,
            'school_house_id' => 0,
            'blood_group' => '',
            'hostel_room_id' => 0,
            'adhar_no' => '',
            'samagra_id' => '',
            'bank_account_no' => '',
            'bank_name' => '',
            'ifsc_code' => '',
            'guardian_is' => 'father',
            'father_name' => '',
            'father_phone' => '',
            'father_occupation' => '',
            'mother_name' => '',
            'mother_phone' => '',
            'mother_occupation' => '',
            'guardian_name' => 'Guardian '.$suffix,
            'guardian_relation' => '',
            'guardian_phone' => '',
            'guardian_occupation' => '',
            'guardian_address' => '',
            'guardian_email' => '',
            'father_pic' => '',
            'mother_pic' => '',
            'guardian_pic' => '',
            'is_active' => 'yes',
            'previous_school' => '',
            'height' => '',
            'weight' => '',
            'measurement_date' => date('Y-m-d'),
            'dis_reason' => 0,
            'note' => '',
            'dis_note' => '',
            'app_key' => '',
            'parent_app_key' => '',
            'disable_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->cleanupStudentIds[] = $studentId;

        $userId = (int) DB::table('users')->insertGetId([
            'user_id' => $studentId,
            'username' => 'review'.$suffix,
            'password' => 'secret',
            'childs' => '',
            'role' => 'student',
            'lang_id' => 1,
            'currency_id' => 0,
            'verification_code' => '',
            'login_token' => '',
            'is_active' => 'yes',
        ]);
        $this->cleanupUserIds[] = $userId;

        return $userId;
    }

    public function test_staff_profile_shows_teacher_rating_summary_and_reviews(): void
    {
        $this->actingAsSuperAdmin();
        $teacherId = $this->createTeacherStaff();
        $suffix = uniqid();
        $reviewerUserId = $this->createStudentReviewer($suffix);

        foreach ([5, 4, 5] as $rate) {
            $this->cleanupRatingIds[] = (int) DB::table('staff_rating')->insertGetId([
                'staff_id' => $teacherId,
                'comment' => 'Great teaching '.$suffix,
                'rate' => $rate,
                'user_id' => $reviewerUserId,
                'role' => 'student',
                'status' => 1,
            ]);
        }

        $this->get('/admin/staff/profile/'.$teacherId)
            ->assertOk()
            ->assertSee('4.6 average based on 3', false)
            ->assertSee('Review Student', false)
            ->assertSee('Great teaching '.$suffix, false);
    }
}
