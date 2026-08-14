<?php

namespace App\Modules\OnlineAdmission\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\OnlineAdmission\Services\OnlineAdmissionApplicationService;
use App\Modules\Roles\Services\PermissionService;
use App\Modules\Settings\Models\SchSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * CI admin/Onlinestudent — list/edit/enroll/delete persist (fees/mail/SaaS deferred).
 */
class OnlineStudentController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected OnlineAdmissionApplicationService $applications,
    ) {
    }

    public function index(): View
    {
        abort_unless($this->permissions->hasPrivilege('online_admission', 'can_view'), 403);

        return view('shared::layouts.admin', [
            'title' => 'Student List',
            'contentView' => 'onlineadmission::admin.student_index',
            'pageTitle' => 'Student List',
            'listResult' => $this->applications->listAll(),
            'schSetting' => SchSetting::query()->orderBy('id')->first(),
            'canEdit' => $this->permissions->hasPrivilege('online_admission', 'can_edit'),
            'canDelete' => $this->permissions->hasPrivilege('online_admission', 'can_delete'),
        ]);
    }

    public function edit(Request $request, int $id): View|RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('online_admission', 'can_edit'), 403);
        $student = $this->applications->find($id);
        abort_if($student === null, 404);

        if ($request->isMethod('post')) {
            $errors = $this->validateStudent($request);
            if ($errors === []) {
                if ((string) $request->input('save') === 'enroll') {
                    $ok = $this->applications->enroll($id, $request->all());
                    if (! $ok) {
                        return redirect()->back()->with('error', 'Please check student admission no.');
                    }
                } else {
                    $this->applications->update($id, $request->all());
                }

                return redirect('admin/onlinestudent')->with('success', 'Record updated successfully.');
            }

            return $this->editView($student, $errors, $request->all());
        }

        return $this->editView($student);
    }

    public function delete(int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('online_admission', 'can_delete'), 403);
        $this->applications->delete($id);

        return redirect('admin/onlinestudent');
    }

    public function getByClass(Request $request): JsonResponse
    {
        abort_unless($this->permissions->hasPrivilege('online_admission', 'can_view'), 403);

        return response()->json($this->applications->sectionsForClass((int) $request->input('class_id')));
    }

    public function checkpaymentstatus(Request $request): Response
    {
        abort_unless($this->permissions->hasPrivilege('online_admission', 'can_edit'), 403);

        return response($this->applications->paymentStatusMessage((int) $request->input('id')), 200)
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }

    /**
     * @param  array<string, mixed>  $student
     * @param  array<string, string>  $errors
     * @param  array<string, mixed>  $old
     */
    protected function editView(array $student, array $errors = [], array $old = []): View
    {
        $classId = (int) ($old['class_id'] ?? $student['class_id'] ?? 0);
        $schSetting = SchSetting::query()->orderBy('id')->first();

        return view('shared::layouts.admin', [
            'title' => 'Edit Student',
            'contentView' => 'onlineadmission::admin.student_edit',
            'pageTitle' => 'Edit Student',
            'student' => $student,
            'classlist' => $this->applications->classes(),
            'sectionlist' => $classId > 0 ? $this->applications->sectionsForClass($classId) : [],
            'formErrors' => $errors,
            'old' => $old,
            'schSetting' => $schSetting,
        ]);
    }

    /**
     * @return array<string, string>
     */
    protected function validateStudent(Request $request): array
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

        if ((string) $request->input('save') === 'enroll') {
            $settings = SchSetting::query()->orderBy('id')->first();
            if ($settings && (int) $settings->adm_auto_insert !== 1 && trim((string) $request->input('admission_no')) === '') {
                $errors['admission_no'] = 'The Admission No field is required.';
            }
            $email = trim((string) $request->input('email'));
            if ($email !== '' && $this->applications->studentEmailExists($email)) {
                $errors['email'] = 'Record already exist.';
            }
        }

        return $errors;
    }
}
