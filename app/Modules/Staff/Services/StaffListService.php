<?php

namespace App\Modules\Staff\Services;

use App\Modules\Roles\Models\Role;
use App\Modules\Shared\Services\SchoolContext;
use App\Modules\Staff\Models\Staff;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * CI Staff_model list helpers — get(), getEmployee(), searchFullText(), getStaffRole().
 */
class StaffListService
{
    public function __construct(
        protected SchoolContext $school,
    ) {
    }

    /**
     * CI Staff_model::getStaffRole — active roles for staff list filters/forms.
     *
     * @return Collection<int, Role>
     */
    public function rolesForFilter(): Collection
    {
        $query = Role::query()
            ->where('is_active', 'yes')
            ->orderBy('id');

        $this->applySuperadminRoleQueryFilter($query);

        return $query->get();
    }

    /**
     * CI Staff_model::get / searchFullText / getEmployee — active staff list base query.
     *
     * @return Builder<Staff>
     */
    public function activeStaffQuery(): Builder
    {
        $query = Staff::query()
            ->where('staff.is_active', 1)
            ->orderBy('staff.id');

        $this->applySuperadminStaffQueryFilter($query);

        return $query;
    }

    /**
     * CI Staff_model — roles.id != 7 when superadmin_restriction is disabled.
     */
    protected function applySuperadminStaffQueryFilter(Builder $query): void
    {
        /** @var Staff|null $staff */
        $staff = Auth::guard('staff')->user();
        if (! $staff) {
            return;
        }

        $roleId = (int) ($staff->roles()->value('roles.id') ?? 0);
        if ($roleId === 7) {
            return;
        }

        if ($this->school->superadminRestriction() === 'disabled') {
            $query->whereDoesntHave('roles', function (Builder $roleQuery) {
                $roleQuery->where('roles.id', 7);
            });
        }
    }

    /**
     * CI Staff_model::getStaffRole — hide superadmin role from dropdowns.
     */
    protected function applySuperadminRoleQueryFilter(Builder $query): void
    {
        /** @var Staff|null $staff */
        $staff = Auth::guard('staff')->user();
        if (! $staff) {
            return;
        }

        $roleId = (int) ($staff->roles()->value('roles.id') ?? 0);
        if ($roleId === 7) {
            return;
        }

        if ($this->school->superadminRestriction() === 'disabled') {
            $query->where('roles.id', '!=', 7);
        }
    }
}
