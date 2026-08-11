<?php

namespace App\Modules\Students\Services;

use App\Modules\Academics\Services\CurrentSessionResolver;
use App\Modules\Academics\Services\CustomFieldValueService;
use App\Modules\Auth\Models\PortalUser;
use App\Modules\Settings\Models\SchSetting;
use App\Modules\Students\Models\Student;
use App\Modules\Students\Models\StudentSession;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Mirrors CI Student_model::addNewMethod core path (student + session + portal users + custom fields).
 * Deferred for later parity passes: fees master, transport fees, multi-class rows,
 * sibling parent reuse, media uploads, barcode, SMS.
 */
class StudentAdmissionService
{
    public function __construct(
        protected CurrentSessionResolver $currentSession,
        protected CustomFieldValueService $customFields
    ) {
    }

    /**
     * @param  array<string, mixed>  $studentData
     * @param  list<array{custom_field_id:int,field_value:string}>  $customFieldRows
     * @return array{student_id:int,student_session_id:int,student_username:string,student_password:string,parent_password:string,sibling_reused:bool}
     */
    public function admit(
        array $studentData,
        int $classId,
        int $sectionId,
        ?float $feesDiscount = 0,
        array $customFieldRows = [],
        int $siblingId = 0
    ): array {
        $settings = SchSetting::query()->firstOrFail();
        $sessionId = $this->currentSession->id();

        if ($sessionId <= 0) {
            throw new \RuntimeException('Current academic session is not configured in sch_settings.');
        }

        return DB::transaction(function () use ($studentData, $classId, $sectionId, $feesDiscount, $settings, $sessionId, $customFieldRows, $siblingId) {
            if (empty($studentData['admission_no']) && (int) $settings->adm_auto_insert === 1) {
                $studentData['admission_no'] = $this->nextAdmissionNo($settings);
                if ((int) $settings->adm_update_status === 0) {
                    $settings->adm_update_status = 1;
                    $settings->save();
                }
            }

            if (empty($studentData['image'])) {
                $studentData['image'] = strtolower((string) ($studentData['gender'] ?? '')) === 'female'
                    ? 'uploads/student_images/default_female.jpg'
                    : 'uploads/student_images/default_male.jpg';
            }

            $studentData['is_active'] = $studentData['is_active'] ?? 'yes';
            $studentData['parent_id'] = $studentData['parent_id'] ?? 0;
            $studentData['hostel_room_id'] = $studentData['hostel_room_id'] ?? 0;
            // Legacy NOT NULL columns without useful defaults.
            $studentData['blood_group'] = $studentData['blood_group'] ?? '';
            $studentData['guardian_is'] = $studentData['guardian_is'] ?? '';
            $studentData['guardian_occupation'] = $studentData['guardian_occupation'] ?? '';
            $studentData['father_pic'] = $studentData['father_pic'] ?? '';
            $studentData['mother_pic'] = $studentData['mother_pic'] ?? '';
            $studentData['guardian_pic'] = $studentData['guardian_pic'] ?? '';
            $studentData['height'] = $studentData['height'] ?? '';
            $studentData['weight'] = $studentData['weight'] ?? '';
            $studentData['dis_reason'] = $studentData['dis_reason'] ?? 0;
            $studentData['dis_note'] = $studentData['dis_note'] ?? '';

            $student = Student::query()->create($studentData);

            $studentSession = StudentSession::query()->create([
                'student_id' => $student->id,
                'class_id' => $classId,
                'section_id' => $sectionId,
                'session_id' => $sessionId,
                'fees_discount' => $feesDiscount ?? 0,
                'route_pickup_point_id' => null,
                'vehroute_id' => null,
                'is_alumni' => 0,
                'is_active' => 'yes',
                'is_leave' => 0,
                'default_login' => 0,
                'transport_fees' => 0,
            ]);

            // Role library prefixes (std / parent). CI Student_model properties are empty
            // (known quirk); using Role.php values so portal usernames stay distinct.
            $studentPassword = $this->randomPassword();
            $parentPassword = '';

            PortalUser::query()->create([
                'username' => 'std'.$student->id,
                'password' => $studentPassword, // plaintext parity with CI insert
                'user_id' => $student->id,
                'role' => 'student',
                'lang_id' => (int) ($settings->lang_id ?? 0),
                'currency_id' => 0,
                'childs' => '',
                'verification_code' => '',
                'login_token' => '',
                'is_active' => 'yes',
            ]);

            $siblingReused = false;
            if ($siblingId > 0) {
                $sibling = Student::query()->find($siblingId);
                if (! $sibling || (int) $sibling->parent_id <= 0) {
                    throw new \RuntimeException('Selected sibling does not have a valid parent account.');
                }
                $student->parent_id = (int) $sibling->parent_id;
                $student->save();
                $siblingReused = true;
            } else {
                $parentPassword = $this->randomPassword();
                $parent = PortalUser::query()->create([
                    'username' => 'parent'.$student->id,
                    'password' => $parentPassword,
                    'user_id' => 0,
                    'role' => 'parent',
                    'lang_id' => (int) ($settings->lang_id ?? 0),
                    'currency_id' => 0,
                    'childs' => (string) $student->id,
                    'verification_code' => '',
                    'login_token' => '',
                    'is_active' => 'yes',
                ]);

                $student->parent_id = $parent->id;
                $student->save();
            }

            $this->customFields->insertFor((int) $student->id, $customFieldRows);

            return [
                'student_id' => (int) $student->id,
                'student_session_id' => (int) $studentSession->id,
                'student_username' => 'std'.$student->id,
                'student_password' => $studentPassword,
                'parent_password' => $parentPassword,
                'sibling_reused' => $siblingReused,
            ];
        });
    }

    protected function nextAdmissionNo(SchSetting $settings): string
    {
        $prefix = (string) $settings->adm_prefix;
        $digits = max(1, (int) $settings->adm_no_digit);

        $last = Student::query()->orderByDesc('id')->value('admission_no');
        if ($last && str_starts_with((string) $last, $prefix)) {
            $numeric = (int) Str::after((string) $last, $prefix);

            return $prefix.sprintf('%0'.$digits.'d', $numeric + 1);
        }

        return $prefix.(string) $settings->adm_start_from;
    }

    /**
     * Mirrors Role::get_random_password(6, 6, false, true, false).
     */
    protected function randomPassword(): string
    {
        $selection = 'aeuoyibcdfghjklmnpqrstvwxz1234567890';
        $password = '';
        for ($i = 0; $i < 6; $i++) {
            $password .= $selection[random_int(0, strlen($selection) - 1)];
        }

        return $password;
    }
}
