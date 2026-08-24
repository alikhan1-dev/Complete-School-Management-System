<?php

namespace App\Modules\Fees\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Fees\Services\FeeReminderCronService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use InvalidArgumentException;

/**
 * CI Cron::feereminder/{key} HTTP entry (public, key-gated).
 */
class FeeReminderCronController extends Controller
{
    public function __construct(
        protected FeeReminderCronService $cron,
    ) {
    }

    public function __invoke(Request $request, string $key): JsonResponse|Response
    {
        try {
            $date = $request->query('date');
            $date = is_string($date) && $date !== '' ? $date : null;
            $result = $this->cron->run($key, $date);
        } catch (InvalidArgumentException $e) {
            return response($e->getMessage(), 403);
        }

        return response()->json([
            'status' => 1,
            'reminder_rules' => $result['reminder_rules'],
            'candidates' => $result['candidates'],
            'queued' => $result['queued'],
            'accepted' => $result['accepted'],
            'deferred' => true,
            'message' => 'Fee reminder cron completed (live send deferred).',
        ]);
    }
}
