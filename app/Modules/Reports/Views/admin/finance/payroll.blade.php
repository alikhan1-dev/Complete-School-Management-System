@include('reports::admin.finance.hub')

@php $currency = $reports->currencySymbol(); @endphp

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-search"></i> {{ __('system.select_criteria') }}</h3>
    </div>
    <form action="{{ url('financereports/payroll') }}" method="post">
        @csrf
        <div class="box-body">
            <div class="row">
                <div class="col-sm-6 col-md-3">
                    <div class="form-group">
                        <label>{{ __('system.search_type') }}</label>
                        <select class="form-control" name="search_type" id="search_type">
                            @foreach($searchlist as $key => $label)
                                <option value="{{ $key }}" @selected((string) $filters['search_type'] === (string) $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3 period-dates" style="{{ ($filters['search_type'] ?? '') === 'period' ? '' : 'display:none;' }}">
                    <div class="form-group">
                        <label>{{ __('system.date_from') }}</label>
                        <input type="text" name="date_from" class="form-control" value="{{ $filters['date_from'] }}">
                    </div>
                </div>
                <div class="col-md-3 period-dates" style="{{ ($filters['search_type'] ?? '') === 'period' ? '' : 'display:none;' }}">
                    <div class="form-group">
                        <label>{{ __('system.date_to') }}</label>
                        <input type="text" name="date_to" class="form-control" value="{{ $filters['date_to'] }}">
                    </div>
                </div>
            </div>
        </div>
        <div class="box-footer">
            <button type="submit" name="search" value="search_filter" class="btn btn-primary btn-sm pull-right">
                <i class="fa fa-search"></i> {{ __('system.search') }}
            </button>
        </div>
    </form>
</div>

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-money"></i> {{ __('system.payroll_report') }}</h3>
        <small class="pull-right">{{ $label }}</small>
    </div>
    <div class="box-body table-responsive">
        <table class="table table-striped table-bordered table-hover">
            <thead>
                <tr>
                    <th>{{ __('system.name') }}</th>
                    <th>{{ __('system.role') }}</th>
                    <th>{{ __('system.designation') }}</th>
                    <th>{{ __('system.month_year') }}</th>
                    <th>{{ __('system.payslip') }} #</th>
                    <th class="text text-right">{{ __('system.basic_salary') }} ({{ $currency }})</th>
                    <th class="text text-right">{{ __('system.earning') }} ({{ $currency }})</th>
                    <th class="text text-right">{{ __('system.deduction') }} ({{ $currency }})</th>
                    <th class="text text-right">{{ __('system.gross_salary') }} ({{ $currency }})</th>
                    <th class="text text-right">{{ __('system.tax') }} ({{ $currency }})</th>
                    <th class="text text-right">{{ __('system.net_salary') }} ({{ $currency }})</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $sumBasic = 0; $sumEarn = 0; $sumDed = 0; $sumGross = 0; $sumTax = 0; $sumNet = 0;
                @endphp
                @forelse($payrollList as $value)
                    @php
                        $basic = (float) ($value->basic ?? 0);
                        $allow = (float) ($value->total_allowance ?? 0);
                        $ded = (float) ($value->total_deduction ?? 0);
                        $tax = (float) ($value->tax ?? 0);
                        $gross = $basic + $allow - $ded;
                        $net = (float) ($value->net_salary ?? 0);
                        $sumBasic += $basic;
                        $sumEarn += $allow;
                        $sumDed += $ded;
                        $sumGross += $gross > 0 ? $gross : 0;
                        $sumTax += $tax;
                        $sumNet += $net;
                        $monthKey = strtolower((string) ($value->month ?? ''));
                    @endphp
                    <tr>
                        <td>{{ $value->name }} {{ $value->surname }} ({{ $value->employee_id }})</td>
                        <td>{{ $value->user_type }}</td>
                        <td>{{ $value->designation }}</td>
                        <td>{{ $monthKey !== '' ? (__('system.'.$monthKey) !== 'system.'.$monthKey ? __('system.'.$monthKey) : $value->month) : '' }} - {{ $value->year }}</td>
                        <td>{{ $value->id }}@if(!empty($value->payment_mode)) <small>({{ $value->payment_mode }})</small>@endif</td>
                        <td class="text text-right">@if($basic > 0){{ $reports->formatAmount($basic) }}@endif</td>
                        <td class="text text-right">@if($allow > 0){{ $reports->formatAmount($allow) }}@endif</td>
                        <td class="text text-right">@if($ded > 0){{ $reports->formatAmount($ded) }}@endif</td>
                        <td class="text text-right">@if($gross > 0){{ $reports->formatAmount($gross) }}@endif</td>
                        <td class="text text-right">@if($tax > 0){{ $reports->formatAmount($tax) }}@endif</td>
                        <td class="text text-right">@if($net > 0){{ $reports->formatAmount($net) }}@endif</td>
                    </tr>
                @empty
                    <tr><td colspan="11">{{ __('system.no_record_found') }}</td></tr>
                @endforelse
                @if(!empty($payrollList))
                    <tr>
                        <th colspan="5" class="text text-right">{{ __('system.grand_total') }}</th>
                        <th class="text text-right">{{ $reports->formatAmount($sumBasic) }}</th>
                        <th class="text text-right">{{ $reports->formatAmount($sumEarn) }}</th>
                        <th class="text text-right">{{ $reports->formatAmount($sumDed) }}</th>
                        <th class="text text-right">{{ $reports->formatAmount($sumGross) }}</th>
                        <th class="text text-right">{{ $reports->formatAmount($sumTax) }}</th>
                        <th class="text text-right">{{ $reports->formatAmount($sumNet) }}</th>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>

@push('scripts')
<script>
(function ($) {
    $('#search_type').on('change', function () {
        $('.period-dates').toggle($(this).val() === 'period');
    });
})(jQuery);
</script>
@endpush
