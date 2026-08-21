@include('reports::admin.finance.hub')

@php $currency = $reports->currencySymbol(); @endphp

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-search"></i> {{ __('system.select_criteria') }}</h3>
    </div>
    <form action="{{ url('financereports/onlinefees_report') }}" method="post">
        @csrf
        <div class="box-body">
            <div class="row">
                <div class="col-sm-6 col-md-3">
                    <div class="form-group">
                        <label>{{ __('system.search_type') }} <small class="req">*</small></label>
                        <select class="form-control" name="search_type" id="search_type">
                            @foreach($searchlist as $key => $label)
                                <option value="{{ $key }}" @selected((string) $filters['search_type'] === (string) $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('search_type')<span class="text-danger">{{ $message }}</span>@enderror
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

@if($searched && empty($collectlist))
    <div class="alert alert-info">{{ __('system.no_record_found') }}</div>
@elseif($searched)
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title"><i class="fa fa-money"></i> {{ __('system.online_fees_report') }}</h3>
        </div>
        <div class="box-body table-responsive">
            <table class="table table-striped table-bordered table-hover">
                <thead>
                    <tr>
                        <th>{{ __('system.payment_id') }}</th>
                        <th>{{ __('system.date') }}</th>
                        <th>{{ __('system.admission_no') }}</th>
                        <th>{{ __('system.name') }}</th>
                        <th>{{ __('system.class') }}</th>
                        <th>{{ __('system.fee_type') }}</th>
                        <th>{{ __('system.mode') }}</th>
                        <th>{{ __('system.transaction_description') }}</th>
                        <th class="text text-right">{{ __('system.amount') }} ({{ $currency }})</th>
                        <th class="text text-right">{{ __('system.discount') }} ({{ $currency }})</th>
                        <th class="text text-right">{{ __('system.fine') }} ({{ $currency }})</th>
                        <th class="text text-right">{{ __('system.total') }} ({{ $currency }})</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $allamount = 0;
                        $alldiscount = 0;
                        $finetotal = 0;
                        $alltotal = 0;
                    @endphp
                    @foreach($collectlist as $collect)
                        @php
                            $amt = (float) $collect['amount'];
                            $disc = (float) $collect['amount_discount'];
                            $fine = (float) $collect['amount_fine'];
                            $line = $amt + $fine;
                            $allamount += $amt;
                            $alldiscount += $disc;
                            $finetotal += $fine;
                            $alltotal += $line;
                        @endphp
                        <tr>
                            <td>{{ $collect['id'] }}/{{ $collect['inv_no'] }}</td>
                            <td>{{ $reports->formatDate($collect['date']) }}</td>
                            <td>{{ $collect['admission_no'] }}</td>
                            <td>{{ $reports->fullName((object) $collect) }}</td>
                            <td>{{ $collect['class'] }} ({{ $collect['section'] }})</td>
                            <td>
                                @if(!empty($collect['is_system']))
                                    {{ __('system.'.$collect['type']) }}
                                @else
                                    {{ $collect['type'] }} ({{ $collect['code'] }})
                                @endif
                            </td>
                            <td>{{ $collect['payment_mode'] }}</td>
                            <td>{{ $collect['description'] }}</td>
                            <td class="text text-right">{{ $reports->formatAmount($amt) }}</td>
                            <td class="text text-right">{{ $reports->formatAmount($disc) }}</td>
                            <td class="text text-right">{{ $reports->formatAmount($fine) }}</td>
                            <td class="text text-right">{{ $reports->formatAmount($line) }}</td>
                        </tr>
                    @endforeach
                    <tr>
                        <td colspan="7"></td>
                        <td style="font-weight:bold">{{ __('system.grand_total') }}</td>
                        <td class="text text-right" style="font-weight:bold">{{ $currency }}{{ $reports->formatAmount($allamount) }}</td>
                        <td class="text text-right" style="font-weight:bold">{{ $currency }}{{ $reports->formatAmount($alldiscount) }}</td>
                        <td class="text text-right" style="font-weight:bold">{{ $currency }}{{ $reports->formatAmount($finetotal) }}</td>
                        <td class="text text-right" style="font-weight:bold">{{ $currency }}{{ $reports->formatAmount($alltotal) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
@endif

@push('scripts')
<script>
(function ($) {
    $('#search_type').on('change', function () {
        $('.period-dates').toggle($(this).val() === 'period');
    });
})(jQuery);
</script>
@endpush
