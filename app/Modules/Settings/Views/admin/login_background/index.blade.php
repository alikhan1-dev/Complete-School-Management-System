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
                <h4>{{ __('system.admin_penal') }}</h4>
                <p>
                    <img src="{{ $logos->publicUrl('admin_login_page_background', $result->admin_login_page_background ?? null) }}"
                         alt="admin login background" style="max-width:304px;max-height:236px">
                </p>
                <p class="text-muted">(1460px X 1080px)</p>
                @if($canEdit)
                    <form method="post" action="{{ url('schsettings/add_admin_login_background') }}" enctype="multipart/form-data" class="login-bg-form">
                        @csrf
                        <input type="hidden" name="id" value="{{ $result->id }}">
                        <input type="hidden" name="logo_type" value="admin_logo">
                        <input type="file" name="file" accept=".jpg,.jpeg,.png" required>
                        <button type="submit" class="btn btn-primary btn-sm">{{ __('system.update') }}</button>
                    </form>
                @endif
            </div>
            <div class="col-md-6">
                <h4>{{ __('system.user_penal') }}</h4>
                <p>
                    <img src="{{ $logos->publicUrl('user_login_page_background', $result->user_login_page_background ?? null) }}"
                         alt="user login background" style="max-width:304px;max-height:236px">
                </p>
                <p class="text-muted">(1460px X 1080px)</p>
                @if($canEdit)
                    <form method="post" action="{{ url('schsettings/add_admin_login_background') }}" enctype="multipart/form-data" class="login-bg-form">
                        @csrf
                        <input type="hidden" name="id" value="{{ $result->id }}">
                        <input type="hidden" name="logo_type" value="user_login">
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
    $('.login-bg-form').on('submit', function (e) {
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
                    alert((data.error && data.error.file) ? data.error.file : (data.message || 'Upload failed.'));
                }
            }
        });
    });
</script>
@endpush
