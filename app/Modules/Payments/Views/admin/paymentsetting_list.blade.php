@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger">
        <ul style="margin:0;padding-left:18px;">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="row">
    <div class="col-md-10">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">{{ $pageTitle ?? 'Payment Methods' }}</h3>
            </div>
            <div class="box-body">
                @foreach($gateways as $type => $gateway)
                    @php
                        $row = $rowsByType[$type] ?? null;
                    @endphp
                    <div class="panel panel-default" @if(!empty($gateway['hidden'])) style="display:none" @endif>
                        <div class="panel-heading">{{ $gateway['label'] }}</div>
                        <div class="panel-body">
                            <form method="post" action="{{ url('admin/paymentsettings/'.$gateway['action']) }}" class="form-horizontal">
                                @csrf
                                @foreach($gateway['fields'] as $field)
                                    @php
                                        $input = $field['input'] ?? 'text';
                                        $value = old($field['name'], $row ? ($row->{$field['column']} ?? '') : '');
                                    @endphp
                                    <div class="form-group">
                                        <label class="col-sm-4 control-label">
                                            {{ $field['label'] }}
                                            @if(!empty($field['required']))
                                                <small class="req"> *</small>
                                            @endif
                                        </label>
                                        <div class="col-sm-8">
                                            <input type="{{ $input === 'password' ? 'password' : 'text' }}" class="form-control" name="{{ $field['name'] }}" value="{{ $value }}">
                                        </div>
                                    </div>
                                @endforeach
                                @php
                                    $chargeType = old('charge_type', $row->charge_type ?? 'none');
                                    $chargeValue = old($gateway['charge_field'], $row->charge_value ?? '');
                                @endphp
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">Processing Fees Type</label>
                                    <div class="col-sm-8">
                                        @foreach(['none' => 'None', 'percentage' => 'Percentage (%)', 'fix' => 'Fix Amount'] as $value => $label)
                                            <label class="radio-inline">
                                                <input type="radio" name="charge_type" value="{{ $value }}" {{ (string) $chargeType === $value ? 'checked' : '' }}> {{ $label }}
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">Percentage / Fix Amount</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" name="{{ $gateway['charge_field'] }}" value="{{ $chargeValue }}">
                                    </div>
                                </div>
                                @if(!empty($canEdit))
                                    <button type="submit" class="btn btn-primary">Save</button>
                                @endif
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="box box-primary">
            <form method="post" action="{{ url('admin/paymentsettings/setting') }}">
                @csrf
                <div class="box-body">
                    <label>Select Payment Gateway</label>
                    @foreach($gateways as $type => $gateway)
                        @if(empty($gateway['hidden']))
                            <div class="radio">
                                <label>
                                    <input type="radio" name="payment_setting" value="{{ $type }}" {{ $activeType === $type ? 'checked' : '' }}>
                                    {{ $gateway['label'] }}
                                </label>
                            </div>
                        @endif
                    @endforeach
                    <div class="radio">
                        <label>
                            <input type="radio" name="payment_setting" value="none" {{ $activeType === 'none' ? 'checked' : '' }}>
                            None
                        </label>
                    </div>
                </div>
                @if(!empty($canEdit))
                    <div class="box-footer">
                        <button type="submit" class="btn btn-primary">Save</button>
                    </div>
                @endif
            </form>
        </div>
        <div class="box box-primary">
            <form method="post" action="{{ url('admin/paymentsettings/payment_gateway_config') }}" id="payment_gateway_config">
                @csrf
                <div class="box-body">
                    <label>Payment Setting</label>
                    <select class="form-control" name="payment_setting">
                        <option value="">Select</option>
                        @foreach($gateways as $type => $gateway)
                            @if(empty($gateway['hidden']))
                                <option value="{{ $type }}" {{ old('payment_setting', $activeType) === $type ? 'selected' : '' }}>{{ $gateway['label'] }}</option>
                            @endif
                        @endforeach
                        <option value="none" {{ old('payment_setting') === 'none' ? 'selected' : '' }}>None</option>
                    </select>
                    <label style="margin-top:10px;">Account Type</label>
                    <select class="form-control" name="account_type">
                        <option value="">Select</option>
                        <option value="none" {{ old('account_type') === 'none' ? 'selected' : '' }}>None</option>
                        <option value="percentage" {{ old('account_type') === 'percentage' ? 'selected' : '' }}>Percentage</option>
                        <option value="fix" {{ old('account_type') === 'fix' ? 'selected' : '' }}>Fix Amount</option>
                    </select>
                    <label style="margin-top:10px;">Fine Amount</label>
                    <input type="text" class="form-control" name="fine_amount" value="{{ old('fine_amount') }}">
                </div>
                @if(!empty($canEdit))
                    <div class="box-footer">
                        <button type="submit" class="btn btn-primary">Save</button>
                    </div>
                @endif
            </form>
        </div>
    </div>
</div>
