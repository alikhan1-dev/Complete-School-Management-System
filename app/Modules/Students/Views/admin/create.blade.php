@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="post" action="{{ route('students.store') }}">
@csrf
<div class="box box-primary">
    <div class="box-header with-border"><h3 class="box-title">Student Admission</h3></div>
        <div class="box-body">
            <div class="row">
                @if(! (int) ($schSetting->adm_auto_insert ?? 0))
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Admission No</label> <small class="req">*</small>
                            <input type="text" name="admission_no" class="form-control" value="{{ old('admission_no') }}" required>
                        </div>
                    </div>
                @endif
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Roll No</label>
                        <input type="text" name="roll_no" class="form-control" value="{{ old('roll_no') }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Admission Date</label>
                        <input type="date" name="admission_date" class="form-control" value="{{ old('admission_date') }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>First Name</label> <small class="req">*</small>
                        <input type="text" name="firstname" class="form-control" value="{{ old('firstname') }}" required>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Middle Name</label>
                        <input type="text" name="middlename" class="form-control" value="{{ old('middlename') }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Last Name</label>
                        <input type="text" name="lastname" class="form-control" value="{{ old('lastname') }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Gender</label> <small class="req">*</small>
                        <select name="gender" class="form-control" required>
                            <option value="">Select</option>
                            <option value="Male" @selected(old('gender') === 'Male')>Male</option>
                            <option value="Female" @selected(old('gender') === 'Female')>Female</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Date Of Birth</label> <small class="req">*</small>
                        <input type="date" name="dob" class="form-control" value="{{ old('dob') }}" required>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Class</label> <small class="req">*</small>
                        <select id="class_id" name="class_id" class="form-control" required>
                            <option value="">Select</option>
                            @foreach($classes as $class)
                                <option value="{{ $class->id }}" @selected((string) old('class_id') === (string) $class->id)>{{ $class->class }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Section</label> <small class="req">*</small>
                        <select id="section_id" name="section_id" class="form-control" required>
                            <option value="">Select</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Category</label>
                        <select name="category_id" class="form-control">
                            <option value="">Select</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" @selected((string) old('category_id') === (string) $category->id)>{{ $category->category }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Mobile Number</label>
                        <input type="text" name="mobileno" class="form-control" value="{{ old('mobileno') }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control" value="{{ old('email') }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Blood Group</label>
                        <input type="text" name="blood_group" class="form-control" value="{{ old('blood_group') }}">
                    </div>
                </div>
            </div>

            <hr>
            <h4>Parent / Guardian
                <button type="button" class="btn btn-sm btn-default mysiblings" data-toggle="modal" data-target="#mySiblingModal">
                    <i class="fa fa-plus"></i> Add Sibling
                </button>
                <span id="sibling_name" class="label label-success" style="margin-left:8px;">
                    @if(old('sibling_name')) Sibling : {{ old('sibling_name') }} @endif
                </span>
            </h4>
            <input type="hidden" name="sibling_id" id="sibling_id" value="{{ old('sibling_id', 0) }}">
            <input type="hidden" name="sibling_name" id="sibling_name_next" value="{{ old('sibling_name') }}">

            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Father Name</label>
                        <input type="text" name="father_name" id="father_name" class="form-control" value="{{ old('father_name') }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Mother Name</label>
                        <input type="text" name="mother_name" id="mother_name" class="form-control" value="{{ old('mother_name') }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Guardian</label> @if((int) ($schSetting->guardian_name ?? 0) === 1)<small class="req">*</small>@endif
                        <select name="guardian_is" id="guardian_is" class="form-control" @if((int) ($schSetting->guardian_name ?? 0) === 1) required @endif>
                            <option value="">Select</option>
                            <option value="father" @selected(old('guardian_is') === 'father')>Father</option>
                            <option value="mother" @selected(old('guardian_is') === 'mother')>Mother</option>
                            <option value="other" @selected(old('guardian_is') === 'other')>Other</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Guardian Name</label> @if((int) ($schSetting->guardian_name ?? 0) === 1)<small class="req">*</small>@endif
                        <input type="text" name="guardian_name" id="guardian_name" class="form-control" value="{{ old('guardian_name') }}" @if((int) ($schSetting->guardian_name ?? 0) === 1) required @endif>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Guardian Phone</label> @if((int) ($schSetting->guardian_phone ?? 0) === 1)<small class="req">*</small>@endif
                        <input type="text" name="guardian_phone" id="guardian_phone" class="form-control" value="{{ old('guardian_phone') }}" @if((int) ($schSetting->guardian_phone ?? 0) === 1) required @endif>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Guardian Email</label>
                        <input type="email" name="guardian_email" id="guardian_email" class="form-control" value="{{ old('guardian_email') }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Guardian Relation</label>
                        <input type="text" name="guardian_relation" id="guardian_relation" class="form-control" value="{{ old('guardian_relation') }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Guardian Occupation</label>
                        <input type="text" name="guardian_occupation" id="guardian_occupation" class="form-control" value="{{ old('guardian_occupation') }}">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Guardian Address</label>
                        <textarea name="guardian_address" id="guardian_address" class="form-control" rows="2">{{ old('guardian_address') }}</textarea>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Current Address</label>
                        <textarea name="current_address" id="current_address" class="form-control" rows="2">{{ old('current_address') }}</textarea>
                    </div>
                </div>
            </div>

            @include('academics::partials.custom_fields_form', [
                'customFields' => $customFields,
                'customFieldValues' => $customFieldValues ?? [],
                'belongTo' => $belongTo ?? 'students',
            ])
        </div>
    </div>

    @include('students::admin.partials.admission_fees')

    @include('students::admin.partials.admission_multiclass')

    <div class="box box-primary">
        <div class="box-footer">
            <button type="submit" class="btn btn-info pull-right">Save</button>
        </div>
    </div>
</form>

<div class="modal fade" id="mySiblingModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title">Add Sibling</h4>
            </div>
            <div class="modal-body">
                <div class="sibling_msg"></div>
                <div class="form-group">
                    <label>Class</label>
                    <select id="sibiling_class_id" class="form-control">
                        <option value="">Select</option>
                        @foreach($classes as $class)
                            <option value="{{ $class->id }}">{{ $class->class }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>Section</label>
                    <select id="sibiling_section_id" class="form-control">
                        <option value="">Select</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Student</label>
                    <select id="sibiling_student_id" class="form-control">
                        <option value="">Select</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary add_sibling">Add</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(function () {
    var oldSection = '{{ old('section_id') }}';
    function loadSections(classId, selected, $target) {
        $target.html('<option value="">Select</option>');
        if (!classId) return;
        $.getJSON('{{ url('sections/getByClass') }}', {class_id: classId}, function (data) {
            $.each(data, function (i, row) {
                var opt = $('<option>', {value: row.section_id, text: row.section});
                if (String(selected) === String(row.section_id)) opt.prop('selected', true);
                $target.append(opt);
            });
        });
    }
    $('#class_id').on('change', function () { loadSections($(this).val(), '', $('#section_id')); });
    loadSections($('#class_id').val(), oldSection, $('#section_id'));

    $('#sibiling_class_id').on('change', function () {
        loadSections($(this).val(), '', $('#sibiling_section_id'));
        $('#sibiling_student_id').html('<option value="">Select</option>');
    });

    $('#sibiling_section_id').on('change', function () {
        var classId = $('#sibiling_class_id').val();
        var sectionId = $(this).val();
        $('#sibiling_student_id').html('<option value="">Select</option>');
        if (!classId || !sectionId) return;
        $.getJSON('{{ url('student/getByClassAndSection') }}', {class_id: classId, section_id: sectionId}, function (data) {
            $.each(data, function (i, row) {
                $('#sibiling_student_id').append($('<option>', {
                    value: row.id,
                    text: (row.full_name || (row.firstname + ' ' + (row.lastname || ''))) + ' (' + row.admission_no + ')'
                }));
            });
        });
    });

    $('.add_sibling').on('click', function () {
        var studentId = $('#sibiling_student_id').val();
        $('.sibling_msg').html('');
        if (!studentId) {
            $('.sibling_msg').html('<div class="alert alert-danger text-center">No student selected</div>');
            return;
        }
        $.getJSON('{{ url('student/getStudentRecordByID') }}', {student_id: studentId}, function (data) {
            $('#sibling_name').text('Sibling : ' + data.full_name);
            $('#sibling_name_next').val(data.full_name);
            $('#sibling_id').val(studentId);
            $('#father_name').val(data.father_name || '');
            $('#mother_name').val(data.mother_name || '');
            $('#guardian_name').val(data.guardian_name || '');
            $('#guardian_relation').val(data.guardian_relation || '');
            $('#guardian_address').val(data.guardian_address || '');
            $('#guardian_phone').val(data.guardian_phone || '');
            $('#guardian_occupation').val(data.guardian_occupation || '');
            $('#guardian_email').val(data.guardian_email || '');
            $('#current_address').val(data.current_address || '');
            if (data.guardian_is) {
                $('#guardian_is').val(data.guardian_is);
            }
            $('#mySiblingModal').modal('hide');
        });
    });
});
</script>
@endpush
