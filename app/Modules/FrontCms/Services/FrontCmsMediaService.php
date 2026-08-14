<?php

namespace App\Modules\FrontCms\Services;

use App\Modules\FrontCms\Models\CmsMediaGallery;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * CI cms_media_model + admin/front/Media persist.
 * Live YouTube oEmbed thumbnail fetch deferred.
 * SaaS storage quota deferred.
 */
class FrontCmsMediaService
{
    public const PER_PAGE = 30;

    public const MEDIA_TYPES = [
        'image/jpeg' => 'Image',
        'video' => 'Video',
        'text/plain' => 'Text',
        'application/zip' => 'Zip',
        'application/x-rar' => 'Rar',
        'application/pdf' => 'Pdf',
        'application/msword' => 'Word',
        'application/vnd.ms-excel' => 'Excel',
        'other' => 'Other',
    ];

    public function directory(): string
    {
        return public_path('uploads/gallery/media');
    }

    public function thumbDirectory(): string
    {
        return public_path('uploads/gallery/media/thumb');
    }

    /**
     * @return array{extensions: list<string>, mimes: list<string>, max_bytes: int}
     */
    public function uploadRules(): array
    {
        $row = DB::table('filetypes')->orderBy('id')->first();
        $extensions = [];
        $mimes = [];
        $maxBytes = 10485760;

        if ($row) {
            $extensions = array_values(array_filter(array_map(
                fn ($ext) => strtolower(ltrim(trim($ext), '.')),
                explode(',', (string) ($row->image_extension ?? '').','.(string) ($row->file_extension ?? ''))
            )));
            $mimes = array_values(array_filter(array_map(
                fn ($mime) => strtolower(trim($mime)),
                explode(',', (string) ($row->image_mime ?? '').','.(string) ($row->file_mime ?? ''))
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

    public function countAll(?string $keyword, ?string $fileType): int
    {
        return $this->filteredQuery($keyword, $fileType)->count();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function page(?string $keyword, ?string $fileType, int $page): array
    {
        $page = max(1, $page);
        $start = ($page - 1) * self::PER_PAGE;

        return $this->filteredQuery($keyword, $fileType)
            ->orderByDesc('id')
            ->offset($start)
            ->limit(self::PER_PAGE)
            ->get()
            ->map(fn ($row) => $row->toArray())
            ->all();
    }

    public function find(int $id): ?array
    {
        $row = CmsMediaGallery::query()->where('id', $id)->first();

        return $row?->toArray();
    }

    /**
     * @param  list<UploadedFile>  $files
     * @return array{ok: bool, msg: string}
     */
    public function storeFiles(array $files): array
    {
        $rules = $this->uploadRules();
        foreach ($files as $file) {
            $error = $this->validateFile($file, $rules);
            if ($error !== null) {
                return ['ok' => false, 'msg' => $error];
            }
        }

        File::ensureDirectoryExists($this->directory());
        File::ensureDirectoryExists($this->thumbDirectory());

        foreach ($files as $file) {
            $this->storeUploaded($file);
        }

        return ['ok' => true, 'msg' => 'Record saved successfully.'];
    }

    public function storeVideoUrl(string $url): array
    {
        $url = trim($url);
        if (! $this->isYoutubeUrl($url)) {
            return ['ok' => false, 'msg' => 'Please Fill Correct Youtube Video Link'];
        }

        CmsMediaGallery::query()->create([
            'image' => '',
            'img_name' => '',
            'file_type' => 'video',
            'file_size' => '0',
            'thumb_name' => '',
            'thumb_path' => 'uploads/gallery/youtube_video/thumb/',
            'dir_path' => 'uploads/gallery/youtube_video/',
            'vid_url' => $url,
            'vid_title' => $url,
        ]);

        return ['ok' => true, 'msg' => 'File Upload Successfully'];
    }

    public function delete(int $id): bool
    {
        $row = $this->find($id);
        if ($row === null) {
            return false;
        }

        $deleted = CmsMediaGallery::query()->where('id', $id)->delete() > 0;
        if ($deleted) {
            $this->unlinkStored((string) ($row['dir_path'] ?? ''), (string) ($row['img_name'] ?? ''));
            $this->unlinkStored((string) ($row['thumb_path'] ?? ''), (string) ($row['img_name'] ?? ''));
            $this->unlinkStored((string) ($row['thumb_path'] ?? ''), (string) ($row['thumb_name'] ?? ''));
        }

        return $deleted;
    }

    public function publicUrl(string $relative): string
    {
        $relative = ltrim(str_replace('\\', '/', $relative), './');

        return asset($relative);
    }

    public function fileview(string $name): string
    {
        $parts = explode('!', $name);
        if (count($parts) > 1) {
            return (string) end($parts);
        }

        return $name;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    public function tileHtml(array $row, bool $isGallery): string
    {
        $id = (int) $row['id'];
        $type = (string) ($row['file_type'] ?? '');
        $isImage = in_array($type, ['image/png', 'image/jpeg', 'image/gif', 'image/jpg'], true) ? '1' : '0';
        $imgName = (string) ($row['img_name'] ?? '');
        $thumb = trim((string) ($row['thumb_path'] ?? '').(string) ($row['thumb_name'] ?? ''), '/');
        $full = trim((string) ($row['dir_path'] ?? '').$imgName, '/');
        $label = $type === 'video' ? (string) ($row['vid_title'] ?? '') : $this->fileview($imgName);
        $src = $thumb !== '' ? $this->publicUrl($thumb) : $this->publicUrl($full);
        $dataImg = $full !== '' ? $this->publicUrl($full) : (string) ($row['vid_url'] ?? '');

        $html = "<div class='col-lg-2 col-sm-4 col-md-3 col-xs-6 img_div_modal image_div div_record_{$id}'>";
        $html .= "<div class='fadeoverlay'><div class='fadeheight'>";
        $html .= "<img data-fid='{$id}' data-content_type='".e($type)."' data-content_name='".e($this->fileview($imgName))."' data-is_image='{$isImage}' data-vid_url='".e((string) ($row['vid_url'] ?? ''))."' data-img='".e($dataImg)."' data-thumb_img='".e($src)."' src='".e($src)."'>";
        $html .= '</div>';
        if (! $isGallery) {
            $html .= "<div class='overlay3'><a href='#' class='uploadclosebtn' data-record_id='{$id}'>Delete</a></div>";
        }
        $html .= '</div>';
        $html .= "<p class='fadeoverlay-para'>".e($label).'</p></div>';

        return $html;
    }

    public function paginationHtml(int $total, int $page): string
    {
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        if ($pages < 2) {
            return '';
        }

        $html = '<ul class="pagination">';
        for ($i = 1; $i <= $pages; $i++) {
            if ($i === $page) {
                $html .= "<li class='active'><a href='#'>{$i}</a></li>";
            } else {
                $html .= "<li><a href='#' data-ci-pagination-page='{$i}'>{$i}</a></li>";
            }
        }
        $html .= '</ul>';

        return $html;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<\App\Modules\FrontCms\Models\CmsMediaGallery>
     */
    protected function filteredQuery(?string $keyword, ?string $fileType)
    {
        $keyword = trim((string) $keyword);
        $fileType = trim((string) $fileType);

        return CmsMediaGallery::query()
            ->when($keyword !== '', function ($query) use ($keyword) {
                $query->where(function ($inner) use ($keyword) {
                    $inner->where('img_name', 'like', '%'.$keyword.'%')
                        ->orWhere('vid_title', 'like', '%'.$keyword.'%');
                });
            })
            ->when($fileType !== '', fn ($query) => $query->where('file_type', 'like', '%'.$fileType.'%'));
    }

    /**
     * @param  array{extensions: list<string>, mimes: list<string>, max_bytes: int}  $rules
     */
    protected function validateFile(UploadedFile $file, array $rules): ?string
    {
        $ext = strtolower((string) $file->getClientOriginalExtension());
        $mime = strtolower((string) ($file->getMimeType() ?: $file->getClientMimeType()));
        $size = (int) $file->getSize();

        if ($rules['extensions'] !== [] && ! in_array($ext, $rules['extensions'], true)) {
            return 'Extension Not Allowed';
        }
        if ($rules['mimes'] !== [] && ! in_array($mime, $rules['mimes'], true)) {
            return 'Extension Not Allowed';
        }
        if ($size > $rules['max_bytes']) {
            return 'File Size Should Be Less Than '.number_format($rules['max_bytes'] / 1048576, 2).' MB';
        }

        return null;
    }

    protected function storeUploaded(UploadedFile $file): int
    {
        $mime = (string) ($file->getMimeType() ?: $file->getClientMimeType() ?: 'application/octet-stream');
        $size = (string) $file->getSize();
        $original = basename((string) $file->getClientOriginalName());
        $saved = time().'-'.uniqid((string) random_int(1000, 9999), false).'!'.$original;
        $file->move($this->directory(), $saved);
        File::copy($this->directory().DIRECTORY_SEPARATOR.$saved, $this->thumbDirectory().DIRECTORY_SEPARATOR.$saved);

        $row = CmsMediaGallery::query()->create([
            'image' => $original,
            'img_name' => $saved,
            'file_type' => $mime,
            'file_size' => $size,
            'thumb_name' => $saved,
            'thumb_path' => 'uploads/gallery/media/thumb/',
            'dir_path' => 'uploads/gallery/media/',
            'vid_url' => '',
            'vid_title' => '',
        ]);

        return (int) $row->id;
    }

    protected function unlinkStored(string $dir, string $name): void
    {
        $name = basename(str_replace('\\', '/', $name));
        if ($name === '') {
            return;
        }
        $relative = trim(str_replace('\\', '/', $dir), '/').'/'.$name;
        $path = public_path($relative);
        if (File::isFile($path)) {
            File::delete($path);
        }
    }

    protected function isYoutubeUrl(string $url): bool
    {
        return str_contains($url, 'youtube.com') || str_contains($url, 'youtu.be');
    }
}
