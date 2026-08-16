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

        <form id="maintenance_form" method="post" action="{{ url('schsettings/save_maintenance') }}">
            @csrf
            <input type="hidden" name="sch_id" value="{{ $result->id }}">

            <div class="form-group">
                <label>{{ __('system.maintenance_mode') }}</label>
                <div>
                    <input id="maintenance_mode" name="maintenance_mode" type="checkbox" value="1"
                        @checked((string) ($result->maintenance_mode ?? '') === '1')>
                </div>
            </div>

            @if($canEdit)
                <button type="submit" class="btn btn-primary">{{ __('system.save') }}</button>
            @endif
        </form>
    </div>
</div>
@push('scripts')
<script>
    $(document).on('submit', '#maintenance_form', function (e) {
        e.preventDefault();
        var form = $(this);
        var submit_btn = $('button[type=submit]', this);
        submit_btn.prop('disabled', true);
        $.ajax({
            type: 'POST',
            url: form.attr('action'),
            data: form.serialize(),
            dataType: 'json',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            success: function (data) {
                // CI view checks string "fail"; controller returns numeric 0/1 — preserve both shapes.
                if (data.status == 'fail' || data.status === 0) {
                    var message = '';
                    $.each(data.error || {}, function (index, value) { message += value; });
                    if (typeof errorMsg === 'function') { errorMsg(message); } else { alert(message); }
                } else {
                    if (typeof successMsg === 'function') { successMsg(data.message); } else { alert(data.message); }
                }
            },
            complete: function () { submit_btn.prop('disabled', false); }
        });
    });
</script>
@endpush
