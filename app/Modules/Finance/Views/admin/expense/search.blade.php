@php
    $filters = $filters ?? [];
    $searchlist = $searchlist ?? [];
    $result = $result ?? null;
    $searched = ! empty($searched);
    $currencySymbol = $currencySymbol ?? '';
@endphp

@if($errors->any())
    <div class="alert alert-danger">
        <ul style="margin:0;padding-left:18px;">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-search"></i> {{ __('system.select_criteria') }}</h3>
    </div>
    <div class="box-body">
        <div class="row">
            <div class="col-md-6">
                <form method="post" action="{{ route('finance.expense.search') }}">
                    @csrf
                    <input type="hidden" name="button_type" value="search_filter">
                    <div class="form-group">
                        <label>{{ __('system.search_type') }} <small class="req">*</small></label>
                        <select class="form-control" name="search_type" id="expense_search_type" required>
                            @foreach($searchlist as $key => $label)
                                <option value="{{ $key }}" @selected((string) ($filters['search_type'] ?? '') === (string) $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row period-dates" style="{{ ($filters['search_type'] ?? '') === 'period' ? '' : 'display:none;' }}">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{ __('system.date_from') }}</label>
                                <input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] ?? '' }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{ __('system.date_to') }}</label>
                                <input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] ?? '' }}">
                            </div>
                        </div>
                    </div>
                    <button type="submit" name="search" value="search_filter" class="btn btn-primary btn-sm pull-right">
                        <i class="fa fa-search"></i> {{ __('system.search') }}
                    </button>
                </form>
            </div>
            <div class="col-md-6">
                <form method="post" action="{{ route('finance.expense.search') }}">
                    @csrf
                    <input type="hidden" name="button_type" value="search_full">
                    <div class="form-group">
                        <label>{{ __('system.search') }} <small class="req">*</small></label>
                        <input type="text" name="search_text" class="form-control" required
                               value="{{ $filters['search_text'] ?? '' }}"
                               placeholder="{{ __('system.search_by_expense') }}">
                    </div>
                    <button type="submit" name="search" value="search_full" class="btn btn-primary btn-sm pull-right">
                        <i class="fa fa-search"></i> {{ __('system.search') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@if($searched)
    <div class="box box-info">
        <div class="box-header with-border">
            <h3 class="box-title"><i class="fa fa-money"></i> {{ __('system.expense_list') }}</h3>
            @if(($result['mode'] ?? '') === 'filter' && ! empty($result['date_from']))
                <p class="help-block" style="margin:8px 0 0;">
                    Expense Result From {{ $result['date_from'] }} To {{ $result['date_to'] }}
                </p>
            @endif
        </div>
        <div class="box-body table-responsive">
            <table class="table table-striped table-bordered table-hover">
                <thead>
                <tr>
                    <th>{{ __('system.name') }}</th>
                    <th>{{ __('system.invoice_number') }}</th>
                    <th>{{ __('system.expense_head') }}</th>
                    <th>{{ __('system.date') }}</th>
                    <th class="text-right">{{ __('system.amount') }} ({{ $currencySymbol }})</th>
                </tr>
                </thead>
                <tbody>
                @forelse(($result['rows'] ?? collect()) as $row)
                    <tr>
                        <td>{{ $row->name }}</td>
                        <td>{{ $row->invoice_no }}</td>
                        <td>{{ $row->exp_category }}</td>
                        <td>{{ $row->date }}</td>
                        <td class="text-right">{{ $currencySymbol }}{{ number_format((float) $row->amount, 2, '.', '') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center">{{ __('system.no_record_found') }}</td>
                    </tr>
                @endforelse
                @if(($result['rows'] ?? collect())->isNotEmpty())
                    <tr>
                        <td colspan="4"></td>
                        <td class="text-right">
                            <b>{{ __('system.grand_total') }} : {{ $currencySymbol }}{{ number_format((float) ($result['total'] ?? 0), 2, '.', '') }}</b>
                        </td>
                    </tr>
                @endif
                </tbody>
            </table>
        </div>
    </div>
@endif

<script>
    document.getElementById('expense_search_type')?.addEventListener('change', function () {
        document.querySelectorAll('.period-dates').forEach(function (el) {
            el.style.display = this.value === 'period' ? '' : 'none';
        }.bind(this));
    });
</script>
