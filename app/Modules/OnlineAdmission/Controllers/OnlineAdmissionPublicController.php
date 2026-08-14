<?php

namespace App\Modules\OnlineAdmission\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\FrontCms\Services\FrontCmsPublicService;
use App\Modules\OnlineAdmission\Services\OnlineAdmissionApplicationService;
use App\Modules\OnlineAdmission\Services\OnlineAdmissionFormFileService;
use App\Modules\OnlineAdmission\Services\OnlineAdmissionPublicService;
use App\Modules\OnlineAdmission\Services\OnlineAdmissionSettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * CI Welcome admission / review / status / submit / edit (payments, mail, captcha, custom fields, files deferred).
 */
class OnlineAdmissionPublicController extends Controller
{
    public function __construct(
        protected OnlineAdmissionPublicService $public,
        protected OnlineAdmissionApplicationService $applications,
        protected OnlineAdmissionSettingService $settings,
        protected OnlineAdmissionFormFileService $files,
        protected FrontCmsPublicService $cms,
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
            $errors = $this->validateAdmission($request);
            if ($errors === []) {
                $reference = $this->public->submit($request->all());
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
            $errors = $this->validateAdmission($request, false);
            if ($errors === []) {
                $this->public->updateByReference($referenceNo, $request->all());
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
            'formErrors' => $errors,
            'old' => $old,
            'formAction' => $referenceNo !== null
                ? url('welcome/editonlineadmission/'.$referenceNo)
                : url('online_admission'),
            'admissionId' => $admissionId,
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
}
