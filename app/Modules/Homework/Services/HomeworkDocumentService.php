<?php

namespace App\Modules\Homework\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * CI media_storage path: uploads/homework/
 * SaaS storage quota checks deferred.
 */
class HomeworkDocumentService
{
    public function directory(): string
    {
        return public_path('uploads/homework');
    }

    /**
     * @return array{extensions: list<string>, max_kb: int}
     */
    public function uploadRulesFromFiletypes(): array
    {
        $row = DB::table('filetypes')->orderBy('id')->first();
        $extensions = [];
        $maxKb = 10240;

        if ($row) {
            $extensions = array_values(array_filter(array_map(
                fn ($ext) => strtolower(ltrim(trim($ext), '.')),
                explode(',', (string) ($row->file_extension ?? ''))
            )));
            $bytes = (int) ($row->file_size ?? 0);
            if ($bytes > 0) {
                $maxKb = (int) ceil($bytes / 1024);
            }
        }

        if ($extensions === []) {
            $extensions = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'zip', 'txt'];
        }

        return [
            'extensions' => $extensions,
            'max_kb' => $maxKb,
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
        if ($filename === null || $filename === '') {
            return;
        }

        $safe = basename($filename);
        $path = $this->directory().DIRECTORY_SEPARATOR.$safe;
        if (File::isFile($path)) {
            File::delete($path);
        }
    }

    public function download(string $filename): BinaryFileResponse
    {
        $safe = basename($filename);
        abort_unless($safe !== '' && $safe === $filename && ! str_contains($safe, '..'), 404);

        $path = $this->directory().DIRECTORY_SEPARATOR.$safe;
        abort_unless(is_file($path), 404);

        return response()->download($path, $safe);
    }
}
