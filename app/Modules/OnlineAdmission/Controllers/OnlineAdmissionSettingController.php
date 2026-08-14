<?php

namespace App\Modules\OnlineAdmission\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\OnlineAdmission\Services\OnlineAdmissionFormFileService;
use App\Modules\OnlineAdmission\Services\OnlineAdmissionSettingService;
use App\Modules\Roles\Services\PermissionService;
use App\Modules\Shared\Services\SchoolContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * CI admin/Onlineadmission — settings persist (SaaS quota deferred).
 */
class OnlineAdmissionSettingController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected OnlineAdmissionSettingService $settings,
        protected OnlineAdmissionFormFileService $files,
        protected SchoolContext $school,
    ) {
    }

    public function admissionsetting(Request $request): View|RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('online_admission', 'can_view'), 403);

        if ($request->isMethod('post') && $request->filled('submitbtn')) {
            abort_unless($this->permissions->hasPrivilege('online_admission', 'can_edit'), 403);
            $errors = $this->validateSettings($request);
            if ($errors === []) {
                $file = $request->file('file');
                $this->settings->saveSettings(
                    $request->all(),
                    $file instanceof UploadedFile ? $file : null,
                );

                return redirect('admin/onlineadmission/admissionsetting')->with('success', 'Record updated successfully.');
            }

            return $this->formView($errors, $request->all());
        }

        return $this->formView();
    }

    public function changeformfieldsetting(Request $request): JsonResponse
    {
        abort_unless($this->permissions->hasPrivilege('online_admission', 'can_edit'), 403);

        $name = trim((string) $request->input('name', ''));
        $status = $request->input('status');
        if ($name === '' || $status === null || $status === '') {
            return response()->json([
                'status' => '0',
                'error' => [
                    'name' => $name === '' ? 'The Name field is required.' : '',
                    'status' => ($status === null || $status === '') ? 'The Status field is required.' : '',
                ],
                'msg' => 'Something went wrong',
            ]);
        }

        $this->settings->saveFormField($name, (int) $status);

        return response()->json([
            'status' => '1',
            'error' => '',
            'msg' => 'Record saved successfully.',
        ]);
    }

    public function download(int $id): BinaryFileResponse
    {
        abort_unless($this->permissions->hasPrivilege('online_admission', 'can_view'), 403);
        $school = $this->settings->school();
        abort_unless((int) $school->id === $id, 404);
        $filename = (string) ($school->online_admission_application_form ?? '');
        abort_unless($filename !== '', 404);

        return $this->files->download($filename);
    }

    /**
     * @param  array<string, string>  $errors
     * @param  array<string, mixed>  $old
     */
    protected function formView(array $errors = [], array $old = []): View
    {
        $result = $this->settings->school();

        return view('shared::layouts.admin', [
            'title' => 'Online Admission',
            'contentView' => 'onlineadmission::admin.settings_index',
            'pageTitle' => 'Online Admission',
            'result' => $result,
            'fieldRows' => $this->settings->formFieldRows(),
            'canEdit' => $this->permissions->hasPrivilege('online_admission', 'can_edit'),
            'formErrors' => $errors,
            'old' => $old,
            'currencySymbol' => $this->school->currencySymbol(),
        ]);
    }

    /**
     * @return array<string, string>
     */
    protected function validateSettings(Request $request): array
    {
        $errors = [];
        if ((string) $request->input('online_admission_payment') === 'yes') {
            $amount = trim((string) $request->input('online_admission_amount', ''));
            if ($amount === '') {
                $errors['online_admission_amount'] = 'The Amount field is required.';
            } elseif (! is_numeric($amount) || (float) $amount <= 0) {
                $errors['online_admission_amount'] = 'Invalid payment amount.';
            }
        }

        $file = $request->file('file');
        if ($file instanceof UploadedFile) {
            $rules = $this->files->uploadRulesFromFiletypes();
            $ext = strtolower((string) $file->getClientOriginalExtension());
            $mime = strtolower((string) $file->getMimeType());
            if ($rules['extensions'] !== [] && ! in_array($ext, $rules['extensions'], true)) {
                $errors['file'] = 'File type not allowed.';
            } elseif ($rules['mimes'] !== [] && ! in_array($mime, $rules['mimes'], true)) {
                $errors['file'] = 'File type not allowed.';
            } elseif ($file->getSize() > $rules['max_bytes']) {
                $errors['file'] = 'File size should be less than '.number_format($rules['max_bytes'] / 1048576, 2).' MB';
            }
        }

        return $errors;
    }
}
