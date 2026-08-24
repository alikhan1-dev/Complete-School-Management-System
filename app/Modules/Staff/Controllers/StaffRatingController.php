<?php

namespace App\Modules\Staff\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Roles\Services\PermissionService;
use App\Modules\Staff\Services\StaffRatingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * CI admin/Staff::rating, ratingapr, delete_rateing.
 */
class StaffRatingController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected StaffRatingService $ratings,
    ) {
    }

    public function index(): View
    {
        abort_unless($this->permissions->hasPrivilege('teachers_rating', 'can_view'), 403);

        return view('shared::layouts.admin', [
            'title' => (string) __('system.teachers_rating'),
            'contentView' => 'staff::admin.rating',
            'resultlist' => $this->ratings->adminList(),
            'canEdit' => $this->permissions->hasPrivilege('teachers_rating', 'can_edit'),
            'canDelete' => $this->permissions->hasPrivilege('teachers_rating', 'can_delete'),
        ]);
    }

    public function approve(int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('teachers_rating', 'can_edit'), 403);
        abort_if($this->ratings->find($id) === null, 404);

        $this->ratings->approve($id);

        return redirect()->route('staff.rating.index');
    }

    public function destroy(int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('teachers_rating', 'can_delete'), 403);
        abort_if($this->ratings->find($id) === null, 404);

        $this->ratings->delete($id);

        return redirect()->route('staff.rating.index');
    }
}
