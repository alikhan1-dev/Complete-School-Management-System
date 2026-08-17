<?php

namespace App\Modules\Content\Services;

use App\Modules\Content\Models\ContentType;
use App\Modules\Content\Models\UploadContent;
use App\Modules\Shared\Services\SchoolContext;
use App\Modules\Staff\Models\Staff;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\Mime\MimeTypes;

/**
 * CI Uploadcontent_model + admin/Content upload persist.
 * Live YouTube oEmbed thumbnail fetch and SaaS storage quota deferred.
 */
class UploadContentService
{
    public const DIR_PATH = 'uploads/school_content/material/media/';

    public const THUMB_PATH = 'uploads/school_content/material/media/thumb/';

    public const PER_PAGE = 12;

    public const THUMB_WIDTH = 100;

    public const THUMB_HEIGHT = 100;

    public function __construct(protected SchoolContext $school)
    {
    }

    /**
     * @return Collection<int, ContentType>
     */
    public function contentTypes(): Collection
    {
        return ContentType::query()->orderBy('id')->get();
    }

    /**
     * @return array{number: int, file_size: int}
     */
    public function totalRecord(?Staff $staff): array
    {
        $base = UploadContent::query();
        if ($staff !== null && ! $staff->isSuperAdmin()) {
            $base->where('upload_by', (int) $staff->id);
        }

        return [
            'number' => (int) (clone $base)->count(),
            'file_size' => (int) (clone $base)->sum('file_size'),
        ];
    }

    public function find(int $id): ?UploadContent
    {
        return UploadContent::query()->find($id);
    }

    /**
     * @return array{count: int, rows: Collection<int, object>}
     */
    public function page(?Staff $staff, string $search, int $start, int $limit): array
    {
        $query = $this->listQuery($staff, $search);
        $count = (int) (clone $query)->count();
        $rows = $query->orderByDesc('upload_contents.id')
            ->offset(max(0, $start))
            ->limit($limit)
            ->get();

        return [
            'count' => $count,
            'rows' => $rows,
        ];
    }

    /**
     * @param  list<UploadedFile>  $files
     * @return array{ok: bool, errors: array<string, string>, msg: string}
     */
    public function storeFiles(array $files, int $contentTypeId, int $staffId): array
    {
        $rules = $this->uploadRules();
        foreach ($files as $file) {
            $error = $this->validateFile($file, $rules);
            if ($error !== null) {
                return ['ok' => false, 'errors' => ['file' => $this->p($error)], 'msg' => ''];
            }
        }

        File::ensureDirectoryExists($this->directory());
        File::ensureDirectoryExists($this->thumbDirectory());

        foreach ($files as $file) {
            $this->storeUploaded($file, $contentTypeId, $staffId);
        }

        return ['ok' => true, 'errors' => [], 'msg' => (string) __('system.success_message')];
    }

