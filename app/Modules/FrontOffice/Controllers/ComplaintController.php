<?php

namespace App\Modules\FrontOffice\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\FrontOffice\Services\ComplaintDocumentService;
use App\Modules\FrontOffice\Services\ComplaintService;
use App\Modules\Roles\Services\PermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * CI admin/Complaint — form POST persist (SaaS quota deferred).
 */
class ComplaintController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected ComplaintService $complaints,
        protected ComplaintDocumentService $documents,
    ) {
    }

    public function index(Request $request): View|RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('complaint', 'can_view'), 403);

        if ($request->isMethod('post')) {
            abort_unless($this->permissions->hasPrivilege('complaint', 'can_add'), 403);
            $errors = $this->validateComplaint($request);
            if ($errors === []) {
                $file = $request->file('file');
                $file = $file instanceof UploadedFile ? $file : null;
                $this->complaints->create($request->all(), $file);

                return redirect('admin/complaint')->with('success', 'Record saved successfully.');
            }

            return $this->indexView($errors, $request->all());
        }

        return $this->indexView();
    }

    public function edit(Request $request, int $id): View|RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('complaint', 'can_edit'), 403);
        $row = $this->complaints->find($id);
        abort_if($row === null, 404);

        if ($request->isMethod('post')) {
            $errors = $this->validateComplaint($request);
            if ($errors === []) {
                $file = $request->file('file');
                $file = $file instanceof UploadedFile ? $file : null;
                $this->complaints->update($id, $request->all(), $file);

                return redirect('admin/complaint')->with('success', 'Record updated successfully.');
            }

            return $this->editView($row, $errors, $request->all());
        }

        return $this->editView($row);
    }

    public function details(int $id): View
    {
        abort_unless($this->permissions->hasPrivilege('complaint', 'can_view'), 403);
        $row = $this->complaints->find($id);
        abort_if($row === null, 404);

        return view('frontoffice::admin.complaint_details', [
            'complaint_data' => $row,
            'complaints' => $this->complaints,
        ]);
    }

    public function delete(int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('complaint', 'can_delete'), 403);
        $this->complaints->delete($id);

        return redirect('admin/complaint')->with('success', 'Record deleted successfully.');
    }

    public function download(int $id): BinaryFileResponse
    {
        $row = $this->complaints->find($id);
        abort_if($row === null || ($row['image'] ?? '') === '', 404);

        return $this->documents->download((string) $row['image']);
    }

    /**
     * @param  array<string, string>  $errors
     * @param  array<string, mixed>  $old
     */
    protected function indexView(array $errors = [], array $old = []): View
    {
        return view('shared::layouts.admin', [
            'title' => 'Complaint',
            'contentView' => 'frontoffice::admin.complaint_index',
            'pageTitle' => 'Add Complain',
            'complaint_list' => $this->complaints->listAll(),
            'complaint_type' => $this->complaints->types(),
            'complaintsource' => $this->complaints->sources(),
            'canAdd' => $this->permissions->hasPrivilege('complaint', 'can_add'),
            'canEdit' => $this->permissions->hasPrivilege('complaint', 'can_edit'),
            'canDelete' => $this->permissions->hasPrivilege('complaint', 'can_delete'),
            'formErrors' => $errors,
            'old' => $old,
            'today' => $this->complaints->formatDate(date('Y-m-d')),
            'complaints' => $this->complaints,
        ]);
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<string, string>  $errors
     * @param  array<string, mixed>  $old
     */
    protected function editView(array $row, array $errors = [], array $old = []): View
    {
        return view('shared::layouts.admin', [
            'title' => 'Edit Complain',
            'contentView' => 'frontoffice::admin.complaint_edit',
            'pageTitle' => 'Edit Complain',
            'complaint_data' => $row,
            'complaint_list' => $this->complaints->listAll(),
            'complaint_type' => $this->complaints->types(),
            'complaintsource' => $this->complaints->sources(),
            'canAdd' => $this->permissions->hasPrivilege('complaint', 'can_add'),
            'canEdit' => true,
            'canDelete' => $this->permissions->hasPrivilege('complaint', 'can_delete'),
            'formErrors' => $errors,
            'old' => $old,
            'complaints' => $this->complaints,
        ]);
    }

    /**
     * @return array<string, string>
     */
    protected function validateComplaint(Request $request): array
    {
        $errors = [];
        if (trim((string) $request->input('name', '')) === '') {
            $errors['name'] = 'The Complain By field is required.';
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

        return $errors;
    }
}
