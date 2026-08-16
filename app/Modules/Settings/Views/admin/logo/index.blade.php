@php
    $result = $result ?? (object) [];
@endphp
<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">{{ $pageTitle }}</h3>
    </div>
    <div class="box-body">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @error('file')
            <div class="alert alert-danger">{{ $message }}</div>
        @enderror

        <div class="row">
            <div class="col-md-6">
                <h4>{{ __('system.print_logo') }}</h4>
                <p>
                    <img src="{{ $logos->publicUrl('image', $result->image ?? null) }}" alt="print logo" style="max-width:304px;max-height:236px">
                </p>
                @if($canEdit)
                    <form method="post" action="{{ url('schsettings/ajax_editlogo') }}" enctype="multipart/form-data" class="logo-upload-form">
                        @csrf
                        <input type="hidden" name="id" value="{{ $result->id }}">
                        <input type="file" name="file" accept=".jpg,.jpeg,.png" required>
                        <button type="submit" class="btn btn-primary btn-sm">{{ __('system.update') }}</button>
                    </form>
                @endif
            </div>
            <div class="col-md-6">
                <h4>{{ __('system.admin_logo') }}</h4>
                <p>
                    <img src="{{ $logos->publicUrl('admin_logo', $result->admin_logo ?? null) }}" alt="admin logo" style="max-width:204px;max-height:60px">
                </p>
                @if($canEdit)
                    <form method="post" action="{{ url('schsettings/ajax_editadmin_adminlogo') }}" enctype="multipart/form-data" class="logo-upload-form">
                        @csrf
                        <input type="hidden" name="id" value="{{ $result->id }}">
                        <input type="file" name="file" accept=".jpg,.jpeg,.png" required>
                        <button type="submit" class="btn btn-primary btn-sm">{{ __('system.update') }}</button>
                    </form>
                @endif
            </div>
        </div>
        <div class="row" style="margin-top:20px">
            <div class="col-md-6">
                <h4>{{ __('system.admin_small_logo') }}</h4>
                <p>
                    <img src="{{ $logos->publicUrl('admin_small_logo', $result->admin_small_logo ?? null) }}" alt="admin small logo" style="max-width:32px;max-height:32px">
                </p>
                @if($canEdit)
                    <form method="post" action="{{ url('schsettings/ajax_editadmin_smalllogo') }}" enctype="multipart/form-data" class="logo-upload-form">
                        @csrf
                        <input type="hidden" name="id" value="{{ $result->id }}">
                        <input type="file" name="file" accept=".jpg,.jpeg,.png" required>
                        <button type="submit" class="btn btn-primary btn-sm">{{ __('system.update') }}</button>
                    </form>
                @endif
            </div>
            <div class="col-md-6">
                <h4>{{ __('system.app_logo') }}</h4>
                <p>
                    <img src="{{ $logos->publicUrl('app_logo', $result->app_logo ?? null) }}" alt="app logo" style="max-width:290px;max-height:51px">
                </p>
                @if($canEdit)
                    <form method="post" action="{{ url('schsettings/ajax_applogo') }}" enctype="multipart/form-data" class="logo-upload-form">
                        @csrf
                        <input type="hidden" name="id" value="{{ $result->id }}">
                        <input type="file" name="file" accept=".jpg,.jpeg,.png" required>
                        <button type="submit" class="btn btn-primary btn-sm">{{ __('system.update') }}</button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>
@push('scripts')
<script>
    $('.logo-upload-form').on('submit', function (e) {
        e.preventDefault();
        var $form = $(this);
        var fd = new FormData(this);
        $.ajax({
            url: $form.attr('action'),
            type: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function (data) {
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    var message = '';
                    if (data.error) {
                        $.each(data.error, function (index, value) {
                            message += value;
                        });
                    }
                    alert(message || data.message || 'Upload failed.');
                }
            }
        });
    });
</script>
@endpush
