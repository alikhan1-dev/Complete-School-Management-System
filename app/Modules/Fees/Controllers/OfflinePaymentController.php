<?php

namespace App\Modules\Fees\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Fees\Services\OfflinePaymentService;
use App\Modules\Roles\Services\PermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * CI admin/Offlinepayment — admin offline bank payment requests.
 */
class OfflinePaymentController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected OfflinePaymentService $offline,
    ) {
    }

    public function index(): View
    {
        abort_unless($this->permissions->hasPrivilege('offline_bank_payments', 'can_view'), 403);

        return view('shared::layouts.admin', [
            'title' => (string) __('system.offline_bank_payments'),
            'contentView' => 'fees::admin.offlinepayment.index',
            'payments' => $this->offline->listPayments(),
            'offline' => $this->offline,
        ]);
    }

    public function show(int $id): View
    {
        abort_unless($this->permissions->hasPrivilege('offline_bank_payments', 'can_view'), 403);

        $payment = $this->offline->find($id);
        abort_if(! $payment, 404);

        return view('shared::layouts.admin', [
            'title' => (string) __('system.payment_details'),
            'contentView' => 'fees::admin.offlinepayment.show',
            'payment' => $payment,
            'amountToPaid' => $this->offline->amountToPaid($payment),
            'offline' => $this->offline,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('offline_bank_payments', 'can_view'), 403);

        $data = $request->validate([
            'offline_fees_payment_id' => ['required', 'integer'],
            'payment_status' => ['required', 'integer', 'in:1,2'],
            'amount' => ['required', 'numeric', 'min:0'],
            'fine' => ['required', 'numeric', 'min:0'],
            'reply' => ['nullable', 'string'],
        ]);

        /** @var \App\Modules\Staff\Models\Staff $staff */
        $staff = $request->user('staff');

        try {
            $this->offline->updateStatus(
                (int) $data['offline_fees_payment_id'],
                (int) $data['payment_status'],
                (float) $data['amount'],
                (float) $data['fine'],
                (string) ($data['reply'] ?? ''),
                $staff
            );
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['payment_status' => $e->getMessage()]);
        }

        return redirect()
            ->route('fees.offlinepayment.index')
            ->with('success', (string) __('system.success_message'));
    }

    public function download(int $id): BinaryFileResponse
    {
        abort_unless($this->permissions->hasPrivilege('offline_bank_payments', 'can_view'), 403);

        $payment = $this->offline->find($id);
        abort_if(! $payment, 404);

        $path = $this->offline->attachmentAbsolutePath($payment);
        abort_if($path === null, 404);

        return response()->download($path, basename($path));
    }
}
