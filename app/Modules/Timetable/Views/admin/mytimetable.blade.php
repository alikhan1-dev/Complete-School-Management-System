<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">{{ __('system.teacher_time_table') }}</h3>
        <div class="box-tools">
            <a href="{{ route('timetable.classreport') }}" class="btn btn-default btn-sm">{{ __('system.class_timetable') }}</a>
        </div>
    </div>
    <div class="box-body">
        @if($isAdminPicker)
            <form id="getTimetable" class="row">
                @csrf
                <div class="col-sm-5">
                    <div class="form-group">
                        <label>{{ __('system.teachers') }} <span class="text-danger">*</span></label>
                        <select name="teacher" id="teacher" class="form-control" required>
                            <option value="">{{ __('system.select') }}</option>
                            @foreach($teachers as $teacher)
                                @php
                                    $label = trim(($teacher->name ?? '').' '.($teacher->surname ?? ''))
                                        .' ('.($teacher->employee_id ?? '').')';
                                @endphp
                                <option value="{{ $teacher->id }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-sm-2">
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-primary btn-sm btn-block" id="load">
                            <i class="fa fa-search"></i> {{ __('system.search') }}
                        </button>
                    </div>
                </div>
            </form>
            <div id="timetable_data" class="table-responsive" style="margin-top:15px;"></div>
        @else
            @include('timetable::admin.partials.teachertimetable_grid', ['week' => $week, 'staffId' => $staffId])
        @endif
    </div>
</div>

@if($isAdminPicker)
@push('scripts')
<script>
$(function () {
    $('#getTimetable').on('submit', function (e) {
        e.preventDefault();
        var $btn = $('#load');
        $btn.prop('disabled', true);
        $.ajax({
            type: 'POST',
            url: '{{ route('timetable.get_teacher_timetable') }}',
            data: $(this).serialize(),
            dataType: 'json',
            success: function (data) {
                if (String(data.status) === '0') {
                    var message = '';
                    if (data.error) {
                        $.each(data.error, function (i, val) { message += val + ' '; });
                    }
                    alert(message || 'Validation failed.');
                    $('#timetable_data').html('');
                    return;
                }
                $('#timetable_data').html(data.message || '');
            },
            error: function () {
                alert('{{ __('system.error_occurred_please_try_again') }}');
            },
            complete: function () {
                $btn.prop('disabled', false);
            }
        });
    });
});
</script>
@endpush
@endif
