<?php

namespace App\Modules\Finance\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * CI media_storage paths: uploads/school_income, uploads/school_expense.
 * SaaS storage quota checks deferred.
 */
class FinanceDocumentService
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

    public function absolutePath(string $filename, string $folder): string
    {
        return public_path('uploads/'.$folder.'/'.$filename);
    }
}
