<?php

namespace App\Modules\Fees\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Fees\Services\FeeReceiptPdfService;
use App\Modules\Fees\Services\FeeReceiptService;
use App\Modules\Fees\Services\FeeReceiptTokenService;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * CI Site::download_fee_receipt_token / download_fee_receipt_pdf.
 */
class DownloadFeeReceiptController extends Controller
{
    public function __construct(
        protected FeeReceiptTokenService $tokens,
        protected FeeReceiptService $receipts,
        protected FeeReceiptPdfService $pdf,
    ) {
    }

    public function __invoke(string $token): Response
    {
        $params = $this->tokens->decode($token);
        if ($params === null) {
            throw new BadRequestHttpException('Invalid receipt link');
        }

        $feeCategory = (string) ($params['fee_category'] ?? 'fees');
        $payload = $this->receipts->groupReceiptPayload($feeCategory, [
            'trans_fee_id' => $params['transport_fees_id'],
            'fee_session_group_id' => $params['fee_session_group_id'],
            'fee_master_id' => $params['student_fees_master_id'],
            'fee_groups_feetype_id' => $params['fee_groups_feetype_id'],
        ]);

        if ($payload === null) {
            abort(404);
        }

        $viewData = array_merge([
            'copies' => $this->receipts->invoiceCopiesPublic(),
            'printDate' => $this->receipts->formatDate(now()->format('Y-m-d')),
            'headerUrl' => $this->receipts->receiptHeaderUrl(),
            'footerHtml' => $this->receipts->receiptFooterHtml(),
            'singlePagePrint' => $this->receipts->singlePagePrint(),
            'currencySymbol' => $this->receipts->currencySymbol(),
            'formatAmount' => fn (float|int|string $amount): string => $this->receipts->formatAmount($amount),
            'groupStatusLabel' => fn (string $status): string => $this->receipts->groupStatusLabel($status),
            'feeList' => $payload['feeList'],
            'line' => $payload['line'],
            'studentName' => $this->receipts->studentDisplayName($payload['feeList']),
        ]);

        $rendered = $this->pdf->render($viewData);

        return response($rendered['binary'], 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$rendered['filename'].'"',
        ]);
    }
}
