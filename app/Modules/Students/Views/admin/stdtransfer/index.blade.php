@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<div class="box box-primary">
    <div class="box-header with-border"><h3 class="box-title">Select Criteria</h3></div>
    <form method="post" action="{{ route('students.stdtransfer.index') }}" class="stdtransfer">
        @csrf
        <div class="box-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Class</label> <small class="req">*</small>
                        <select id="class_id" name="class_id" class="form-control" required>
                            <option value="">Select</option>
                            @foreach($classes as $class)
                                <option value="{{ $class->id }}" @selected((string) old('class_id', $oldInput['class_id'] ?? '') === (string) $class->id)>{{ $class->class }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Section</label> <small class="req">*</small>
                        <select id="section_id" name="section_id" class="form-control" required>
                            <option value="">Select</option>
                        </select>
                    </div>
                </div>
            </div>
            <h4>Promote Students In Next Session</h4>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Promote In Session</label> <small class="req">*</small>
                        <select name="session_id" class="form-control" required>
                            <option value="">Select</option>
                            @foreach($sessions as $session)
                                <option value="{{ $session->id }}" @selected((string) old('session_id', $oldInput['session_id'] ?? '') === (string) $session->id)>{{ $session->session }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Class</label> <small class="req">*</small>
                        <select id="class_promote_id" name="class_promote_id" class="form-control" required>
                            <option value="">Select</option>
                            @foreach($classes as $class)
                                <option value="{{ $class->id }}" @selected((string) old('class_promote_id', $oldInput['class_promote_id'] ?? '') === (string) $class->id)>{{ $class->class }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Section</label> <small class="req">*</small>
                        <select id="section_promote_id" name="section_promote_id" class="form-control" required>
                            <option value="">Select</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class="box-footer">
            <button type="submit" class="btn btn-primary btn-sm pull-right">Search</button>
        </div>
    </form>
</div>

@if(! is_null($resultlist))
<div class="box box-primary">
    <div class="box-header with-border"><h3 class="box-title">Student List</h3></div>
    <div class="box-body">
        <form action="#" method="post" class="promote_form">
            @csrf
            <input type="hidden" name="class_post" value="{{ $class_post }}">
            <input type="hidden" name="section_post" value="{{ $section_post }}">
            <input type="hidden" name="class_promote_id" value="{{ $class_promoted_post }}">
            <input type="hidden" name="section_promote_id" value="{{ $section_promoted_post }}">
            <input type="hidden" name="session_id" value="{{ $session_promoted_post }}">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                    <tr>
                        <th><input type="checkbox" id="checkAll"></th>
                        <th>Admission No</th>
                        <th>Student Name</th>
                        <th>Father Name</th>
                        <th>Date Of Birth</th>
                        <th>Current Result</th>
                        <th>Next Session Status</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($resultlist as $student)
                        @php
                            $name = trim(implode(' ', array_filter([
                                $student->firstname,
                                ((int) ($schSetting->middlename ?? 1) === 1) ? $student->middlename : null,
                                ((int) ($schSetting->lastname ?? 1) === 1) ? $student->lastname : null,
                            ])));
                        @endphp
                        <tr>
                            <td><input class="checkbox student-check" name="student_list[]" type="checkbox" value="{{ $student->id }}"></td>
                            <td>{{ $student->admission_no }}</td>
                            <td>{{ $name }}</td>
                            <td>{{ $student->father_name }}</td>
                            <td>{{ $student->dob }}</td>
                            <td>
                                <label class="radio-inline"><input type="radio" name="result_{{ $student->id }}" value="pass" checked> Pass</label>
                                <label class="radio-inline"><input type="radio" name="result_{{ $student->id }}" value="fail"> Fail</label>
                            </td>
                            <td>
                                <label class="radio-inline"><input type="radio" name="next_working_{{ $student->id }}" value="countinue" checked> Continue</label>
                                <label class="radio-inline"><input type="radio" name="next_working_{{ $student->id }}" value="leave"> Leave</label>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-danger">No record found</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </form>
    </div>
    @if($resultlist->isNotEmpty())
        <div class="box-footer clearfix">
            <a class="btn btn-sm btn-primary pull-right" data-toggle="modal" data-target="#promoteStudentModal">Promote</a>
        </div>
    @endif
</div>
@endif

<div class="modal fade" id="promoteStudentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Promote Confirmation</h4>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to promote selected students?</p>
                <div id="promote_errors" class="text-danger"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="confirmPromote">Save</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(function () {
    var oldSection = '{{ old('section_id', $oldInput['section_id'] ?? '') }}';
    var oldPromoteSection = '{{ old('section_promote_id', $oldInput['section_promote_id'] ?? '') }}';

    function loadSections(classSelect, sectionSelect, selected) {
        $(sectionSelect).html('<option value="">Select</option>');
        var classId = $(classSelect).val();
        if (!classId) return;
        $.getJSON('{{ url('sections/getByClass') }}', {class_id: classId}, function (data) {
            $.each(data, function (i, row) {
                var opt = $('<option>', {value: row.section_id, text: row.section});
                if (String(selected) === String(row.section_id)) opt.prop('selected', true);
                $(sectionSelect).append(opt);
            });
        });
    }

    $('#class_id').on('change', function () { loadSections('#class_id', '#section_id', ''); });
    $('#class_promote_id').on('change', function () { loadSections('#class_promote_id', '#section_promote_id', ''); });
    loadSections('#class_id', '#section_id', oldSection);
    loadSections('#class_promote_id', '#section_promote_id', oldPromoteSection);

    $('#checkAll').on('change', function () {
        $('.student-check').prop('checked', $(this).is(':checked'));
    });

    $('#confirmPromote').on('click', function () {
        $('#promote_errors').empty();
        $.ajax({
            url: '{{ route('students.stdtransfer.promote') }}',
            type: 'POST',
            data: $('.promote_form').serialize(),
            success: function (res) {
                if (res.status === 'success') {
                    window.location.reload();
                } else {
                    var msg = res.msg || {};
                    $('#promote_errors').html(Object.values(msg).filter(Boolean).join('<br>'));
                }
            },
            error: function (xhr) {
                $('#promote_errors').text('Promotion failed. Please try again.');
            }
        });
    });
});
</script>
@endpush
