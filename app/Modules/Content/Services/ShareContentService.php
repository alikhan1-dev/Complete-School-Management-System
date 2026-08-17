<?php

namespace App\Modules\Content\Services;

use App\Modules\Content\Models\ShareContent;
use App\Modules\Content\Models\ShareContentFor;
use App\Modules\Content\Models\ShareUploadContent;
use App\Modules\Content\Support\EncLib;
use App\Modules\Roles\Models\Role;
use App\Modules\Shared\Services\SchoolContext;
use App\Modules\Staff\Models\Staff;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * CI Sharecontent_model + admin/Content share persist.
 */
class ShareContentService
{
    public function __construct(
        protected SchoolContext $school,
        protected UploadContentService $uploads,
    ) {
    }

    public function uploads(): UploadContentService
    {
        return $this->uploads;
    }

    /**
     * @return Collection<int, Role>
     */
    public function roles(): Collection
    {
        $roles = Role::query()->orderBy('id')->get();
        if ($this->school->superadminRestriction() === 'disabled') {
            return $roles->filter(fn (Role $role) => $role->name !== 'Super Admin')->values();
        }

        return $roles;
    }

    /**
     * @return Collection<int, object>
     */
    public function classSections(): Collection
    {
        return DB::table('class_sections')
            ->join('classes', 'classes.id', '=', 'class_sections.class_id')
            ->join('sections', 'sections.id', '=', 'class_sections.section_id')
            ->orderBy('classes.class')
            ->orderBy('sections.section')
            ->select([
                'class_sections.id',
                'classes.class',
                'sections.section',
            ])
            ->get();
    }

    public function currentStaff(): ?Staff
    {
        $staff = Auth::guard('staff')->user();

        return $staff instanceof Staff ? $staff : null;
    }

    /**
     * @return Collection<int, object>
     */
    public function listForStaff(?Staff $staff): Collection
    {
        $query = $this->shareListQuery($staff);

        return $query->orderByDesc('share_contents.id')->get();
    }

    /**
     * @return array{draw: int, recordsTotal: int, recordsFiltered: int, data: list<list<string>>}
     */
    public function dataTable(Request $request, bool $canDelete, ?Staff $staff): array
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
        $columns = ['title', 'send_to', 'share_date', 'valid_upto', 'name', 'description'];

        $base = $this->shareListQuery($staff);
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
        $orderColumn = $columns[$orderCol] ?? null;
        if ($orderColumn === 'name') {
            $base->orderBy('staff.name', $orderDir);
        } elseif ($orderColumn !== null) {
            $base->orderBy('share_contents.'.$orderColumn, $orderDir);
        } else {
            $base->orderByDesc('share_contents.id');
        }

