<?php

namespace App\Modules\Transport\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Roles\Services\PermissionService;
use App\Modules\Transport\Services\TransportRouteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CI admin/route — create/edit/delete route titles.
 * Student transport report: StudentTransportReportController.
 */
class RouteController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected TransportRouteService $routes,
    ) {
    }

    public function index(): View
    {
        abort_unless($this->permissions->hasPrivilege('routes', 'can_view'), 403);

        return view('shared::layouts.admin', [
            'title' => 'Routes',
            'contentView' => 'transport::admin.routes.index',
            'routes' => $this->routes->listRoutes(),
            'editing' => null,
            'canAdd' => $this->permissions->hasPrivilege('routes', 'can_add'),
            'canEdit' => $this->permissions->hasPrivilege('routes', 'can_edit'),
            'canDelete' => $this->permissions->hasPrivilege('routes', 'can_delete'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('routes', 'can_add'), 403);

        $this->routes->create($this->validated($request));

        return redirect()
            ->route('transport.routes.index')
            ->with('success', 'Route created successfully.');
    }

    public function edit(int $id): View
    {
        abort_unless($this->permissions->hasPrivilege('routes', 'can_edit'), 403);

        return view('shared::layouts.admin', [
            'title' => 'Edit Route',
            'contentView' => 'transport::admin.routes.index',
            'routes' => $this->routes->listRoutes(),
            'editing' => $this->routes->find($id),
            'canAdd' => $this->permissions->hasPrivilege('routes', 'can_add'),
            'canEdit' => true,
            'canDelete' => $this->permissions->hasPrivilege('routes', 'can_delete'),
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('routes', 'can_edit'), 403);

        $route = $this->routes->find($id);
        $this->routes->update($route, $this->validated($request));

        return redirect()
            ->route('transport.routes.index')
            ->with('success', 'Route updated successfully.');
    }

    public function destroy(int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('routes', 'can_delete'), 403);

        $this->routes->delete($this->routes->find($id));

        return redirect()
            ->route('transport.routes.index')
            ->with('success', 'Route deleted successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function validated(Request $request): array
    {
        return $request->validate([
            'route_title' => ['required', 'string', 'max:200'],
            'no_of_vehicle' => ['nullable', 'integer', 'min:0'],
        ]);
    }
}
