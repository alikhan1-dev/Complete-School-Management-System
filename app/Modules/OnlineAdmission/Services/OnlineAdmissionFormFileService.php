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

    /**
     * CI Welcome::image_handle_upload uses image_extension/image_mime and file_size.
     *
     * @return array{extensions: list<string>, max_bytes: int, mimes: list<string>}
     */
    public function imageRulesFromFiletypes(): array
    {
        $row = DB::table('filetypes')->orderBy('id')->first();
        $extensions = [];
        $mimes = [];
        $maxBytes = 10485760;

        if ($row) {
            $extensions = array_values(array_filter(array_map(
                fn ($ext) => strtolower(ltrim(trim($ext), '.')),
                explode(',', (string) ($row->image_extension ?? ''))
            )));
            $mimes = array_values(array_filter(array_map(
                fn ($mime) => strtolower(trim($mime)),
                explode(',', (string) ($row->image_mime ?? ''))
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

    public function documentDirectory(): string
    {
        return public_path('uploads/student_documents/online_admission_doc');
    }

    public function imageDirectory(): string
    {
        return public_path('uploads/student_images/online_admission_image');
    }

    public function storeApplicantDocument(UploadedFile $file): string
    {
        return $this->storeIn($this->documentDirectory(), $file);
    }

    public function storeApplicantImage(UploadedFile $file): string
    {
        $saved = $this->storeIn($this->imageDirectory(), $file);

        return 'uploads/student_images/online_admission_image/'.$saved;
    }

    /**
     * @param  array{extensions: list<string>, max_bytes: int, mimes: list<string>}  $rules
     */
    public function validateApplicantFile(UploadedFile $file, array $rules): ?string
    {
        $ext = strtolower($file->getClientOriginalExtension());
        $clientMime = strtolower((string) $file->getClientMimeType());
        $detected = strtolower((string) ($file->getMimeType() ?: $clientMime));

        if ($rules['mimes'] !== [] && ! in_array($detected, $rules['mimes'], true)) {
            return 'File Type Not Allowed';
        }
        if ($rules['extensions'] !== [] && ! in_array($ext, $rules['extensions'], true)) {
            return 'Extension Not Allowed';
        }
        if ($rules['mimes'] !== [] && $clientMime !== '' && ! in_array($clientMime, $rules['mimes'], true)) {
            return 'Extension Not Allowed';
        }
        if ($file->getSize() > $rules['max_bytes']) {
            return 'File size should be less than '.number_format($rules['max_bytes'] / 1048576, 2).' MB';
        }

        return null;
    }

    protected function storeIn(string $dir, UploadedFile $file): string
    {
        File::ensureDirectoryExists($dir);
        $original = basename((string) $file->getClientOriginalName());
        $saved = time().'-'.uniqid((string) random_int(1000, 9999), false).'!'.$original;
        $file->move($dir, $saved);

        return $saved;
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