        $rows = $base->offset($start)->limit($length)->get();
        $data = [];
        foreach ($rows as $value) {
            $shareLink = '';
            if ($value->send_to === 'public') {
                $url = $this->publicShareUrl((int) $value->id);
                $shareLink = "<button type='button' class='btn btn-primary btn-xs' data-recordid=".(int) $value->id.' data-link='.e($url)." data-toggle='modal' data-target='#linkModal' title='".e(__('system.link'))."' ><i class='fa fa-link'></i></button>";
            }
            $editbtn = "<button type='button' class='btn btn-primary btn-xs' data-recordid=".(int) $value->id." data-toggle='modal' data-target='#viewShareModal' title='".e(__('system.view') ?: 'View')."' ><i class='fa fa-eye'></i></button>";
            $deletebtn = '';
            if ($canDelete) {
                $confirm = e(__('system.delete_confirm'));
                $deletebtn = "<a onclick='return confirm(\"".$confirm."\");' href='".url('admin/content/delete_content/'.$value->id)."' class='btn btn-primary btn-xs' title='".e(__('system.delete'))."' data-toggle='tooltip'><i class='fa fa-trash'></i></a>";
            }
            $description = (string) ($value->description ?? '');
            $data[] = [
                (string) $value->title,
                (string) __('system.'.$value->send_to),
                $this->formatDate($value->share_date),
                $this->formatDate($value->valid_upto),
                $this->uploads->staffFullName($value->name, $value->surname, $value->employee_id),
                $description === '' ? (string) __('system.no_description') : $description,
                $shareLink.' '.$editbtn.' '.$deletebtn,
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
     * @return array{ok: bool, errors: array<string, string>, id?: int, msg?: string, shared_url?: string}
     */
    public function share(array $input, int $staffId, bool $publicUrl): array
    {
        $errors = $this->validateShare($input, $publicUrl);
        if ($errors !== []) {
            return ['ok' => false, 'errors' => $errors];
        }

        $selected = $this->selectedContentIds($input);
        $sendTo = $publicUrl ? 'public' : (string) ($input['send_to'] ?? '');
        $forRows = $publicUrl ? [] : $this->shareForRows($input, $sendTo);

        $id = (int) DB::transaction(function () use ($input, $staffId, $sendTo, $selected, $forRows) {
            $share = ShareContent::query()->create([
                'title' => trim((string) ($input['title'] ?? '')),
                'send_to' => $sendTo,
                'share_date' => $this->parseDate((string) ($input['share_date'] ?? '')),
                'valid_upto' => $this->parseDateNullable((string) ($input['valid_upto'] ?? '')),
                'description' => (string) ($input['description'] ?? ''),
                'created_by' => $staffId,
                'created_at' => now(),
            ]);

            foreach ($forRows as $row) {
                $row['share_content_id'] = $share->id;
                ShareContentFor::query()->create($row);
            }
            foreach ($selected as $uploadId) {
                ShareUploadContent::query()->create([
                    'upload_content_id' => $uploadId,
                    'share_content_id' => $share->id,
                ]);
            }

            return (int) $share->id;
        });

        if ($publicUrl) {
            return [
                'ok' => true,
                'errors' => [],
                'id' => $id,
                'shared_url' => $this->publicShareUrl($id),
                'msg' => (string) __('system.success_message'),
            ];
        }

        return [
            'ok' => true,
            'errors' => [],
            'id' => $id,
            'msg' => (string) __('system.record_shared_successfully'),
        ];
    }

    public function delete(int $id): void
    {
        ShareContent::query()->where('id', $id)->delete();
    }

    public function findWithDocuments(int $id): ?object
    {
        $row = DB::table('share_contents')
            ->join('staff', 'staff.id', '=', 'share_contents.created_by')
            ->leftJoin('staff_roles', 'staff.id', '=', 'staff_roles.staff_id')
            ->where('share_contents.id', $id)
            ->select([
                'share_contents.*',
                'staff.name',
                'staff.surname',
                'staff.employee_id',
                'staff_roles.role_id',
            ])
            ->first();
        if ($row === null) {
            return null;
        }
        $row->upload_contents = DB::table('share_upload_contents')
            ->join('upload_contents', 'upload_contents.id', '=', 'share_upload_contents.upload_content_id')
            ->where('share_upload_contents.share_content_id', $id)
            ->select([
                'share_upload_contents.*',
                'upload_contents.real_name',
                'upload_contents.thumb_path',
                'upload_contents.dir_path',
                'upload_contents.img_name',
                'upload_contents.thumb_name',
                'upload_contents.file_type',
                'upload_contents.mime_type',
                'upload_contents.vid_url',
                'upload_contents.vid_title',
            ])
            ->get();

        return $row;
    }

    /**
     * @return list<string>
     */
    public function sharedUserLabels(int $shareContentId): array
    {
        $rows = DB::table('share_content_for')
            ->leftJoin('roles', 'roles.id', '=', 'share_content_for.group_id')
            ->leftJoin('students', 'students.id', '=', 'share_content_for.student_id')
            ->leftJoin('users', 'users.id', '=', 'share_content_for.user_parent_id')
            ->leftJoin('students as parent_student', 'parent_student.id', '=', 'users.childs')
            ->leftJoin('staff', 'staff.id', '=', 'share_content_for.staff_id')
            ->leftJoin('staff_roles', 'staff_roles.staff_id', '=', 'staff.id')
            ->leftJoin('roles as staff_role_alias', 'staff_role_alias.id', '=', 'staff_roles.role_id')
            ->leftJoin('class_sections', 'class_sections.id', '=', 'share_content_for.class_section_id')
            ->leftJoin('classes', 'classes.id', '=', 'class_sections.class_id')
            ->leftJoin('sections', 'sections.id', '=', 'class_sections.section_id')
            ->where('share_content_for.share_content_id', $shareContentId)
            ->select([
                'share_content_for.*',
                'roles.name as role_name',
                'students.firstname as student_first_name',
                'students.middlename as student_middle_name',
                'students.lastname as student_last_name',
                'students.admission_no as student_admission_on',
                'parent_student.guardian_name',
                'staff.name as staff_first_name',
                'staff.surname as staff_surname',
                'staff.employee_id as staff_employee_id',
                'staff_role_alias.name as staff_role_name',
                'classes.class',
                'sections.section',
            ])
            ->get();

        $labels = [];
        foreach ($rows as $row) {
            if ($row->group_id !== null && $row->group_id !== '') {
                $labels[] = ((int) $row->group_id) ? (string) $row->role_name : (string) $row->group_id;
            } elseif ($row->staff_id) {
                $emp = $row->staff_employee_id !== null && $row->staff_employee_id !== '' ? ' ('.$row->staff_employee_id.') ' : '';
                $labels[] = trim((string) $row->staff_first_name.' '.(string) $row->staff_surname).$emp.' ('.$row->staff_role_name.')';
            } elseif ($row->user_parent_id) {
                $labels[] = (string) $row->guardian_name.' ('.__('system.guardian').')';
            } elseif ($row->student_id) {
                $name = trim((string) $row->student_first_name.' '.(string) $row->student_middle_name.' '.(string) $row->student_last_name);
                $labels[] = $name.' ('.$row->student_admission_on.')';
            } elseif ($row->class_section_id) {
                $labels[] = $row->class.' ('.$row->section.')';
            }
        }

        return $labels;
    }

    public function checkValid(int $uploadContentId, int $shareContentId): ?object
    {
        return DB::table('share_upload_contents')
            ->join('upload_contents', 'upload_contents.id', '=', 'share_upload_contents.upload_content_id')
            ->join('share_contents', 'share_contents.id', '=', 'share_upload_contents.share_content_id')
            ->where('share_upload_contents.upload_content_id', $uploadContentId)
            ->where('share_upload_contents.share_content_id', $shareContentId)
            ->select([
                'share_upload_contents.*',
                'upload_contents.thumb_path',
                'upload_contents.dir_path',
                'upload_contents.img_name',
                'upload_contents.thumb_name',
                'upload_contents.file_type',
                'upload_contents.vid_url',
                'upload_contents.vid_title',
                'share_contents.share_date',
                'share_contents.valid_upto',
            ])
            ->first();
    }

    public function publicShareUrl(int $shareContentId): string
    {
        return rtrim((string) config('app.url'), '/').'/site/share/'.EncLib::encrypt((string) $shareContentId);
    }

    public function isShareWindowOpen(object $share): bool
    {
        $today = strtotime(date('Y-m-d'));
        $shareDate = strtotime((string) $share->share_date);
        if ($shareDate === false || $today < $shareDate) {
            return false;
        }
        $valid = (string) ($share->valid_upto ?? '');
        if ($valid === '' || $valid === '0000-00-00') {
            return true;
        }

        return $today <= (strtotime($valid) ?: 0);
    }

    public function formatDate(mixed $value): string
    {
        if ($value === null || $value === '' || $value === '0000-00-00') {
            return '';
        }

        return Carbon::parse((string) $value)->format($this->school->dateFormat() ?: 'd/m/Y');
    }

    public function parseDate(string $value): string
    {
        $parsed = $this->parseDateNullable($value);
        if ($parsed === null) {
            return date('Y-m-d');
        }

        return $parsed;
    }

    public function parseDateNullable(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1) {
            return $value;
        }
        try {
            return Carbon::createFromFormat($this->school->dateFormat() ?: 'd/m/Y', $value)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    public function encryptedShareId(int $id): string
    {
        return EncLib::encrypt((string) $id);
    }

    /**
     * @return \Illuminate\Database\Query\Builder
     */
    protected function shareListQuery(?Staff $staff)
    {
        $select = [
            'share_contents.id',
            'share_contents.send_to',
            'share_contents.title',
            'share_contents.share_date',
            'share_contents.valid_upto',
            'share_contents.description',
            'share_contents.created_by',
            'staff.name',
            'staff.surname',
            'staff.employee_id',
        ];

        if ($staff === null || $staff->isSuperAdmin()) {
            return DB::table('share_contents')
                ->join('staff', 'share_contents.created_by', '=', 'staff.id')
                ->select($select);
        }

        $roleId = (int) ($staff->primaryRole()?->id ?? 0);
        $staffId = (int) $staff->id;
        $today = date('Y-m-d');

        $own = DB::table('share_contents')
            ->join('staff', 'share_contents.created_by', '=', 'staff.id')
            ->where('share_contents.created_by', $staffId)
            ->select($select);

        $shared = DB::table('share_content_for')
            ->join('share_contents', 'share_contents.id', '=', 'share_content_for.share_content_id')
            ->join('staff', 'share_contents.created_by', '=', 'staff.id')
            ->where(function ($q) use ($roleId, $staffId) {
                $q->where('share_content_for.group_id', (string) $roleId)
                    ->orWhere('share_content_for.staff_id', $staffId);
            })
            ->where(function ($q) use ($today) {
                $q->whereDate('share_contents.valid_upto', '>=', $today)
                    ->orWhereNull('share_contents.valid_upto');
            })
            ->select($select);

        return DB::query()->fromSub($own->union($shared), 'share_contents')
            ->join('staff', 'share_contents.created_by', '=', 'staff.id')
            ->select($select);
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, string>
     */
    protected function validateShare(array $input, bool $publicUrl): array
    {
        $errors = [];
        if (trim((string) ($input['title'] ?? '')) === '') {
            $errors['title'] = $this->p('The Title field is required.');
        }
        if (trim((string) ($input['share_date'] ?? '')) === '') {
            $errors['share_date'] = $this->p('The Share Date field is required.');
        }
        if ($this->selectedContentIds($input) === []) {
            $errors['selected_contents[]'] = $this->p('The Contents field is required.');
        }
        if ($publicUrl) {
            return $errors;
        }
        $sendTo = (string) ($input['send_to'] ?? '');
        if ($sendTo === '') {
            $errors['send_to'] = $this->p('The Send To field is required.');
        }
        if ($sendTo === 'group' && empty($input['user'])) {
            $errors['groups'] = $this->p('The Group field is required.');
        }
        if ($sendTo === 'class' && empty($input['class_section_id'])) {
            $errors['class_sections'] = $this->p('The Section field is required.');
        }
        if ($sendTo === 'individual') {
            $users = $input['user_list'] ?? null;
            $decoded = is_string($users) ? json_decode($users) : $users;
            if (empty($decoded)) {
                $errors['users_array'] = $this->p('The Users field is required.');
            }
        }

        return $errors;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return list<int>
     */
    protected function selectedContentIds(array $input): array
    {
        $selected = $input['selected_contents'] ?? [];
        if (! is_array($selected)) {
            $selected = [$selected];
        }

        return array_values(array_filter(array_map('intval', $selected)));
    }

    /**
     * @param  array<string, mixed>  $input
     * @return list<array<string, mixed>>
     */
    protected function shareForRows(array $input, string $sendTo): array
    {
        $rows = [];
        if ($sendTo === 'group') {
            $groups = $input['user'] ?? [];
            if (! is_array($groups)) {
                $groups = [$groups];
            }
            foreach ($groups as $group) {
                $rows[] = [
                    'group_id' => (string) $group,
                    'student_id' => null,
                    'user_parent_id' => null,
                    'staff_id' => null,
                    'class_section_id' => null,
                ];
            }
        } elseif ($sendTo === 'class') {
            $sections = $input['class_section_id'] ?? [];
            if (! is_array($sections)) {
                $sections = [$sections];
            }
            foreach ($sections as $sectionId) {
                $rows[] = [
                    'group_id' => null,
                    'student_id' => null,
                    'user_parent_id' => null,
                    'staff_id' => null,
                    'class_section_id' => (int) $sectionId,
                ];
            }
        } elseif ($sendTo === 'individual') {
            $users = $input['user_list'] ?? '[]';
            $decoded = is_string($users) ? json_decode($users) : $users;
            if (! is_array($decoded) && ! is_object($decoded)) {
                return $rows;
            }
            foreach ($decoded as $item) {
                $first = is_array($item) ? ($item[0] ?? null) : $item;
                if (is_array($first)) {
                    $first = (object) $first;
                }
                if (! is_object($first)) {
                    continue;
                }
                $category = (string) ($first->category ?? '');
                $recordId = (int) ($first->record_id ?? 0);
                $parentId = (int) ($first->parent_id ?? 0);
                if ($category === 'staff') {
                    $rows[] = $this->individualRow(staffId: $recordId);
                } elseif ($category === 'student') {
                    $rows[] = $this->individualRow(studentId: $recordId);
                } elseif ($category === 'parent') {
                    $rows[] = $this->individualRow(parentId: $parentId);
                } elseif ($category === 'student_guardian') {
                    $rows[] = $this->individualRow(parentId: $parentId);
                    $rows[] = $this->individualRow(studentId: $recordId);
                }
            }
        }

        return $rows;
    }

    /**
     * @return array<string, mixed>
     */
    protected function individualRow(?int $staffId = null, ?int $studentId = null, ?int $parentId = null): array
    {
        return [
            'group_id' => null,
            'student_id' => $studentId,
            'user_parent_id' => $parentId,
            'staff_id' => $staffId,
            'class_section_id' => null,
        ];
    }

    protected function p(string $message): string
    {
        return '<p>'.$message.'</p>';
    }
}
