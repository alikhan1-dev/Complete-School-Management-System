@php
    $result = $result ?? (object) [];
    $digitList = $digitList ?? range(1, 12);
@endphp
<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">{{ $pageTitle }}</h3>
    </div>
    <div class="box-body">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <form id="id_auto_generation_form" method="post" action="{{ url('schsettings/saveidautogeneration') }}">
            @csrf
            <input type="hidden" name="sch_id" value="{{ $result->id }}">

            <h4>{{ __('system.student_admission_no_auto_generation') }}</h4>

            <div class="form-group">
                <label>{{ __('system.auto_admission_no') }}</label>
                <div>
                    <input id="adm_auto_insert" name="adm_auto_insert" type="checkbox" value="1"
                        @checked((string) ($result->adm_auto_insert ?? '') === '1')>
                </div>
            </div>

            <div class="form-group">
                <label>{{ __('system.admission_no_prefix') }} <small class="req">*</small></label>
                <input type="text" name="adm_prefix" id="adm_prefix" class="form-control"
                       value="{{ old('adm_prefix', $result->adm_prefix ?? '') }}">
            </div>

            <div class="form-group">
                <label>{{ __('system.admission_no_digit') }} <small class="req">*</small></label>
                <select id="adm_no_digit" name="adm_no_digit" class="form-control">
                    <option value="">{{ __('system.select') }}</option>
                    @foreach($digitList as $digit)
                        <option value="{{ $digit }}" @selected((string) ($result->adm_no_digit ?? '') === (string) $digit)>{{ $digit }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label>{{ __('system.admission_start_from') }} <small class="req">*</small></label>
                <input type="text" name="adm_start_from" id="adm_start_from" class="form-control"
                       value="{{ old('adm_start_from', $result->adm_start_from ?? '') }}">
            </div>

            <hr>
            <h4>{{ __('system.staff_id_auto_generation') }}</h4>

            <div class="form-group">
                <label>{{ __('system.auto_staff_id') }}</label>
                <div>
                    <input id="staffid_auto_insert" name="staffid_auto_insert" type="checkbox" value="1"
                        @checked((string) ($result->staffid_auto_insert ?? '') === '1')>
                </div>
            </div>

            <div class="form-group">
                <label>{{ __('system.staff_id_prefix') }} <small class="req">*</small></label>
                <input type="text" name="staffid_prefix" id="staffid_prefix" class="form-control"
                       value="{{ old('staffid_prefix', $result->staffid_prefix ?? '') }}">
            </div>

            <div class="form-group">
                <label>{{ __('system.staff_no_digit') }} <small class="req">*</small></label>
                <select id="staffid_no_digit" name="staffid_no_digit" class="form-control">
                    <option value="">{{ __('system.select') }}</option>
                    @foreach($digitList as $digit)
                        <option value="{{ $digit }}" @selected((string) ($result->staffid_no_digit ?? '') === (string) $digit)>{{ $digit }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label>{{ __('system.staff_id_start_from') }} <small class="req">*</small></label>
                <input type="text" name="staffid_start_from" id="staffid_start_from" class="form-control"
                       value="{{ old('staffid_start_from', $result->staffid_start_from ?? '') }}">
            </div>

            @if($canEdit)
                <button type="submit" class="btn btn-primary id_auto_generation">{{ __('system.save') }}</button>
            @endif
        </form>
    </div>
</div>
@push('scripts')
<script>
    $('.id_auto_generation').on('click', function (e) {
        e.preventDefault();
        var $this = $(this);
        $this.prop('disabled', true);
        $.ajax({
            url: '{{ url('schsettings/saveidautogeneration') }}',
            type: 'POST',
            data: $('#id_auto_generation_form').serialize(),
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
