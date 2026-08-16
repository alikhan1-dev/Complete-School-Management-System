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

        <form id="chatsetting_form" method="post" action="{{ url('schsettings/savechatsetting') }}">
            @csrf
            <input type="hidden" name="sch_id" value="{{ $result->id }}">

            <div class="form-group">
                <label>{{ __('system.allow_student_to_delete_chat') }}</label>
                <div>
                    <input id="student_delete_chat" name="student_delete_chat" type="checkbox" value="1"
                        @checked((string) ($result->student_delete_chat ?? '') === '1')>
                </div>
            </div>

            <div class="form-group">
                <label>{{ __('system.allow_guardian_to_delete_chat') }}</label>
                <div>
                    <input id="guardian_delete_chat" name="guardian_delete_chat" type="checkbox" value="1"
                        @checked((string) ($result->guardian_delete_chat ?? '') === '1')>
                </div>
            </div>

            <div class="form-group">
                <label>{{ __('system.allow_staff_to_delete_chat') }}</label>
                <div>
                    <input id="staff_delete_chat" name="staff_delete_chat" type="checkbox" value="1"
                        @checked((string) ($result->staff_delete_chat ?? '') === '1')>
                </div>
            </div>

            @if($canEdit)
                <button type="submit" class="btn btn-primary edit_student_guardian">{{ __('system.save') }}</button>
            @endif
        </form>
    </div>
</div>
@push('scripts')
<script>
    $('.edit_student_guardian').on('click', function (e) {
        e.preventDefault();
        var $this = $(this);
        $this.prop('disabled', true);
        $.ajax({
            url: '{{ url('schsettings/savechatsetting') }}',
            type: 'POST',
            data: $('#chatsetting_form').serialize(),
            dataType: 'json',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            success: function (data) {
                if (data.status == 'fail') {
                    var message = '';
                    $.each(data.error || {}, function (index, value) { message += value; });
                    if (typeof errorMsg === 'function') { errorMsg(message); } else { alert(message); }
                } else {
                    if (typeof successMsg === 'function') { successMsg(data.message); } else { alert(data.message); }
                }
            },
            complete: function () { $this.prop('disabled', false); }
        });
    });
</script>
@endpush
