<?php

namespace App\Modules\Transport\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * CI uploads/vehicle_photo — SaaS storage quota deferred.
 */
class VehicleDocumentService
{
    public const FOLDER = 'vehicle_photo';

    public function store(UploadedFile $file): string
    {
        $dir = public_path('uploads/'.self::FOLDER);
        File::ensureDirectoryExists($dir);

        $ext = strtolower($file->getClientOriginalExtension() ?: 'bin');
        $name = time().Str::random(8).'.'.$ext;
        $file->move($dir, $name);

        return $name;
    }

    public function delete(?string $filename): void
    {
        if ($filename === null || $filename === '') {
            return;
        }

        $path = public_path('uploads/'.self::FOLDER.'/'.$filename);
        if (File::isFile($path)) {
            File::delete($path);
        }
    }

    public function publicUrl(?string $filename): ?string
    {
        if ($filename === null || $filename === '') {
            return null;
        }

        return asset('uploads/'.self::FOLDER.'/'.$filename);
    }
}
