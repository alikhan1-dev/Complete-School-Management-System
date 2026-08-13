<?php

namespace App\Modules\Exams\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Exams\Services\ExamDocumentService;
use App\Modules\Exams\Services\ExamPrintTemplateService;
use App\Modules\Roles\Services\PermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CI admin/Marksheet — design marksheet templates.
 */
class MarksheetTemplateController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected ExamPrintTemplateService $templates,
        protected ExamDocumentService $documents
    ) {
    }

    public function index(): View
    {
        abort_unless($this->permissions->hasPrivilege('design_marksheet', 'can_view'), 403);

        return view('shared::layouts.admin', [
            'title' => 'Design Marksheet',
            'contentView' => 'exams::admin.marksheet.index',
            'templates' => $this->templates->listMarksheets(),
            'editing' => null,
            'flagFields' => $this->templates->marksheetFlagFields(),
            'canAdd' => $this->permissions->hasPrivilege('design_marksheet', 'can_add'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('design_marksheet', 'can_add'), 403);

        $this->validateTemplate($request);
        $this->templates->createMarksheet($this->templates->marksheetPayload($request));

        return redirect()->route('exams.marksheet_templates.index')->with('success', 'Marksheet template saved successfully.');
    }

    public function edit(int $id): View
    {
        abort_unless($this->permissions->hasPrivilege('design_marksheet', 'can_edit'), 403);

        return view('shared::layouts.admin', [
            'title' => 'Edit Marksheet',
            'contentView' => 'exams::admin.marksheet.index',
            'templates' => $this->templates->listMarksheets(),
            'editing' => $this->templates->findMarksheet($id),
            'flagFields' => $this->templates->marksheetFlagFields(),
            'canAdd' => false,
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('design_marksheet', 'can_edit'), 403);

        $row = $this->templates->findMarksheet($id);
        $this->validateTemplate($request);
        $this->templates->updateMarksheet($row, $this->templates->marksheetPayload($request, $row));

        return redirect()->route('exams.marksheet_templates.index')->with('success', 'Marksheet template updated successfully.');
    }

    public function destroy(int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('design_marksheet', 'can_delete'), 403);

        $this->templates->deleteMarksheet($this->templates->findMarksheet($id));

        return redirect()->route('exams.marksheet_templates.index')->with('success', 'Marksheet template deleted successfully.');
    }

    protected function validateTemplate(Request $request): void
    {
        $fileRules = ['nullable', 'image', 'max:4096'];
        $request->validate([
            'template' => ['required', 'string', 'max:200'],
            'heading' => ['nullable', 'string'],
            'title' => ['nullable', 'string'],
            'exam_name' => ['nullable', 'string', 'max:200'],
            'school_name' => ['nullable', 'string', 'max:200'],
            'exam_center' => ['nullable', 'string', 'max:200'],
            'date' => ['nullable', 'string', 'max:20'],
            'content' => ['nullable', 'string'],
            'content_footer' => ['nullable', 'string'],
            'header_image' => $fileRules,
            'left_logo' => $fileRules,
            'right_logo' => $fileRules,
            'left_sign' => $fileRules,
            'middle_sign' => $fileRules,
            'right_sign' => $fileRules,
            'background_img' => $fileRules,
        ]);
    }
}
