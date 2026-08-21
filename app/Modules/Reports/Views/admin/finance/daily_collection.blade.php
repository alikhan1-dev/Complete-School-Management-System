@include('reports::admin.finance.hub')

@php $currency = $reports->currencySymbol(); @endphp

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-search"></i> {{ __('system.select_criteria') }}</h3>
    </div>
    <form action="{{ url('financereports/reportdailycollection') }}" method="post">
        @csrf
        <div class="box-body">
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>{{ __('system.date_from') }} <small class="req">*</small></label>
                        <input type="text" name="date_from" class="form-control" value="{{ $filters['date_from'] }}">
                        @error('date_from')<span class="text-danger">{{ $message }}</span>@enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>{{ __('system.date_to') }} <small class="req">*</small></label>
                        <input type="text" name="date_to" class="form-control" value="{{ $filters['date_to'] }}">
                        @error('date_to')<span class="text-danger">{{ $message }}</span>@enderror
                    </div>
                </div>
            </div>
        </div>
        <div class="box-footer">
            <button type="submit" class="btn btn-primary btn-sm pull-right"><i class="fa fa-search"></i> {{ __('system.search') }}</button>
        </div>
    </form>
</div>

@if($searched && is_array($fees_data))
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title"><i class="fa fa-users"></i> {{ __('system.daily_collection_report') }}</h3>
        </div>
        <div class="box-body table-responsive">
            <table class="table table-striped table-bordered table-hover">
                <thead>
                    <tr>
                        <th>{{ __('system.date') }}</th>
                        <th class="text text-center">{{ __('system.total_transactions') }}</th>
                        <th class="text text-right">{{ __('system.amount') }}</th>
                        <th class="text text-right">{{ __('system.action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @php $totalAmount = 0; @endphp
                    @foreach($fees_data as $feeKey => $feeValue)
                        @php $totalAmount += $feeValue['amt']; @endphp
                        <tr>
                            <td>{{ $reports->formatDate(date('Y-m-d', $feeKey)) }}</td>
                            <td class="text text-center">{{ $feeValue['count'] }}</td>
                            <td class="text text-right">{{ $currency }}{{ $reports->formatAmount($feeValue['amt']) }}</td>
                            <td class="text text-right">
                                @if($feeValue['count'] > 0)
                                    <button type="button"
                                            class="btn btn-default btn-xs viewDeposit"
                                            data-date="{{ date('Y-m-d', $feeKey) }}"
                                            data-fees="{{ implode(',', $feeValue['student_fees_deposite_ids']) }}">
                                        {{ __('system.view') }}
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    <tr>
                        <td></td>
                        <td class="text text-right"><b>{{ __('system.total') }}</b></td>
                        <td class="text text-right"><b>{{ $currency }}{{ $reports->formatAmount($totalAmount) }}</b></td>
                        <td></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <div id="collectionDepositModal" class="modal fade" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">{{ __('system.daily_collection_report') }}</h4>
                </div>
                <div class="modal-body" id="collectionDepositBody"></div>
            </div>
        </div>
    </div>
@endif

@push('scripts')
<script>
$(function () {
    $(document).on('click', '.viewDeposit', function () {
        $.post(@json(url('financereports/feeCollectionStudentDeposit')), {
            _token: @json(csrf_token()),
            date: $(this).data('date'),
            fees_id: $(this).data('fees')
        }, function (res) {
            if (res.status == 1) {
                $('#collectionDepositBody').html(res.page);
                $('#collectionDepositModal').modal('show');
            }
        }, 'json');
    });
});
</script>
@endpush
