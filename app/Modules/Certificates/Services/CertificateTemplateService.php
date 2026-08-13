<?php

namespace App\Modules\Certificates\Services;

use App\Modules\Certificates\Models\Certificate;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * CI Certificate_model — student certificate template CRUD.
 * Deferred: ID cards, staff ID, TC, SaaS quota.
 */
class CertificateTemplateService
{
    /** CI created_for = 2 means student certificates. */
    public const CREATED_FOR_STUDENT = 2;

    public function __construct(protected CertificateDocumentService $documents)
    {
    }

    /**
     * @return Collection<int, Certificate>
     */
    public function listStudentCertificates(): Collection
    {
        return Certificate::query()
            ->where('created_for', self::CREATED_FOR_STUDENT)
            ->where('status', 1)
            ->orderBy('id')
            ->get();
    }

    public function findStudentCertificate(int $id): Certificate
    {
        return Certificate::query()
            ->where('created_for', self::CREATED_FOR_STUDENT)
            ->where('status', 1)
            ->findOrFail($id);
    }

    /**
     * Placeholders shown in the design UI (replacement deferred to generate slice).
     *
     * @return list<string>
     */
    public function placeholderHints(): array
    {
        return [
            '[name]', '[dob]', '[present_address]', '[guardian]', '[created_at]',
            '[admission_no]', '[roll_no]', '[class]', '[section]', '[gender]',
            '[admission_date]', '[category]', '[cast]', '[father_name]', '[mother_name]',
            '[religion]', '[email]', '[phone]', '[present_date]',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function buildPayload(Request $request, ?Certificate $existing = null): array
    {
        $enableImage = $request->boolean('is_active_student_img') ? 1 : 0;

        $payload = [
            'certificate_name' => (string) $request->input('certificate_name'),
            'certificate_text' => (string) $request->input('certificate_text'),
            'left_header' => (string) $request->input('left_header', ''),
            'center_header' => (string) $request->input('center_header', ''),
            'right_header' => (string) $request->input('right_header', ''),
            'left_footer' => (string) $request->input('left_footer', ''),
            'center_footer' => (string) $request->input('center_footer', ''),
            'right_footer' => (string) $request->input('right_footer', ''),
            'header_height' => (int) $request->input('header_height', 0),
            'content_height' => (int) $request->input('content_height', 0),
            'footer_height' => (int) $request->input('footer_height', 0),
            'content_width' => (int) $request->input('content_width', 0),
            'enable_student_image' => $enableImage,
            'enable_image_height' => $enableImage ? (int) $request->input('image_height', 0) : 0,
            'background_image' => $existing ? (string) ($existing->background_image ?? '') : '',
        ];

        if ($existing === null) {
            $payload['created_for'] = self::CREATED_FOR_STUDENT;
            $payload['status'] = 1;
        }

        if ($request->boolean('removebackground_image')) {
            if ($existing && $existing->background_image) {
                $this->documents->delete((string) $existing->background_image);
            }
            $payload['background_image'] = '';
        } elseif ($request->hasFile('background_image')) {
            if ($existing && $existing->background_image) {
                $this->documents->delete((string) $existing->background_image);
            }
            $payload['background_image'] = $this->documents->store($request->file('background_image'));
        }

        return $payload;
    }

    public function create(array $payload): Certificate
    {
        return Certificate::query()->create($payload);
    }

    public function update(Certificate $row, array $payload): Certificate
    {
        $row->fill($payload);
        $row->save();

        return $row;
    }

    public function delete(Certificate $row): void
    {
        $this->documents->delete((string) ($row->background_image ?? ''));
        $row->delete();
    }
}
