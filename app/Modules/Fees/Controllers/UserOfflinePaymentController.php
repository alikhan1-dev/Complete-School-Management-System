<?php

namespace App\Modules\Fees\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Fees\Services\OfflinePaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * CI user/Offlinepayment + Payment::pay offline_payment branch.
 * Deferred: full student getfees ledger UI; SaaS storage quota.
 */
class UserOfflinePaymentController extends Controller
{
    public function __construct(
        protected OfflinePaymentService $offline,
    ) {
    }

    /**
     * CI user/gateway/Payment::pay when submit_mode=offline_payment.
     */
    public function start(Request $request): RedirectResponse
    {
        abort_unless($this->offline->isPortalEnabled(), 403);

        $data = $request->validate([
            'fee_category' => ['required', 'string', 'in:fees,transport'],
            'student_fees_master_id' => ['nullable', 'integer'],
            'fee_groups_feetype_id' => ['nullable', 'integer'],
            'student_transport_fee_id' => ['nullable', 'integer'],
        ]);

        $sessionId = $this->offline->currentStudentSessionId();
        abort_if($sessionId <= 0, 403);

        try {
            $this->offline->startParams($data, $sessionId);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['fee_category' => $e->getMessage()]);
        }

        return redirect()->route('user.offlinepayment.index');
    }

    public function index(Request $request): View|RedirectResponse
    {
        abort_unless($this->offline->isPortalEnabled(), 403);

        $sessionId = $this->offline->currentStudentSessionId();
        abort_if($sessionId <= 0, 403);

        $params = $this->offline->sessionParams();
        if ($params === null) {
            return redirect()
                ->route('user.fees.getfees')
                ->withErrors(['payment' => 'Select a fee line before submitting an offline bank payment.']);
        }

        if ($request->isMethod('post')) {
            $rules = $this->offline->uploadRules();
            $data = $request->validate([
                'payment_date' => ['required', 'date'],
                'bank_from' => ['required', 'string', 'max:200'],
                'bank_account_transferred' => ['required', 'string', 'max:200'],
                'reference' => ['nullable', 'string', 'max:200'],
                'amount' => ['required', 'numeric', 'min:0'],
                'attachment' => [
                    'nullable',
                    'file',
                    'max:'.$rules['max_kb'],
                    'mimes:'.implode(',', $rules['extensions']),
                ],
            ]);

            try {
                $this->offline->submit([
                    ...$data,
                    'attachment' => $request->file('attachment'),
                ], $sessionId, $params);
            } catch (InvalidArgumentException $e) {
                return back()->withInput()->withErrors(['amount' => $e->getMessage()]);
            }

            return redirect()
                ->route('user.fees.getfees')
                ->with('success', (string) __('system.thank_you_for_the_payment_we_will_review_and_update_your_payment'));
        }

        return view('shared::layouts.student_parent', [
            'title' => (string) __('system.offline_bank_payments'),
            'contentView' => 'fees::user.offlinepayment.index',
            'instructionHtml' => $this->offline->instructionHtml(),
            'params' => $params,
            'currencySymbol' => app(\App\Modules\Shared\Services\SchoolContext::class)->currencySymbol(),
        ]);
    }

    public function requests(): View
    {
        abort_unless($this->offline->isPortalEnabled(), 403);

        $sessionId = $this->offline->currentStudentSessionId();
        abort_if($sessionId <= 0, 403);

        return view('shared::layouts.student_parent', [
            'title' => (string) __('system.offline_bank_payments'),
            'contentView' => 'fees::user.offlinepayment.requests',
            'payments' => $this->offline->listForStudentSession($sessionId),
            'offline' => $this->offline,
        ]);
    }

    public function show(int $id): View
    {
        abort_unless($this->offline->isPortalEnabled(), 403);

        $sessionId = $this->offline->currentStudentSessionId();
        abort_if($sessionId <= 0, 403);

        $payment = $this->offline->findForStudentSession($id, $sessionId);
        abort_if(! $payment, 404);

        return view('shared::layouts.student_parent', [
            'title' => (string) __('system.payment_details'),
            'contentView' => 'fees::user.offlinepayment.show',
            'payment' => $payment,
            'offline' => $this->offline,
        ]);
    }

    public function download(int $id): BinaryFileResponse
    {
        abort_unless($this->offline->isPortalEnabled(), 403);

        $sessionId = $this->offline->currentStudentSessionId();
        abort_if($sessionId <= 0, 403);

        $payment = $this->offline->findForStudentSession($id, $sessionId);
        abort_if(! $payment, 404);

        $path = $this->offline->attachmentAbsolutePath($payment);
        abort_if($path === null, 404);

        return response()->download($path, basename($path));
    }
}
