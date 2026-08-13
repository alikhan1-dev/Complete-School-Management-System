<?php

namespace App\Modules\Certificates\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Mpdf\HTMLParserMode;
use Mpdf\Mpdf;
use Mpdf\MpdfException;

/**
 * CI M_pdf + print_transfer_certificate PDF output (Legal, inline download).
 * Email of PDF deferred with other certificate modules.
 */
class TransferCertificatePdfService
{
    /**
     * @param  array<string, mixed>  $payload  from DownloadTransferCertificateService::buildPrintPayload
     * @return array{binary:string,filename:string}
     *
     * @throws MpdfException
     */
    public function render(array $payload): array
    {
        $payload = $this->localizeImageUrls($payload);

        $html = view('certificates::admin.transfercertificate.pdf', $payload)->render();
        $stylesheet = $this->stylesheet();

        $tempDir = storage_path('app/mpdf-temp');
        File::ensureDirectoryExists($tempDir);

        $mpdf = new Mpdf([
            'tempDir' => $tempDir,
            'mode' => 'utf-8',
            'format' => 'Legal',
            'margin_left' => 8,
            'margin_right' => 8,
            'margin_top' => 8,
            'margin_bottom' => 8,
            'default_font' => 'dejavusans',
        ]);

        $mpdf->SetDisplayMode('fullpage');
        $mpdf->autoScriptToLang = true;
        $mpdf->autoLangToFont = true;
        $mpdf->showWatermarkText = false;

        if ($stylesheet !== '') {
            $mpdf->WriteHTML($stylesheet, HTMLParserMode::HEADER_CSS);
        }
        $mpdf->WriteHTML($html, HTMLParserMode::HTML_BODY);

        $studentName = preg_replace('/[^A-Za-z0-9_\- ]+/', '', (string) ($payload['studentName'] ?? 'student')) ?: 'student';
        $admissionNo = preg_replace('/[^A-Za-z0-9_\-]+/', '', (string) ($payload['student']->admission_no ?? 'tc')) ?: 'tc';
        $filename = Str::slug($studentName.'_'.$admissionNo, '_').'.pdf';

        return [
            'binary' => $mpdf->Output($filename, \Mpdf\Output\Destination::STRING_RETURN),
            'filename' => $filename,
        ];
    }

    protected function stylesheet(): string
    {
        $path = public_path('backend/resume_pdf_style.css');
        if (File::isFile($path)) {
            return (string) File::get($path);
        }

        return 'body{font-family: DejaVu Sans, Arial, sans-serif; font-size:12px; color:#000;}'
            .'table{border-collapse:collapse;width:100%;}'
            .'td,th{border:1px solid #333;padding:5px;vertical-align:top;}'
            .'h2{text-align:center;}';
    }

    /**
     * Prefer local filesystem paths for mPDF image embedding.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function localizeImageUrls(array $payload): array
    {
        foreach (['headerUrl', 'classTeacherSignatureUrl', 'checkedByUrl', 'principalSignatureUrl'] as $key) {
            $payload[$key] = $this->toLocalImageSrc($payload[$key] ?? null);
        }

        if (! empty($payload['fieldRows']) && is_array($payload['fieldRows'])) {
            foreach ($payload['fieldRows'] as $i => $row) {
                if (empty($row['html']) || empty($row['value'])) {
                    continue;
                }
                $payload['fieldRows'][$i]['value'] = preg_replace_callback(
                    '/src=(["\'])(.*?)\1/i',
                    function (array $m) {
                        $local = $this->toLocalImageSrc($m[2]);

                        return 'src='.$m[1].($local ?? $m[2]).$m[1];
                    },
                    (string) $row['value']
                );
            }
        }

        return $payload;
    }

    protected function toLocalImageSrc(?string $url): ?string
    {
        if ($url === null || $url === '') {
            return null;
        }

        $path = parse_url($url, PHP_URL_PATH);
        if (! is_string($path) || $path === '') {
            return $url;
        }

        // asset('uploads/...') => /uploads/...
        $relative = ltrim($path, '/');
        // Handle subdirectory installs: strip up to uploads/
        if (preg_match('#(?:^|/)(uploads/.+)$#', $relative, $m)) {
            $relative = $m[1];
        }

        $local = public_path($relative);
        if (File::isFile($local)) {
            return $local;
        }

        return $url;
    }
}
