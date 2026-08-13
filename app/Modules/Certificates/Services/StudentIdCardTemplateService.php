<?php

namespace App\Modules\Certificates\Services;

use App\Modules\Certificates\Models\IdCard;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * CI Student_id_card_model — student ID card template CRUD.
 * Deferred: TC, SaaS quota.
 */
class StudentIdCardTemplateService
{
    /** @var list<string> */
    public const FLAG_FIELDS = [
        'is_active_admission_no',
        'is_active_student_name',
        'is_active_class',
        'is_active_father_name',
        'is_active_mother_name',
        'is_active_address',
        'is_active_phone',
        'is_active_dob',
        'is_active_blood_group',
        'enable_vertical_card',
        'enable_student_barcode',
        'enable_student_rollno',
        'enable_student_house_name',
    ];

    public function __construct(protected StudentIdCardDocumentService $documents)
    {
    }

    /**
     * @return Collection<int, IdCard>
     */
    public function list(): Collection
    {
        return IdCard::query()->orderBy('id')->get();
    }

    public function find(int $id): IdCard
    {
        return IdCard::query()->findOrFail($id);
    }

    /**
     * @return array<string, mixed>
     */
    public function buildPayload(Request $request, ?IdCard $existing = null): array
    {
        $payload = [
            'title' => (string) $request->input('title'),
            'school_name' => (string) $request->input('school_name'),
            'school_address' => (string) $request->input('address'),
            'header_color' => (string) $request->input('header_color', ''),
            'enable_admission_no' => $request->boolean('is_active_admission_no') ? 1 : 0,
            'enable_student_name' => $request->boolean('is_active_student_name') ? 1 : 0,
            'enable_class' => $request->boolean('is_active_class') ? 1 : 0,
            'enable_fathers_name' => $request->boolean('is_active_father_name') ? 1 : 0,
            'enable_mothers_name' => $request->boolean('is_active_mother_name') ? 1 : 0,
            'enable_address' => $request->boolean('is_active_address') ? 1 : 0,
            'enable_phone' => $request->boolean('is_active_phone') ? 1 : 0,
            'enable_dob' => $request->boolean('is_active_dob') ? 1 : 0,
            'enable_blood_group' => $request->boolean('is_active_blood_group') ? 1 : 0,
            'enable_vertical_card' => $request->boolean('enable_vertical_card') ? 1 : 0,
            'enable_student_barcode' => $request->boolean('enable_student_barcode') ? 1 : 0,
            'enable_student_rollno' => $request->boolean('enable_student_rollno') ? 1 : 0,
            'enable_student_house_name' => $request->boolean('enable_student_house_name') ? 1 : 0,
            'background' => $existing ? (string) ($existing->background ?? '') : '',
            'logo' => $existing ? (string) ($existing->logo ?? '') : '',
            'sign_image' => $existing ? (string) ($existing->sign_image ?? '') : '',
        ];

        if ($existing === null) {
            $payload['status'] = 1;
        }

        $this->applyImageUpdates($request, $existing, $payload);

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function applyImageUpdates(Request $request, ?IdCard $existing, array &$payload): void
    {
        $map = [
            'background_image' => [
                'column' => 'background',
                'folder' => StudentIdCardDocumentService::FOLDER_BACKGROUND,
                'remove' => 'removebackground_image',
            ],
            'logo_img' => [
                'column' => 'logo',
                'folder' => StudentIdCardDocumentService::FOLDER_LOGO,
                'remove' => 'removelogo_image',
            ],
            'sign_image' => [
                'column' => 'sign_image',
                'folder' => StudentIdCardDocumentService::FOLDER_SIGNATURE,
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

    public function create(array $payload): IdCard
    {
        return IdCard::query()->create($payload);
    }

    public function update(IdCard $row, array $payload): IdCard
    {
        $row->fill($payload);
        $row->save();

        return $row;
    }

    public function delete(IdCard $row): void
    {
        $this->documents->delete((string) ($row->background ?? ''), StudentIdCardDocumentService::FOLDER_BACKGROUND);
        $this->documents->delete((string) ($row->logo ?? ''), StudentIdCardDocumentService::FOLDER_LOGO);
        $this->documents->delete((string) ($row->sign_image ?? ''), StudentIdCardDocumentService::FOLDER_SIGNATURE);
        $row->delete();
    }

    /**
     * @return array{backgroundUrl: ?string, logoUrl: ?string, signUrl: ?string}
     */
    public function assetUrls(IdCard $row): array
    {
        return [
            'backgroundUrl' => $this->documents->url($row->background, StudentIdCardDocumentService::FOLDER_BACKGROUND),
            'logoUrl' => $this->documents->url($row->logo, StudentIdCardDocumentService::FOLDER_LOGO),
            'signUrl' => $this->documents->url($row->sign_image, StudentIdCardDocumentService::FOLDER_SIGNATURE),
        ];
    }
}
