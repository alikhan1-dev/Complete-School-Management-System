<?php

namespace App\Modules\FrontOffice\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\FrontOffice\Services\VisitorDocumentService;
use App\Modules\FrontOffice\Services\VisitorService;
use App\Modules\Roles\Services\PermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * CI admin/Visitors — visitor book persist (SaaS quota deferred).
 */
class VisitorsController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected VisitorService $visitors,
        protected VisitorDocumentService $documents,
    ) {
    }

    public function index(): View
    {
        abort_unless($this->permissions->hasPrivilege('visitor_book', 'can_view'), 403);

        return view('shared::layouts.admin', [
            'title' => 'Visitor List',
            'contentView' => 'frontoffice::admin.visitor_index',
            'pageTitle' => 'Visitor List',
            'visitor_list' => $this->visitors->listAll(),
            'Purpose' => $this->visitors->purposes(),
            'meeting_with' => VisitorService::MEETING_WITH,
            'stafflist' => $this->visitors->staffList(),
            'classlist' => $this->visitors->classes(),
            'today' => $this->visitors->formatDate(date('Y-m-d')),
            'canAdd' => $this->permissions->hasPrivilege('visitor_book', 'can_add'),
            'canEdit' => $this->permissions->hasPrivilege('visitor_book', 'can_edit'),
            'canDelete' => $this->permissions->hasPrivilege('visitor_book', 'can_delete'),
            'visitors' => $this->visitors,
        ]);
    }

    public function add(Request $request): JsonResponse
    {
        abort_unless($this->permissions->hasPrivilege('visitor_book', 'can_add'), 403);
        $errors = $this->validateVisitor($request, false);
        if ($errors !== []) {
            return response()->json(['status' => 'fail', 'error' => $errors, 'message' => '']);
        }

        $file = $request->file('file');
        $file = $file instanceof UploadedFile ? $file : null;
        $this->visitors->create($request->all(), $file);

        return response()->json([
            'status' => 'success',
            'error' => '',
            'message' => 'Record saved successfully.',
        ]);
    }

    public function editvisitor(Request $request): JsonResponse
    {
        abort_unless($this->permissions->hasPrivilege('visitor_book', 'can_view'), 403);
        $id = (int) $request->input('visitorid');
        $row = $this->visitors->find($id);
        abort_if($row === null, 404);
        $page = view('frontoffice::admin.visitor_edit', [
            'Purpose' => $this->visitors->purposes(),
            'visitor_data' => $row,
            'meeting_with' => VisitorService::MEETING_WITH,
            'stafflist' => $this->visitors->staffList(),
            'classlist' => $this->visitors->classes(),
            'visitors' => $this->visitors,
        ])->render();

        return response()->json(['page' => $page]);
    }

    public function edit(Request $request): JsonResponse
    {
        abort_unless($this->permissions->hasPrivilege('visitor_book', 'can_edit'), 403);
        $errors = $this->validateVisitor($request, true);
        if ($errors !== []) {
            return response()->json(['status' => 'fail', 'error' => $errors, 'message' => '']);
        }

        $file = $request->file('file');
        $file = $file instanceof UploadedFile ? $file : null;
        $this->visitors->update((int) $request->input('visitor_id'), $request->all(), $file);

        return response()->json([
            'status' => 'success',
            'error' => '',
            'message' => 'Record saved successfully.',
        ]);
    }

    public function delete(Request $request): JsonResponse
    {
        abort_unless($this->permissions->hasPrivilege('visitor_book', 'can_delete'), 403);
        $this->visitors->delete((int) $request->input('id'));

        return response()->json(['message' => 'Record deleted successfully.']);
    }

    public function details(int $id): View
    {
        abort_unless($this->permissions->hasPrivilege('visitor_book', 'can_view'), 403);
        $row = $this->visitors->find($id);
        abort_if($row === null, 404);

        return view('frontoffice::admin.visitor_details', [
            'data' => $row,
            'visitors' => $this->visitors,
        ]);
    }

    public function download(int $id): BinaryFileResponse
    {
        $row = $this->visitors->find($id);
        abort_if($row === null || ($row['image'] ?? '') === '', 404);

        return $this->documents->download((string) $row['image']);
    }

    public function getstudent(Request $request): JsonResponse
    {
        return response()->json([
            'studentlist' => $this->visitors->studentsByClassSection(
                (int) $request->input('class_id'),
                (int) $request->input('section_id'),
            ),
        ]);
    }

    public function staffvisitor(): View
    {
        return view('shared::layouts.admin', [
            'title' => 'Visitor List',
            'contentView' => 'frontoffice::admin.staff_visitor',
            'pageTitle' => 'Visitor List',
            'visitor_list' => $this->visitors->listByStaff($this->visitors->currentStaffId()),
            'visitors' => $this->visitors,
        ]);
    }

    /**
     * @return array<string, string>
     */
    protected function validateVisitor(Request $request, bool $edit): array
    {
        $errors = [];
        $meetingKey = $edit ? 'edit_meeting_with' : 'meeting_with';
        $meeting = (string) $request->input($meetingKey, '');
        if (trim((string) $request->input('purpose', '')) === '') {
            $errors['purpose'] = 'The Purpose field is required.';
        }
        if (trim($meeting) === '') {
            $errors[$meetingKey] = 'The Meeting With field is required.';
        }
        if (trim((string) $request->input('name', '')) === '') {
            $errors['name'] = 'The Visitor Name field is required.';
        }
        if (trim((string) $request->input('date', '')) === '') {
            $errors['date'] = 'The Date field is required.';
        }

        $file = $request->file('file');
        if ($file instanceof UploadedFile) {
            $meta = $this->documents->uploadRulesFromFiletypes();
            $ext = strtolower((string) $file->getClientOriginalExtension());
            if (! in_array($ext, $meta['extensions'], true)) {
                $errors['file'] = 'Extension not allowed.';
            } elseif ($file->getSize() > ($meta['max_kb'] * 1024)) {
                $errors['file'] = 'File size should be less than '.number_format($meta['max_kb'] / 1024, 2).' MB';
            }
        }

        if ($meeting === 'staff') {
            $staffKey = $edit ? 'edit_staff_id' : 'staff_id';
            if ((int) $request->input($staffKey) <= 0) {
                $errors[$staffKey] = 'The Staff field is required.';
            }
        } elseif ($meeting === 'student') {
            $classKey = $edit ? 'edit_class_id' : 'class_id';
            $sectionKey = $edit ? 'edit_class_section_id' : 'class_section_id';
            $studentKey = $edit ? 'edit_student_session_id' : 'student_session_id';
            if ((int) $request->input($classKey) <= 0) {
                $errors[$classKey] = 'The Class field is required.';
            }
            if ((int) $request->input($sectionKey) <= 0) {
                $errors[$sectionKey] = 'The Section field is required.';
            }
            if ((int) $request->input($studentKey) <= 0) {
                $errors[$studentKey] = 'The Student field is required.';
            }
        }

        return $errors;
    }
}
