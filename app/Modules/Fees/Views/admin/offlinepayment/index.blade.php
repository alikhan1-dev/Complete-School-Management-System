@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">{{ __('system.offline_bank_payments') }}</h3>
        <div class="box-tools">
            <a href="{{ route('fees.studentfee.index') }}" class="btn btn-default btn-sm">{{ __('system.collect_fees') }}</a>
        </div>
    </div>
    <div class="box-body table-responsive">
        <table class="table table-striped table-bordered table-hover">
            <thead>
            <tr>
                <th>{{ __('system.request_id') }}</th>
                <th>{{ __('system.admission_no') }}</th>
                <th>{{ __('system.name') }}</th>
                <th>{{ __('system.class') }}</th>
                <th>{{ __('system.payment_date') }}</th>
                <th>{{ __('system.submit_date') }}</th>
                <th class="text-right">{{ __('system.amount') }}</th>
                <th>{{ __('system.status') }}</th>
                <th>{{ __('system.status_date') }}</th>
                <th>{{ __('system.payment_id') }}</th>
                <th class="text-right">{{ __('system.action') }}</th>
            </tr>
            </thead>
            <tbody>
            @forelse($payments as $row)
                <tr>
                    <td>{{ $row->id }}</td>
                    <td>{{ $row->admission_no }}</td>
                    <td>{{ $offline->studentDisplayName($row) }}</td>
                    <td>{{ $row->class }}({{ $row->section }})</td>
                    <td>{{ $offline->formatDate($row->payment_date) }}</td>
                    <td>{{ $offline->formatDateTime($row->submit_date) }}</td>
                    <td class="text-right">{{ number_format((float) $row->amount, 2) }}</td>
                    <td>
                        @php $st = (string) $row->is_active; @endphp
                        @if($st === '1')
                            <span class="label label-success">{{ $offline->statusLabel($st) }}</span>
                        @elseif($st === '2')
                            <span class="label label-danger">{{ $offline->statusLabel($st) }}</span>
                        @else
                            <span class="label label-warning">{{ $offline->statusLabel($st) }}</span>
                        @endif
                    </td>
                    <td>{{ $offline->formatDateTime($row->approve_date) }}</td>
                    <td>{{ $row->invoice_id }}</td>
                    <td class="text-right">
                        <a href="{{ route('fees.offlinepayment.show', $row->id) }}" class="btn btn-primary btn-xs" title="{{ __('system.view') }}">
                            <i class="fa fa-reorder"></i>
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="11" class="text-center text-muted">{{ __('system.no_record_found') }}</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
