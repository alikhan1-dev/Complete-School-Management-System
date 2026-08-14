<?php

namespace App\Modules\OnlineAdmission\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * CI media_storage path: uploads/admission_form/
 * SaaS storage quota deferred.
 */
class OnlineAdmissionFormFileService
{
    public function directory(): string
    {
        return public_path('uploads/admission_form');
    }

    /**
     * @return array{extensions: list<string>, max_bytes: int, mimes: list<string>}
     */
    public function uploadRulesFromFiletypes(): array
    {
        $row = DB::table('filetypes')->orderBy('id')->first();
        $extensions = [];
        $mimes = [];
        $maxBytes = 10485760;

        if ($row) {
            $extensions = array_values(array_filter(array_map(
                fn ($ext) => strtolower(ltrim(trim($ext), '.')),
                explode(',', (string) ($row->file_extension ?? ''))
            )));
            $mimes = array_values(array_filter(array_map(
                fn ($mime) => strtolower(trim($mime)),
                explode(',', (string) ($row->file_mime ?? ''))
            )));
            $bytes = (int) ($row->file_size ?? 0);
            if ($bytes > 0) {
                $maxBytes = $bytes;
            }
        }

        return [
            'extensions' => $extensions,
            'mimes' => $mimes,
            'max_bytes' => $maxBytes,
        ];
    }

    public function store(UploadedFile $file): string
    {
        $dir = $this->directory();
        File::ensureDirectoryExists($dir);

        $original = basename((string) $file->getClientOriginalName());
        $saved = time().'-'.uniqid((string) random_int(1000, 9999), false).'!'.$original;
        $file->move($dir, $saved);

        return $saved;
    }

    public function delete(?string $filename): void
    {
        $safe = $this->basenameFromStored($filename);
        if ($safe === '') {
            return;
        }
        $path = $this->directory().DIRECTORY_SEPARATOR.$safe;
        if (File::isFile($path)) {
            File::delete($path);
        }
    }

    public function download(string $filename): BinaryFileResponse
    {
        $safe = $this->basenameFromStored($filename);
        abort_unless($safe !== '', 404);
        $path = $this->directory().DIRECTORY_SEPARATOR.$safe;
        abort_unless(File::isFile($path), 404);

        return response()->download($path, $safe);
    }

    public function basenameFromStored(?string $stored): string
    {
        if ($stored === null || trim($stored) === '') {
            return '';
        }

        return basename(str_replace('\\', '/', $stored));
    }
}
