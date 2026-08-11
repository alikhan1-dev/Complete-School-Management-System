<?php

namespace App\Modules\Students\Services;

use App\Modules\Students\Models\StudentDoc;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * CI Student create_doc / download / doc_delete + Student_model doc helpers.
 * Files live under public/uploads/student_documents/{student_id}/ (CI path).
 */
class StudentDocumentService
{
    /**
     * @return Collection<int, StudentDoc>
     */
    public function listFor(int $studentId): Collection
    {
        return StudentDoc::query()
            ->where('student_id', $studentId)
            ->orderByDesc('id')
            ->get();
    }

    public function findForStudent(int $docId, int $studentId): ?StudentDoc
    {
        return StudentDoc::query()
            ->where('id', $docId)
            ->where('student_id', $studentId)
            ->first();
    }

    /**
     * @param  list<UploadedFile>  $files
     * @return list<StudentDoc>
     */
    public function store(int $studentId, string $title, array $files): array
    {
        $dir = $this->directory($studentId);
        File::ensureDirectoryExists($dir);

        $created = [];

        DB::transaction(function () use ($studentId, $title, $files, $dir, &$created) {
            foreach ($files as $file) {
                $savedName = $this->storeFile($file, $dir);
                $created[] = StudentDoc::query()->create([
                    'student_id' => $studentId,
                    'title' => $title,
                    'doc' => $savedName,
                ]);
            }
        });

        return $created;
    }

    public function delete(StudentDoc $doc): void
    {
        $path = $this->absolutePath((int) $doc->student_id, (string) $doc->doc);
        if ($doc->doc && File::isFile($path)) {
            File::delete($path);
        }

        $doc->delete();
    }

    public function deleteAllForStudent(int $studentId): void
    {
        $docs = $this->listFor($studentId);
        foreach ($docs as $doc) {
            $this->delete($doc);
        }

        $dir = $this->directory($studentId);
        if (File::isDirectory($dir)) {
            File::deleteDirectory($dir);
        }
    }

    public function absolutePath(int $studentId, string $fileName): string
    {
        return $this->directory($studentId).DIRECTORY_SEPARATOR.basename($fileName);
    }

    public function directory(int $studentId): string
    {
        return public_path('uploads/student_documents/'.$studentId);
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
            $extensions = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'xls', 'xlsx', 'zip', 'txt'];
        }

        return [
            'extensions' => $extensions,
            'max_kb' => $maxKb,
        ];
    }

    protected function storeFile(UploadedFile $file, string $dir): string
    {
        $ext = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'bin');
        $base = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $base = Str::slug($base) ?: 'document';
        $savedName = $base.'_'.uniqid().'.'.$ext;
        $file->move($dir, $savedName);

        return $savedName;
    }
}
