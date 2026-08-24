<?php

namespace App\Modules\Staff\Services;

use App\Modules\Staff\Models\StaffTimeline;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * CI admin/Timeline staff methods + Timeline_model staff helpers.
 * Attachments: public/uploads/staff_timeline/
 */
class StaffTimelineService
{
    /**
     * CI Timeline_model::getStaffTimeline.
     *
     * @return Collection<int, StaffTimeline>
     */
    public function listFor(int $staffId, bool $visibleOnly = false): Collection
    {
        $query = StaffTimeline::query()
            ->where('staff_id', $staffId)
            ->orderBy('timeline_date')
            ->orderBy('id');

        if ($visibleOnly) {
            $query->where('status', 'yes');
        }

        return $query->get();
    }

    public function find(int $id): ?StaffTimeline
    {
        return StaffTimeline::query()->find($id);
    }

    /**
     * @param  array{title:string,timeline_date:string,description?:string,status?:string}  $data
     */
    public function create(int $staffId, array $data, ?UploadedFile $file = null): StaffTimeline
    {
        $document = '';
        if ($file) {
            $document = $this->storeFile($file);
        }

        return StaffTimeline::query()->create([
            'staff_id' => $staffId,
            'title' => $data['title'],
            'timeline_date' => $data['timeline_date'],
            'description' => (string) ($data['description'] ?? ''),
            'document' => $document,
            'status' => ($data['status'] ?? '') === 'yes' ? 'yes' : '',
            'date' => now()->toDateString(),
        ]);
    }

    /**
     * @param  array{title:string,timeline_date:string,description?:string,status?:string}  $data
     */
    public function update(StaffTimeline $row, array $data, ?UploadedFile $file = null): StaffTimeline
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

    public function delete(StaffTimeline $row): void
    {
        $this->deleteFile((string) $row->document);
        $row->delete();
    }

    public function absolutePath(string $fileName): string
    {
        return $this->directory().DIRECTORY_SEPARATOR.basename($fileName);
    }

    public function directory(): string
    {
        return public_path('uploads/staff_timeline');
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
