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
            <a href="{{ route('user.offlinepayment.requests') }}" class="btn btn-default btn-sm">{{ __('system.view') }}</a>
        </div>
    </div>

    @if(filled($instructionHtml))
        <div class="box-body pb0">
            <label>{{ __('system.instructions') }}</label>
            <div class="unorder-list">{!! $instructionHtml !!}</div>
        </div>
    @endif

    <form method="post" action="{{ route('user.offlinepayment.index') }}" enctype="multipart/form-data">
        @csrf
        <div class="box-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>{{ __('system.date_of_payment') }} <span class="text-danger">*</span></label>
                        <input type="date" name="payment_date" class="form-control" value="{{ old('payment_date') }}" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>{{ __('system.payment_mode') }} <span class="text-danger">*</span></label>
                        <input type="text" name="bank_from" class="form-control" value="{{ old('bank_from') }}" required>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>{{ __('system.payment_from') }} <span class="text-danger">*</span></label>
                        <input type="text" name="bank_account_transferred" class="form-control" value="{{ old('bank_account_transferred') }}" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>{{ __('system.reference') }}</label>
                        <input type="text" name="reference" class="form-control" value="{{ old('reference') }}">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>{{ __('system.amount_paid') }} ({{ $currencySymbol }}) <span class="text-danger">*</span></label>
                        <input type="text" name="amount" class="form-control" value="{{ old('amount') }}" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>{{ __('system.proof_of_payment') }}</label>
                        <input type="file" name="attachment" class="form-control">
                    </div>
                </div>
            </div>
        </div>
        <div class="box-footer">
            <button type="submit" class="btn btn-primary pull-right">{{ __('system.save') }}</button>
        </div>
    </form>
</div>
