@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="box box-primary">
    <div class="box-header with-border"><h3 class="box-title">Edit Student</h3></div>
    <form method="post" action="{{ route('students.update', $student->id) }}">
        @csrf
        <div class="box-body">
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Admission No</label> <small class="req">*</small>
                        <input type="text" name="admission_no" class="form-control" value="{{ old('admission_no', $student->admission_no) }}" required>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Roll No</label>
                        <input type="text" name="roll_no" class="form-control" value="{{ old('roll_no', $student->roll_no) }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Admission Date</label>
                        <input type="date" name="admission_date" class="form-control" value="{{ old('admission_date', $student->admission_date) }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>First Name</label> <small class="req">*</small>
                        <input type="text" name="firstname" class="form-control" value="{{ old('firstname', $student->firstname) }}" required>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Middle Name</label>
                        <input type="text" name="middlename" class="form-control" value="{{ old('middlename', $student->middlename) }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Last Name</label>
                        <input type="text" name="lastname" class="form-control" value="{{ old('lastname', $student->lastname) }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Gender</label> <small class="req">*</small>
                        <select name="gender" class="form-control" required>
                            <option value="Male" @selected(old('gender', $student->gender) === 'Male')>Male</option>
                            <option value="Female" @selected(old('gender', $student->gender) === 'Female')>Female</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Date Of Birth</label> <small class="req">*</small>
                        <input type="date" name="dob" class="form-control" value="{{ old('dob', $student->dob) }}" required>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Class</label> <small class="req">*</small>
                        <select id="class_id" name="class_id" class="form-control" required>
                            <option value="">Select</option>
                            @foreach($classes as $class)
                                <option value="{{ $class->id }}" @selected((string) old('class_id', $student->class_id) === (string) $class->id)>{{ $class->class }}</option>
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
                        <label>Mobile Number</label>
                        <input type="text" name="mobileno" class="form-control" value="{{ old('mobileno', $student->mobileno) }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control" value="{{ old('email', $student->email) }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Guardian</label>
                        <select name="guardian_is" class="form-control">
                            <option value="father" @selected(old('guardian_is', $student->guardian_is) === 'father')>Father</option>
                            <option value="mother" @selected(old('guardian_is', $student->guardian_is) === 'mother')>Mother</option>
                            <option value="other" @selected(old('guardian_is', $student->guardian_is) === 'other')>Other</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Guardian Name</label>
                        <input type="text" name="guardian_name" class="form-control" value="{{ old('guardian_name', $student->guardian_name) }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Guardian Phone</label>
                        <input type="text" name="guardian_phone" class="form-control" value="{{ old('guardian_phone', $student->guardian_phone) }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Father Name</label>
                        <input type="text" name="father_name" class="form-control" value="{{ old('father_name', $student->father_name) }}">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Current Address</label>
                        <textarea name="current_address" class="form-control" rows="2">{{ old('current_address', $student->current_address) }}</textarea>
                    </div>
                </div>
            </div>

            @include('academics::partials.custom_fields_form', [
                'customFields' => $customFields,
                'customFieldValues' => $customFieldValues ?? [],
                'belongTo' => $belongTo ?? 'students',
            ])
        </div>
        <div class="box-footer">
            <a href="{{ route('students.view', $student->id) }}" class="btn btn-default">Cancel</a>
            <button type="submit" class="btn btn-info pull-right">Save</button>
        </div>
    </form>
</div>

@push('scripts')
<script>
$(function () {
    var selectedSection = '{{ old('section_id', $student->section_id) }}';
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
    loadSections($('#class_id').val(), selectedSection);
});
</script>
@endpush
