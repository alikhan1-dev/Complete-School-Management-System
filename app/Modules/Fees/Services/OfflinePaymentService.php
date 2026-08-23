<?php

namespace App\Modules\Fees\Services;

use App\Modules\Fees\Models\OfflineFeesPayment;
use App\Modules\Shared\Services\SchoolContext;
use App\Modules\Staff\Models\Staff;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use InvalidArgumentException;

/**
 * CI admin/Offlinepayment + user/Offlinepayment — offline bank payment requests.
 * Deferred: student getfees UI entry points, DataTables AJAX pixel-parity, SaaS storage quota.
 */
class OfflinePaymentService
{
    public const SESSION_PARAMS_KEY = 'offline_payment_params';

    public function __construct(
        protected FeeCollectService $collect,
        protected SchoolContext $school,
    ) {
    }

    public function isPortalEnabled(): bool
    {
        $flag = $this->school->get('is_offline_fee_payment', 0);

        return (string) $flag === '1' || $flag === 1 || $flag === true;
    }

    public function instructionHtml(): string
    {
        return (string) $this->school->get('offline_bank_payment_instruction', '');
    }

    public function currentStudentSessionId(): int
    {
        return (int) (session('current_class.student_session_id') ?? 0);
    }

    /**
     * @return array{extensions: list<string>, max_kb: int}
     */
    public function uploadRules(): array
    {
        $row = DB::table('filetypes')->orderBy('id')->first();
        $extensions = [];
        $maxKb = 10240;

        if ($row) {
            $extensions = array_values(array_filter(array_map(
                fn ($ext) => strtolower(ltrim(trim($ext), '.')),
                explode(',', (string) ($row->file_extension ?? ''))
            )));
            $bytes = (int) ($row->file_size ?? 0);
            if ($bytes > 0) {
                $maxKb = (int) ceil($bytes / 1024);
            }
        }

        if ($extensions === []) {
            $extensions = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'zip', 'txt'];
        }

