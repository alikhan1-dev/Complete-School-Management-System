<?php

namespace App\Modules\Content\Services;

use App\Modules\Content\Models\ContentType;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * CI Contenttype_model + admin/Contenttype persist.
 */
class ContentTypeService
{
    /**
     * HTML list (CI DataTables default sort is id desc).
     *
     * @return Collection<int, ContentType>
     */
    public function listAll(): Collection
    {
        return ContentType::query()->orderByDesc('id')->get();
    }

    public function find(int $id): ?ContentType
    {
        return ContentType::query()->find($id);
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function create(array $input): ContentType
    {
        return ContentType::query()->create($this->payload($input) + [
            'is_active' => 1,
        ]);
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function update(int $id, array $input): void
    {
        ContentType::query()->where('id', $id)->update($this->payload($input));
    }

    public function delete(int $id): void
    {
        ContentType::query()->where('id', $id)->delete();
    }

    /**
     * CI getcontenttypelist DataTables JSON rows (HTML action column).
     *
     * @return array{draw: int, recordsTotal: int, recordsFiltered: int, data: list<list<string>>}
     */
    public function dataTable(Request $request, bool $canEdit, bool $canDelete): array
    {
        $draw = (int) $request->input('draw', 1);
        $start = max(0, (int) $request->input('start', 0));
        $length = (int) $request->input('length', 50);
        if ($length <= 0) {
            $length = 50;
        }

        $search = trim((string) data_get($request->all(), 'search.value', ''));
        $orderCol = (int) data_get($request->all(), 'order.0.column', -1);
        $orderDir = strtolower((string) data_get($request->all(), 'order.0.dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $columns = ['name', 'description'];

        $base = ContentType::query();
        $recordsTotal = (int) (clone $base)->count();

        if ($search !== '') {
            $like = '%'.addcslashes($search, '%_\\').'%';
            $base->where(function ($q) use ($like) {
                $q->where('name', 'like', $like)
                    ->orWhere('description', 'like', $like);
            });
        }

        $recordsFiltered = (int) (clone $base)->count();
        if (isset($columns[$orderCol])) {
            $base->orderBy($columns[$orderCol], $orderDir);
        } else {
            $base->orderByDesc('id');
        }

        $rows = $base->offset($start)->limit($length)->get();
        $data = [];
        foreach ($rows as $value) {
            $editbtn = '';
            $deletebtn = '';
            if ($canEdit) {
                $editbtn = "<a href='".url('admin/contenttype/edit/'.$value->id)."'   class='btn btn-primary btn-xs'  data-toggle='tooltip' title='".e(__('system.edit'))."'><i class='fa fa-pencil'></i></a>";
            }
            if ($canDelete) {
                $confirm = e(__('system.delete_confirm'));
                $deletebtn = "<a onclick='return confirm(\"".$confirm."\");' href='".url('admin/contenttype/delete/'.$value->id)."' class='btn btn-primary btn-xs' title='".e(__('system.delete'))."' data-toggle='tooltip'><i class='fa fa-trash'></i></a>";
            }

            $description = (string) ($value->description ?? '');
            $data[] = [
                (string) $value->name,
                $description === '' ? (string) __('system.no_description') : $description,
                $editbtn.' '.$deletebtn,
            ];
        }

        return [
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{name: string, description: string}
     */
    protected function payload(array $input): array
    {
        return [
            'name' => trim((string) ($input['name'] ?? '')),
            'description' => (string) ($input['description'] ?? ''),
        ];
    }
}
