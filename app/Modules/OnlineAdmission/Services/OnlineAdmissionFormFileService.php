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

    /**
     * CI Onlinestudent enroll: copy online_admission_doc into student_documents/{id}/.
     */
    public function copyDocumentToStudent(string $storedName, int $studentId): ?string
    {
        $name = $this->basenameFromStored($storedName);
        if ($name === '') {
            return null;
        }
        $src = $this->documentDirectory().DIRECTORY_SEPARATOR.$name;
        if (! File::isFile($src)) {
            return null;
        }
        $destDir = public_path('uploads/student_documents/'.$studentId);
        File::ensureDirectoryExists($destDir);
        $dest = $destDir.DIRECTORY_SEPARATOR.$name;
        if (! @copy($src, $dest)) {
            return null;
        }

        return $name;
    }

    /**
     * CI enroll photo copy: uploads/student_images/{studentId}{suffix}.{ext}.
     */
    public function copyImageToStudent(string $sourceRelative, int $studentId, string $suffix): ?string
    {
        $relative = ltrim(str_replace(['./', '\\'], ['', '/'], $sourceRelative), '/');
        if ($relative === '') {
            return null;
        }
        $src = public_path($relative);
        if (! File::isFile($src)) {
            return null;
        }
        $ext = strtolower((string) pathinfo($src, PATHINFO_EXTENSION)) ?: 'jpg';
        $imgName = $studentId.$suffix.'.'.$ext;
        $destDir = public_path('uploads/student_images');
        File::ensureDirectoryExists($destDir);
        $dest = $destDir.DIRECTORY_SEPARATOR.$imgName;
        if (! @copy($src, $dest)) {
            return null;
        }

        return 'uploads/student_images/'.$imgName;
    }

    public function storeStudentImage(UploadedFile $file, int $studentId, string $suffix): string
    {
        $ext = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'jpg');
        $imgName = $studentId.$suffix.'.'.$ext;
        $destDir = public_path('uploads/student_images');
        File::ensureDirectoryExists($destDir);
        $file->move($destDir, $imgName);

        return 'uploads/student_images/'.$imgName;
    }
}
