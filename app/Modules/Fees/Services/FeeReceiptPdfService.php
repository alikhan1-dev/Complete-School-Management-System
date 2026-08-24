<?php

namespace App\Modules\Fees\Services;

use Illuminate\Support\Facades\File;
use Mpdf\HTMLParserMode;
use Mpdf\Mpdf;
use Mpdf\MpdfException;

/**
 * CI Site::download_fee_receipt_pdf (mPDF download of fee / transport line receipt).
 */
class FeeReceiptPdfService
{
    public function __construct(
        protected FeeReceiptService $receipts,
    ) {
    }

    /**
     * @param  array<string, mixed>  $viewData  shared print + group line payload
     * @return array{binary:string,filename:string}
     *
     * @throws MpdfException
     */
    public function render(array $viewData): array
    {
        $html = view('fees::print.downloadFeeReceiptPdf', $viewData)->render();
        $stylesheet = $this->stylesheet();

        $tempDir = storage_path('app/mpdf-temp');
        File::ensureDirectoryExists($tempDir);

        $mpdf = new Mpdf([
            'tempDir' => $tempDir,
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 10,
            'margin_right' => 10,
            'margin_top' => 10,
            'margin_bottom' => 10,
            'default_font' => 'dejavusans',
        ]);

        $mpdf->SetDisplayMode('fullpage');
        $mpdf->autoScriptToLang = true;
        $mpdf->autoLangToFont = true;

        if ($stylesheet !== '') {
            $mpdf->WriteHTML($stylesheet, HTMLParserMode::HEADER_CSS);
        }
        $mpdf->WriteHTML($html, HTMLParserMode::HTML_BODY);

        return [
            'binary' => $mpdf->Output('fees_receipt.pdf', \Mpdf\Output\Destination::STRING_RETURN),
            'filename' => 'fees_receipt.pdf',
        ];
    }

    protected function stylesheet(): string
    {
        $path = public_path('backend/fee_receipt_pdf_style.css');
        if (File::isFile($path)) {
            return (string) File::get($path);
        }

        return 'body{font-family: DejaVu Sans, Arial, sans-serif; font-size:12px; color:#000;}'
            .'table{border-collapse:collapse;width:100%;}'
            .'td,th{padding:4px;vertical-align:top;}'
            .'.page-break{page-break-before:always;}';
    }
}
