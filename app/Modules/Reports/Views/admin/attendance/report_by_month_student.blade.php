@include('reports::admin.attendance.hub')

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-search"></i> {{ __('system.select_criteria') }}</h3>
    </div>
    <form action="{{ url('attendencereports/reportbymonthstudent') }}" method="post">
        @csrf
        <div class="box-body">
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>{{ __('system.class') }} <small class="req">*</small></label>
                        <select id="class_id" name="class_id" class="form-control">
                            <option value="">{{ __('system.select') }}</option>
                            @foreach($classes as $class)
                                <option value="{{ $class->id }}" @selected((string) $filters['class_id'] === (string) $class->id)>{{ $class->class }}</option>
                            @endforeach
                        </select>
                        @error('class_id')<span class="text-danger">{{ $message }}</span>@enderror
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>{{ __('system.section') }} <small class="req">*</small></label>
                        <select id="section_id" name="section_id" class="form-control">
                            <option value="">{{ __('system.select') }}</option>
                        </select>
                        @error('section_id')<span class="text-danger">{{ $message }}</span>@enderror
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>{{ __('system.student') }} <small class="req">*</small></label>
                        <select id="student_id" name="student_id" class="form-control">
                            <option value="">{{ __('system.select') }}</option>
                        </select>
                        @error('student_id')<span class="text-danger">{{ $message }}</span>@enderror
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>{{ __('system.month') }} <small class="req">*</small></label>
                        <select id="month" name="month" class="form-control">
                            <option value="">{{ __('system.select') }}</option>
                            @foreach($monthlist as $mKey => $monthLabel)
                                <option value="{{ $mKey }}" @selected((string) $filters['month'] === (string) $mKey)>{{ $monthLabel }}</option>
                            @endforeach
                        </select>
                        @error('month')<span class="text-danger">{{ $message }}</span>@enderror
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>{{ __('system.subject') }}</label>
                        <select id="subject_id" name="subject_id" class="form-control">
                            <option value="">{{ __('system.select') }}</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class="box-footer">
            <button type="submit" name="search" value="search" class="btn btn-primary btn-sm pull-right">
                <i class="fa fa-search"></i> {{ __('system.search') }}
            </button>
        </div>
    </form>
</div>

@if($searched)
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title"><i class="fa fa-users"></i> {{ __('system.student_period_attendance') }}</h3>
        </div>
        <div class="box-body table-responsive">
            @if(empty($resultlist) || empty($resultlist['students_attendances']))
                <div class="alert alert-info">{{ __('system.admited_alert') }}</div>
            @else
                <table class="table table-hover table-striped">
                    <thead>
                        <tr>
                            <th>{{ __('system.date') }}</th>
                            <th>{{ __('system.day') }}</th>
                            <th>{{ __('system.period_attendance') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($resultlist['students_attendances'] as $studentValue)
                            <tr>
                                <td>{{ $studentValue['date'] }}</td>
                                <td>{{ __('system.'.strtolower($studentValue['day'])) }}</td>
                                <td>
                                    @if(empty($studentValue['subjects']))
                                        <span class="label label-danger">N/A</span>
                                    @else
                                        @foreach($studentValue['subjects'] as $idx => $subject)
                                            @php
                                                $count = $idx + 1;
                                                $attendance = $studentValue['attendances'] ?? null;
                                                $typeId = $attendance ? ($attendance->{'attendence_type_id_'.$count} ?? null) : null;
                                                $key = $reports->attendanceTypeKey($attendencetypeslist, $typeId);
                                            @endphp
                                            <div class="row subject_row">
                                                <div class="col-md-4"><b>{{ $subject->name }}</b>@if($subject->code !== '') ({{ $subject->code }})@endif</div>
                                                <div class="col-md-4">{{ $subject->time_from }} - {{ $subject->time_to }}</div>
                                                <div class="col-md-4">
                                                    @if($key === '')
                                                        <span class="label label-danger">N/A</span>
                                                    @else
                                                        {{ $key }}
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    @endif
                                </td>
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
    function loadSections(classId, selected, after) {
        var $section = $('#section_id');
        $section.html('<option value="">{{ __('system.select') }}</option>');
        $('#student_id').html('<option value="">{{ __('system.select') }}</option>');
        $('#subject_id').html('<option value="">{{ __('system.select') }}</option>');
        if (!classId) return;
        $.getJSON(@json(url('sections/getByClass')), {class_id: classId}, function (data) {
            $.each(data, function (i, obj) {
                var sel = String(selected) === String(obj.section_id) ? ' selected' : '';
                $section.append('<option value="' + obj.section_id + '"' + sel + '>' + obj.section + '</option>');
            });
            if (typeof after === 'function') after();
        });
    }
    function loadStudents(classId, sectionId, selected) {
        var $student = $('#student_id');
        $student.html('<option value="">{{ __('system.select') }}</option>');
        if (!classId || !sectionId) return;
        $.getJSON(@json(url('student/getByClassAndSection')), {class_id: classId, section_id: sectionId}, function (data) {
            $.each(data, function (i, obj) {
                var name = (obj.firstname || '') + ' ' + (obj.lastname || '');
                var sel = String(selected) === String(obj.id) ? ' selected' : '';
                $student.append('<option value="' + obj.id + '"' + sel + '>' + name + '</option>');
            });
        });
    }
    function loadSubjects(classId, sectionId, selected) {
        var $subject = $('#subject_id');
        $subject.html('<option value="">{{ __('system.select') }}</option>');
        if (!classId || !sectionId) return;
        $.ajax({
            type: 'POST',
            url: @json(url('admin/subjectgroup/getAllSubjectByClassandSection')),
            data: {_token: @json(csrf_token()), class_id: classId, section_id: sectionId},
            dataType: 'json',
            success: function (data) {
                $.each(data, function (i, obj) {
                    var label = obj.subject_name + (obj.subject_code ? ' (' + obj.subject_code + ')' : '');
                    var sel = String(selected) === String(obj.subject_id) ? ' selected' : '';
                    $subject.append('<option value="' + obj.subject_id + '"' + sel + '>' + label + '</option>');
                });
            }
        });
    }
    var classId = $('#class_id').val();
    var sectionId = @json($filters['section_id']);
    loadSections(classId, sectionId, function () {
        if (sectionId) {
            loadStudents(classId, sectionId, @json($filters['student_id']));
            loadSubjects(classId, sectionId, @json($filters['subject_id']));
        }
    });
    $('#class_id').on('change', function () { loadSections($(this).val(), ''); });
    $('#section_id').on('change', function () {
        loadStudents($('#class_id').val(), $(this).val(), '');
        loadSubjects($('#class_id').val(), $(this).val(), '');
    });
});
</script>
@endpush
