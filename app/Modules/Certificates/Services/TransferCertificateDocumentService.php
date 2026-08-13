<?php

namespace App\Modules\Certificates\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * CI media_storage for TC assets under uploads/transfer_certificate/.
 * SaaS storage quota deferred.
 */
class TransferCertificateDocumentService
{
    public const FOLDER = 'transfer_certificate';

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

    public function url(?string $filename): ?string
    {
        if ($filename === null || $filename === '') {
            return null;
        }

        return asset('uploads/'.self::FOLDER.'/'.$filename);
    }
}
