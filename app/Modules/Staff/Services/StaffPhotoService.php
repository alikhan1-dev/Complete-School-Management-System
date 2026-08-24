<?php

namespace App\Modules\Staff\Services;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\Rules\File as ValidationFile;
use InvalidArgumentException;

/**
 * CI admin/Staff::handle_upload + file field on create/edit.
 * Files live under public/uploads/staff_images/.
 * SaaS storage quota deferred.
 */
class StaffPhotoService
{
    public function directory(): string
    {
        return public_path('uploads/staff_images');
    }

    public function absolutePath(string $fileName): string
    {
        return $this->directory().DIRECTORY_SEPARATOR.basename($fileName);
    }

    public function publicUrl(string $fileName): string
    {
        $fileName = trim($fileName);
        if ($fileName === '') {
            return '';
        }

        return asset('uploads/staff_images/'.$fileName);
    }

    /**
     * @return array{extensions: list<string>, max_kb: int, mimes: list<string>}
     */
    public function imageRulesFromFiletypes(): array
    {
        $row = DB::table('filetypes')->orderBy('id')->first();
        $extensions = [];
        $mimes = [];
        $maxKb = 10240;

        if ($row) {
            $extensions = array_values(array_filter(array_map(
                fn ($ext) => strtolower(ltrim(trim($ext), '.')),
                explode(',', (string) ($row->image_extension ?? ''))
            )));
            $mimes = array_values(array_filter(array_map(
                fn ($mime) => strtolower(trim($mime)),
                explode(',', (string) ($row->image_mime ?? ''))
            )));
            $bytes = (int) ($row->image_size ?? 0);
            if ($bytes <= 0) {
                $bytes = (int) ($row->file_size ?? 0);
            }
            if ($bytes > 0) {
                $maxKb = (int) ceil($bytes / 1024);
            }
        }

        if ($extensions === []) {
            $extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        }

        return [
            'extensions' => $extensions,
            'max_kb' => $maxKb,
            'mimes' => $mimes,
        ];
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function photoValidationRules(bool $staffPhotoEnabled): array
    {
        if (! $staffPhotoEnabled) {
            return [];
        }

        $meta = $this->imageRulesFromFiletypes();
        $rule = ValidationFile::types($meta['extensions'])
            ->max($meta['max_kb']);

        return [
            'file' => ['nullable', 'file', 'image', $rule],
        ];
    }

    public function shouldSync(?UploadedFile $file): bool
    {
        return $file instanceof UploadedFile;
    }

    public function photoFromRequest(Request $request): ?UploadedFile
    {
        $file = $request->file('file');

        return $file instanceof UploadedFile ? $file : null;
    }

    public function store(UploadedFile $file): string
    {
        File::ensureDirectoryExists($this->directory());

        $original = basename((string) $file->getClientOriginalName());
        $savedName = time().'-'.uniqid('', true).'!'.$original;
        $file->move($this->directory(), $savedName);

        return $savedName;
    }

    public function replace(string $existing, UploadedFile $file): string
    {
        $savedName = $this->store($file);
        if ($existing !== '') {
            $this->delete($existing);
        }

        return $savedName;
    }

    public function delete(string $fileName): void
    {
        $fileName = trim($fileName);
        if ($fileName === '') {
            return;
        }

        $path = $this->absolutePath($fileName);
        if (File::isFile($path)) {
            File::delete($path);
        }
    }

    public function assertReadableImage(UploadedFile $file): void
    {
        $path = $file->getRealPath();
        if ($path === false || @getimagesize($path) === false) {
            throw new InvalidArgumentException(__('system.file_type_not_allowed'));
        }
    }
}
