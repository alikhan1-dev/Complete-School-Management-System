@php
    $summary = $salarySummary ?? ['net_salary' => 0, 'earnings' => 0, 'deduction' => 0, 'basic_salary' => 0, 'tax' => 0];
    $symbol = $currencySymbol ?? '';
    $formatAmount = static fn (float $amount): string => number_format($amount, 2, '.', '');
    $grossSalary = (float) ($summary['basic_salary'] ?? 0) + (float) ($summary['earnings'] ?? 0) - (float) ($summary['deduction'] ?? 0);
    $totalDeduction = (float) ($summary['deduction'] ?? 0) + (float) ($summary['tax'] ?? 0);
    $dateFormat = $schoolDateFormat ?? 'Y-m-d';
@endphp

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">{{ __('system.payroll') }}</h3>
    </div>
    <div class="box-body">
        <div class="row">
            <div class="col-lg-3 col-md-4 col-sm-6">
                <div class="well well-sm text-center">
                    <h5>{{ __('system.total_net_salary_paid') }}</h5>
                    <h4>{{ $symbol }}{{ $formatAmount((float) ($summary['net_salary'] ?? 0)) }}</h4>
                </div>
            </div>
            <div class="col-lg-3 col-md-4 col-sm-6">
                <div class="well well-sm text-center">
                    <h5>{{ __('system.total_gross_salary') }}</h5>
                    <h4>{{ $symbol }}{{ $formatAmount($grossSalary) }}</h4>
                </div>
            </div>
            <div class="col-lg-3 col-md-4 col-sm-6">
                <div class="well well-sm text-center">
                    <h5>{{ __('system.total_earning') }}</h5>
                    <h4>{{ $symbol }}{{ $formatAmount((float) ($summary['earnings'] ?? 0)) }}</h4>
                </div>
            </div>
            <div class="col-lg-3 col-md-4 col-sm-6">
                <div class="well well-sm text-center">
                    <h5>{{ __('system.total_deduction') }}</h5>
                    <h4>{{ $symbol }}{{ $formatAmount($totalDeduction) }}</h4>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                <tr>
                    <th>{{ __('system.payslip') }} #</th>
                    <th>{{ __('system.month_year') }}</th>
                    <th>{{ __('system.date') }}</th>
                    <th>{{ __('system.mode') }}</th>
                    <th>{{ __('system.status') }}</th>
                    <th class="text-right">{{ __('system.net_salary') }} ({{ $symbol }})</th>
                    <th class="text-right">{{ __('system.action') }}</th>
                </tr>
                </thead>
                <tbody>
                @forelse($staffPayroll ?? [] as $payrollRow)
                    @php
                        $status = (string) ($payrollRow['status'] ?? '');
                        $statusClass = match ($status) {
                            'paid' => 'label-success',
                            'generated' => 'label-warning',
                            default => 'label-default',
                        };
                        $paymentDate = (string) ($payrollRow['payment_date'] ?? '');
                        $paymentDisplay = $paymentDate !== '' && $paymentDate !== '0000-00-00'
                            ? date($dateFormat, strtotime($paymentDate))
                            : '';
                        $monthKey = strtolower((string) ($payrollRow['month'] ?? ''));
                    @endphp
                    <tr>
                        <td>
                            {{ $payrollRow['id'] }}
                            @if(!empty($payrollRow['remark']))
                                <div class="text-muted small">{{ $payrollRow['remark'] }}</div>
                            @endif
                        </td>
                        <td>{{ __('system.'.$monthKey) }} - {{ $payrollRow['year'] ?? '' }}</td>
                        <td>{{ $paymentDisplay }}</td>
                        <td>{{ ($paymentModeLabels ?? [])[$payrollRow['payment_mode'] ?? ''] ?? '' }}</td>
                        <td>
                            <span class="label {{ $statusClass }}">
                                {{ ($payrollStatusLabels ?? [])[$status] ?? $status }}
                            </span>
                        </td>
                        <td class="text-right">{{ $formatAmount((float) ($payrollRow['net_salary'] ?? 0)) }}</td>
                        <td class="text-right">
                            @if($status === 'paid' && ($canViewPayroll ?? false))
                                <a href="{{ route('payroll.payslip_view', $payrollRow['id']) }}"
                                   class="btn btn-primary btn-xs" target="_blank" rel="noopener">
                                    {{ __('system.view_payslip') }}
                                </a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center">{{ __('system.no_record_found') }}</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
