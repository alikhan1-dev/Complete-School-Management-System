<?php

namespace App\Modules\FrontOffice\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\FrontOffice\Services\DispatchReceiveDocumentService;
use App\Modules\FrontOffice\Services\DispatchReceiveService;
use App\Modules\Roles\Services\PermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * CI admin/Dispatch — form POST persist (SaaS quota deferred).
 */
class DispatchController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected DispatchReceiveService $records,
        protected DispatchReceiveDocumentService $documents,
    ) {
    }

    public function index(Request $request): View|RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('postal_dispatch', 'can_view'), 403);

        if ($request->isMethod('post')) {
            abort_unless($this->permissions->hasPrivilege('postal_dispatch', 'can_add'), 403);
            $errors = $this->validateDispatch($request);
            if ($errors === []) {
                $file = $request->file('file');
                $file = $file instanceof UploadedFile ? $file : null;
                $this->records->createDispatch($request->all(), $file);

                return redirect('admin/dispatch')->with('success', 'Record saved successfully.');
            }

            return $this->indexView($errors, $request->all());
        }

        return $this->indexView();
    }

    public function editdispatch(Request $request, int $id): View|RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('postal_dispatch', 'can_edit'), 403);
        $row = $this->records->find($id, DispatchReceiveService::TYPE_DISPATCH);
        abort_if($row === null, 404);

        if ($request->isMethod('post')) {
            $errors = $this->validateDispatch($request);
            if ($errors === []) {
                $file = $request->file('file');
                $file = $file instanceof UploadedFile ? $file : null;
                $this->records->updateDispatch($id, $request->all(), $file);

                return redirect('admin/dispatch')->with('success', 'Record updated successfully.');
            }

            return $this->editView($row, $errors, $request->all());
        }

        return $this->editView($row);
    }

    public function details(int $id, string $type): View
    {
        abort_unless(in_array($type, [DispatchReceiveService::TYPE_DISPATCH, DispatchReceiveService::TYPE_RECEIVE], true), 404);
        $row = $this->records->find($id, $type);
        abort_if($row === null, 404);

        return view('frontoffice::admin.dispatch_receive_details', [
            'data' => $row,
            'records' => $this->records,
        ]);
    }

    public function delete(int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('postal_dispatch', 'can_delete'), 403);
        $this->records->delete($id, DispatchReceiveService::TYPE_DISPATCH);

        return redirect('admin/dispatch')->with('success', 'Record deleted successfully.');
    }

    public function download(int $id): BinaryFileResponse
    {
        $row = $this->records->find($id, DispatchReceiveService::TYPE_DISPATCH);
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
            'title' => 'Postal Dispatch',
            'contentView' => 'frontoffice::admin.dispatch_index',
            'pageTitle' => 'Add Postal Dispatch',
            'DispatchList' => $this->records->listByType(DispatchReceiveService::TYPE_DISPATCH),
            'canAdd' => $this->permissions->hasPrivilege('postal_dispatch', 'can_add'),
            'canEdit' => $this->permissions->hasPrivilege('postal_dispatch', 'can_edit'),
            'canDelete' => $this->permissions->hasPrivilege('postal_dispatch', 'can_delete'),
            'formErrors' => $errors,
            'old' => $old,
            'today' => $this->records->formatDate(date('Y-m-d')),
            'records' => $this->records,
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
            'title' => 'Edit Postal Dispatch',
            'contentView' => 'frontoffice::admin.dispatch_edit',
            'pageTitle' => 'Edit Postal Dispatch',
            'Dispatch_data' => $row,
            'DispatchList' => $this->records->listByType(DispatchReceiveService::TYPE_DISPATCH),
            'canAdd' => $this->permissions->hasPrivilege('postal_dispatch', 'can_add'),
            'canEdit' => true,
            'canDelete' => $this->permissions->hasPrivilege('postal_dispatch', 'can_delete'),
            'formErrors' => $errors,
            'old' => $old,
            'records' => $this->records,
        ]);
    }

    /**
     * @return array<string, string>
     */
    protected function validateDispatch(Request $request): array
    {
        $errors = [];
        if (trim((string) $request->input('to_title', '')) === '') {
            $errors['to_title'] = 'The To Title field is required.';
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
