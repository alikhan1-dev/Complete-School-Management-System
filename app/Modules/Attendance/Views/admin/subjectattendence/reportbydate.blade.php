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
        <h3 class="box-title">{{ __('system.period_attendance_by_date') }}</h3>
        <div class="box-tools">
            <a href="{{ route('attendance.subjectattendence.index') }}" class="btn btn-default btn-sm">{{ __('system.period_attendance') }}</a>
        </div>
    </div>
    <div class="box-body">
        <p class="text-muted" style="margin-top:0;">
            Shows each student's period attendance for all subjects scheduled on the selected date
            (same as CI <code>searchByStudentsAttendanceByDate</code>).
        </p>
        <form method="post" action="{{ route('attendance.subjectattendence.reportbydate') }}" class="row">
            @csrf
            <div class="col-sm-3">
                <div class="form-group">
                    <label>Class <span class="text-danger">*</span></label>
                    <select id="class_id" name="class_id" class="form-control" required>
                        <option value="">Select</option>
                        @foreach($classes as $class)
                            <option value="{{ $class->id }}" @selected((string) ($filters['class_id'] ?? '') === (string) $class->id)>{{ $class->class }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-sm-3">
                <div class="form-group">
                    <label>Section <span class="text-danger">*</span></label>
                    <select id="section_id" name="section_id" class="form-control" required>
                        <option value="">Select</option>
                    </select>
                </div>
            </div>
            <div class="col-sm-3">
                <div class="form-group">
                    <label>Date <span class="text-danger">*</span></label>
                    <input type="date" name="date" class="form-control" value="{{ old('date', $filters['date'] ?? date('Y-m-d')) }}" required>
                </div>
            </div>
            <div class="col-sm-3">
                <div class="form-group">
                    <label>&nbsp;</label>
                    <button type="submit" class="btn btn-primary btn-sm btn-block"><i class="fa fa-search"></i> Search</button>
                </div>
            </div>
        </form>
    </div>
</div>

@if($searched ?? false)
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">{{ __('system.student_list') }}</h3>
        </div>
        <div class="box-body table-responsive">
            @if($report === null || $report->student_record->isEmpty())
                <div class="alert alert-info" style="margin-bottom:0;">{{ __('system.admited_alert') }}</div>
            @else
                @php
                    $typeLabels = $types->keyBy('id');
                @endphp
                <table class="table table-striped table-bordered table-hover">
                    <thead>
                    <tr>
                        <th>Student</th>
                        @foreach($report->subjects as $subject)
                            <th class="text-center">
                                @php
                                    $codeSuffix = ($subject->code ?? '') !== '' ? ' ('.$subject->code.')' : '';
                                @endphp
                                {{ ($subject->name ?? 'Subject').$codeSuffix }}<br>
                                <small>{{ $subject->time_from ?? '' }} - {{ $subject->time_to ?? '' }}</small>
                            </th>
                        @endforeach
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($report->student_record as $student)
                        <tr>
                            <td>
                                {{ trim(($student->firstname ?? '').' '.($student->middlename ?? '').' '.($student->lastname ?? '')) }}
                                ({{ $student->admission_no }})
                            </td>
                            @for($i = 1; $i <= $report->subjects->count(); $i++)
                                @php
                                    $typeId = $student->{'attendence_type_id_'.$i} ?? null;
                                @endphp
                                <td class="text-center">
                                    @if($typeId === null || $typeId === '')
                                        <span class="label label-danger">N/A</span>
                                    @else
                                        {!! $typeLabels->get((int) $typeId)?->key_value ?? '—' !!}
                                    @endif
                                </td>
                            @endfor
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
@endif

@push('scripts')
<script>
$(function () {
    var oldSection = '{{ $filters['section_id'] ?? '' }}';
    function loadSections(classId, selected) {
        $('#section_id').html('<option value="">Select</option>');
        if (!classId) return;
        $.getJSON('{{ url('sections/getByClass') }}', {class_id: classId}, function (data) {
            $.each(data, function (i, row) {
                var opt = $('<option>', {value: row.section_id, text: row.section});
                if (String(selected) === String(row.section_id)) opt.prop('selected', true);
                $('#section_id').append(opt);
            });
        });
    }
    $('#class_id').on('change', function () { loadSections($(this).val(), ''); });
    loadSections($('#class_id').val(), oldSection);
});
</script>
@endpush
