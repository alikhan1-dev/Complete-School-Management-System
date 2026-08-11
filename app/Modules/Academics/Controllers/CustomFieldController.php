<?php

namespace App\Modules\Academics\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academics\Models\CustomField;
use App\Modules\Academics\Requests\StoreCustomFieldRequest;
use App\Modules\Academics\Requests\UpdateCustomFieldRequest;
use App\Modules\Academics\Support\CustomFieldConfig;
use App\Modules\Roles\Services\PermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CustomFieldController extends Controller
{
    public function __construct(protected PermissionService $permissions)
    {
    }

    public function index(): View
    {
        abort_unless($this->permissions->hasPrivilege('custom_fields', 'can_view'), 403);

        return view('shared::layouts.admin', [
            'title' => 'Custom Fields',
            'contentView' => 'academics::admin.custom_fields.index',
            'fieldTypes' => CustomFieldConfig::types(),
            'fieldTables' => CustomFieldConfig::tables(),
            'customfields' => $this->bundleByBelong(),
        ]);
    }

    public function store(StoreCustomFieldRequest $request): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('custom_fields', 'can_add'), 403);

        $belongTo = $request->validated('belong_to');
        $maxWeight = (int) CustomField::query()->where('belong_to', $belongTo)->max('weight');

        CustomField::query()->create([
            'belong_to' => $belongTo,
            'type' => $request->validated('type'),
            'bs_column' => (int) $request->validated('column'),
            'name' => $request->validated('name'),
            'field_values' => $request->validated('field_values') ?? '',
            'validation' => $request->boolean('validation') ? 1 : 0,
            'visible_on_table' => $request->boolean('display_tbl') ? 1 : 0,
            'weight' => $maxWeight + 1,
            'is_active' => 1,
        ]);

        return redirect()->route('academics.custom_fields.index')->with('success', 'Custom field created successfully.');
    }

    public function edit(int $id): View
    {
        abort_unless($this->permissions->hasPrivilege('custom_fields', 'can_edit'), 403);

        return view('shared::layouts.admin', [
            'title' => 'Edit Custom Field',
            'contentView' => 'academics::admin.custom_fields.edit',
            'fieldTypes' => CustomFieldConfig::types(),
            'fieldTables' => CustomFieldConfig::tables(),
            'customfields' => $this->bundleByBelong(),
            'field' => CustomField::query()->findOrFail($id),
        ]);
    }

    public function update(UpdateCustomFieldRequest $request, int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('custom_fields', 'can_edit'), 403);

        $field = CustomField::query()->findOrFail($id);
        $field->belong_to = $request->validated('belong_to');
        $field->type = $request->validated('type');
        $field->name = $request->validated('name');
        $field->field_values = $request->validated('field_values') ?? '';
        $field->validation = $request->boolean('validation') ? 1 : 0;
        $field->visible_on_table = $request->boolean('display_tbl') ? 1 : 0;
        if ($request->filled('column')) {
            $field->bs_column = (int) $request->validated('column');
        }
        $field->save();

        return redirect()->route('academics.custom_fields.index')->with('success', 'Custom field updated successfully.');
    }

    public function destroy(int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('custom_fields', 'can_delete'), 403);

        CustomField::query()->findOrFail($id)->delete(); // cascade deletes values via FK

        return redirect()->route('academics.custom_fields.index')->with('success', 'Custom field deleted successfully.');
    }

    /**
     * CI: POST admin/customfield/updateorder
     */
    public function updateOrder(Request $request): JsonResponse
    {
        abort_unless($this->permissions->hasPrivilege('custom_fields', 'can_edit'), 403);

        $items = $request->input('items', []);
        if (! is_array($items) || $items === []) {
            return response()->json(['status' => '1', 'msg' => 'Update Message']);
        }

        DB::transaction(function () use ($items) {
            $weight = 1;
            foreach ($items as $itemId) {
                CustomField::query()->where('id', (int) $itemId)->update(['weight' => $weight]);
                $weight++;
            }
        });

        return response()->json(['status' => '1', 'msg' => 'Update Message']);
    }

    /**
     * @return array<string, \Illuminate\Support\Collection<int, CustomField>>
     */
    protected function bundleByBelong(): array
    {
        $fields = CustomField::query()
            ->orderBy('belong_to')
            ->orderBy('weight')
            ->orderBy('id')
            ->get();

        $bundle = [];
        foreach ($fields as $field) {
            $bundle[$field->belong_to][] = $field;
        }

        return $bundle;
    }
}
