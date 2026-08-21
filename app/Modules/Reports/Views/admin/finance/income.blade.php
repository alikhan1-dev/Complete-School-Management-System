@include('reports::admin.finance.hub')

@php $currency = $reports->currencySymbol(); @endphp

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-search"></i> {{ __('system.select_criteria') }}</h3>
    </div>
    <form action="{{ url('financereports/income') }}" method="post">
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
            <h3 class="box-title"><i class="fa fa-money"></i> {{ __('system.income_report') }}</h3>
        </div>
        <div class="box-body table-responsive">
            <table class="table table-striped table-bordered table-hover">
                <thead>
                    <tr>
                        <th>{{ __('system.name') }}</th>
                        <th>{{ __('system.invoice_number') }}</th>
                        <th>{{ __('system.income_head') }}</th>
                        <th>{{ __('system.date') }}</th>
                        <th class="text text-right">{{ __('system.amount') }} ({{ $currency }})</th>
                    </tr>
                </thead>
                <tbody>
                    @php $grand = 0; @endphp
                    @forelse($incomeList as $row)
                        @php $grand += (float) $row->amount; @endphp
                        <tr>
                            <td>{{ $row->name }}</td>
                            <td>{{ $row->invoice_no }}</td>
                            <td>{{ $row->income_category }}</td>
                            <td>{{ $reports->formatDate($row->date) }}</td>
                            <td class="text text-right">{{ $currency }}{{ $reports->formatAmount($row->amount) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5">{{ __('system.no_record_found') }}</td></tr>
                    @endforelse
                    @if(!empty($incomeList))
                        <tr>
                            <td colspan="3"></td>
                            <td><b>{{ __('system.grand_total') }}</b></td>
                            <td class="text text-right"><b>{{ $currency }}{{ $reports->formatAmount($grand) }}</b></td>
                        </tr>
                    @endif
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
