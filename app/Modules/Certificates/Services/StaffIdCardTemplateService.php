<?php

namespace App\Modules\Certificates\Services;

use App\Modules\Certificates\Models\StaffIdCard;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * CI Staffidcard_model — staff ID card template CRUD.
 * Deferred: TC, enable_staff_role UI, SaaS quota.
 */
class StaffIdCardTemplateService
{
    public function __construct(protected StaffIdCardDocumentService $documents)
    {
    }

    /**
     * @return Collection<int, StaffIdCard>
     */
    public function list(): Collection
    {
        return StaffIdCard::query()->orderBy('id')->get();
    }

    public function find(int $id): StaffIdCard
    {
        return StaffIdCard::query()->findOrFail($id);
    }

    /**
     * @return array<string, mixed>
     */
    public function buildPayload(Request $request, ?StaffIdCard $existing = null): array
    {
        $payload = [
            'title' => (string) $request->input('title'),
            'school_name' => (string) $request->input('school_name'),
            'school_address' => (string) $request->input('address'),
            'header_color' => (string) $request->input('header_color', ''),
            'enable_staff_id' => $request->boolean('is_active_staff_id') ? 1 : 0,
            'enable_staff_department' => $request->boolean('is_active_department') ? 1 : 0,
            'enable_designation' => $request->boolean('is_active_designation') ? 1 : 0,
            'enable_name' => $request->boolean('is_active_staff_name') ? 1 : 0,
            'enable_fathers_name' => $request->boolean('is_active_staff_father_name') ? 1 : 0,
            'enable_mothers_name' => $request->boolean('is_active_staff_mother_name') ? 1 : 0,
            'enable_date_of_joining' => $request->boolean('is_active_date_of_joining') ? 1 : 0,
            'enable_permanent_address' => $request->boolean('is_active_staff_permanent_address') ? 1 : 0,
            'enable_staff_dob' => $request->boolean('is_active_staff_dob') ? 1 : 0,
            'enable_staff_phone' => $request->boolean('is_active_staff_phone') ? 1 : 0,
            'enable_vertical_card' => $request->boolean('enable_vertical_card') ? 1 : 0,
            'enable_staff_barcode' => $request->boolean('enable_staff_barcode') ? 1 : 0,
            'background' => $existing ? (string) ($existing->background ?? '') : '',
            'logo' => $existing ? (string) ($existing->logo ?? '') : '',
            'sign_image' => $existing ? (string) ($existing->sign_image ?? '') : '',
        ];

        if ($existing === null) {
            // CI create never sets enable_staff_role; column is NOT NULL — default off.
            $payload['enable_staff_role'] = 0;
            $payload['status'] = 1;
        }

        $this->applyImageUpdates($request, $existing, $payload);

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function applyImageUpdates(Request $request, ?StaffIdCard $existing, array &$payload): void
    {
        $map = [
            'background_image' => [
                'column' => 'background',
                'folder' => StaffIdCardDocumentService::FOLDER_BACKGROUND,
                'remove' => 'removebackground_image',
            ],
            'logo_img' => [
                'column' => 'logo',
                'folder' => StaffIdCardDocumentService::FOLDER_LOGO,
                'remove' => 'removelogo_image',
            ],
            'sign_image' => [
                'column' => 'sign_image',
                'folder' => StaffIdCardDocumentService::FOLDER_SIGNATURE,
                'remove' => 'removesign_image',
            ],
        ];

        foreach ($map as $fileKey => $meta) {
            $column = $meta['column'];
            $folder = $meta['folder'];
            $current = $existing ? (string) ($existing->{$column} ?? '') : '';

            if ($request->boolean($meta['remove'])) {
                if ($current !== '') {
                    $this->documents->delete($current, $folder);
                }
                $payload[$column] = '';
                $current = '';
            }

            if ($request->hasFile($fileKey)) {
                if ($current !== '') {
                    $this->documents->delete($current, $folder);
                }
                $payload[$column] = $this->documents->store($request->file($fileKey), $folder);
            }
        }
    }

    public function create(array $payload): StaffIdCard
    {
        return StaffIdCard::query()->create($payload);
    }

    public function update(StaffIdCard $row, array $payload): StaffIdCard
    {
        $row->fill($payload);
        $row->save();

        return $row;
    }

    public function delete(StaffIdCard $row): void
    {
        $this->documents->delete((string) ($row->background ?? ''), StaffIdCardDocumentService::FOLDER_BACKGROUND);
        $this->documents->delete((string) ($row->logo ?? ''), StaffIdCardDocumentService::FOLDER_LOGO);
        $this->documents->delete((string) ($row->sign_image ?? ''), StaffIdCardDocumentService::FOLDER_SIGNATURE);
        $row->delete();
    }

    /**
     * @return array{backgroundUrl: ?string, logoUrl: ?string, signUrl: ?string}
     */
    public function assetUrls(StaffIdCard $row): array
    {
        return [
            'backgroundUrl' => $this->documents->url($row->background, StaffIdCardDocumentService::FOLDER_BACKGROUND),
            'logoUrl' => $this->documents->url($row->logo, StaffIdCardDocumentService::FOLDER_LOGO),
            'signUrl' => $this->documents->url($row->sign_image, StaffIdCardDocumentService::FOLDER_SIGNATURE),
        ];
    }
}
