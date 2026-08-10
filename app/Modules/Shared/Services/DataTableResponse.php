<?php

namespace App\Modules\Shared\Services;

use Illuminate\Http\JsonResponse;

/**
 * Reproduce CodeIgniter Datatables library JSON contract.
 */
class DataTableResponse
{
    public static function make(int $draw, int $recordsTotal, int $recordsFiltered, array $data): JsonResponse
    {
        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }
}
