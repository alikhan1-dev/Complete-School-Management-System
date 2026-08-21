@include('reports::admin.attendance.hub')

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-search"></i> {{ __('system.select_criteria') }}</h3>
    </div>
    <form action="{{ url('attendencereports/reportbymonth') }}" method="post">
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
                <div class="col-md-3">
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
                <div class="col-md-3">
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
            @if(empty($resultlist) || empty($resultlist['class_students']))
                <div class="alert alert-info">No student admitted in this Class-Section</div>
            @else
                <table class="table table-hover table-striped">
                    <thead>
                        <tr>
                            <th>Student</th>
                            @for($i = 1; $i <= $no_of_days; $i++)
                                <th class="text text-center">{{ sprintf('%02d', $i) }}</th>
                            @endfor
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($resultlist['class_students'] as $student)
                            <tr>
                                <td>{{ $reports->fullName($student) }} ({{ $student->admission_no }})</td>
                                @for($i = 1; $i <= $no_of_days; $i++)
                                    @php $dayKey = sprintf('%02d', $i); @endphp
                                    <td class="text text-center">
                                        @php
                                            $dayData = $resultlist['students_attendances'][$dayKey] ?? ['subjects' => [], 'students' => []];
                                            $subjects = $dayData['subjects'] ?? [];
                                            $attendance = ($dayData['students'][(int) $student->id] ?? null);
                                        @endphp
                                        @if(empty($subjects))
                                            <span class="label label-danger">N/A</span>
                                        @else
                                            @foreach($subjects as $idx => $subject)
                                                @php
                                                    $count = $idx + 1;
                                                    $typeId = $attendance->{'attendence_type_id_'.$count} ?? null;
                                                    $key = $reports->attendanceTypeKey($attendencetypeslist, $typeId);
                                                @endphp
                                                <div class="list-group" style="width: 180px;">
                                                    {{ $subject->name }}@if($subject->code !== '') ({{ $subject->code }})@endif
                                                    <br/>{{ $subject->time_from }} - {{ $subject->time_to }}<br/>
                                                    @if($key === '')
                                                        <span class="label label-danger">N/A</span>
                                                    @else
                                                        {{ $key }}
                                                    @endif
                                                </div>
                                            @endforeach
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
    function loadSections(classId, selected) {
        var $section = $('#section_id');
        $section.html('<option value="">{{ __('system.select') }}</option>');
        $('#subject_id').html('<option value="">{{ __('system.select') }}</option>');
        if (!classId) return;
        $.getJSON(@json(url('sections/getByClass')), {class_id: classId}, function (data) {
            $.each(data, function (i, obj) {
                var sel = String(selected) === String(obj.section_id) ? ' selected' : '';
                $section.append('<option value="' + obj.section_id + '"' + sel + '>' + obj.section + '</option>');
            });
            if (selected) {
                loadSubjects(classId, selected, @json($filters['subject_id']));
            }
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
    loadSections($('#class_id').val(), @json($filters['section_id']));
    $('#class_id').on('change', function () { loadSections($(this).val(), ''); });
    $('#section_id').on('change', function () { loadSubjects($('#class_id').val(), $(this).val(), ''); });
});
</script>
@endpush
