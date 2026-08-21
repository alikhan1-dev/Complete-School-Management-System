@include('reports::admin.finance.hub')

@php $currency = $reports->currencySymbol(); @endphp

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-search"></i> {{ __('system.select_criteria') }}</h3>
    </div>
    <form action="{{ url('financereports/expensegroup') }}" method="post">
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
                <div class="col-sm-6 col-md-3">
                    <div class="form-group">
                        <label>{{ __('system.expense_head') }}</label>
                        <select class="form-control" name="head">
                            <option value="">{{ __('system.select') }}</option>
                            @foreach($headlist as $head)
                                <option value="{{ $head->id }}" @selected((string) $filters['head'] === (string) $head->id)>{{ $head->exp_category }}</option>
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

@if($searched)
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title"><i class="fa fa-money"></i> {{ __('system.expense_group_report') }}</h3>
        </div>
        <div class="box-body table-responsive">
            <table class="table table-striped table-bordered table-hover">
                <thead>
                    <tr>
                        <th>{{ __('system.expense_head') }}</th>
                        <th>{{ __('system.expense_id') }}</th>
                        <th>{{ __('system.name') }}</th>
                        <th>{{ __('system.date') }}</th>
                        <th>{{ __('system.invoice_number') }}</th>
                        <th class="text text-right">{{ __('system.amount') }} ({{ $currency }})</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        @if($row['type'] === 'row')
                            <tr>
                                <td>{{ $row['category'] }}</td>
                                <td>{{ $row['id'] }}</td>
                                <td>{{ $row['name'] }}</td>
                                <td>{{ $reports->formatDate($row['date']) }}</td>
                                <td>{{ $row['invoice_no'] }}</td>
                                <td class="text text-right">{{ $reports->formatAmount($row['amount']) }}</td>
                            </tr>
                        @elseif($row['type'] === 'subtotal')
                            <tr>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td><b>{{ __('system.sub_total') }}</b></td>
                                <td class="text text-right"><b>{{ $currency }}{{ $reports->formatAmount($row['amount']) }}</b></td>
                            </tr>
                        @elseif($row['type'] === 'total')
                            <tr>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td><b>{{ __('system.total') }}</b></td>
                                <td class="text text-right"><b>{{ $currency }}{{ $reports->formatAmount($row['amount']) }}</b></td>
                            </tr>
                        @endif
                    @empty
                        <tr><td colspan="6">{{ __('system.no_record_found') }}</td></tr>
                    @endforelse
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
