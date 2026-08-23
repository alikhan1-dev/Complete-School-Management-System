@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@php
    $isTransport = filled($payment->student_transport_fee_id ?? null);
    $isPending = (string) $payment->is_active === '0';
@endphp

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">{{ __('system.payment_details') }}</h3>
        <div class="box-tools">
            <a href="{{ route('fees.offlinepayment.index') }}" class="btn btn-default btn-sm">{{ __('system.back') }}</a>
        </div>
    </div>
    <div class="box-body">
        <div class="row">
            <div class="col-md-6">
                <h4 class="mt0">{{ __('system.student_details') }}</h4>
                <h5 class="text-info pull-right">{{ __('system.request_id') }} : {{ $payment->id }}</h5>
                <table class="table table-striped">
                    <tr>
                        <th width="40%">{{ __('system.admission_no') }}</th>
                        <td>{{ $payment->admission_no }}</td>
                    </tr>
                    <tr>
                        <th>{{ __('system.name') }}</th>
                        <td>{{ $offline->studentDisplayName($payment) }}</td>
                    </tr>
                    <tr>
                        <th>{{ __('system.class') }}</th>
                        <td>{{ $payment->class }}({{ $payment->section }})</td>
                    </tr>
                    <tr>
                        <th>{{ __('system.mobile_number') }}</th>
                        <td>{{ $payment->mobileno }}</td>
                    </tr>
                    <tr>
                        <th>{{ __('system.email') }}</th>
                        <td>{{ $payment->email }}</td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                @if($isPending)
                    <form method="post" action="{{ route('fees.offlinepayment.update') }}">
                        @csrf
                        <input type="hidden" name="offline_fees_payment_id" value="{{ $payment->id }}">
                        <div class="form-group">
                            <label>{{ __('system.status') }}</label>
                            <div>
                                <label class="radio-inline">
                                    <input type="radio" name="payment_status" value="1" @checked(old('payment_status', '1') === '1')>
                                    {{ __('system.approve') }}
                                </label>
                                <label class="radio-inline">
                                    <input type="radio" name="payment_status" value="2" @checked(old('payment_status') === '2')>
                                    {{ __('system.reject') }}
                                </label>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>{{ __('system.amount') }} <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="amount" value="{{ old('amount', number_format($amountToPaid['amount'], 2, '.', '')) }}" required>
                        </div>
                        <div class="form-group">
                            <label>{{ __('system.fine') }} <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="fine" value="{{ old('fine', number_format($amountToPaid['fine'], 2, '.', '')) }}" required>
                        </div>
                        <div class="form-group">
                            <label>{{ __('system.comment_reason') }}</label>
                            <textarea class="form-control" name="reply" rows="3">{{ old('reply') }}</textarea>
                        </div>
                        <button type="submit" class="btn btn-info">{{ __('system.update') }}</button>
                    </form>
                @else
                    <p>
                        <strong>{{ __('system.status') }}:</strong>
                        {{ $offline->statusLabel($payment->is_active) }}
                    </p>
                    @if(filled($payment->invoice_id))
                        <p><strong>{{ __('system.payment_id') }}:</strong> {{ $payment->invoice_id }}</p>
                    @endif
                    @if(filled($payment->reply))
                        <p><strong>{{ __('system.comment_reason') }}:</strong> {{ $payment->reply }}</p>
                    @endif
                @endif
            </div>
        </div>

        <hr>

        <div class="row">
            <div class="col-md-8">
                <h4>{{ __('system.payment_details') }}</h4>
                <table class="table table-bordered">
                    <tr>
                        <th width="40%">{{ __('system.payment_date') }}</th>
                        <td>{{ $offline->formatDate($payment->payment_date) }}</td>
                    </tr>
                    <tr>
                        <th>{{ __('system.submit_date') }}</th>
                        <td>{{ $offline->formatDateTime($payment->submit_date) }}</td>
                    </tr>
                    <tr>
                        <th>{{ __('system.amount') }}</th>
                        <td>{{ number_format((float) $payment->amount, 2) }}</td>
                    </tr>
                    <tr>
                        <th>Bank From</th>
                        <td>{{ $payment->bank_from }}</td>
                    </tr>
                    <tr>
                        <th>Account Transferred</th>
                        <td>{{ $payment->bank_account_transferred }}</td>
                    </tr>
                    <tr>
                        <th>{{ __('system.reference') }}</th>
                        <td>{{ $payment->reference }}</td>
                    </tr>
                    @if($isTransport)
                        <tr>
                            <th>Transport</th>
                            <td>
                                {{ $payment->route_title }} / {{ $payment->pickup_point }}
                                @if(filled($payment->month)) ({{ $payment->month }}) @endif
                            </td>
                        </tr>
                    @else
                        <tr>
                            <th>Fee Group</th>
                            <td>{{ $payment->fee_group_name }}</td>
                        </tr>
                        <tr>
                            <th>Fee Type</th>
                            <td>{{ $payment->type }} ({{ $payment->code }})</td>
                        </tr>
                    @endif
                </table>
            </div>
            <div class="col-md-4">
                @if(filled($payment->attachment))
                    <p>
                        <a class="btn btn-default" href="{{ route('fees.offlinepayment.download', $payment->id) }}">
                            <i class="fa fa-download"></i> {{ __('system.download') }}
                        </a>
                    </p>
                @endif
            </div>
        </div>
    </div>
</div>
