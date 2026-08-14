<?php

namespace App\Modules\OnlineAdmission\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\FrontCms\Services\FrontCmsPublicService;
use App\Modules\OnlineAdmission\Services\OnlineAdmissionApplicationService;
use App\Modules\OnlineAdmission\Services\OnlineAdmissionCustomFieldService;
use App\Modules\OnlineAdmission\Services\OnlineAdmissionFormFileService;
use App\Modules\OnlineAdmission\Services\OnlineAdmissionPublicService;
use App\Modules\OnlineAdmission\Services\OnlineAdmissionSettingService;
use App\Modules\Shared\Services\CaptchaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * CI Welcome admission / review / status / submit / edit (live mail deferred).
 */
class OnlineAdmissionPublicController extends Controller
{
    public function __construct(
        protected OnlineAdmissionPublicService $public,
        protected OnlineAdmissionApplicationService $applications,
        protected OnlineAdmissionSettingService $settings,
        protected OnlineAdmissionFormFileService $files,
        protected FrontCmsPublicService $cms,
        protected OnlineAdmissionCustomFieldService $customFields,
        protected CaptchaService $captcha,
    ) {
    }

    public function admission(Request $request): View|RedirectResponse
    {
        if ($closed = $this->closedGate()) {
            return $closed;
        }
        if (! $this->public->canOpenAdmissionForm()) {
            return $this->cms->isPublicEnabled()
                ? redirect('frontend')
                : redirect('site/userlogin');
        }

        if ($request->isMethod('post')) {
            $errors = array_merge(
                $this->validateAdmission($request),
                $this->validateUploads($request),
                $this->customFields->validate((array) $request->input('custom_fields.students', [])),
                $this->captcha->validatePosted('admission', (string) $request->input('captcha', '')),
            );
            if ($errors === []) {
                $reference = $this->public->submit($request->all(), $request->allFiles());
                session()->put('validlogin', $reference);

                return redirect('welcome/online_admission_review/'.$reference)
                    ->with('success', 'Thanks for registration please note your reference number '.$reference.' for further communication');
            }

            return $this->formView($errors, $request->all());
        }

        return $this->formView();
    }

    public function editonlineadmission(Request $request, string $referenceNo): View|RedirectResponse
    {
        if ($closed = $this->closedGate()) {
            return $closed;
        }

        $row = $this->public->findByReference($referenceNo);
        if ($row === null) {
            abort(404);
        }

        if ($request->isMethod('post') && $request->filled('admission_id')) {
            $errors = array_merge(
                $this->validateAdmission($request, false),
                $this->validateUploads($request),
                $this->customFields->validate((array) $request->input('custom_fields.students', [])),
            );
            if ($errors === []) {
                $this->public->updateByReference($referenceNo, $request->all(), $request->allFiles());
                session()->put('validlogin', $referenceNo);

                return redirect('welcome/online_admission_review/'.$referenceNo)
                    ->with('success', 'Record updated successfully.');
            }

            return $this->formView($errors, $request->all(), $referenceNo, (int) $row['id']);
        }

        $old = $row;
        $old['class_id'] = $row['class_id'] ?? '';
        $old['section_id'] = $row['class_section_id'] ?? '';
        $old['house'] = $row['school_house_id'] ?? '';

        return $this->formView([], $old, $referenceNo, (int) $row['id']);
    }

    public function review(string $referenceNo): View|RedirectResponse
    {
        if ($closed = $this->closedGate()) {
            return $closed;
        }

        $row = $this->public->findByReference($referenceNo);
        if ($row === null) {
            abort(404);
        }
        if (! $this->public->canViewReview($referenceNo)) {
            abort(403, 'No direct script access allowed');
        }

        $lookups = $this->public->formLookups();

        return view('onlineadmission::public.review', array_merge($lookups['cmsLayout'], [
            'page' => [
                'title' => 'Online Admission Review',
                'meta_title' => 'online admission review',
            ],
            'student' => $row,
            'schSetting' => $lookups['schSetting'],
            'conditions' => $lookups['conditions'],
            'isStaffReview' => Auth::guard('staff')->check(),
        ]));
    }

    public function checkadmissionstatus(Request $request): JsonResponse
    {
        if ($this->public->publicSiteClosed()) {
            return response()->json(['status' => '0', 'error' => 'Closed', 'msg' => '']);
        }

        $refno = trim((string) $request->input('refno', ''));
        $dob = trim((string) $request->input('student_dob', ''));
        if ($refno === '' || $dob === '') {
            return response()->json([
                'status' => '0',
                'error' => [
                    'refno' => $refno === '' ? 'The Reference No field is required.' : '',
                    'dob' => $dob === '' ? 'The Date Of Birth field is required.' : '',
                ],
                'msg' => 'Something went wrong',
            ]);
        }

        return response()->json($this->public->checkStatus($refno, $dob));
    }

