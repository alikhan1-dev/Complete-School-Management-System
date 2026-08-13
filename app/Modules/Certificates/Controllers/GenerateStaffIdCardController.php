<?php

namespace App\Modules\Certificates\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Certificates\Services\GenerateStaffIdCardService;
use App\Modules\Roles\Services\PermissionService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CI admin/Generatestaffidcard — search by role & print selected staff ID cards.
 * Deferred: AJAX JSON print, mPDF.
 */
class GenerateStaffIdCardController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected GenerateStaffIdCardService $generate
    ) {
    }

    public function index(Request $request): View
    {
        abort_unless($this->permissions->hasPrivilege('generate_staff_id_card', 'can_view'), 403);

        $filters = [
            'role_id' => $request->input('role_id'),
            'id_card' => $request->input('id_card'),
        ];

        $staffList = null;
        $selectedIdCard = null;

        $shouldSearch = $request->isMethod('post')
            || $request->filled('id_card');

        if ($shouldSearch) {
            $data = $request->validate([
                'role_id' => ['nullable', 'integer'],
                'id_card' => ['required', 'integer'],
            ]);

            $filters['role_id'] = $data['role_id'] ?? null;
            $filters['id_card'] = $data['id_card'];

            $selectedIdCard = $this->generate->findTemplate((int) $data['id_card']);
            $roleId = ! empty($data['role_id']) ? (int) $data['role_id'] : null;
            $staffList = $this->generate->searchStaff($roleId);
        }

        return view('shared::layouts.admin', [
            'title' => 'Generate Staff ID Card',
            'contentView' => 'certificates::admin.staffidcard.generate',
            'roles' => $this->generate->listRoles(),
            'idcards' => $this->generate->listTemplates(),
            'filters' => $filters,
            'staffList' => $staffList,
            'selectedIdCard' => $selectedIdCard,
        ]);
    }

    public function print(Request $request): View
    {
        abort_unless($this->permissions->hasPrivilege('generate_staff_id_card', 'can_view'), 403);

        $data = $request->validate([
            'id_card' => ['required', 'integer'],
            'staff_ids' => ['required', 'array', 'min:1'],
            'staff_ids.*' => ['integer'],
        ]);

        $idcard = $this->generate->findTemplate((int) $data['id_card']);
        $payload = $this->generate->buildPrintPayload($idcard, $data['staff_ids']);

        abort_if($payload['rows'] === [], 422, 'No staff selected for ID card print.');

        return view('certificates::admin.staffidcard.print', $payload);
    }
}
