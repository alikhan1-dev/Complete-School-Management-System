<?php

namespace App\Modules\FrontCms\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;

/**
 * CI media_storage path: uploads/school_content/logo/
 * SaaS storage quota deferred.
 */
class FrontCmsLogoService
{
    public function directory(): string
    {
        return public_path('uploads/school_content/logo');
    }

    public function store(UploadedFile $file): string
    {
        $dir = $this->directory();
        File::ensureDirectoryExists($dir);

        $original = basename((string) $file->getClientOriginalName());
        $saved = time().'-'.uniqid((string) random_int(1000, 9999), false).'!'.$original;
        $file->move($dir, $saved);

        return './uploads/school_content/logo/'.$saved;
    }

    public function deleteStoredPath(?string $stored): void
    {
        $filename = $this->basenameFromStored($stored);
        if ($filename === '') {
            return;
        }

        $path = $this->directory().DIRECTORY_SEPARATOR.$filename;
        if (File::isFile($path)) {
            File::delete($path);
        }
    }

    public function basenameFromStored(?string $stored): string
    {
        if ($stored === null || trim($stored) === '') {
            return '';
        }

        return basename(str_replace('\\', '/', $stored));
    }
}