    public function submitadmission(Request $request): JsonResponse|RedirectResponse
    {
        if ($this->public->publicSiteClosed()) {
            return response()->json(['status' => '0', 'error' => 'Closed', 'msg' => '']);
        }

        if (! $request->filled('checkterm')) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'status' => '0',
                    'error' => 'The Terms Conditions field is required.',
                    'msg' => '',
                ]);
            }

            return redirect()->back()->with('error', 'The Terms Conditions field is required.');
        }

        $id = (int) $request->input('admission_id');
        $result = $this->public->submitForm($id);
        if ($result === null) {
            return response()->json(['status' => '0', 'error' => 'Invalid admission.', 'msg' => '']);
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json($result);
        }

        return redirect('welcome/online_admission_review/'.$result['reference_no'])
            ->with('success', 'Record saved successfully.');
    }

    public function getSections(Request $request): JsonResponse
    {
        if ($this->public->publicSiteClosed()) {
            return response()->json([]);
        }

        return response()->json($this->applications->sectionsForClass((int) $request->input('class_id')));
    }

    public function download(int $id): BinaryFileResponse
    {
        if ($this->public->publicSiteClosed()) {
            abort(403);
        }
        $school = $this->settings->school();
        abort_unless((int) $school->id === $id, 404);
        $filename = (string) ($school->online_admission_application_form ?? '');
        abort_unless($filename !== '', 404);

        return $this->files->download($filename);
    }

    public function refreshCaptcha(): \Illuminate\Http\Response
    {
        if ($this->public->publicSiteClosed()) {
            abort(403);
        }

        return response($this->captcha->generate()['image'], 200)
            ->header('Content-Type', 'text/html; charset=UTF-8');
    }

    protected function closedGate(): ?RedirectResponse
    {
        if ($this->public->publicSiteClosed()) {
            return redirect('site/userlogin');
        }

        return null;
    }

    /**
     * @param  array<string, string>  $errors
     * @param  array<string, mixed>  $old
     */
    protected function formView(array $errors = [], array $old = [], ?string $referenceNo = null, int $admissionId = 0): View
    {
        $lookups = $this->public->formLookups();
        $classId = (int) ($old['class_id'] ?? 0);

        return view('onlineadmission::public.admission', array_merge($lookups['cmsLayout'], [
            'page' => [
                'title' => 'Online Admission Form',
                'meta_title' => 'online admission form',
            ],
            'classlist' => $lookups['classlist'],
            'sectionlist' => $classId > 0 ? $this->applications->sectionsForClass($classId) : [],
            'categorylist' => $lookups['categorylist'],
            'houses' => $lookups['houses'],
            'schSetting' => $lookups['schSetting'],
            'instruction' => $lookups['instruction'],
            'applicationForm' => $lookups['applicationForm'],
            'guardianRequired' => $lookups['guardianRequired'],
            'showStudentPhoto' => $lookups['showStudentPhoto'],
            'showFatherPic' => $lookups['showFatherPic'],
            'showMotherPic' => $lookups['showMotherPic'],
            'showGuardianPic' => $lookups['showGuardianPic'],
            'showDocuments' => $lookups['showDocuments'],
            'formErrors' => $errors,
            'old' => $old,
            'formAction' => $referenceNo !== null
                ? url('welcome/editonlineadmission/'.$referenceNo)
                : url('online_admission'),
            'admissionId' => $admissionId,
            'customFields' => $this->customFields->visibleFields(),
            'customFieldValues' => $this->customFieldValuesForForm($old, $admissionId),
            'showCaptcha' => $admissionId < 1 && $this->captcha->isEnabled('admission'),
            'captchaImage' => ($admissionId < 1 && $this->captcha->isEnabled('admission'))
                ? $this->captcha->generate()['image']
                : '',
        ]));
    }

    /**
     * @return array<string, string>
     */
    protected function validateAdmission(Request $request, bool $requireEmailAlways = true): array
    {
        $errors = [];
        if (trim((string) $request->input('firstname')) === '') {
            $errors['firstname'] = 'The First Name field is required.';
        }
        if (trim((string) $request->input('dob')) === '') {
            $errors['dob'] = 'The Date Of Birth field is required.';
        }
        if ((int) $request->input('class_id') < 1) {
            $errors['class_id'] = 'The Class field is required.';
        }
        if ((int) $request->input('section_id') < 1) {
            $errors['section_id'] = 'The Section field is required.';
        }
        if (trim((string) $request->input('gender')) === '') {
            $errors['gender'] = 'The Gender field is required.';
        }
        $email = trim((string) $request->input('email'));
        if ($requireEmailAlways || $this->settings->fieldEnabled('student_email')) {
            if ($email === '') {
                $errors['email'] = 'The Email field is required.';
            }
        }
        if ($this->settings->fieldEnabled('if_guardian_is')) {
            if (trim((string) $request->input('guardian_is')) === '') {
                $errors['guardian_is'] = 'The Guardian field is required.';
            }
            if (trim((string) $request->input('guardian_name')) === '') {
                $errors['guardian_name'] = 'The Guardian Name field is required.';
            }
            if (trim((string) $request->input('guardian_relation')) === '') {
                $errors['guardian_relation'] = 'The Guardian Relation field is required.';
            }
        }

        return $errors;
    }

    /**
     * @param  array<string, mixed>  $old
     * @return array<int, string>
     */
    protected function customFieldValuesForForm(array $old, int $admissionId): array
    {
        $posted = $old['custom_fields']['students'] ?? null;
        if (is_array($posted)) {
            return $this->customFields->postedValues($posted);
        }
        if ($admissionId > 0) {
            return $this->customFields->valuesMap($admissionId);
        }

        return [];
    }

    /**
     * @return array<string, string>
     */
    protected function validateUploads(Request $request): array
    {
        $errors = [];
        $imageRules = $this->files->imageRulesFromFiletypes();
        $documentRules = $this->files->uploadRulesFromFiletypes();
        $images = [
            'file' => 'Student Photo',
            'father_pic' => 'Father Photo',
            'mother_pic' => 'Mother Photo',
            'guardian_pic' => 'Guardian Photo',
        ];
        foreach ($images as $field => $label) {
            $upload = $request->file($field);
            if ($upload === null || ! $upload->isValid()) {
                continue;
            }
            $message = $this->files->validateApplicantFile($upload, $imageRules);
            if ($message !== null) {
                $errors[$field] = $message;
            }
        }
        $document = $request->file('document');
        if ($document !== null && $document->isValid()) {
            $message = $this->files->validateApplicantFile($document, $documentRules);
            if ($message !== null) {
                $errors['document'] = $message;
            }
        }

        return $errors;
    }
}
