<?php

namespace App\Modules\Inventory\Services;

use App\Modules\Inventory\Models\InventoryItem;
use App\Modules\Inventory\Models\ItemIssue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * CI admin/issueitem — issue/return/delete (form POST replaces AJAX add).
 */
class IssueItemService
{
    public function __construct(
        protected ItemCategoryService $categories,
        protected InventoryItemService $items,
    ) {
    }

    /**
     * @return Collection<int, object>
     */
    public function listIssues(): Collection
    {
        return DB::table('item_issue')
            ->join('item', 'item.id', '=', 'item_issue.item_id')
            ->join('item_category', 'item_category.id', '=', 'item.item_category_id')
            ->join('staff', 'staff.id', '=', 'item_issue.issue_to')
            ->join('staff as issueby', 'issueby.id', '=', 'item_issue.issue_by')
            ->leftJoin('staff_roles', 'staff_roles.staff_id', '=', 'staff.id')
            ->leftJoin('roles', 'roles.id', '=', 'staff_roles.role_id')
            ->orderByDesc('item_issue.id')
            ->select([
                'item_issue.*',
                'item.name as item_name',
                'item_category.item_category',
                'staff.name as staff_name',
                'staff.surname',
                'staff.employee_id',
                'issueby.name as issueby_staff_name',
                'issueby.surname as issueby_surname',
                'issueby.employee_id as issueby_employee_id',
                'roles.name as role_name',
            ])
            ->get();
    }

    public function find(int $id): ItemIssue
    {
        return ItemIssue::query()->findOrFail($id);
    }

    public function categoriesForSelect(): Collection
    {
        return $this->categories->listCategories()->sortBy('item_category')->values();
    }

    /**
     * Roles for "User Type" select.
     *
     * @return Collection<int, object>
     */
    public function rolesForSelect(): Collection
    {
        return DB::table('roles')->orderBy('name')->get(['id', 'name']);
    }

    /**
     * Staff available as Issue By (CI inventry_staff simplified).
     *
     * @return Collection<int, object>
     */
    public function issueByStaff(): Collection
    {
        return DB::table('staff')
            ->leftJoin('staff_roles', 'staff_roles.staff_id', '=', 'staff.id')
            ->leftJoin('roles', 'roles.id', '=', 'staff_roles.role_id')
            ->where('staff.is_active', 1)
            ->orderBy('staff.name')
            ->select([
                'staff.id',
                'staff.name',
                'staff.surname',
                'staff.employee_id',
                'roles.id as role_id',
            ])
            ->get();
    }

    /**
     * CI Issueitem::getUser — staff by role.
     *
     * @return list<object>
     */
    public function staffByRole(int $roleId): array
    {
        return DB::table('staff')
            ->join('staff_roles', 'staff_roles.staff_id', '=', 'staff.id')
            ->join('roles', 'roles.id', '=', 'staff_roles.role_id')
            ->where('staff.is_active', 1)
            ->where('roles.id', $roleId)
            ->orderBy('staff.name')
            ->get([
                'staff.id',
                'staff.name',
                'staff.surname',
                'staff.employee_id',
                'roles.id as role_id',
                'roles.name as role',
            ])
            ->all();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): ItemIssue
    {
        $itemId = (int) $data['item_id'];
        $quantity = (int) $data['quantity'];
        $available = (float) ($this->items->availableQuantity($itemId)['available'] ?? 0);
        if ($quantity > $available) {
            throw ValidationException::withMessages([
                'quantity' => 'Unavailable quantity '.$quantity,
            ]);
        }

        if (! InventoryItem::query()->whereKey($itemId)->exists()) {
            throw ValidationException::withMessages(['item_id' => 'Selected item is invalid.']);
        }

        $returnDate = trim((string) ($data['return_date'] ?? ''));

        return ItemIssue::query()->create([
            'issue_type' => (string) $data['account_type'],
            'issue_to' => (int) $data['issue_to'],
            'issue_by' => (int) $data['issue_by'],
            'issue_date' => (string) $data['issue_date'],
            'return_date' => $returnDate !== '' ? $returnDate : null,
            'note' => (string) ($data['note'] ?? ''),
            'quantity' => $quantity,
            'item_category_id' => (int) $data['item_category_id'],
            'item_id' => $itemId,
            'is_returned' => 1,
            'is_active' => 'yes',
        ]);
    }

    public function markReturned(int $issueId): void
    {
        $issue = $this->find($issueId);
        $issue->is_returned = 0;
        $issue->return_date = now()->toDateString();
        $issue->save();
    }

    public function delete(ItemIssue $issue): void
    {
        $issue->delete();
    }
}
