<?php

namespace App\Modules\Staff\Services;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\File as ValidationFile;
use InvalidArgumentException;

/**
 * CI admin/Staff document fields + download/doc_delete + Staff_model::doc_delete.
 * Files live under public/uploads/staff_documents/{staff_id}/.
 * SaaS storage quota deferred.
 */
class StaffDocumentService
{
    /** @var list<string> */
    public const DOCUMENT_KEYS = [
        'resume',
        'joining_letter',
        'resignation_letter',
        'other_document_file',
    ];

    public function assertAllowedKey(string $docKey): void
    {
        if (! in_array($docKey, self::DOCUMENT_KEYS, true)) {
            throw new InvalidArgumentException('Invalid staff document key.');
        }
    }

    public function filename(object $staff, string $docKey): ?string
    {
        $this->assertAllowedKey($docKey);

        $value = (string) ($staff->{$docKey} ?? '');

        return $value !== '' ? $value : null;
    }

    public function absolutePath(int $staffId, string $fileName): string
    {
        return $this->directory($staffId).DIRECTORY_SEPARATOR.basename($fileName);
    }

    public function directory(int $staffId): string
    {
        return public_path('uploads/staff_documents/'.$staffId);
    }

    public function delete(int $staffId, string $docKey): void
    {
        $this->assertAllowedKey($docKey);

        $staff = DB::table('staff')->where('id', $staffId)->first();
        if ($staff === null) {
            throw new InvalidArgumentException('Staff not found.');
        }

        $fileName = $this->filename($staff, $docKey);
        if ($fileName !== null) {
            $path = $this->absolutePath($staffId, $fileName);
            if (File::isFile($path)) {
                File::delete($path);
            }
        }

        $payload = match ($docKey) {
            'resume' => ['resume' => ''],
            'joining_letter' => ['joining_letter' => ''],
            'resignation_letter' => ['resignation_letter' => ''],
            'other_document_file' => [
                'other_document_name' => '',
                'other_document_file' => '',
            ],
        };

        DB::table('staff')->where('id', $staffId)->update($payload);
    }

    /**
     * @return list<array{key:string,label:string,filename:string,title?:string}>
     */
    public function listForProfile(object $staff): array
    {
        $documents = [];

        foreach (self::DOCUMENT_KEYS as $key) {
            $filename = $this->filename($staff, $key);
            if ($filename === null) {
                continue;
            }

            $entry = [
                'key' => $key,
                'label' => $this->label($key, $staff),
                'filename' => $filename,
            ];

            if ($key === 'other_document_file') {
                $entry['title'] = (string) ($staff->other_document_name ?? '');
            }

            $documents[] = $entry;
        }

        return $documents;
    }

    /**
     * CI Staff::create/edit document uploads (first_doc..fourth_doc).
     *
     * @param  array{
     *     first_doc?: UploadedFile|null,
     *     second_doc?: UploadedFile|null,
     *     third_doc?: UploadedFile|null,
     *     fourth_doc?: UploadedFile|null,
     *     fourth_title?: string|null,
     *     resume?: string|null,
     *     joining_letter?: string|null,
     *     resignation_letter?: string|null,
     *     other_document_name?: string|null,
     *     other_document_file?: string|null
     * }  $uploads
     * @param  array<string, string>  $existing
     * @return array<string, string>
     */
    public function syncFromUploads(int $staffId, array $uploads, array $existing = []): array
    {
        if ($staffId <= 0) {
            throw new InvalidArgumentException('Staff id is required.');
        }

        File::ensureDirectoryExists($this->directory($staffId));

        $resume = $this->resolveSlot(
            $staffId,
            $uploads['first_doc'] ?? null,
            (string) ($uploads['resume'] ?? $existing['resume'] ?? '')
        );
        $joiningLetter = $this->resolveSlot(
            $staffId,
            $uploads['second_doc'] ?? null,
            (string) ($uploads['joining_letter'] ?? $existing['joining_letter'] ?? '')
        );
        $resignationLetter = $this->resolveSlot(
            $staffId,
            $uploads['third_doc'] ?? null,
            (string) ($uploads['resignation_letter'] ?? $existing['resignation_letter'] ?? '')
        );
        $otherFile = $this->resolveSlot(
            $staffId,
            $uploads['fourth_doc'] ?? null,
            (string) ($uploads['other_document_file'] ?? $existing['other_document_file'] ?? '')
        );

        $otherTitle = (string) ($uploads['other_document_name'] ?? $existing['other_document_name'] ?? '');
        if (($uploads['fourth_doc'] ?? null) instanceof UploadedFile) {
            $otherTitle = (string) ($uploads['fourth_title'] ?? '');
        } elseif (array_key_exists('fourth_title', $uploads)) {
            $otherTitle = (string) ($uploads['fourth_title'] ?? $otherTitle);
        }

        if ($otherFile === '') {
            $otherTitle = '';
        }

        return [
            'resume' => $resume,
            'joining_letter' => $joiningLetter,
            'resignation_letter' => $resignationLetter,
            'other_document_name' => $otherTitle,
            'other_document_file' => $otherFile,
        ];
    }

