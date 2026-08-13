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
 * CI admin/Admitcard — design admit card templates.
 */
class AdmitcardTemplateController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected ExamPrintTemplateService $templates,
        protected ExamDocumentService $documents
    ) {
    }

    public function index(): View
    {
        abort_unless($this->permissions->hasPrivilege('design_admit_card', 'can_view'), 403);

        return view('shared::layouts.admin', [
            'title' => 'Design Admit Card',
            'contentView' => 'exams::admin.admitcard.index',
            'templates' => $this->templates->listAdmitcards(),
            'editing' => null,
            'flagFields' => $this->templates->admitcardFlagFields(),
            'canAdd' => $this->permissions->hasPrivilege('design_admit_card', 'can_add'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('design_admit_card', 'can_add'), 403);

        $this->validateTemplate($request);
        $this->templates->createAdmitcard($this->templates->admitcardPayload($request));

        return redirect()->route('exams.admitcard_templates.index')->with('success', 'Admit card template saved successfully.');
    }

    public function edit(int $id): View
    {
        abort_unless($this->permissions->hasPrivilege('design_admit_card', 'can_edit'), 403);

        return view('shared::layouts.admin', [
            'title' => 'Edit Admit Card',
            'contentView' => 'exams::admin.admitcard.index',
            'templates' => $this->templates->listAdmitcards(),
            'editing' => $this->templates->findAdmitcard($id),
            'flagFields' => $this->templates->admitcardFlagFields(),
            'canAdd' => false,
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('design_admit_card', 'can_edit'), 403);

        $row = $this->templates->findAdmitcard($id);
        $this->validateTemplate($request);
        $this->templates->updateAdmitcard($row, $this->templates->admitcardPayload($request, $row));

        return redirect()->route('exams.admitcard_templates.index')->with('success', 'Admit card template updated successfully.');
    }

    public function destroy(int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('design_admit_card', 'can_delete'), 403);

        $this->templates->deleteAdmitcard($this->templates->findAdmitcard($id));

        return redirect()->route('exams.admitcard_templates.index')->with('success', 'Admit card template deleted successfully.');
    }

    public function activate(int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('design_admit_card', 'can_edit'), 403);

        $this->templates->findAdmitcard($id);
        $this->templates->activateAdmitcard($id);

        return redirect()->route('exams.admitcard_templates.index')->with('success', 'Admit card template activated.');
    }

    protected function validateTemplate(Request $request): void
    {
        $fileRules = ['nullable', 'image', 'max:4096'];
        $request->validate([
            'template' => ['required', 'string', 'max:250'],
            'heading' => ['nullable', 'string'],
            'title' => ['nullable', 'string'],
            'exam_name' => ['nullable', 'string', 'max:200'],
            'school_name' => ['nullable', 'string', 'max:200'],
            'exam_center' => ['nullable', 'string', 'max:200'],
            'content_footer' => ['nullable', 'string'],
            'left_logo' => $fileRules,
            'right_logo' => $fileRules,
            'sign' => $fileRules,
            'background_img' => $fileRules,
        ]);
    }
}
