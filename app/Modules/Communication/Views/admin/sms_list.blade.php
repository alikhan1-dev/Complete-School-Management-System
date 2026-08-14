@php
    $activeTab = $activeTab ?? 'clickatell';
    if (! isset($gateways[$activeTab])) {
        $activeTab = array_key_first($gateways) ?: 'clickatell';
    }
@endphp

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
    <div class="col-md-12">
        <div class="nav-tabs-custom">
            <div class="box-header with-border">
                <h3 class="box-title">{{ $pageTitle ?? 'SMS Setting' }}</h3>
            </div>
            <ul class="nav nav-tabs">
                @foreach($gateways as $type => $gateway)
                    <li class="{{ $activeTab === $type ? 'active' : '' }}">
                        <a href="#tab_{{ $type }}" data-toggle="tab">{{ $gateway['label'] }}</a>
                    </li>
                @endforeach
            </ul>
            <div class="tab-content">
                @foreach($gateways as $type => $gateway)
                    @php
                        $row = $smsByType[$type] ?? null;
                    @endphp
                    <div class="tab-pane {{ $activeTab === $type ? 'active' : '' }}" id="tab_{{ $type }}">
                        <form method="post" action="{{ url('smsconfig/'.$gateway['action']) }}" class="form-horizontal" accept-charset="utf-8">
                            @csrf
                            <div class="box-body">
                                <div class="col-md-7">
                                    @foreach($gateway['fields'] as $field)
                                        @php
                                            $input = $field['input'] ?? 'text';
                                            $value = old($field['name'], $row ? ($row->{$field['column']} ?? '') : '');
                                        @endphp
                                        <div class="form-group">
                                            <label class="col-sm-5 control-label">
                                                {{ $field['label'] }}
                                                @if(!empty($field['required']))
                                                    <small class="req"> *</small>
                                                @endif
                                            </label>
                                            <div class="col-sm-7">
                                                @if($input === 'status' || $input === 'select')
                                                    <select class="form-control" name="{{ $field['name'] }}">
                                                        @foreach(($field['options'] ?? $statuslist) as $optKey => $optLabel)
                                                            <option value="{{ $optKey }}" @selected((string) $value === (string) $optKey)>{{ $optLabel }}</option>
                                                        @endforeach
                                                    </select>
                                                @elseif($input === 'password')
                                                    <input type="password" class="form-control" name="{{ $field['name'] }}" value="{{ $value }}">
                                                @else
                                                    <input type="text" class="form-control" name="{{ $field['name'] }}" value="{{ $value }}">
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <div class="box-footer">
                                <div class="col-md-offset-3">
                                    @if(!empty($canEdit))
                                        <button type="submit" class="btn btn-primary">Save</button>
                                    @endif
                                </div>
                            </div>
                        </form>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