        return [
            'extensions' => $extensions,
            'max_kb' => $maxKb,
        ];
    }

    /**
     * CI user/gateway/Payment::pay offline_payment branch — stash fee line for the form.
     *
     * @param  array{
     *     fee_category:string,
     *     student_fees_master_id?:int|null,
     *     fee_groups_feetype_id?:int|null,
     *     student_transport_fee_id?:int|null
     * }  $input
     * @return array<string, mixed>
     */
    public function startParams(array $input, int $studentSessionId): array
    {
        if ($studentSessionId <= 0) {
            throw new InvalidArgumentException('Student session is required.');
        }

        $category = (string) ($input['fee_category'] ?? 'fees');
        $masterId = (int) ($input['student_fees_master_id'] ?? 0);
        $feetypeId = (int) ($input['fee_groups_feetype_id'] ?? 0);
        $transportId = (int) ($input['student_transport_fee_id'] ?? 0);

        if ($category === 'transport') {
            if ($transportId <= 0) {
                throw new InvalidArgumentException('Transport fee is required.');
            }
            $owned = DB::table('student_transport_fees')
                ->where('id', $transportId)
                ->where('student_session_id', $studentSessionId)
                ->exists();
            if (! $owned) {
                throw new InvalidArgumentException('Transport fee does not belong to this student.');
            }
            $params = [
                'fee_category' => 'transport',
                'student_fees_master_id' => 0,
                'fee_groups_feetype_id' => 0,
                'student_transport_fee_id' => $transportId,
            ];
        } else {
            if ($masterId <= 0 || $feetypeId <= 0) {
                throw new InvalidArgumentException('Fee line is required.');
            }
            $owned = DB::table('student_fees_master')
                ->where('id', $masterId)
                ->where('student_session_id', $studentSessionId)
                ->exists();
            if (! $owned) {
                throw new InvalidArgumentException('Fee does not belong to this student.');
            }
            $params = [
                'fee_category' => 'fees',
                'student_fees_master_id' => $masterId,
                'fee_groups_feetype_id' => $feetypeId,
                'student_transport_fee_id' => 0,
            ];
        }

        session([self::SESSION_PARAMS_KEY => $params]);

        return $params;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function sessionParams(): ?array
    {
        $params = session(self::SESSION_PARAMS_KEY);
        if (! is_array($params) || $params === []) {
            return null;
        }

        return $params;
    }

    public function clearSessionParams(): void
    {
        session()->forget(self::SESSION_PARAMS_KEY);
    }

    /**
     * @return Collection<int, object>
     */
    public function listForStudentSession(int $studentSessionId): Collection
    {
        if ($studentSessionId <= 0) {
            return collect();
        }

        return DB::table('offline_fees_payments')
            ->where('student_session_id', $studentSessionId)
            ->orderByDesc('submit_date')
            ->select([
                'id',
                'payment_date',
                'submit_date',
                'approve_date',
                'amount',
                'is_active',
                'invoice_id',
                'bank_from',
                'reference',
            ])
            ->get();
    }

    public function findForStudentSession(int $id, int $studentSessionId): ?object
    {
        if ($id <= 0 || $studentSessionId <= 0) {
            return null;
        }

        $payment = $this->find($id);
        if (! $payment || (int) $payment->student_session_id !== $studentSessionId) {
            return null;
        }

        return $payment;
    }

    /**
     * CI user/Offlinepayment::index submit.
     *
     * @param  array{
     *     payment_date:string,
     *     bank_from:string,
     *     bank_account_transferred:string,
     *     reference?:string|null,
     *     amount:float|string,
     *     attachment?:\Illuminate\Http\UploadedFile|null
     * }  $data
     */
    public function submit(array $data, int $studentSessionId, array $params): int
    {
        if (! $this->isPortalEnabled()) {
            throw new InvalidArgumentException('Offline bank payment is disabled.');
        }
        if ($studentSessionId <= 0) {
            throw new InvalidArgumentException('Student session is required.');
        }

        $masterId = (int) ($params['student_fees_master_id'] ?? 0);
        $feetypeId = (int) ($params['fee_groups_feetype_id'] ?? 0);
        $transportId = (int) ($params['student_transport_fee_id'] ?? 0);

        if ($transportId > 0) {
            $owned = DB::table('student_transport_fees')
                ->where('id', $transportId)
                ->where('student_session_id', $studentSessionId)
                ->exists();
            if (! $owned) {
                throw new InvalidArgumentException('Transport fee does not belong to this student.');
            }
        } elseif ($masterId > 0) {
            $owned = DB::table('student_fees_master')
                ->where('id', $masterId)
                ->where('student_session_id', $studentSessionId)
                ->exists();
            if (! $owned) {
                throw new InvalidArgumentException('Fee does not belong to this student.');
            }
        } else {
            throw new InvalidArgumentException('Payment fee line is missing. Start again from fees.');
        }

        $attachmentName = null;
        $file = $data['attachment'] ?? null;
        if ($file instanceof \Illuminate\Http\UploadedFile && $file->isValid()) {
            $attachmentName = $this->storeAttachment($file);
        }

        $id = (int) DB::table('offline_fees_payments')->insertGetId([
            'student_session_id' => $studentSessionId,
            'student_fees_master_id' => $masterId > 0 ? $masterId : null,
            'fee_groups_feetype_id' => $feetypeId > 0 ? $feetypeId : null,
            'student_transport_fee_id' => $transportId > 0 ? $transportId : null,
            'payment_date' => (string) $data['payment_date'],
            'bank_from' => (string) $data['bank_from'],
            'bank_account_transferred' => (string) $data['bank_account_transferred'],
            'reference' => (string) ($data['reference'] ?? ''),
            'amount' => round((float) $data['amount'], 2),
            'submit_date' => now()->format('Y-m-d H:i:s'),
            'attachment' => $attachmentName,
            'is_active' => '0',
        ]);

        $this->clearSessionParams();

        return $id;
    }

    public function storeAttachment(\Illuminate\Http\UploadedFile $file): string
    {
        $dir = public_path('uploads/offline_payments');
        if (! File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        $ext = strtolower($file->getClientOriginalExtension() ?: 'bin');
        $name = 'off_'.uniqid('', true).'.'.$ext;
        $file->move($dir, $name);

        return $name;
    }

    public function formatDate(mixed $value): string
    {
        if ($value === null || $value === '' || $value === '0000-00-00' || $value === '0000-00-00 00:00:00') {
            return '';
        }

        return Carbon::parse((string) $value)->format($this->school->dateFormat() ?: 'd/m/Y');
    }

    public function formatDateTime(mixed $value): string
    {
        if ($value === null || $value === '' || $value === '0000-00-00 00:00:00') {
            return '';
        }

        return Carbon::parse((string) $value)->format(($this->school->dateFormat() ?: 'd/m/Y').' H:i:s');
    }

    public function studentDisplayName(object $row): string
    {
        $first = trim((string) ($row->firstname ?? ''));
        $middle = trim((string) ($row->middlename ?? ''));
        $last = trim((string) ($row->lastname ?? ''));
        $name = ((int) $this->school->get('middlename', 1) === 1) && $middle !== ''
            ? trim($first.' '.$middle)
            : $first;
        if (((int) $this->school->get('lastname', 1) === 1) && $last !== '') {
            $name = trim($name.' '.$last);
        }

        return $name !== '' ? $name : $first;
    }

    public function statusLabel(string|int $isActive): string
    {
        return match ((string) $isActive) {
            '1' => (string) __('system.approved'),
            '2' => (string) __('system.rejected'),
            default => (string) __('system.pending'),
        };
    }

    /**
     * @return Collection<int, object>
     */
    public function listPayments(): Collection
    {
        return DB::table('offline_fees_payments')
            ->join('student_session', 'student_session.id', '=', 'offline_fees_payments.student_session_id')
            ->join('students', 'students.id', '=', 'student_session.student_id')
            ->join('classes', 'classes.id', '=', 'student_session.class_id')
            ->join('sections', 'sections.id', '=', 'student_session.section_id')
            ->orderByDesc('offline_fees_payments.submit_date')
            ->select([
                'offline_fees_payments.id',
                'offline_fees_payments.payment_date',
                'offline_fees_payments.submit_date',
                'offline_fees_payments.approve_date',
                'offline_fees_payments.amount',
                'offline_fees_payments.is_active',
                'offline_fees_payments.invoice_id',
                'students.admission_no',
                'students.firstname',
                'students.middlename',
                'students.lastname',
                'classes.class',
                'sections.section',
            ])
            ->get();
    }

    public function find(int $id): ?object
    {
        if ($id <= 0) {
            return null;
        }

        return DB::table('offline_fees_payments')
            ->leftJoin('fee_groups_feetype', 'fee_groups_feetype.id', '=', 'offline_fees_payments.fee_groups_feetype_id')
            ->leftJoin('fee_groups', 'fee_groups.id', '=', 'fee_groups_feetype.fee_groups_id')
            ->leftJoin('feetype', 'feetype.id', '=', 'fee_groups_feetype.feetype_id')
            ->leftJoin('student_transport_fees', 'student_transport_fees.id', '=', 'offline_fees_payments.student_transport_fee_id')
            ->leftJoin('transport_feemaster', 'transport_feemaster.id', '=', 'student_transport_fees.transport_feemaster_id')
            ->leftJoin('route_pickup_point', 'route_pickup_point.id', '=', 'student_transport_fees.route_pickup_point_id')
            ->leftJoin('pickup_point', 'pickup_point.id', '=', 'route_pickup_point.pickup_point_id')
            ->leftJoin('transport_route', 'transport_route.id', '=', 'route_pickup_point.transport_route_id')
            ->join('student_session', 'student_session.id', '=', 'offline_fees_payments.student_session_id')
            ->join('students', 'students.id', '=', 'student_session.student_id')
            ->join('classes', 'classes.id', '=', 'student_session.class_id')
            ->join('sections', 'sections.id', '=', 'student_session.section_id')
            ->where('offline_fees_payments.id', $id)
            ->select([
                'offline_fees_payments.*',
                'fee_groups_feetype.due_date',
                'fee_groups_feetype.fine_amount as fee_fine_amount',
                'fee_groups_feetype.fine_type as fee_fine_type',
                'fee_groups_feetype.fine_percentage as fee_fine_percentage',
                'fee_groups_feetype.amount as fee_type_amount',
                'feetype.type',
                'feetype.code',
                'fee_groups.name as fee_group_name',
                'transport_feemaster.month',
                'transport_feemaster.due_date as transport_feemaster_due_date',
                'transport_feemaster.fine_amount as transport_fine_amount',
                'transport_feemaster.fine_type as transport_fine_type',
                'transport_feemaster.fine_percentage as transport_fine_percentage',
                'route_pickup_point.fees as transport_fees_amount',
                'pickup_point.name as pickup_point',
                'transport_route.route_title',
                'students.id as student_id',
                'students.admission_no',
                'students.firstname',
                'students.middlename',
                'students.lastname',
                'students.mobileno',
                'students.email',
                'classes.class',
                'sections.section',
                'student_session.id as student_session_id',
            ])
            ->first();
    }

    /**
     * CI Offlinepayment::getpayment amount/fine split when payment is past due.
     *
     * @return array{amount:float,fine:float}
     */
    public function amountToPaid(object $payment): array
    {
        $amount = (float) ($payment->amount ?? 0);
        $fine = 0.0;
        $isTransport = filled($payment->student_transport_fee_id ?? null);

        if (! $isTransport) {
            $dueDate = $payment->due_date ?? null;
            $fineAmount = (float) ($payment->fee_fine_amount ?? 0);
            $paidFine = $this->paidFineForFee(
                (int) ($payment->student_fees_master_id ?? 0),
                (int) ($payment->fee_groups_feetype_id ?? 0)
            );
            if ($this->isPastDue($dueDate, $payment->payment_date) && $fineAmount > $paidFine) {
                $remainingFine = round($fineAmount - $paidFine, 2);
                $amount = round($amount - $remainingFine, 2);
                $fine = $remainingFine;
            }
        } else {
            $dueDate = $payment->transport_feemaster_due_date ?? null;
            $feesBase = (float) ($payment->transport_fees_amount ?? 0);
            $fineType = (string) ($payment->transport_fine_type ?? '');
            $fineAmount = $fineType === 'percentage'
                ? round($feesBase * ((float) ($payment->transport_fine_percentage ?? 0)) / 100, 2)
                : (float) ($payment->transport_fine_amount ?? 0);
            $paidFine = $this->paidFineForTransport((int) $payment->student_transport_fee_id);
            if ($this->isPastDue($dueDate, $payment->payment_date) && $fineAmount > $paidFine) {
                $remainingFine = round($fineAmount - $paidFine, 2);
                $amount = round($amount - $remainingFine, 2);
                $fine = $remainingFine;
            }
        }

        return [
            'amount' => max(0, $amount),
            'fine' => max(0, $fine),
        ];
    }

    /**
     * CI OfflinePayment_model::update — approve deposits fee; reject only updates status.
     *
     * @return array{invoice_id:?string}
     */
    public function updateStatus(int $id, int $status, float $amount, float $fine, string $reply, Staff $staff): array
    {
        if (! in_array($status, [1, 2], true)) {
            throw new InvalidArgumentException('Invalid payment status.');
        }

        $payment = OfflineFeesPayment::query()->find($id);
        if (! $payment) {
            throw new InvalidArgumentException('Payment request not found.');
        }
        if ((string) $payment->is_active !== '0') {
            throw new InvalidArgumentException('Payment request is already processed.');
        }

        return DB::transaction(function () use ($payment, $status, $amount, $fine, $reply, $staff) {
            $invoiceId = null;

            if ($status === 1) {
                $description = 'Amount credited through offline bank payment Request ID : '.$payment->id;
                $isTransport = filled($payment->student_transport_fee_id);

                if ($isTransport) {
                    $result = $this->collect->depositTransport([
                        'student_transport_fee_id' => (int) $payment->student_transport_fee_id,
                        'student_session_id' => (int) $payment->student_session_id,
                        'date' => (string) $payment->payment_date,
                        'amount' => $amount,
                        'amount_discount' => 0,
                        'amount_fine' => $fine,
                        'payment_mode' => 'bank_payment',
                        'description' => $description,
                        'discounts' => [],
                    ], $staff);
                } else {
                    $result = $this->collect->deposit([
                        'student_fees_master_id' => (int) $payment->student_fees_master_id,
                        'fee_groups_feetype_id' => (int) $payment->fee_groups_feetype_id,
                        'student_session_id' => (int) $payment->student_session_id,
                        'date' => (string) $payment->payment_date,
                        'amount' => $amount,
                        'amount_discount' => 0,
                        'amount_fine' => $fine,
                        'payment_mode' => 'bank_payment',
                        'description' => $description,
                        'discounts' => [],
                    ], $staff);
                }

                $invoiceId = $result['invoice_id'].'/'.$result['sub_invoice_id'];
                $payment->invoice_id = $invoiceId;
            }

            $payment->is_active = (string) $status;
            $payment->reply = $reply;
            $payment->approve_date = now()->format('Y-m-d H:i:s');
            $payment->approved_by = (int) $staff->id;
            $payment->save();

            return ['invoice_id' => $invoiceId];
        });
    }

    public function attachmentAbsolutePath(object $payment): ?string
    {
        $name = trim((string) ($payment->attachment ?? ''));
        if ($name === '') {
            return null;
        }

        $path = public_path('uploads/offline_payments/'.$name);

        return File::isFile($path) ? $path : null;
    }

    protected function isPastDue(mixed $dueDate, mixed $paymentDate): bool
    {
        if ($dueDate === null || $dueDate === '' || $dueDate === '0000-00-00') {
            return false;
        }
        if ($paymentDate === null || $paymentDate === '') {
            return false;
        }

        return strtotime((string) $dueDate) < strtotime((string) $paymentDate);
    }

    protected function paidFineForFee(int $masterId, int $feetypeId): float
    {
        if ($masterId <= 0 || $feetypeId <= 0) {
            return 0.0;
        }

        $raw = DB::table('student_fees_deposite')
            ->where('student_fees_master_id', $masterId)
            ->where('fee_groups_feetype_id', $feetypeId)
            ->value('amount_detail');

        return $this->sumFine($raw);
    }

    protected function paidFineForTransport(int $transportFeeId): float
    {
        if ($transportFeeId <= 0) {
            return 0.0;
        }

        $raw = DB::table('student_fees_deposite')
            ->where('student_transport_fee_id', $transportFeeId)
            ->value('amount_detail');

        return $this->sumFine($raw);
    }

    protected function sumFine(mixed $raw): float
    {
        if ($raw === null || $raw === '' || $raw === '0') {
            return 0.0;
        }
        $decoded = json_decode((string) $raw, true);
        if (! is_array($decoded)) {
            return 0.0;
        }
        $total = 0.0;
        foreach ($decoded as $entry) {
            $total += (float) ($entry['amount_fine'] ?? 0);
        }

        return round($total, 2);
    }
}
