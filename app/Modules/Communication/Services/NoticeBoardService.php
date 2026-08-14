<?php

namespace App\Modules\Communication\Services;

use App\Modules\Communication\Models\SendNotification;
use App\Modules\Roles\Models\Role;
use App\Modules\Shared\Services\SchoolContext;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * CI Notification_model + admin/notification notice board persist.
 * Mail/SMS/push send on add/edit is deferred with compose/Mailsms.
 * SaaS storage quota deferred.
 */
class NoticeBoardService
{
    public const SUPERADMIN_ROLE_ID = 7;

    public function __construct(
        protected SchoolContext $school,
        protected NoticeBoardDocumentService $documents,
    ) {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listForStaff(int $staffId, int $roleId): array
    {
        $sub = DB::table('notification_roles')
            ->select('send_notification_id', DB::raw('GROUP_CONCAT(role_id) as roles'));
        if ($roleId !== self::SUPERADMIN_ROLE_ID) {
            $sub->where('role_id', $roleId);
        }
        $sub->groupBy('send_notification_id');

        return DB::table('send_notification')
            ->joinSub($sub, 'notification_roles', function ($join) {
                $join->on('notification_roles.send_notification_id', '=', 'send_notification.id');
            })
            ->orderByDesc('send_notification.id')
            ->select('send_notification.*', 'notification_roles.roles')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findForStaff(int $id, int $roleId): ?array
    {
        $sub = DB::table('notification_roles')
            ->select('send_notification_id', DB::raw('GROUP_CONCAT(role_id) as roles'));
        if ($roleId !== self::SUPERADMIN_ROLE_ID) {
            $sub->where('role_id', $roleId);
        }
        $sub->groupBy('send_notification_id');

        $row = DB::table('send_notification')
            ->joinSub($sub, 'notification_roles', function ($join) {
                $join->on('notification_roles.send_notification_id', '=', 'send_notification.id');
            })
            ->where('send_notification.id', $id)
            ->select('send_notification.*', 'notification_roles.roles')
            ->first();

        return $row ? (array) $row : null;
    }

    public function findRaw(int $id): ?SendNotification
    {
        return SendNotification::query()->find($id);
    }

    /**
     * @return list<object{id: int, name: string}>
     */
    public function rolesForForm(int $viewerRoleId): array
    {
        $query = Role::query()->orderBy('id');
        if ($this->school->superadminRestriction() === 'disabled' && $viewerRoleId !== self::SUPERADMIN_ROLE_ID) {
            $query->where('id', '!=', self::SUPERADMIN_ROLE_ID);
        }

        return $query->get(['id', 'name'])->all();
    }

    /**
     * @param  array<string, mixed>  $input
     * @param  list<string>  $visible
     */
    public function create(array $input, array $visible, ?UploadedFile $file, int $staffId, string $createdBy): SendNotification
    {
        $attachment = '';
        if ($file) {
            $attachment = $this->documents->store($file);
        }

        $flags = $this->visibilityFlags($visible);
        $staffRoles = $this->staffRolesForInsert($visible);

        return DB::transaction(function () use ($input, $flags, $staffRoles, $attachment, $staffId, $createdBy) {
            $row = SendNotification::query()->create([
                'message' => (string) ($input['message'] ?? ''),
                'title' => (string) ($input['title'] ?? ''),
                'date' => $this->parseDate((string) ($input['date'] ?? '')),
                'created_by' => $createdBy,
                'created_id' => $staffId,
                'visible_student' => $flags['student'],
                'visible_staff' => $flags['staff'],
                'visible_parent' => $flags['parent'],
                'publish_date' => $this->parseDate((string) ($input['publish_date'] ?? '')),
                'attachment' => $attachment,
            ]);

            if ($staffRoles !== []) {
                $batch = [];
                foreach ($staffRoles as $roleId) {
                    $batch[] = [
                        'send_notification_id' => $row->id,
                        'role_id' => $roleId,
                        'is_active' => 0,
                    ];
                }
                DB::table('notification_roles')->insert($batch);
            }

            return $row;
        });
    }

    /**
     * @param  array<string, mixed>  $input
     * @param  list<string>  $visible
     * @param  list<int>  $prevRoles
     */
    public function update(
        SendNotification $row,
        array $input,
        array $visible,
        array $prevRoles,
        ?UploadedFile $file,
        int $staffId,
        string $createdBy,
    ): SendNotification {
        $imgName = (string) ($row->attachment ?? '');
        if ($file) {
            $this->documents->delete($imgName);
            $imgName = $this->documents->store($file);
        }

        $flags = $this->visibilityFlags($visible);
        $instStaff = [];
        foreach ($visible as $value) {
            if (is_numeric($value)) {
                $instStaff[] = (int) $value;
            }
        }
        $toBeDel = array_values(array_diff($prevRoles, $instStaff));
        $toBeInsert = array_values(array_diff($instStaff, $prevRoles));

        return DB::transaction(function () use ($row, $input, $flags, $imgName, $staffId, $createdBy, $toBeDel, $toBeInsert) {
            $row->fill([
                'message' => (string) ($input['message'] ?? ''),
                'title' => (string) ($input['title'] ?? ''),
                'date' => $this->parseDate((string) ($input['date'] ?? '')),
                'created_by' => $createdBy,
                'created_id' => $staffId,
                'visible_student' => $flags['student'],
                'visible_staff' => $flags['staff'],
                'visible_parent' => $flags['parent'],
                'publish_date' => $this->parseDate((string) ($input['publish_date'] ?? '')),
                'attachment' => $imgName,
            ])->save();

            if ($toBeInsert !== []) {
                $batch = [];
                foreach ($toBeInsert as $roleId) {
                    $batch[] = [
                        'send_notification_id' => $row->id,
                        'role_id' => $roleId,
                        'is_active' => 0,
                    ];
                }
                DB::table('notification_roles')->insert($batch);
            }
            if ($toBeDel !== []) {
                DB::table('notification_roles')
                    ->where('send_notification_id', $row->id)
                    ->whereIn('role_id', $toBeDel)
                    ->delete();
            }

            return $row->fresh();
        });
    }

    public function delete(SendNotification $row): void
    {
        $this->documents->delete((string) ($row->attachment ?? ''));
        $row->delete();
    }

    public function deletePastNotices(): int
    {
        $rows = SendNotification::query()
            ->whereDate('publish_date', '<', Carbon::today()->toDateString())
            ->get();
        $count = 0;
        foreach ($rows as $row) {
            $this->delete($row);
            $count++;
        }

        return $count;
    }

    /**
     * @return list<array{id: int, name: string}>
     */
    public function rolesByIds(array $ids): array
    {
        $ids = array_values(array_filter(array_map('intval', $ids)));
        if ($ids === []) {
            return [];
        }

        return Role::query()->whereIn('id', $ids)->get(['id', 'name'])->toArray();
    }

    public function parseDate(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            throw new InvalidArgumentException('Date is required.');
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1) {
            return $value;
        }

        $format = $this->school->dateFormat() ?: 'd/m/Y';
        $parsed = Carbon::createFromFormat($format, $value);

        return $parsed->format('Y-m-d');
    }

    public function formatDate(?string $value): string
    {
        if ($value === null || $value === '' || $value === '0000-00-00') {
            return '';
        }
        $format = $this->school->dateFormat() ?: 'd/m/Y';

        return Carbon::parse($value)->format($format);
    }

    /**
     * @param  list<string>  $visible
     * @return array{student: string, staff: string, parent: string}
     */
    public function visibilityFlags(array $visible): array
    {
        $student = 'No';
        $staff = 'No';
        $parent = 'No';

        if (! in_array('7', $visible, false) && ! in_array(7, $visible, false)) {
            $staff = 'Yes';
        }

        foreach ($visible as $value) {
            if ($value === 'student') {
                $student = 'Yes';
            } elseif ($value === 'parent') {
                $parent = 'Yes';
            } elseif (is_numeric($value)) {
                $staff = 'Yes';
            }
        }

        return compact('student', 'staff', 'parent');
    }

    /**
     * CI add() staff_roles including forced Super Admin (id 7) when unchecked.
     *
     * @param  list<string>  $visible
     * @return list<int>
     */
    public function staffRolesForInsert(array $visible): array
    {
        $roleIds = [];
        if (! in_array('7', $visible, false) && ! in_array(7, $visible, false)) {
            $roleIds[] = self::SUPERADMIN_ROLE_ID;
        }
        foreach ($visible as $value) {
            if (is_numeric($value)) {
                $roleIds[] = (int) $value;
            }
        }

        return array_values(array_unique($roleIds));
    }

    public function currentStaffId(): int
    {
        return (int) (Auth::guard('staff')->id() ?? 0);
    }

    public function currentRoleId(): int
    {
        $staff = Auth::guard('staff')->user();
        if (! $staff) {
            return 0;
        }
        $role = $staff->primaryRole();

        return $role ? (int) $role->id : 0;
    }

    public function currentCreatedBy(): string
    {
        $staff = Auth::guard('staff')->user();
        if (! $staff) {
            return '';
        }
        $role = $staff->primaryRole();

        return $role ? (string) $role->name : '';
    }
}
