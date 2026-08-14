@php
    $emailType = old('email_type', $emaillist->email_type ?? '');
    $smtpHidden = $emailType === 'smtp' ? '' : 'display:none;';
    $sesHidden = $emailType === 'aws_ses' ? '' : 'display:none;';
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
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fa fa-envelope"></i> {{ $pageTitle ?? 'Email Setting' }}</h3>
            </div>
            <form method="post" action="{{ route('communication.emailconfig.save') }}" class="form-horizontal" accept-charset="utf-8">
                @csrf
                <div class="box-body">
                    <div class="form-group">
                        <label class="control-label col-md-3">Email Engine</label>
                        <div class="col-md-6">
                            <select id="email_type" name="email_type" class="form-control" autofocus>
                                @foreach($mailMethods as $methodKey => $methodLabel)
                                    <option value="{{ $methodKey }}" @selected($emailType === $methodKey)>{{ $methodLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="is_disabled" style="{{ $smtpHidden }}">
                        <div class="form-group">
                            <label class="control-label col-md-3">Email</label>
                            <div class="col-md-6">
                                <input name="smtp_email" type="text" class="form-control"
                                       value="{{ old('smtp_email', $emaillist->smtp_email ?? '') }}">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label col-md-3">SMTP Username</label>
                            <div class="col-md-6">
                                <input name="smtp_username" type="text" class="form-control"
                                       value="{{ old('smtp_username', $emaillist->smtp_username ?? '') }}">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label col-md-3">SMTP Password</label>
                            <div class="col-md-6">
                                <input name="smtp_password" type="password" class="form-control"
                                       value="{{ old('smtp_password', $emaillist->smtp_password ?? '') }}">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label col-md-3">SMTP Server</label>
                            <div class="col-md-6">
                                <input name="smtp_server" type="text" class="form-control"
                                       value="{{ old('smtp_server', $emaillist->smtp_server ?? '') }}">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label col-md-3">SMTP Port</label>
                            <div class="col-md-6">
                                <input name="smtp_port" type="text" class="form-control"
                                       value="{{ old('smtp_port', $emaillist->smtp_port ?? '') }}">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label col-md-3">SMTP Security</label>
                            <div class="col-md-6">
                                <select name="smtp_security" class="form-control">
                                    @foreach($smtpEncryption as $encKey => $encLabel)
                                        <option value="{{ $encKey }}" @selected(old('smtp_security', $emaillist->ssl_tls ?? '') == $encKey)>{{ $encLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label col-md-3">SMTP Auth</label>
                            <div class="col-md-6">
                                <select name="smtp_auth" class="form-control">
                                    @foreach($smtpAuth as $authKey => $authLabel)
                                        <option value="{{ $authKey }}" @selected(old('smtp_auth', $emaillist->smtp_auth ?? 'false') == $authKey)>{{ $authLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="is_disabled_ses" style="{{ $sesHidden }}">
                        <div class="form-group">
                            <label class="control-label col-md-3">Email</label>
                            <div class="col-md-6">
                                <input name="aws_email" type="text" class="form-control"
                                       value="{{ old('aws_email', $emaillist->smtp_username ?? '') }}">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label col-md-3">Access Key ID</label>
                            <div class="col-md-6">
                                <input name="access_key" type="text" class="form-control"
                                       value="{{ old('access_key', $emaillist->api_key ?? '') }}">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label col-md-3">Secret Access Key</label>
                            <div class="col-md-6">
                                <input name="secret_access_key" type="password" class="form-control"
                                       value="{{ old('secret_access_key', $emaillist->api_secret ?? '') }}">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label col-md-3">Region</label>
                            <div class="col-md-6">
                                <input name="region" type="text" class="form-control"
                                       value="{{ old('region', $emaillist->region ?? '') }}">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="box-footer">
                    <div class="col-md-6 col-md-offset-3">
                        @if(!empty($canEdit))
                            <button type="submit" class="btn btn-info">Save</button>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(document).on('change', '#email_type', function () {
    var selected = $(this).val();
    if (selected === 'smtp') {
        $('.is_disabled_ses').hide();
        $('.is_disabled').show();
    } else if (selected === 'aws_ses') {
        $('.is_disabled').hide();
        $('.is_disabled_ses').show();
    } else {
        $('.is_disabled_ses').hide();
        $('.is_disabled').hide();
    }
});
</script>
@endpush
