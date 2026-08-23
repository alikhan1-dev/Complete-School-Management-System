<?php

namespace App\Modules\Students\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academics\Models\SchoolClass;
use App\Modules\Academics\Services\CustomFieldValueService;
use App\Modules\Parents\Services\ParentAccountService;
use App\Modules\Roles\Services\PermissionService;
use App\Modules\Settings\Models\SchSetting;
use App\Modules\Students\Models\Category;
use App\Modules\Students\Models\Student;
use App\Modules\Students\Requests\StoreStudentDocumentRequest;
use App\Modules\Students\Requests\StoreStudentRequest;
use App\Modules\Students\Requests\UpdateStudentRequest;
use App\Modules\Students\Services\StudentAdmissionService;
use App\Modules\Students\Services\StudentDocumentService;
use App\Modules\Students\Services\StudentLifecycleService;
use App\Modules\Students\Services\StudentSearchService;
use App\Modules\Students\Services\StudentTimelineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StudentController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected StudentSearchService $search,
        protected StudentAdmissionService $admission,
        protected StudentLifecycleService $lifecycle,
        protected CustomFieldValueService $customFields,
        protected StudentDocumentService $documents,
        protected StudentTimelineService $timeline,
        protected ParentAccountService $parents,
    ) {
    }

    public function search(): View
    {
        abort_unless($this->permissions->hasPrivilege('student', 'can_view'), 403);

        $settings = SchSetting::query()->first();
        $classes = SchoolClass::query()->orderBy('id')->get();

        return view('shared::layouts.admin', [
            'title' => 'Student Search',
            'contentView' => 'students::admin.search',
            'classes' => $classes,
            'schSetting' => $settings,
        ]);
    }

    public function searchValidation(Request $request): JsonResponse
    {
        abort_unless($this->permissions->hasPrivilege('student', 'can_view'), 403);

        $searchType = (string) $request->input('search_type', $request->input('search'));

        if ($searchType === 'search_filter') {
            $request->validate([
                'class_id' => ['required', 'integer', 'exists:classes,id'],
            ]);
        }

        return response()->json([
            'status' => 1,
            'error' => '',
            'params' => [
                'class_id' => $request->input('class_id'),
                'section_id' => $request->input('section_id'),
                'search_type' => $searchType,
                'search_text' => $request->input('search_text'),
            ],
        ]);
    }

    public function datatable(Request $request): JsonResponse
    {
        abort_unless($this->permissions->hasPrivilege('student', 'can_view'), 403);

        $draw = (int) $request->input('draw', 1);
        $searchType = (string) $request->input('srch_type');
        $classId = $request->filled('class_id') ? (int) $request->input('class_id') : null;
        $sectionId = $request->filled('section_id') ? (int) $request->input('section_id') : null;
        $searchText = trim((string) $request->input('search_text', ''));

        $rows = $searchType === 'search_full'
            ? $this->search->searchFullText($searchText)
            : $this->search->searchByClassSection($classId, $sectionId);

        $settings = SchSetting::query()->first();
        $canEdit = $this->permissions->hasPrivilege('student', 'can_edit');

        $data = $rows->map(function ($student) use ($settings, $canEdit) {
            $name = trim(implode(' ', array_filter([
                $student->firstname,
                ((int) ($settings->middlename ?? 1) === 1) ? $student->middlename : null,
                ((int) ($settings->lastname ?? 1) === 1) ? $student->lastname : null,
            ])));

            $actions = '<a href="'.url('student/view/'.$student->id).'" class="btn btn-primary btn-xs">View</a> ';
            if ($canEdit) {
                $actions .= '<a href="'.url('student/edit/'.$student->id).'" class="btn btn-primary btn-xs">Edit</a>';
            }

            $row = [
                e($student->admission_no),
                '<a href="'.url('student/view/'.$student->id).'">'.e($name).'</a>',
            ];

            if ((int) ($settings->roll_no ?? 1) === 1) {
                $row[] = e((string) $student->roll_no);
            }

            $row[] = e((string) $student->class_section_list);

            if ((int) ($settings->father_name ?? 1) === 1) {
                $row[] = e((string) $student->father_name);
            }

            $row[] = e((string) $student->dob);
            $row[] = e((string) $student->gender);

            if ((int) ($settings->category ?? 1) === 1) {
                $row[] = e((string) $student->category);
            }
            if ((int) ($settings->mobile_no ?? 1) === 1) {
                $row[] = e((string) $student->mobileno);
            }

            $row[] = $actions;

            return $row;
        })->values()->all();

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => count($data),
            'recordsFiltered' => count($data),
            'data' => $data,
            'student_detail_view' => '',
        ]);
    }

    public function create(): View
    {
        abort_unless($this->permissions->hasPrivilege('student', 'can_add'), 403);

        return view('shared::layouts.admin', [
            'title' => 'Add Student',
            'contentView' => 'students::admin.create',
            'classes' => SchoolClass::query()->orderBy('id')->get(),
            'categories' => Category::query()->orderBy('id')->get(),
            'schSetting' => SchSetting::query()->first(),
            'customFields' => $this->customFields->fieldsFor('students'),
            'customFieldValues' => [],
            'belongTo' => 'students',
        ]);
    }

    public function store(StoreStudentRequest $request): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('student', 'can_add'), 403);

        $data = $this->mapStudentPayload($request->validated());
        if (Auth::guard('staff')->id()) {
            $data['created_by'] = Auth::guard('staff')->id();
        }

        $customRows = $this->customFields->normalizePosted(
            'students',
            (array) data_get($request->all(), 'custom_fields.students', [])
        );

        $siblingId = (int) ($request->input('sibling_id') ?? 0);

        $result = $this->admission->admit(
            $data,
            (int) $request->validated('class_id'),
            (int) $request->validated('section_id'),
            (float) ($request->input('fees_discount') ?? 0),
            $customRows,
            $siblingId
        );

        $message = 'Student created successfully. Login: '.$result['student_username'].' / '.$result['student_password'];
        if (! empty($result['sibling_reused'])) {
            $message .= ' (parent account reused from sibling)';
        }

        return redirect()
            ->route('students.view', $result['student_id'])
            ->with('success', $message);
    }

    public function show(int $id): View
    {
        abort_unless($this->permissions->hasPrivilege('student', 'can_view'), 403);

        $student = $this->search->findForView($id);
        abort_if(! $student, 404);

        $schSetting = SchSetting::query()->first();
        $guardianCredential = $this->parents->guardianCredential((int) ($student->parent_id ?? 0));

        return view('shared::layouts.admin', [
            'title' => 'Student Details',
            'contentView' => 'students::admin.show',
            'student' => $student,
            'schSetting' => $schSetting,
            'customFields' => $this->customFields->fieldsFor('students'),
            'customFieldValues' => $this->customFields->valuesMap('students', $id),
            'studentDocs' => $this->documents->listFor($id),
            'uploadDocumentsEnabled' => (int) ($schSetting->upload_documents ?? 0) === 1,
            'timelineList' => $this->timeline->listFor($id),
            'editingTimeline' => request()->filled('edit_timeline')
                ? $this->timeline->find((int) request()->query('edit_timeline'))
                : null,
            'siblings' => $this->search->siblingsOf((int) ($student->parent_id ?? 0), $id),
            'guardianCredential' => $guardianCredential,
            'canViewLoginDetails' => $this->permissions->hasPrivilege('student_login_credential_report', 'can_view'),
            'canSendCredentials' => $this->permissions->hasPrivilege('disable_student', 'can_view'),
        ]);
    }

    public function createDoc(StoreStudentDocumentRequest $request): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('student', 'can_add'), 403);

        $studentId = (int) $request->validated('student_id');
        Student::query()->findOrFail($studentId);

        $schSetting = SchSetting::query()->first();
        abort_unless((int) ($schSetting->upload_documents ?? 0) === 1, 403);

        /** @var list<\Illuminate\Http\UploadedFile> $files */
        $files = $request->file('first_doc', []);
        $this->documents->store($studentId, (string) $request->validated('first_title'), $files);

        return redirect()
            ->route('students.view', $studentId)
            ->with('success', 'Document uploaded successfully.');
    }

    public function downloadDoc(int $studentId, int $docId): BinaryFileResponse
    {
        abort_unless($this->permissions->hasPrivilege('student', 'can_view'), 403);

        $doc = $this->documents->findForStudent($docId, $studentId);
        abort_if(! $doc || ! $doc->doc, 404);

        $path = $this->documents->absolutePath($studentId, (string) $doc->doc);
        abort_unless(File::isFile($path), 404);

        return response()->download($path, basename((string) $doc->doc));
    }

    public function destroyDoc(int $id, int $studentId): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('student', 'can_delete'), 403);

        $doc = $this->documents->findForStudent($id, $studentId);
        abort_if(! $doc, 404);

        $this->documents->delete($doc);

        return redirect()
            ->route('students.view', $studentId)
            ->with('success', 'Document deleted successfully.');
    }

    public function edit(int $id): View
    {
        abort_unless($this->permissions->hasPrivilege('student', 'can_edit'), 403);

        $student = $this->search->findForView($id);
        abort_if(! $student, 404);

        return view('shared::layouts.admin', [
            'title' => 'Edit Student',
            'contentView' => 'students::admin.edit',
            'student' => $student,
            'classes' => SchoolClass::query()->orderBy('id')->get(),
            'categories' => Category::query()->orderBy('id')->get(),
            'schSetting' => SchSetting::query()->first(),
            'customFields' => $this->customFields->fieldsFor('students'),
            'customFieldValues' => $this->customFields->valuesMap('students', $id),
            'belongTo' => 'students',
        ]);
    }

    public function update(UpdateStudentRequest $request, int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('student', 'can_edit'), 403);

        $student = Student::query()->findOrFail($id);
        $payload = $this->mapStudentPayload($request->validated());
        unset($payload['created_by']);

        foreach ($payload as $key => $value) {
            $student->{$key} = $value;
        }
        $student->save();

        $this->lifecycle->syncCurrentSessionClassSection(
            $id,
            (int) $request->validated('class_id'),
            (int) $request->validated('section_id')
        );

        $customRows = $this->customFields->normalizePosted(
            'students',
            (array) data_get($request->all(), 'custom_fields.students', [])
        );
        $this->customFields->upsertFor($id, $customRows);

        return redirect()->route('students.view', $id)->with('success', 'Student updated successfully.');
    }

    public function destroy(int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('student', 'can_delete'), 403);

        $this->lifecycle->delete($id);

        return redirect()->route('students.search')->with('success', 'Student deleted successfully.');
    }

    public function disable(int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('disable_student', 'can_view')
            || $this->permissions->hasPrivilege('student', 'can_edit'), 403);

        $this->lifecycle->disable($id);

        return redirect()->route('students.view', $id)->with('success', 'Student disabled.');
    }

    public function enable(int $id): JsonResponse
    {
        abort_unless($this->permissions->hasPrivilege('disable_student', 'can_view')
            || $this->permissions->hasPrivilege('student', 'can_edit'), 403);

        $this->lifecycle->enable($id);

        return response()->json(0); // CI enablestudent echoes "0"
    }

    public function disableReason(Request $request): JsonResponse
    {
        $request->validate([
            'reason' => ['required'],
            'disable_date' => ['required', 'date'],
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'note' => ['nullable', 'string'],
        ]);

        $this->lifecycle->disable(
            (int) $request->input('student_id'),
            (string) $request->input('reason'),
            $request->input('note'),
            (string) $request->input('disable_date')
        );

        return response()->json(['status' => 'success', 'error' => '', 'message' => 'Student disabled.']);
    }

    /**
     * CI Student::getlogindetail — student + parent portal credentials.
     */
    public function getlogindetail(Request $request): JsonResponse
    {
        abort_unless($this->permissions->hasPrivilege('student_login_credential_report', 'can_view'), 403);

        $studentId = (int) $request->input('student_id');
        abort_if($studentId <= 0, 404);

        $rows = [];
        foreach ($this->parents->loginDetailsForStudent($studentId) as $row) {
            $role = strtolower((string) ($row->role ?? ''));
            $rows[] = [
                'id' => (int) $row->id,
                'user_id' => (int) $row->user_id,
                'username' => (string) $row->username,
                'password' => (string) $row->password,
                'role' => $role !== '' ? (string) __('system.'.$role) : '',
            ];
        }

        return response()->json($rows);
    }

    /**
     * CI Student::sendpassword — student_login_credential (live send deferred).
     */
    public function sendpassword(Request $request): JsonResponse
    {
        abort_unless($this->permissions->hasPrivilege('disable_student', 'can_view')
            || $this->permissions->hasPrivilege('student', 'can_view'), 403);

        $studentId = (int) $request->input('student_id');
        abort_if($studentId <= 0, 404);
        Student::query()->findOrFail($studentId);

        $credential = $this->parents->studentCredential($studentId);
        $this->parents->queueLoginCredentialNotification([
            'student_id' => $studentId,
            'credential_for' => 'student',
            'username' => (string) ($request->input('username') ?: ($credential->username ?? '')),
            'password' => (string) ($request->input('password') ?: ($credential->password ?? '')),
            'contact_no' => (string) $request->input('contact_no', ''),
            'email' => (string) $request->input('email', ''),
            'admission_no' => (string) $request->input('admission_no', ''),
            'student_session_id' => $request->input('student_session_id'),
        ]);

        return response()->json(['status' => 1, 'message' => (string) __('system.message_successfully_sent')]);
    }

    /**
     * CI Student::send_parent_password — parent credential notification (live send deferred).
     */
    public function sendParentPassword(Request $request): JsonResponse
    {
        abort_unless($this->permissions->hasPrivilege('disable_student', 'can_view')
            || $this->permissions->hasPrivilege('student', 'can_view'), 403);

        $studentId = (int) $request->input('student_id');
        abort_if($studentId <= 0, 404);
        $student = Student::query()->findOrFail($studentId);
        $guardian = $this->parents->guardianCredential((int) ($student->parent_id ?? 0));

        $this->parents->queueLoginCredentialNotification([
            'student_id' => $studentId,
            'credential_for' => 'parent',
            'username' => (string) ($request->input('username') ?: ($guardian->username ?? '')),
            'password' => (string) ($request->input('password') ?: ($guardian->password ?? '')),
            'contact_no' => (string) $request->input('contact_no', ''),
            'email' => (string) $request->input('email', ''),
            'admission_no' => (string) $request->input('admission_no', ''),
            'student_session_id' => $request->input('student_session_id'),
        ]);

        return response()->json(['status' => 1, 'message' => (string) __('system.message_successfully_sent')]);
    }

    public function getByClassAndSection(Request $request): JsonResponse
    {
        $classId = $request->filled('class_id') ? (int) $request->query('class_id') : null;
        $sectionId = $request->filled('section_id') ? (int) $request->query('section_id') : null;
        $settings = SchSetting::query()->first();

        $rows = $this->search->searchByClassSection($classId, $sectionId)->map(function ($student) use ($settings) {
            $arr = (array) $student;
            $arr['full_name'] = trim(implode(' ', array_filter([
                $student->firstname,
                ((int) ($settings->middlename ?? 1) === 1) ? $student->middlename : null,
                ((int) ($settings->lastname ?? 1) === 1) ? $student->lastname : null,
            ])));

            return $arr;
        })->values();

        return response()->json($rows);
    }

    public function getStudentRecordByID(Request $request): JsonResponse
    {
        abort_unless($this->permissions->hasPrivilege('student', 'can_view')
            || $this->permissions->hasPrivilege('student', 'can_add'), 403);

        $studentId = (int) $request->query('student_id');
        abort_if($studentId <= 0, 404);

        $row = $this->search->findRecordById($studentId);
        abort_if(! $row, 404);

        return response()->json($row);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    protected function mapStudentPayload(array $validated): array
    {
        $map = [
            'firstname' => $validated['firstname'],
            'middlename' => $validated['middlename'] ?? null,
            'lastname' => $validated['lastname'] ?? null,
            'gender' => $validated['gender'],
            'dob' => $validated['dob'],
            'admission_no' => $validated['admission_no'] ?? null,
            'admission_date' => $validated['admission_date'] ?? null,
            'roll_no' => $validated['roll_no'] ?? null,
            'email' => $validated['email'] ?? null,
            'mobileno' => $validated['mobileno'] ?? null,
            'religion' => $validated['religion'] ?? null,
            'cast' => $validated['cast'] ?? null,
            'category_id' => $validated['category_id'] ?? null,
            'blood_group' => $validated['blood_group'] ?? '',
            'school_house_id' => $validated['house'] ?? null,
            'father_name' => $validated['father_name'] ?? null,
            'father_phone' => $validated['father_phone'] ?? null,
            'father_occupation' => $validated['father_occupation'] ?? null,
            'mother_name' => $validated['mother_name'] ?? null,
            'mother_phone' => $validated['mother_phone'] ?? null,
            'mother_occupation' => $validated['mother_occupation'] ?? null,
            'guardian_name' => $validated['guardian_name'] ?? null,
            'guardian_is' => $validated['guardian_is'] ?? null,
            'guardian_relation' => $validated['guardian_relation'] ?? null,
            'guardian_phone' => $validated['guardian_phone'] ?? null,
            'guardian_email' => $validated['guardian_email'] ?? null,
            'guardian_occupation' => $validated['guardian_occupation'] ?? null,
            'guardian_address' => $validated['guardian_address'] ?? null,
            'current_address' => $validated['current_address'] ?? null,
            'permanent_address' => $validated['permanent_address'] ?? null,
            'state' => $validated['state'] ?? null,
            'city' => $validated['city'] ?? null,
            'pincode' => $validated['pincode'] ?? null,
            'previous_school' => $validated['previous_school'] ?? null,
            'adhar_no' => $validated['adhar_no'] ?? null,
            'samagra_id' => $validated['samagra_id'] ?? null,
            'bank_account_no' => $validated['bank_account_no'] ?? null,
            'bank_name' => $validated['bank_name'] ?? null,
            'ifsc_code' => $validated['ifsc_code'] ?? null,
            'rte' => $validated['rte'] ?? null,
            'note' => $validated['note'] ?? null,
        ];

        return array_filter($map, fn ($v) => $v !== null);
    }
}
