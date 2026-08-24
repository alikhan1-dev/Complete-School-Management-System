<?php

namespace App\Modules\Fees\Services;

/**
 * CI encode_receipt_url / Site::download_fee_receipt_token token format.
 *
 * Payload: invoice_id|fee_category|transport_fees_id|fee_groups_feetype_id|student_fees_master_id|fee_session_group_id|type|created_by
 */
class FeeReceiptTokenService
{
    /**
     * @param  array{
     *     invoice_id:int|string,
     *     fee_category:string,
     *     transport_fees_id?:int|string|null,
     *     fee_groups_feetype_id?:int|string|null,
     *     student_fees_master_id?:int|string|null,
     *     fee_session_group_id?:int|string|null,
     *     type:string,
     *     created_by:int|string
     * }  $parts
     */
    public function encode(array $parts): string
    {
        $data = implode('|', [
            (string) ($parts['invoice_id'] ?? 0),
            (string) ($parts['fee_category'] ?? 'fees'),
            (string) ($parts['transport_fees_id'] ?? 0),
            (string) ($parts['fee_groups_feetype_id'] ?? 0),
            (string) ($parts['student_fees_master_id'] ?? 0),
            (string) ($parts['fee_session_group_id'] ?? 0),
            (string) ($parts['type'] ?? 'staff'),
            (string) ($parts['created_by'] ?? 0),
        ]);

        return urlencode(base64_encode($data));
    }

    /**
     * @return array{
     *     invoice_id:int,
     *     fee_category:string,
     *     transport_fees_id:int,
     *     fee_groups_feetype_id:int,
     *     student_fees_master_id:int,
     *     fee_session_group_id:int,
     *     type:string,
     *     created_by:int
     * }|null
     */
    public function decode(string $token): ?array
    {
        $decoded = base64_decode(urldecode($token), true);
        if ($decoded === false || $decoded === '') {
            return null;
        }

        $params = explode('|', $decoded);
        if (count($params) !== 8) {
            return null;
        }

        return [
            'invoice_id' => (int) $params[0],
            'fee_category' => (string) $params[1],
            'transport_fees_id' => (int) $params[2],
            'fee_groups_feetype_id' => (int) $params[3],
            'student_fees_master_id' => (int) $params[4],
            'fee_session_group_id' => (int) $params[5],
            'type' => (string) $params[6],
            'created_by' => (int) $params[7],
        ];
    }

    public function absoluteUrl(string $token): string
    {
        return url('download-receipt/'.$token);
    }
}
