<?php

namespace App\Modules\Certificates\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * CI media_storage uploads for staff ID card templates.
 * Paths: uploads/staff_id_card/{background|logo|signature}/
 * SaaS storage quota deferred.
 */
class StaffIdCardDocumentService
{
    public const BASE = 'staff_id_card';

    public const FOLDER_BACKGROUND = 'background';

    public const FOLDER_LOGO = 'logo';

    public const FOLDER_SIGNATURE = 'signature';

    public function store(UploadedFile $file, string $folder): string
    {
        $dir = public_path('uploads/'.self::BASE.'/'.$folder);
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

        $path = public_path('uploads/'.self::BASE.'/'.$folder.'/'.$filename);
        if (File::isFile($path)) {
            File::delete($path);
        }
    }

    public function url(?string $filename, string $folder): ?string
    {
        if ($filename === null || $filename === '') {
            return null;
        }

        return asset('uploads/'.self::BASE.'/'.$folder.'/'.$filename);
    }
}
