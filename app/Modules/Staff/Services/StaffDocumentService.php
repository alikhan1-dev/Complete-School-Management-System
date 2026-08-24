<?php

namespace App\Modules\Staff\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use InvalidArgumentException;

/**
 * CI admin/Staff::download + doc_delete + Staff_model::doc_delete.
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