    /**
     * Persist a YouTube URL without live oEmbed (CI curls oEmbed; deferred).
     *
     * @return array{ok: bool, errors: array<string, string>, msg: string}
     */
    public function storeVideoUrl(string $url, int $contentTypeId, int $staffId): array
    {
        $url = trim($url);
        if ($url === '') {
            return [
                'ok' => false,
                'errors' => ['url' => $this->p('The URL field is required.')],
                'msg' => '',
            ];
        }
        if (! $this->isYoutubeUrl($url)) {
            return [
                'ok' => false,
                'errors' => ['file' => $this->p((string) __('system.invalid_url_or_try_again'))],
                'msg' => '',
            ];
        }

        UploadContent::query()->create([
            'content_type_id' => $contentTypeId,
            'real_name' => $url,
            'vid_url' => $url,
            'vid_title' => $url,
            'img_name' => '',
            'file_type' => 'video',
            'mime_type' => '',
            'file_size' => '0',
            'thumb_name' => '',
            'thumb_path' => self::THUMB_PATH,
            'dir_path' => self::DIR_PATH,
            'upload_by' => $staffId,
            'created_at' => now(),
        ]);

        return ['ok' => true, 'errors' => [], 'msg' => (string) __('system.file_upload_successfully')];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{ok: bool, errors: array<string, string>}
     */
    public function update(int $id, array $input): array
    {
        $errors = [];
        $name = trim((string) ($input['name'] ?? ''));
        $contentType = trim((string) ($input['content_type'] ?? ''));
        if ($name === '') {
            $errors['name'] = $this->p('The File Name field is required.');
        }
        if ($contentType === '') {
            $errors['content_type'] = $this->p('The Content Type field is required.');
        }
        if ($errors !== []) {
            return ['ok' => false, 'errors' => $errors];
        }

        $row = $this->find($id);
        if ($row === null) {
            return ['ok' => false, 'errors' => ['name' => $this->p((string) __('system.something_went_wrong'))]];
        }

        $row->real_name = $name;
        $row->content_type_id = (int) $contentType;
        $row->save();

        return ['ok' => true, 'errors' => []];
    }

    public function delete(int $id): bool
    {
        $row = $this->find($id);
        if ($row === null) {
            return false;
        }

        $deleted = UploadContent::query()->where('id', $id)->delete() > 0;
        if ($deleted) {
            $this->unlinkStored((string) $row->dir_path, (string) $row->img_name);
            $this->unlinkStored((string) $row->thumb_path, (string) $row->thumb_name);
        }

        return $deleted;
    }

    /**
     * @param  list<int>  $ids
     */
    public function deleteMany(array $ids): bool
    {
        $ids = array_values(array_filter(array_map('intval', $ids)));
        if ($ids === []) {
            return false;
        }

        $rows = UploadContent::query()->whereIn('id', $ids)->get();
        $deleted = UploadContent::query()->whereIn('id', $ids)->delete() > 0;
        if ($deleted) {
            foreach ($rows as $row) {
                $this->unlinkStored((string) $row->dir_path, (string) $row->img_name);
                $this->unlinkStored((string) $row->thumb_path, (string) $row->thumb_name);
            }
        }

        return $deleted;
    }

    public function download(int $id): BinaryFileResponse
    {
        $row = $this->find($id);
        abort_if($row === null, 404);
        $name = basename(str_replace('\\', '/', (string) $row->img_name));
        abort_unless($name !== '' && ! str_contains($name, '..'), 404);
        $relative = trim(str_replace('\\', '/', (string) $row->dir_path), '/').'/'.$name;
        $path = public_path($relative);
        abort_unless(is_file($path), 404);

        return response()->download($path, $name);
    }

    public function formatFileSize(int|float|string|null $bytes): string
    {
        $bytes = (float) $bytes;
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2).' GB';
        }
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2).' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2).' KB';
        }
        if ($bytes > 1) {
            return $bytes.' bytes';
        }
        if ($bytes == 1) {
            return $bytes.' byte';
        }

        return '0 bytes';
    }

    public function staffFullName(?string $firstname, ?string $lastname, ?string $employeeId): string
    {
        $firstname = (string) $firstname;
        $lastname = (string) $lastname;
        $name = $lastname === '' ? $firstname : trim($firstname.' '.$lastname);

        return $name.' ('.$employeeId.')';
    }

    public function formatCreatedAt(mixed $value): string
    {
        if ($value === null || $value === '' || $value === '0000-00-00 00:00:00') {
            return '';
        }

        $format = $this->school->dateFormat() ?: 'd/m/Y';

        return Carbon::parse((string) $value)->format($format.' H:i:s');
    }

    public function fileview(string $name): string
    {
        $parts = explode('!', $name);
        if (count($parts) > 1) {
            return (string) end($parts);
        }

        return $name;
    }

    public function fileIconUrl(object $row): string
    {
        $type = (string) ($row->file_type ?? '');
        $backend = fn (string $file) => asset('backend/images/'.$file);

        if (in_array($type, ['xls', 'xlsx'], true)) {
            return $backend('excelicon.png');
        }
        if (in_array($type, ['ppt', 'pptx'], true)) {
            return $backend('pptxicon.png');
        }
        if (in_array($type, ['doc', 'docx'], true)) {
            return $backend('wordicon.png');
        }
        if (in_array($type, ['csv', 'text/plain', 'txt'], true)) {
            return $backend('txticon.png');
        }
        if ($type === 'pdf') {
            return $backend('pdficon.png');
        }
        if (in_array($type, ['zip', 'rar'], true)) {
            return $backend('zipicon.png');
        }
        if (in_array($type, ['3g2', '3gp', 'mp4', 'm4a', 'f4v', 'flv', 'webm'], true)) {
            return $backend('video-icon.png');
        }
        if ($this->isImageOrVideoThumb($type)) {
            $thumb = trim((string) ($row->thumb_path ?? '').(string) ($row->thumb_name ?? ''), '/');
            if ($thumb !== '') {
                return asset($thumb);
            }
        }

        return $backend('docsicon.png');
    }

    /**
     * CI getuploaddata pagination HTML (JS reads li.unactive[p]).
     */
    public function paginationHtml(int $count, int $curPage): string
    {
        $noOfPaginations = (int) ceil($count / self::PER_PAGE);
        if ($curPage >= 7) {
            $startLoop = $curPage - 3;
            if ($noOfPaginations > $curPage + 3) {
                $endLoop = $curPage + 3;
            } elseif ($curPage <= $noOfPaginations && $curPage > $noOfPaginations - 6) {
                $startLoop = $noOfPaginations - 6;
                $endLoop = $noOfPaginations;
            } else {
                $endLoop = $noOfPaginations;
            }
        } else {
            $startLoop = 1;
            $endLoop = $noOfPaginations > 7 ? 7 : $noOfPaginations;
        }

        $html = "<ul class='pagination'>";
        if ($curPage > 1) {
            $html .= "<li p='1' class='page-item unactive'><a class='page-link' href='javascript:void(0);'><i class='fa fa-angle-double-left'></i></a></li>";
            $pre = $curPage - 1;
            $html .= "<li p='{$pre}' class='page-item unactive'><a class='page-link' href='javascript:void(0);'>  ".__('system.previous').'</a></li>';
        } else {
            $html .= "<li p='1' class='page-item disabled'><a class='page-link' href='javascript:void(0);'><i class='fa fa-angle-double-left'></i></a></li>";
            $html .= "<li class='page-item disabled'><a class='page-link' href='javascript:void(0);'>".__('system.previous').'</a></li>';
        }
        for ($i = $startLoop; $i <= $endLoop; $i++) {
            if ($curPage === $i) {
                $html .= "<li p='{$i}' class = 'page-item active' ><a class='page-link' href='javascript:void(0);'>{$i}</a></li>";
            } else {
                $html .= "<li p='{$i}' class='page-item unactive'><a class='page-link' href='javascript:void(0);'>{$i}</a></li>";
            }
        }
        if ($curPage < $noOfPaginations) {
            $nex = $curPage + 1;
            $html .= "<li p='{$nex}' class='page-item unactive'><a class='page-link' href='javascript:void(0);'>".__('system.next').' </a></li>';
            $html .= "<li p='{$noOfPaginations}' class='page-item unactive'><a class='page-link' href='javascript:void(0);'><i class='fa fa-angle-double-right'></i></a></li>";
        } else {
            $html .= "<li class='page-item disabled'><a class='page-link' href='javascript:void(0);'>".__('system.next').'</a></li>';
            $html .= "<li p='{$noOfPaginations}' class='page-item disabled'><a class='page-link' href='javascript:void(0);'><i class='fa fa-angle-double-right'></i></a></li>";
        }

        return $html.'</ul>';
    }

    public function currentStaff(): ?Staff
    {
        $staff = Auth::guard('staff')->user();

        return $staff instanceof Staff ? $staff : null;
    }

    /**
     * @return array{extensions: list<string>, max_bytes: int}
     */
    public function uploadRules(): array
    {
        $row = DB::table('filetypes')->orderBy('id')->first();
        $extensions = [];
        $maxBytes = 10485760;

        if ($row) {
            $extensions = array_values(array_filter(array_map(
                fn ($ext) => strtolower(ltrim(trim($ext), '.')),
                explode(',', (string) ($row->image_extension ?? '').','.(string) ($row->file_extension ?? ''))
            )));
            $bytes = (int) ($row->file_size ?? 0);
            if ($bytes > 0) {
                $maxBytes = $bytes;
            }
        }

        return [
            'extensions' => $extensions,
            'max_bytes' => $maxBytes,
        ];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<\App\Modules\Content\Models\UploadContent>
     */
    protected function listQuery(?Staff $staff, string $search)
    {
        $query = UploadContent::query()
            ->select([
                'upload_contents.*',
                'staff.name as staff_name',
                'staff.surname as surname',
                'staff.employee_id as employee_id',
                'content_types.name as content_type',
            ])
            ->join('staff', 'staff.id', '=', 'upload_contents.upload_by')
            ->join('content_types', 'content_types.id', '=', 'upload_contents.content_type_id');

        $search = trim($search);
        if ($search !== '') {
            $like = '%'.addcslashes($search, '%_\\').'%';
            $query->where(function ($inner) use ($like) {
                $inner->where('upload_contents.img_name', 'like', $like)
                    ->orWhere('upload_contents.vid_title', 'like', $like)
                    ->orWhere('upload_contents.real_name', 'like', $like)
                    ->orWhere('content_types.name', 'like', $like)
                    ->orWhere('staff.name', 'like', $like)
                    ->orWhere('staff.surname', 'like', $like)
                    ->orWhere('staff.employee_id', 'like', $like);
            });
        }

        if ($staff !== null && ! $staff->isSuperAdmin()) {
            $query->where('upload_contents.upload_by', (int) $staff->id);
        }

        return $query;
    }

    /**
     * @param  array{extensions: list<string>, max_bytes: int}  $rules
     */
    protected function validateFile(UploadedFile $file, array $rules): ?string
    {
        $ext = strtolower((string) $file->getClientOriginalExtension());
        $size = (int) $file->getSize();

        if ($rules['extensions'] !== [] && ! in_array($ext, $rules['extensions'], true)) {
            return (string) __('system.file_type_not_allowed');
        }
        if ($size > $rules['max_bytes']) {
            return (string) __('system.file_size_shoud_be_less_than').number_format($rules['max_bytes'] / 1048576, 2).' MB';
        }

        return null;
    }

    protected function storeUploaded(UploadedFile $file, int $contentTypeId, int $staffId): int
    {
        $mime = (string) ($file->getMimeType() ?: $file->getClientMimeType() ?: 'application/octet-stream');
        $original = basename((string) $file->getClientOriginalName());
        $saved = time().'-'.uniqid((string) random_int(1000, 9999), false).'!'.$original;
        $file->move($this->directory(), $saved);
        $storedPath = $this->directory().DIRECTORY_SEPARATOR.$saved;
        $size = is_file($storedPath) ? (string) filesize($storedPath) : (string) $file->getSize();
        $thumbName = $this->maybeWriteThumb($storedPath, $saved, $mime);

        $row = UploadContent::query()->create([
            'content_type_id' => $contentTypeId,
            'real_name' => $original,
            'img_name' => $saved,
            'mime_type' => $mime,
            'file_type' => $this->findFileType($mime, strtolower((string) pathinfo($original, PATHINFO_EXTENSION))),
            'file_size' => $size,
            'thumb_name' => $thumbName,
            'thumb_path' => self::THUMB_PATH,
            'dir_path' => self::DIR_PATH,
            'vid_url' => '',
            'vid_title' => '',
            'upload_by' => $staffId,
            'created_at' => now(),
        ]);

        return (int) $row->id;
    }

    protected function maybeWriteThumb(string $storedPath, string $savedName, string $mime): string
    {
        $mime = strtolower($mime);
        if (! in_array($mime, ['image/jpeg', 'image/png', 'image/gif'], true) || ! is_file($storedPath)) {
            return '';
        }

        $uniqueEnd = strpos($savedName, '!');
        if ($uniqueEnd === false) {
            return '';
        }
        $unique = substr($savedName, 0, $uniqueEnd + 1);
        $original = substr($savedName, $uniqueEnd + 1);
        $thumbName = $unique.'thumb_'.$original;
        File::ensureDirectoryExists($this->thumbDirectory());
        $dest = $this->thumbDirectory().DIRECTORY_SEPARATOR.$thumbName;

        try {
            $info = getimagesize($storedPath);
            if ($info === false) {
                return '';
            }
            $originalWidth = (int) $info[0];
            $originalHeight = (int) $info[1];
            if ($originalWidth < 1 || $originalHeight < 1) {
                return '';
            }
            if ($originalWidth > $originalHeight) {
                $newWidth = self::THUMB_WIDTH;
                $newHeight = (int) ($originalHeight * $newWidth / $originalWidth);
            } else {
                $newHeight = self::THUMB_HEIGHT;
                $newWidth = (int) ($originalWidth * $newHeight / $originalHeight);
            }
            $destX = (int) ((self::THUMB_WIDTH - $newWidth) / 2);
            $destY = (int) ((self::THUMB_HEIGHT - $newHeight) / 2);

            $create = match ((int) $info[2]) {
                IMAGETYPE_GIF => 'imagecreatefromgif',
                IMAGETYPE_JPEG => 'imagecreatefromjpeg',
                IMAGETYPE_PNG => 'imagecreatefrompng',
                default => null,
            };
            $save = match ((int) $info[2]) {
                IMAGETYPE_GIF => 'imagegif',
                IMAGETYPE_JPEG => 'imagejpeg',
                IMAGETYPE_PNG => 'imagepng',
                default => null,
            };
            if ($create === null || $save === null || ! function_exists($create) || ! function_exists($save)) {
                return '';
            }
            $old = $create($storedPath);
            $new = imagecreatetruecolor(self::THUMB_WIDTH, self::THUMB_HEIGHT);
            imagecopyresized($new, $old, $destX, $destY, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);
            $save($new, $dest);
            imagedestroy($old);
            imagedestroy($new);

            return $thumbName;
        } catch (\Throwable) {
            return '';
        }
    }

    protected function findFileType(string $mime, string $fallbackExt): string
    {
        $mime = strtolower(trim($mime));
        $fallbackExt = strtolower(ltrim($fallbackExt, '.'));
        if ($mime === '') {
            return $fallbackExt;
        }
        $exts = MimeTypes::getDefault()->getExtensions($mime);
        if ($exts !== []) {
            return $exts[0];
        }

        return $fallbackExt !== '' ? $fallbackExt : $mime;
    }

    protected function directory(): string
    {
        return public_path(trim(self::DIR_PATH, '/'));
    }

    protected function thumbDirectory(): string
    {
        return public_path(trim(self::THUMB_PATH, '/'));
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

    protected function isImageOrVideoThumb(string $type): bool
    {
        return in_array($type, [
            'video', 'gif', 'jpeg', 'jpg', 'jpe', 'jp2', 'j2k', 'jpf', 'jpg2', 'jpx',
            'jpm', 'mj2', 'mjp2', 'png', 'tiff', 'tif',
        ], true);
    }

    protected function p(string $message): string
    {
        return '<p>'.$message.'</p>';
    }
}