    /**
     * @param  array<string, mixed>  $uploads
     */
    public function shouldSyncUploads(array $uploads): bool
    {
        foreach (['first_doc', 'second_doc', 'third_doc', 'fourth_doc'] as $key) {
            if (($uploads[$key] ?? null) instanceof UploadedFile) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function documentValidationRules(bool $includeHiddenFields = false): array
    {
        $meta = $this->uploadRulesFromFiletypes();
        $fileRule = [
            'nullable',
            'file',
            ValidationFile::types($meta['extensions'])->max($meta['max_kb']),
        ];

        $rules = [
            'first_doc' => $fileRule,
            'second_doc' => $fileRule,
            'third_doc' => $fileRule,
            'fourth_doc' => $fileRule,
            'fourth_title' => ['nullable', 'string', 'max:200'],
        ];

        if ($includeHiddenFields) {
            $rules['resume'] = ['nullable', 'string', 'max:200'];
            $rules['joining_letter'] = ['nullable', 'string', 'max:200'];
            $rules['resignation_letter'] = ['nullable', 'string', 'max:200'];
            $rules['other_document_file'] = ['nullable', 'string', 'max:200'];
        }

        return $rules;
    }

    /**
     * @return array{
     *     first_doc?: UploadedFile|null,
     *     second_doc?: UploadedFile|null,
     *     third_doc?: UploadedFile|null,
     *     fourth_doc?: UploadedFile|null,
     *     fourth_title?: string|null,
     *     resume?: string|null,
     *     joining_letter?: string|null,
     *     resignation_letter?: string|null,
     *     other_document_file?: string|null
     * }
     */
    public function uploadsFromRequest(Request $request): array
    {
        return [
            'first_doc' => $request->file('first_doc'),
            'second_doc' => $request->file('second_doc'),
            'third_doc' => $request->file('third_doc'),
            'fourth_doc' => $request->file('fourth_doc'),
            'fourth_title' => $request->input('fourth_title'),
            'resume' => $request->input('resume'),
            'joining_letter' => $request->input('joining_letter'),
            'resignation_letter' => $request->input('resignation_letter'),
            'other_document_file' => $request->input('other_document_file'),
        ];
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

    protected function resolveSlot(int $staffId, ?UploadedFile $file, string $existing): string
    {
        if (! $file instanceof UploadedFile) {
            return $existing;
        }

        if ($existing !== '') {
            $path = $this->absolutePath($staffId, $existing);
            if (File::isFile($path)) {
                File::delete($path);
            }
        }

        return $this->storeUploadedFile($staffId, $file);
    }

    protected function storeUploadedFile(int $staffId, UploadedFile $file): string
    {
        $ext = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'bin');
        $base = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $base = Str::slug($base) ?: 'document';
        $savedName = $base.'_'.uniqid().'.'.$ext;
        $file->move($this->directory($staffId), $savedName);

        return $savedName;
    }

    protected function label(string $docKey, object $staff): string
    {
        return match ($docKey) {
            'resume' => (string) __('system.resume'),
            'joining_letter' => (string) __('system.joining_letter'),
            'resignation_letter' => (string) __('system.resignation_letter'),
            'other_document_file' => (string) (($staff->other_document_name ?? '') !== ''
                ? $staff->other_document_name
                : __('system.other_documents')),
        };
    }
}
