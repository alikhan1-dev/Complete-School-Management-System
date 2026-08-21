@include('reports::admin.finance.hub')

@php $currency = $reports->currencySymbol(); @endphp

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-search"></i> {{ __('system.select_criteria') }}</h3>
    </div>
    <form action="{{ url('financereports/incomeexpensebalancereport') }}" method="post">
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

@if($searched)
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title"><i class="fa fa-money"></i> {{ __('system.income_expense_balance_report') }}</h3>
        </div>
        <div class="box-body table-responsive">
            <table class="table table-striped table-bordered table-hover">
                <thead>
                    <tr>
                        <th>{{ __('system.date') }}</th>
                        <th>{{ __('system.name') }}</th>
                        <th>{{ __('system.income_expense_head') }}</th>
                        <th width="20%">{{ __('system.description') }}</th>
                        <th class="text-right" width="12%">{{ __('system.income_money_in') }} ({{ $currency }})</th>
                        <th class="text-right" width="12%">{{ __('system.expense_money_out') }} ({{ $currency }})</th>
                        <th class="text-right" width="12%">{{ __('system.overall_balance') }} ({{ $currency }})</th>
                    </tr>
                </thead>
                <tbody>
                    @php $balance = 0; $income = 0; $expenses = 0; @endphp
                    @forelse($incomeexpensebalancereport as $value)
                        <tr>
                            <td>{{ $reports->formatDate($value['date']) }}</td>
                            <td>{{ $value['name'] }}</td>
                            <td>{{ $value['category'] }}</td>
                            <td>{{ $value['note'] }}</td>
                            <td class="text-right">
                                @if($value['source'] === 'income')
                                    @php $balance += $value['amount']; $income += $value['amount']; @endphp
                                    {{ $value['amount'] }}
                                @endif
                            </td>
                            <td class="text-right">
                                @if($value['source'] === 'expenses')
                                    @php $balance -= $value['amount']; $expenses += $value['amount']; @endphp
                                    {{ $value['amount'] }}
                                @endif
                            </td>
                            <th class="text-right">{{ $balance }}</th>
                        </tr>
                    @empty
                        <tr><td colspan="7">{{ __('system.no_record_found') }}</td></tr>
                    @endforelse
                </tbody>
                @if(!empty($incomeexpensebalancereport))
                    <tr>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td><b>{{ __('system.total') }}</b></td>
                        <td class="text-right"><b>{{ $currency }}{{ $income }}</b></td>
                        <td class="text-right"><b>{{ $currency }}{{ $expenses }}</b></td>
                        <td class="text-right"><b>{{ $currency }}{{ $balance }}</b></td>
                    </tr>
                @endif
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
