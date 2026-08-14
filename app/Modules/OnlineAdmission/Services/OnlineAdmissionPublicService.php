<?php

namespace App\Modules\OnlineAdmission\Services;

use App\Modules\Academics\Models\SchoolHouse;
use App\Modules\FrontCms\Services\FrontCmsPublicService;
use App\Modules\OnlineAdmission\Models\OnlineAdmission;
use App\Modules\Settings\Models\SchSetting;
use App\Modules\Students\Models\Category;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * CI Welcome admission / review / status / submit / edit (payments, mail, captcha, custom fields, files deferred).
 */
class OnlineAdmissionPublicService
{
    public function __construct(
        protected OnlineAdmissionApplicationService $applications,
        protected OnlineAdmissionSettingService $settings,
        protected FrontCmsPublicService $cms,
    ) {
    }

    public function isAdmissionEnabled(): bool
    {
        return (int) SchSetting::query()->value('online_admission') === 1;
    }

    public function canOpenAdmissionForm(): bool
    {
        return $this->isAdmissionEnabled();
    }

    /**
     * Front_Controller: CMS off and admission off → userlogin.
     */
    public function publicSiteClosed(): bool
    {
        return ! $this->isAdmissionEnabled() && ! $this->cms->isPublicEnabled();
    }

    /**
     * @return array<string, mixed>
     */
    public function formLookups(): array
    {
        $school = $this->settings->school();

        return [
            'classlist' => $this->applications->classes(),
            'categorylist' => Category::query()->orderBy('id')->get()->map(fn ($row) => $row->toArray())->all(),
            'houses' => SchoolHouse::query()->orderBy('id')->get()->map(fn ($row) => $row->toArray())->all(),
            'schSetting' => $school,
            'instruction' => (string) ($school->online_admission_instruction ?? ''),
            'conditions' => (string) ($school->online_admission_conditions ?? ''),
            'applicationForm' => (string) ($school->online_admission_application_form ?? ''),
            'guardianRequired' => $this->settings->fieldEnabled('if_guardian_is'),
            'cmsLayout' => $this->cms->isPublicEnabled() ? $this->cms->layoutData('online_admission') : [
                'setting' => (object) ['footer_text' => '', 'cookie_consent' => ''],
                'schoolName' => (string) ($school->name ?? ''),
                'mainMenus' => [],
                'activeMenu' => 'online_admission',
                'cookieConsent' => '',
                'bannerNotices' => [],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function submit(array $input): string
    {
        $reference = $this->uniqueReference();
        $payload = $this->createPayload($input, $reference);
        OnlineAdmission::query()->create($payload);

        return $reference;
    }

    /**
     * CI Welcome::editonlineadmission persist (files/custom fields deferred).
     *
     * @param  array<string, mixed>  $input
     */
    public function updateByReference(string $reference, array $input): bool
    {
        $row = OnlineAdmission::query()->where('reference_no', $reference)->first();
        if ($row === null) {
            return false;
        }

        $payload = $this->createPayload($input, $reference);
        unset(
            $payload['reference_no'],
            $payload['is_enroll'],
            $payload['form_status'],
            $payload['paid_status'],
            $payload['father_pic'],
            $payload['mother_pic'],
            $payload['guardian_pic'],
            $payload['image'],
            $payload['document'],
            $payload['hostel_room_id'],
            $payload['route_id'],
            $payload['vehroute_id'],
        );

        OnlineAdmission::query()->where('id', $row->id)->update($payload);

        return true;
    }

    public function findByReference(string $reference): ?array
    {
        $id = (int) OnlineAdmission::query()->where('reference_no', $reference)->value('id');
        if ($id < 1) {
            return null;
        }

        return $this->applications->find($id);
    }

    public function canViewReview(string $reference): bool
    {
        if (Auth::guard('staff')->check()) {
            return true;
        }

        return (string) session('validlogin') === $reference;
    }

    /**
     * CI checkadmissionstatus JSON.
     *
     * @return array<string, mixed>
     */
    public function checkStatus(string $refno, string $dob): array
    {
        $dobYmd = $this->applications->parseDate($dob);
        $row = OnlineAdmission::query()
            ->where('reference_no', $refno)
            ->where('dob', $dobYmd)
            ->first();

        if ($row === null) {
            return [
                'status' => '2',
                'error' => 'Invalid reference number or date of birth',
                'msg' => '',
                'refno' => $refno,
            ];
        }

        if ((int) $row->is_enroll === 1) {
            return [
                'status' => '2',
                'error' => 'You enrollment has been done please contact to school administrator',
                'msg' => '',
                'refno' => $refno,
            ];
        }

        session()->forget('validlogin');
        session()->put('validlogin', $refno);

        return [
            'status' => '1',
            'error' => '',
            'msg' => '',
            'id' => (int) $row->id,
            'refno' => $refno,
        ];
    }

    public function submitForm(int $admissionId): ?array
    {
        $row = OnlineAdmission::query()->find($admissionId);
        if ($row === null) {
            return null;
        }

        OnlineAdmission::query()->where('id', $admissionId)->update([
            'form_status' => 1,
            'submit_date' => date('Y-m-d'),
        ]);

        return [
            'status' => '1',
            'error' => '',
            'id' => $admissionId,
            'msg' => '',
            'reference_no' => (string) $row->reference_no,
        ];
    }

    protected function uniqueReference(): string
    {
        do {
            $reference = (string) random_int(100000, 999999);
        } while (OnlineAdmission::query()->where('reference_no', $reference)->exists());

        return $reference;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    protected function createPayload(array $input, string $reference): array
    {
        $emptyToNull = static function ($value) {
            $value = is_string($value) ? trim($value) : $value;

            return $value === '' || $value === null ? null : $value;
        };

        return [
            'reference_no' => $reference,
            'firstname' => (string) ($input['firstname'] ?? ''),
            'middlename' => (string) ($input['middlename'] ?? ''),
            'lastname' => (string) ($input['lastname'] ?? ''),
            'class_section_id' => (int) ($input['section_id'] ?? 0) ?: null,
            'dob' => $this->applications->parseDate((string) ($input['dob'] ?? '')),
            'gender' => (string) ($input['gender'] ?? ''),
            'mobileno' => (string) ($input['mobileno'] ?? ''),
            'email' => (string) ($input['email'] ?? ''),
            'category_id' => $emptyToNull($input['category_id'] ?? null),
            'religion' => (string) ($input['religion'] ?? ''),
            'cast' => (string) ($input['cast'] ?? ''),
            'school_house_id' => $emptyToNull($input['house'] ?? null),
            'blood_group' => (string) ($input['blood_group'] ?? ''),
            'height' => (string) ($input['height'] ?? ''),
            'weight' => (string) ($input['weight'] ?? ''),
            'measurement_date' => $this->applications->parseDate((string) ($input['measure_date'] ?? '')) ?: null,
            'father_name' => (string) ($input['father_name'] ?? ''),
            'father_phone' => (string) ($input['father_phone'] ?? ''),
            'father_occupation' => (string) ($input['father_occupation'] ?? ''),
            'mother_name' => (string) ($input['mother_name'] ?? ''),
            'mother_phone' => (string) ($input['mother_phone'] ?? ''),
            'mother_occupation' => (string) ($input['mother_occupation'] ?? ''),
            'previous_school' => (string) ($input['previous_school'] ?? ''),
            'note' => (string) ($input['note'] ?? ''),
            'current_address' => (string) ($input['current_address'] ?? ''),
            'permanent_address' => (string) ($input['permanent_address'] ?? ''),
            'bank_account_no' => (string) ($input['bank_account_no'] ?? ''),
            'bank_name' => (string) ($input['bank_name'] ?? ''),
            'ifsc_code' => (string) ($input['ifsc_code'] ?? ''),
            'adhar_no' => (string) ($input['adhar_no'] ?? ''),
            'samagra_id' => (string) ($input['samagra_id'] ?? ''),
            'rte' => (string) ($input['rte'] ?? 'No'),
            'guardian_is' => (string) ($input['guardian_is'] ?? ''),
            'guardian_name' => (string) ($input['guardian_name'] ?? ''),
            'guardian_relation' => (string) ($input['guardian_relation'] ?? ''),
            'guardian_phone' => (string) ($input['guardian_phone'] ?? ''),
            'guardian_occupation' => (string) ($input['guardian_occupation'] ?? ''),
            'guardian_email' => (string) ($input['guardian_email'] ?? ''),
            'guardian_address' => (string) ($input['guardian_address'] ?? ''),
            'father_pic' => '',
            'mother_pic' => '',
            'guardian_pic' => '',
            'image' => '',
            'document' => '',
            'hostel_room_id' => null,
            'route_id' => 0,
            'vehroute_id' => 0,
            'is_enroll' => 0,
            'form_status' => 0,
            'paid_status' => 0,
        ];
    }
}
