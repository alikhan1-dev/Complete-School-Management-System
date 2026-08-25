<?php

namespace App\Modules\Shared\Services;

use App\Modules\Staff\Models\Staff;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\Auth;

/**
 * CI Role_model::get / Staff_model::getStaffRole / Studentfeemaster_model::get_feesreceived_by.
 */
class SuperadminRoleFilterService
{
    public function __construct(
        protected SchoolContext $school,
    ) {
    }

    public function viewerRoleId(): int
    {
        /** @var Staff|null $staff */
        $staff = Auth::guard('staff')->user();
        if (! $staff) {
            return 0;
        }

        return (int) ($staff->roles()->value('roles.id') ?? 0);
    }

    public function shouldHideSuperadminRole(): bool
    {
        return $this->school->superadminRestriction() === 'disabled'
            && $this->viewerRoleId() !== 7;
    }

    /**
     * @param  QueryBuilder|EloquentBuilder  $query
     */
    public function applyRoleDropdownFilter(QueryBuilder|EloquentBuilder $query): void
    {
        if (! $this->shouldHideSuperadminRole()) {
            return;
        }

        $query->where('roles.id', '!=', 7);
    }

    /**
     * @param  QueryBuilder|EloquentBuilder  $query
     */
    public function applyStaffRoleIdFilter(QueryBuilder|EloquentBuilder $query, string $column = 'staff_roles.role_id'): void
    {
        if (! $this->shouldHideSuperadminRole()) {
            return;
        }

        $query->where($column, '!=', 7);
    }
}
