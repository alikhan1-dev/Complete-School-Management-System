<?php

namespace App\Modules\Inventory\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;

/**
 * CI uploads/inventory_items — SaaS storage quota deferred.
 */
class ItemDocumentService
{
    public const FOLDER = 'inventory_items';

    public function storeForItem(int $itemId, UploadedFile $file): string
    {
        $dir = public_path('uploads/'.self::FOLDER);
        File::ensureDirectoryExists($dir);

        $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $name = $itemId.'.'.$ext;
        $relative = 'uploads/'.self::FOLDER.'/'.$name;

        $absolute = public_path($relative);
        if (File::isFile($absolute)) {
            File::delete($absolute);
        }

        $file->move($dir, $name);

        return $relative;
    }

    public function delete(?string $relativePath): void
    {
        if ($relativePath === null || $relativePath === '') {
            return;
        }

        $path = public_path(ltrim(str_replace('\\', '/', $relativePath), '/'));
        if (File::isFile($path)) {
            File::delete($path);
        }
    }

    public function publicUrl(?string $relativePath): ?string
    {
        if ($relativePath === null || $relativePath === '') {
            return null;
        }

        return asset(ltrim(str_replace('\\', '/', $relativePath), '/'));
    }
}
