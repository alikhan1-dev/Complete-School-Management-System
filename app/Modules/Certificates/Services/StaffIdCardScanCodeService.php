<?php

namespace App\Modules\Certificates\Services;

use Illuminate\Support\Facades\File;
use Picqer\Barcode\BarcodeGeneratorPNG;

/**
 * CI Customlib::generatestaffbarcode — employee_id encoded as scan image.
 * Paths: uploads/staff_id_card/barcodes|qrcode/{staff_id}.png
 * Proper QR matrix deferred; both modes emit Code128 (printable + scannable).
 */
class StaffIdCardScanCodeService
{
    public function generate(string $employeeId, int $staffId, string $scanType = 'barcode'): string
    {
        $code = trim($employeeId) !== '' ? trim($employeeId) : (string) $staffId;
        $folder = $scanType === 'qrcode' ? 'qrcode' : 'barcodes';
        $relative = 'uploads/staff_id_card/'.$folder.'/'.$staffId.'.png';
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
