<?php

namespace App\Modules\Certificates\Services;

use App\Modules\Certificates\Models\TransferCertificateField;
use App\Modules\Certificates\Models\TransferCertificateSetting;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * CI Transfercertificate settings — header/footer, signatures, serial, field toggles.
 * Deferred: download/print TC, verify, prepare, custom-field student edit, mPDF, SaaS.
 */
class TransferCertificateSettingsService
{
    /** Allowed signature/image columns on transfer_certificate_settings. */
    public const IMAGE_FIELDS = [
        'header_image',
        'class_teacher_signature',
        'checked_by',
        'signature_of_principle',
    ];

    public function __construct(protected TransferCertificateDocumentService $documents)
    {
    }

    public function settings(): TransferCertificateSetting
    {
        $row = TransferCertificateSetting::query()->orderBy('id')->first();
        if ($row) {
            return $row;
        }

        return TransferCertificateSetting::query()->create([
            'tc_no_start' => 1,
            'affiliation_no' => '',
            'header_image' => '',
            'footer_content' => '',
            'class_teacher_signature' => '',
            'checked_by' => '',
            'signature_of_principle' => '',
            'create_at' => now()->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * CI get_transfer_certificate_no — next serial to print.
     */
    public function nextTcNumber(): int
    {
        $settings = $this->settings();
        $start = (int) $settings->tc_no_start;
        if ($start <= 0) {
            $start = 1;
        }

        $last = (int) (DB::table('transfer_certificate_no')->orderByDesc('id')->value('tc_no') ?? 0);
        if ($last === 0) {
            return $start;
        }
        if ($start > $last) {
            return $start;
        }

        return $last + 1;
    }

    /**
     * Active TC fields ordered by position (CI getallfields).
     *
     * @return Collection<int, TransferCertificateField>
     */
    public function activeFields(): Collection
    {
        return TransferCertificateField::query()
            ->where('is_active', 1)
            ->orderBy('position')
            ->orderBy('id')
            ->get();
    }

    public function updateHeaderFooter(Request $request): TransferCertificateSetting
    {
        $row = $this->settings();
        $payload = [
            'footer_content' => (string) $request->input('footer_content', ''),
        ];

        if ($request->boolean('remove_header_image')) {
            $this->documents->delete((string) ($row->header_image ?? ''));
            $payload['header_image'] = '';
        } elseif ($request->hasFile('header_image')) {
            $this->documents->delete((string) ($row->header_image ?? ''));
            $payload['header_image'] = $this->documents->store($request->file('header_image'));
        }

        $row->fill($payload);
        $row->save();

        return $row;
    }

    /**
     * CI save_generation_id — serial start + affiliation.
     */
    public function updateSerial(Request $request): TransferCertificateSetting
    {
        $row = $this->settings();
        $tcNoStart = (int) $request->input('tc_no_start');
        $next = $this->nextTcNumber();

        if ($tcNoStart < 1) {
            throw ValidationException::withMessages([
                'tc_no_start' => 'Please enter a valid serial number.',
            ]);
        }

        // CI: start must be unused and >= next printable number.
        $exists = DB::table('transfer_certificate_no')->where('tc_no', $tcNoStart)->exists();
        if ($exists || $tcNoStart < $next) {
            throw ValidationException::withMessages([
                'tc_no_start' => 'Please enter a valid serial number (must be unused and not less than the next TC number).',
            ]);
        }

        $row->fill([
            'tc_no_start' => $tcNoStart,
            'affiliation_no' => (string) $request->input('affiliation_no', ''),
        ]);
        $row->save();

        return $row;
    }

    public function updateImage(string $field, ?UploadedFile $file, bool $remove = false): TransferCertificateSetting
    {
        if (! in_array($field, self::IMAGE_FIELDS, true)) {
            throw ValidationException::withMessages([
                'field' => 'Invalid image field.',
            ]);
        }

        $row = $this->settings();
        $current = (string) ($row->{$field} ?? '');

        if ($remove) {
            $this->documents->delete($current);
            $row->{$field} = '';
            $row->save();

            return $row;
        }

        if ($file === null) {
            throw ValidationException::withMessages([
                'file' => 'Image file is required.',
            ]);
        }

        $this->documents->delete($current);
        $row->{$field} = $this->documents->store($file);
        $row->save();

        return $row;
    }

    /**
     * Bulk save field status + position (form POST instead of CI AJAX toggles).
     *
     * @param  array<int, array{id:int,status:int,position:int}>  $rows
     */
    public function saveFields(array $rows): void
    {
        $guardianNames = [
            'guardian_relation', 'guardian_name', 'guardian_phone', 'guardian_photo',
            'guardian_occupation', 'guardian_email', 'guardian_address',
        ];
        $guardianStatus = null;

        foreach ($rows as $row) {
            $id = (int) ($row['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }

            $field = TransferCertificateField::query()->where('is_active', 1)->find($id);
            if (! $field) {
                continue;
            }

            $status = ! empty($row['status']) ? 1 : 0;
            $position = max(1, (int) ($row['position'] ?? $field->position));

            $field->status = $status;
            $field->position = $position;
            $field->save();

            if ($field->name === 'if_guardian_is') {
                $guardianStatus = $status;
            }
        }

        // CI editguardianfield — toggling if_guardian_is cascades to related guardian fields.
        if ($guardianStatus !== null) {
            TransferCertificateField::query()
                ->where('is_default', 1)
                ->whereIn('name', $guardianNames)
                ->update(['status' => $guardianStatus]);
        }
    }

    /**
     * @return array<string, ?string>
     */
    public function assetUrls(TransferCertificateSetting $row): array
    {
        $urls = [];
        foreach (self::IMAGE_FIELDS as $field) {
            $urls[$field] = $this->documents->url($row->{$field} ?? null);
        }

        return $urls;
    }

    /**
     * Human label for a field row.
     */
    public function fieldLabel(TransferCertificateField $field): string
    {
        $key = (string) ($field->lang_key ?: $field->name);
        $label = str_replace('_', ' ', $key);

        return ucwords($label);
    }
}
