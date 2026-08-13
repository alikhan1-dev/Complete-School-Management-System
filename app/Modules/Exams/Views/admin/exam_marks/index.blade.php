@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
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
        <h3 class="box-title">Exam Marks — {{ $exam->exam }}</h3>
        <div class="box-tools">
            <a href="{{ route('exams.exam_group_exams.index', $group->id) }}" class="btn btn-default btn-sm">Back to Exams</a>
        </div>
    </div>
    <div class="box-body">
        <div class="row" style="margin-bottom:10px;">
            <div class="col-sm-4"><strong>Exam Group:</strong> {{ $group->name }}</div>
            <div class="col-sm-4"><strong>Exam:</strong> {{ $exam->exam }}</div>
        </div>

        <h4>Select Criteria</h4>
        <form method="post" action="{{ route('exams.exam_marks.index', $exam->id) }}" class="row">
            @csrf
            <div class="col-sm-3">
                <div class="form-group">
                    <label>Subject</label> <small class="req">*</small>
                    <select name="exam_group_class_batch_exam_subject_id" class="form-control" required>
                        <option value="">Select</option>
                        @foreach($subjects as $subject)
                            @php
                                $label = $subject->subject_name.($subject->subject_code ? ' ('.$subject->subject_code.')' : '');
                            @endphp
                            <option value="{{ $subject->id }}"
                                @selected((string) ($filters['exam_group_class_batch_exam_subject_id'] ?? '') === (string) $subject->id)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-sm-3">
                <div class="form-group">
                    <label>Class</label> <small class="req">*</small>
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
                    <label>Section</label> <small class="req">*</small>
                    <select id="section_id" name="section_id" class="form-control" required>
                        <option value="">Select</option>
                    </select>
                </div>
            </div>
            <div class="col-sm-3">
                <div class="form-group">
                    <label>Session</label> <small class="req">*</small>
                    <select name="session_id" class="form-control" required>
                        <option value="">Select</option>
                        @foreach($sessions as $session)
                            <option value="{{ $session->id }}" @selected((string) ($filters['session_id'] ?? '') === (string) $session->id)>{{ $session->session }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-sm-12">
                <button type="submit" class="btn btn-primary btn-sm pull-right"><i class="fa fa-search"></i> Search</button>
            </div>
        </form>
    </div>
</div>

@if($resultList !== null && $subjectDetail)
    <div class="box box-info">
        <div class="box-header with-border">
            <h3 class="box-title">Enter Marks</h3>
        </div>
        <div class="box-body">
            <div class="row">
                <div class="col-md-3">
                    <ul class="list-group">
                        <li class="list-group-item">
                            Subject:
                            {{ $subjectDetail->subject_name }}
                            @if($subjectDetail->subject_code)
                                ({{ $subjectDetail->subject_code }})
                            @endif
                        </li>
                        <li class="list-group-item">Date: {{ $subjectDetail->date_from }}</li>
                        <li class="list-group-item">Time: {{ $subjectDetail->time_from }}</li>
                        <li class="list-group-item">Room No: {{ $subjectDetail->room_no }}</li>
                        <li class="list-group-item">Max Marks: {{ $subjectDetail->max_marks }}</li>
                        <li class="list-group-item">Min Marks: {{ $subjectDetail->min_marks }}</li>
                    </ul>
                </div>
                <div class="col-md-9">
                    <form method="post" action="{{ route('exams.exam_marks.save', $exam->id) }}">
                        @csrf
                        <input type="hidden" name="exam_group_class_batch_exam_subject_id" value="{{ $subjectDetail->id }}">
                        <input type="hidden" name="class_id" value="{{ $filters['class_id'] }}">
                        <input type="hidden" name="section_id" value="{{ $filters['section_id'] }}">
                        <input type="hidden" name="session_id" value="{{ $filters['session_id'] }}">
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered">
                                <thead>
                                <tr>
                                    <th>Admission No</th>
                                    <th>Student Name</th>
                                    <th>Father Name</th>
                                    <th>Category</th>
                                    <th>Gender</th>
                                    <th>Attendance</th>
                                    <th>Marks</th>
                                    <th>Note</th>
                                </tr>
                                </thead>
                                <tbody>
                                @forelse($resultList as $student)
                                    @php
                                        $sid = $student->exam_group_class_batch_exam_students_id;
                                        $isAbsent = $student->exam_group_exam_result_attendance === 'absent';
                                    @endphp
                                    <tr>
                                        <td>
                                            <input type="hidden" name="exam_group_student_id[]" value="{{ $sid }}">
                                            {{ $student->admission_no }}
                                        </td>
                                        <td>{{ trim($student->firstname.' '.($student->middlename ?? '').' '.($student->lastname ?? '')) }}</td>
                                        <td>{{ $student->father_name }}</td>
                                        <td>{{ $student->category }}</td>
                                        <td>{{ $student->gender }}</td>
                                        <td>
                                            @foreach($attendanceOptions as $opt)
                                                <label class="checkbox-inline">
                                                    <input type="checkbox" class="attendance_chk"
                                                           name="exam_group_student_attendance_{{ $sid }}"
                                                           value="{{ $opt }}"
                                                           data-student="{{ $sid }}"
                                                           @checked($student->exam_group_exam_result_attendance === $opt)>
                                                    {{ ucfirst($opt) }}
                                                </label>
                                            @endforeach
                                        </td>
                                        <td>
                                            <input type="number" step="any" class="form-control marks-input"
                                                   id="marks_{{ $sid }}"
                                                   name="exam_group_student_mark_{{ $sid }}"
                                                   value="{{ $student->exam_group_exam_result_get_marks }}"
                                                   max="{{ $subjectDetail->max_marks }}"
                                                   @disabled($isAbsent)>
                                        </td>
                                        <td>
                                            <input type="text" class="form-control"
                                                   name="exam_group_student_note_{{ $sid }}"
                                                   value="{{ $student->exam_group_exam_result_note }}">
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="8" class="text-danger text-center">No Record Found</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                        @if($canSave && $resultList->isNotEmpty())
                            <button type="submit" class="btn btn-primary btn-sm pull-right">Save</button>
                        @endif
                    </form>
                </div>
            </div>
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

    $(document).on('change', '.attendance_chk', function () {
        var sid = $(this).data('student');
        var $marks = $('#marks_' + sid);
        if ($(this).is(':checked')) {
            $marks.prop('disabled', true).val('');
        } else {
            $marks.prop('disabled', false);
        }
    });
});
</script>
@endpush
