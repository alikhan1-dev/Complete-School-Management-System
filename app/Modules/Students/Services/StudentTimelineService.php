<?php

namespace App\Modules\Students\Services;

use App\Modules\Students\Models\StudentTimeline;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * CI admin/Timeline student methods + Timeline_model student helpers.
 * Attachments: public/uploads/student_timeline/
 */
class StudentTimelineService
{
    /**
     * @return Collection<int, StudentTimeline>
     */
    public function listFor(int $studentId, bool $visibleOnly = false): Collection
    {
        $query = StudentTimeline::query()
            ->where('student_id', $studentId)
            ->orderBy('timeline_date')
            ->orderBy('id');

        if ($visibleOnly) {
            $query->where('status', 'yes');
        }

        return $query->get();
    }

    public function find(int $id): ?StudentTimeline
    {
        return StudentTimeline::query()->find($id);
    }

    /**
     * @param  array{title:string,timeline_date:string,description?:string,status?:string,document?:string|null}  $data
     */
    public function create(int $studentId, array $data, ?UploadedFile $file = null): StudentTimeline
    {
        $document = '';
        if ($file) {
            $document = $this->storeFile($file);
        }

        return StudentTimeline::query()->create([
            'student_id' => $studentId,
            'title' => $data['title'],
            'timeline_date' => $data['timeline_date'],
            'description' => (string) ($data['description'] ?? ''),
            'document' => $document,
            'status' => ($data['status'] ?? '') === 'yes' ? 'yes' : '',
            'created_student_id' => 0,
            'date' => now()->toDateString(),
        ]);
    }

    /**
     * @param  array{title:string,timeline_date:string,description?:string,status?:string}  $data
     */
    public function update(StudentTimeline $row, array $data, ?UploadedFile $file = null): StudentTimeline
    {
        $row->title = $data['title'];
        $row->timeline_date = $data['timeline_date'];
        $row->description = (string) ($data['description'] ?? '');
        $row->status = ($data['status'] ?? '') === 'yes' ? 'yes' : '';
        $row->date = now()->toDateString();

        if ($file) {
            $this->deleteFile((string) $row->document);
            $row->document = $this->storeFile($file);
        }

        $row->save();

        return $row;
    }

    public function delete(StudentTimeline $row): void
    {
        $this->deleteFile((string) $row->document);
        $row->delete();
    }

    public function deleteAllForStudent(int $studentId): void
    {
        foreach ($this->listFor($studentId) as $row) {
            $this->delete($row);
        }
    }

    public function absolutePath(string $fileName): string
    {
        return $this->directory().DIRECTORY_SEPARATOR.basename($fileName);
    }

    public function directory(): string
    {
        return public_path('uploads/student_timeline');
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

    protected function storeFile(UploadedFile $file): string
    {
        $dir = $this->directory();
        File::ensureDirectoryExists($dir);

        $ext = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'bin');
        $base = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $base = Str::slug($base) ?: 'timeline';
        $savedName = $base.'_'.uniqid().'.'.$ext;
        $file->move($dir, $savedName);

        return $savedName;
    }

    protected function deleteFile(?string $fileName): void
    {
        if (! $fileName) {
            return;
        }

        $path = $this->absolutePath($fileName);
        if (File::isFile($path)) {
            File::delete($path);
        }
    }
}
