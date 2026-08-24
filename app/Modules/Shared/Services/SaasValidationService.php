<?php

namespace App\Modules\Shared\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

/**
 * CI libraries/SaasValidation — reference build returns TRUE for all checks when SaaS is off.
 * Hooks are wired for staff quota/storage so a future SaaS phase can enforce limits.
 */
class SaasValidationService
{
    public function isEnabled(): bool
    {
        return (bool) config('saas.enabled', false);
    }

    /**
     * CI validateCanAddNewResource($input, $resource_name, $quantity).
     *
     * @throws ValidationException
     */
    public function assertCanAddStaff(int $quantity = 1): void
    {
        if (! $this->isEnabled() || $quantity <= 0) {
            return;
        }

        // Future SaaS phase: enforce no_of_staff quota.
        throw ValidationException::withMessages([
            'validate_resource' => 'Staff quota validation is not configured for this environment.',
        ]);
    }

    /**
     * CI validateCanUploadFile for staff create uploads.
     *
     * @param  array<string, UploadedFile|null>  $files
     *
     * @throws ValidationException
     */
    public function assertCanUploadFiles(array $files): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        $hasUpload = false;
        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $hasUpload = true;
                break;
            }
        }

        if (! $hasUpload) {
            return;
        }

        throw ValidationException::withMessages([
            'validate_storage' => 'Storage quota validation is not configured for this environment.',
        ]);
    }

    public function incrementStaffQuota(int $quantity = 1): void
    {
        if (! $this->isEnabled() || $quantity <= 0) {
            return;
        }

        // Future SaaS phase: updateResouceQuota('no_of_staff', $quantity).
    }

    public function decrementStaffQuota(int $quantity = 1): void
    {
        if (! $this->isEnabled() || $quantity <= 0) {
            return;
        }

        // Future SaaS phase: deleteResouceQuota('no_of_staff', $quantity).
    }

    /**
     * CI updateStorageLimit after staff file uploads.
     *
     * @param  array<string, UploadedFile|null>  $files
     */
    public function recordStaffUploadStorage(array $files): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        unset($files);

        // Future SaaS phase: updateResouceQuota('storage', $bytes).
    }

    /**
     * CI deleteResouceQuota('storage', $bytes) when upload fails after quota bump.
     */
    public function releaseStaffUploadStorage(int $bytes): void
    {
        if (! $this->isEnabled() || $bytes <= 0) {
            return;
        }

        // Future SaaS phase: deleteResouceQuota('storage', $bytes).
    }
}
