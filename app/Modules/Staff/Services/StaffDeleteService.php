<?php

namespace App\Modules\Staff\Services;

use App\Modules\Academics\Services\CustomFieldValueService;
use App\Modules\Shared\Services\SaasValidationService;
use App\Modules\Staff\Models\Staff;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * CI admin/Staff::delete + Staff_model::remove + file cleanup.
 */
class StaffDeleteService
{
    public function __construct(
        protected StaffDocumentService $documents,
        protected SaasValidationService $saas,
    ) {
    }

    public function assertCanDelete(Staff $target, Staff $actor): void
    {
        if ((int) $target->id === (int) $actor->id) {
            abort(403);
        }

        $roleId = (int) DB::table('staff_roles')->where('staff_id', $target->id)->value('role_id');
        if ($roleId === 7) {
            abort(403);
        }
    }

    public function delete(int $staffId): void
    {
        $staff = Staff::query()->find($staffId);
        if ($staff === null) {
            throw new \InvalidArgumentException('Staff not found.');
        }

        DB::transaction(function () use ($staff, $staffId) {
            $this->deleteUploadedFiles($staff, $staffId);
            $this->deleteCustomFieldValues($staffId);
            DB::table('staff')->where('id', $staffId)->delete();
        });

        $this->saas->decrementStaffQuota(1);
    }

    protected function deleteCustomFieldValues(int $staffId): void
    {
        DB::table('custom_field_values')
            ->whereIn('custom_field_id', function ($query) {
                $query->select('id')
                    ->from('custom_fields')
                    ->where('belong_to', 'staff');
            })
            ->where('belong_table_id', $staffId)
            ->delete();
    }

    protected function deleteUploadedFiles(object $staff, int $staffId): void
    {
        $image = trim((string) ($staff->image ?? ''));
        if ($image !== '') {
            $imagePath = public_path('uploads/staff_images/'.$image);
            if (File::isFile($imagePath)) {
                File::delete($imagePath);
            }
        }

        foreach (StaffDocumentService::DOCUMENT_KEYS as $docKey) {
            try {
                $fileName = $this->documents->filename($staff, $docKey);
            } catch (\InvalidArgumentException) {
                continue;
            }
            if ($fileName === null) {
                continue;
            }
            $path = $this->documents->absolutePath($staffId, $fileName);
            if (File::isFile($path)) {
                File::delete($path);
            }
        }

        $docDir = $this->documents->directory($staffId);
        if (File::isDirectory($docDir)) {
            File::deleteDirectory($docDir);
        }
    }
}
