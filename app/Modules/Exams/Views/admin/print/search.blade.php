@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

@php
    $searchRoute = $printType === 'marksheet'
        ? route('exams.print.marksheet')
        : route('exams.print.admitcard');
@endphp

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">{{ $printType === 'marksheet' ? 'Print Marksheet' : 'Print Admit Card' }}</h3>
    </div>
    <div class="box-body">
        @if($printType === 'admitcard' && ! $activeAdmitcard)
            <div class="alert alert-warning">Activate an admit card template under Design Admit Card before printing.</div>
        @endif
        <form method="post" action="{{ $searchRoute }}" class="row" id="print_search_form">
            @csrf
            @if($printType === 'marksheet')
                <div class="col-sm-3">
                    <div class="form-group">
                        <label>Marksheet Template</label> <small class="req">*</small>
                        <select name="marksheet" class="form-control" required>
                            <option value="">Select</option>
                            @foreach($marksheets as $ms)
                                <option value="{{ $ms->id }}" @selected((string) ($filters['marksheet'] ?? '') === (string) $ms->id)>{{ $ms->template }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            @endif
            <div class="col-sm-3">
                <div class="form-group">
                    <label>Exam Group</label> <small class="req">*</small>
                    <select id="exam_group_id" name="exam_group_id" class="form-control" required>
                        <option value="">Select</option>
                        @foreach($examGroups as $group)
                            <option value="{{ $group->id }}" @selected((string) ($filters['exam_group_id'] ?? '') === (string) $group->id)>{{ $group->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-sm-3">
                <div class="form-group">
                    <label>Exam</label> <small class="req">*</small>
                    <select id="exam_id" name="exam_id" class="form-control" required>
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
            <div class="col-sm-12">
                <button type="submit" class="btn btn-primary btn-sm pull-right"><i class="fa fa-search"></i> Search</button>
            </div>
        </form>
    </div>
</div>

@if($studentList !== null)
    <div class="box box-info">
        <div class="box-header with-border"><h3 class="box-title">Student List</h3></div>
        <div class="box-body table-responsive">
            <table class="table table-striped table-bordered">
                <thead>
                <tr>
                    <th>Admission No</th>
                    <th>Student Name</th>
                    <th>Class</th>
                    <th>Father Name</th>
                    <th class="text-right">Action</th>
                </tr>
                </thead>
                <tbody>
                @forelse($studentList as $student)
                    <tr>
                        <td>{{ $student->admission_no }}</td>
                        <td>{{ trim($student->firstname.' '.($student->middlename ?? '').' '.($student->lastname ?? '')) }}</td>
                        <td>{{ $student->class }} ({{ $student->section }})</td>
                        <td>{{ $student->father_name }}</td>
                        <td class="text-right">
                            @if($printType === 'marksheet')
                                <a class="btn btn-primary btn-xs" target="_blank"
                                   href="{{ route('exams.print.marksheet_view', $student->exam_student_id) }}?marksheet={{ $filters['marksheet'] }}&exam_id={{ $filters['exam_id'] }}">Print</a>
                            @else
                                <a class="btn btn-primary btn-xs {{ $activeAdmitcard ? '' : 'disabled' }}" target="_blank"
                                   href="{{ route('exams.print.admitcard_view', $student->exam_student_id) }}?exam_id={{ $filters['exam_id'] }}">Print</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-danger text-center">No Record Found</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endif

@push('scripts')
<script>
$(function () {
    var oldExam = '{{ $filters['exam_id'] ?? '' }}';
    var oldSection = '{{ $filters['section_id'] ?? '' }}';

    function loadExams(groupId, selected) {
        $('#exam_id').html('<option value="">Select</option>');
        if (!groupId) return;
        $.getJSON('{{ url('admin/examgroup/examsbygroup') }}/' + groupId, function (data) {
            $.each(data, function (i, row) {
                var opt = $('<option>', {value: row.id, text: row.exam});
                if (String(selected) === String(row.id)) opt.prop('selected', true);
                $('#exam_id').append(opt);
            });
        });
    }
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

    $('#exam_group_id').on('change', function () { loadExams($(this).val(), ''); });
    $('#class_id').on('change', function () { loadSections($(this).val(), ''); });
    loadExams($('#exam_group_id').val(), oldExam);
    loadSections($('#class_id').val(), oldSection);
});
</script>
@endpush
