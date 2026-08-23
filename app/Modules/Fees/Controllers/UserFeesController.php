<?php

namespace App\Modules\Fees\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Fees\Services\StudentFeesPortalService;
use App\Modules\Shared\Services\SchoolContext;
use Illuminate\View\View;
use RuntimeException;

/**
 * CI user/User::getfees — portal student fees ledger.
 */
class UserFeesController extends Controller
{
    public function __construct(
        protected StudentFeesPortalService $portal,
        protected SchoolContext $school,
    ) {
    }

    public function getfees(): View
    {
        try {
            $data = $this->portal->pageData();
        } catch (RuntimeException $e) {
            abort(403, $e->getMessage());
        }

        return view('shared::layouts.student_parent', [
            'title' => (string) __('system.fees'),
            'contentView' => 'fees::user.getfees',
            'student' => $data['student'],
            'sessionFees' => $data['sessionFees'],
            'offlineEnabled' => $data['offlineEnabled'],
            'currencySymbol' => $this->school->currencySymbol(),
        ]);
    }
}
