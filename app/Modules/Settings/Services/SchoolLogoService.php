<?php

namespace App\Modules\Settings\Services;

use App\Modules\Settings\Models\SchSetting;
use App\Modules\Shared\Services\SchoolContext;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;

/**
 * CI Schsettings logo uploads (ajax_editlogo / admin_logo / admin_small_logo / applogo).
 * SaaS storage quota deferred.
 */
class SchoolLogoService
{
    public const TYPES = [
        'image' => [
            'column' => 'image',
            'dir' => 'uploads/school_content/logo',
        ],
        'admin_logo' => [
            'column' => 'admin_logo',
            'dir' => 'uploads/school_content/admin_logo',
        ],
        'admin_small_logo' => [
            'column' => 'admin_small_logo',
            'dir' => 'uploads/school_content/admin_small_logo',
        ],
        'app_logo' => [
            'column' => 'app_logo',
            'dir' => 'uploads/school_content/logo/app_logo',
        ],
        'admin_login_page_background' => [
            'column' => 'admin_login_page_background',
            'dir' => 'uploads/school_content/login_image',
        ],
        'user_login_page_background' => [
            'column' => 'user_login_page_background',
            'dir' => 'uploads/school_content/login_image',
        ],
    ];

    public function __construct(protected SchoolContext $school)
    {
    }

    public function current(): ?SchSetting
    {
        return SchSetting::query()->orderBy('id')->first();
    }

    /**
     * CI media_storage::fileupload — stores basename only in sch_settings.
     *
     * @return array{ok:bool,error?:array<string,string>,message?:string}
     */
    public function upload(string $type, int $id, ?UploadedFile $file): array
    {
        if (! isset(self::TYPES[$type])) {
            return [
                'ok' => false,
                'error' => ['file' => '<p>Invalid logo type.</p>'],
            ];
        }

        $validationError = $this->validateUpload($file);
        if ($validationError !== null) {
            return [
                'ok' => false,
                'error' => [
                    'file' => '<p>'.$validationError.'</p>',
                    'validate_storage' => '',
                ],
            ];
        }

        /** @var UploadedFile $file */
        $row = SchSetting::query()->where('id', $id)->first()
            ?? SchSetting::query()->orderBy('id')->first();

        if ($row === null) {
            return [
                'ok' => false,
                'error' => ['exception' => 'School settings row was not found.'],
                'message' => 'An unexpected error occurred. Please try again.',
            ];
        }

        $meta = self::TYPES[$type];
        $column = $meta['column'];
        $previous = (string) ($row->{$column} ?? '');
        $savedName = $this->store($file, $meta['dir']);

        if ($previous !== '') {
            $this->deleteStored($previous, $meta['dir']);
        }

        $row->{$column} = $savedName;
        $row->save();
        $this->school->clearCache();

        return [
            'ok' => true,
            'message' => $type === 'app_logo'
                ? __('system.update_message')
                : __('system.success_message'),
        ];
    }

    /**
     * CI Schsettings::add_admin_login_background — logo_type admin_logo vs user.
     *
     * @return array{ok:bool,error?:array<string,string>,message?:string}
     */
    public function uploadLoginBackground(int $id, string $logoType, ?UploadedFile $file): array
    {
        $type = $logoType === 'admin_logo'
            ? 'admin_login_page_background'
            : 'user_login_page_background';

        return $this->upload($type, $id, $file);
    }

    public function publicUrl(string $type, ?string $filename): string
    {
        $dir = self::TYPES[$type]['dir'] ?? 'uploads/school_content/logo';
        $placeholderDir = str_contains($dir, 'login_image')
            ? 'uploads/school_content/login_image'
            : 'uploads/school_content/logo';

        if ($filename === null || trim($filename) === '') {
            return asset($placeholderDir.'/images.png');
        }

        return asset($dir.'/'.basename(str_replace('\\', '/', $filename)));
    }

    protected function store(UploadedFile $file, string $relativeDir): string
    {
        $dir = public_path($relativeDir);
        File::ensureDirectoryExists($dir);

        $original = basename((string) $file->getClientOriginalName());
        $saved = time().'-'.uniqid((string) random_int(1000, 9999), false).'!'.$original;
        $file->move($dir, $saved);

        return $saved;
    }

    protected function deleteStored(string $filename, string $relativeDir): void
    {
        $name = basename(str_replace('\\', '/', $filename));
        if ($name === '' || $name === 'images.png') {
            return;
        }

        $path = public_path($relativeDir.DIRECTORY_SEPARATOR.$name);
        if (File::isFile($path)) {
            File::delete($path);
        }
    }

    /**
     * CI Schsettings::handle_upload (extension jpg/jpeg/png; MIME allows gif quirk).
     */
    protected function validateUpload(?UploadedFile $file): ?string
    {
        if ($file === null || ! $file->isValid()) {
            return __('system.logo_file_is_required');
        }

        $extension = strtolower((string) $file->getClientOriginalExtension());
        if (! in_array($extension, ['jpg', 'jpeg', 'png'], true)) {
            return __('system.extension_not_allowed');
        }

        $mime = (string) $file->getMimeType();
        if (! in_array($mime, ['image/gif', 'image/jpeg', 'image/png'], true)) {
            return __('system.file_type_not_allowed');
        }

        if ($file->getSize() > 1024000) {
            return __('system.file_size_shoud_be_less_than').' 1MB';
        }

        return null;
    }
}
