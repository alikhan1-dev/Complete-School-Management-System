<?php

namespace App\Modules\Certificates\Services;

use Illuminate\Support\Facades\File;
use Picqer\Barcode\BarcodeGeneratorPNG;

/**
 * CI Customlib::generatebarcode — admission_no encoded as scan image.
 * Paths match CI: uploads/student_id_card/barcodes|qrcode/{student_id}.png
 * Proper QR matrix deferred; both modes emit Code128 of admission_no (printable + scannable).
 */
class StudentIdCardScanCodeService
{
    public function generate(string $admissionNo, int $studentId, string $scanType = 'barcode'): string
    {
        $code = trim($admissionNo) !== '' ? trim($admissionNo) : (string) $studentId;
        $folder = $scanType === 'qrcode' ? 'qrcode' : 'barcodes';
        $relative = 'uploads/student_id_card/'.$folder.'/'.$studentId.'.png';
        $absolute = public_path($relative);

        File::ensureDirectoryExists(dirname($absolute));

        $generator = new BarcodeGeneratorPNG;
        $png = $generator->getBarcode($code, $generator::TYPE_CODE_128, 2, 60);
        File::put($absolute, $png);

        return $relative;
    }

    public function url(?string $relativePath): ?string
    {
        if ($relativePath === null || $relativePath === '') {
            return null;
        }

        return asset(ltrim($relativePath, '/'));
    }
}
