<?php

namespace App\Modules\Content\Services;

use App\Modules\Auth\Models\PortalUser;
use App\Modules\Shared\Services\SchoolContext;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * CI user/Content list/getsharelist/view/download_content.
 */
class UserContentService
{
    public function __construct(
        protected ShareContentService $shares,
        protected SchoolContext $school,
    ) {
    }

    public function shares(): ShareContentService
    {
        return $this->shares;
    }

    /**
     * @return array{role: string, student_id: int, user_id: int, class_id: int, section_id: int}
     */
    public function portalContext(): array
    {
        $user = Auth::guard('student_parent')->user();
        $role = $user instanceof PortalUser ? (string) ($user->role ?? '') : '';
        $userId = $user instanceof PortalUser ? (int) $user->id : 0;
        $studentId = $user instanceof PortalUser && $role === 'student' ? (int) $user->user_id : 0;

        $sessionId = (int) (session('current_class.student_session_id') ?? 0);
        $row = $sessionId > 0 ? DB::table('student_session')->where('id', $sessionId)->first() : null;

        return [
            'role' => $role,
            'student_id' => $studentId,
            'user_id' => $userId,
            'class_id' => (int) ($row->class_id ?? 0),
            'section_id' => (int) ($row->section_id ?? 0),
        ];
    }

    /**
     * @return Collection<int, object>
     */
    public function listShares(): Collection
    {
        $ctx = $this->portalContext();

        return $this->portalShareQuery($ctx)->orderByDesc('share_contents.id')->get();
    }

    /**
     * @return array{draw: int, recordsTotal: int, recordsFiltered: int, data: list<list<string>>}
     */
    public function dataTable(Request $request): array
    {
        $ctx = $this->portalContext();
        $draw = (int) $request->input('draw', 1);
        $start = max(0, (int) $request->input('start', 0));
        $length = (int) $request->input('length', 100);
        if ($length <= 0) {
            $length = 100;
        }

        $search = trim((string) data_get($request->all(), 'search.value', ''));
        $orderCol = (int) data_get($request->all(), 'order.0.column', -1);
        $orderDir = strtolower((string) data_get($request->all(), 'order.0.dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $orderable = $ctx['role'] === 'parent'
            ? ['title', 'send_to', 'share_date', 'valid_upto', 'staff.name']
            : ['title', 'share_date', 'valid_upto', 'staff.name', 'staff.surname'];

        $base = $this->portalShareQuery($ctx);
        $recordsTotal = (int) (clone $base)->count();

        if ($search !== '') {
            $like = '%'.addcslashes($search, '%_\\').'%';
            $base->where(function ($q) use ($like) {
                $q->where('share_contents.title', 'like', $like)
                    ->orWhere('share_contents.send_to', 'like', $like)
                    ->orWhere('share_contents.share_date', 'like', $like)
                    ->orWhere('share_contents.valid_upto', 'like', $like)
                    ->orWhere('share_contents.description', 'like', $like)
                    ->orWhere('staff.name', 'like', $like)
                    ->orWhere('staff.surname', 'like', $like);
            });
        }

        $recordsFiltered = (int) (clone $base)->count();
        $orderColumn = $orderable[$orderCol] ?? null;
        if ($orderColumn === 'staff.name') {
            $base->orderBy('staff.name', $orderDir);
        } elseif ($orderColumn === 'staff.surname') {
            $base->orderBy('staff.surname', $orderDir);
        } elseif ($orderColumn !== null) {
            $base->orderBy('share_contents.'.$orderColumn, $orderDir);
        } else {
            $base->orderByDesc('share_contents.id');
        }

        $rows = $base->offset($start)->limit($length)->get();
        $data = [];
        foreach ($rows as $value) {
            $view = "<a href='".url('user/content/view/'.$value->id)."' class='btn btn-primary btn-xs' data-toggle='tooltip' title='".e(__('system.view'))."'><i class='fa fa-eye'></i></a>";
            $data[] = [
                (string) $value->title,
                $this->shares->formatDate($value->share_date),
                $this->shares->formatDate($value->valid_upto),
                $this->listSharedBy($value),
                $view,
            ];
        }

        return [
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ];
    }

    public function listSharedBy(object $row): string
    {
        if ($this->hideSharedBy((int) ($row->role_id ?? 0))) {
            return '';
        }

        return $row->name.' '.$row->surname.' ('.$row->employee_id.')';
    }

    public function showSharedByOnView(object $row): bool
    {
        return $this->school->superadminRestriction() === 'enabled' || (int) ($row->role_id ?? 0) !== 7;
    }

    public function hideSharedBy(int $roleId): bool
    {
        return $this->school->superadminRestriction() === 'disabled' && $roleId === 7;
    }

    public function download(int $id): BinaryFileResponse
    {
        return $this->shares->uploads()->download($id);
    }

    /**
     * @param  array{role: string, student_id: int, user_id: int, class_id: int, section_id: int}  $ctx
     * @return \Illuminate\Database\Query\Builder
     */
    protected function portalShareQuery(array $ctx)
    {
        $query = DB::table('share_contents')
            ->join('staff', 'share_contents.created_by', '=', 'staff.id')
            ->join('staff_roles', 'staff_roles.staff_id', '=', 'staff.id')
            ->select([
                'share_contents.*',
                'staff.name',
                'staff.surname',
                'staff.employee_id',
                'staff_roles.role_id',
            ]);

        if ($ctx['role'] === 'student') {
            $query->whereRaw(
                "share_contents.id IN (SELECT share_content_id FROM share_content_for WHERE group_id = 'student' OR student_id = ? OR class_section_id = (SELECT class_sections.id FROM class_sections WHERE class_sections.class_id = ? AND class_sections.section_id = ?))",
                [(string) $ctx['student_id'], (string) $ctx['class_id'], (string) $ctx['section_id']]
            );

            return $query;
        }

        if ($ctx['role'] === 'parent') {
            $query->whereRaw(
                "share_contents.id IN (SELECT share_content_id FROM share_content_for WHERE group_id = 'parent' OR user_parent_id = ? OR class_section_id = (SELECT class_sections.id FROM class_sections WHERE class_sections.class_id = ? AND class_sections.section_id = ?))",
                [(string) $ctx['user_id'], (string) $ctx['class_id'], (string) $ctx['section_id']]
            );

            return $query;
        }

        return $query->whereRaw('1 = 0');
    }
}
