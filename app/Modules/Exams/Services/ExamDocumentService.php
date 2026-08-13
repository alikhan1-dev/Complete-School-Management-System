<?php

namespace App\Modules\Exams\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * CI media_storage uploads for marksheet / admit_card templates.
 * SaaS storage quota checks deferred.
 */
class ExamDocumentService
{
    public function store(UploadedFile $file, string $folder): string
    {
        $dir = public_path('uploads/'.$folder);
        File::ensureDirectoryExists($dir);

        $ext = strtolower($file->getClientOriginalExtension() ?: 'bin');
        $name = time().Str::random(8).'.'.$ext;
        $file->move($dir, $name);

        return $name;
    }

    public function delete(?string $filename, string $folder): void
    {
        if ($filename === null || $filename === '') {
            return;
        }

        $path = public_path('uploads/'.$folder.'/'.$filename);
        if (File::isFile($path)) {
            File::delete($path);
        }
    }

    public function url(?string $filename, string $folder): ?string
    {
        if ($filename === null || $filename === '') {
            return null;
        }

        return asset('uploads/'.$folder.'/'.$filename);
    }
}
