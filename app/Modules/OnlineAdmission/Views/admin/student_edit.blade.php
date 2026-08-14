<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">{{ $pageTitle }}</h3>
    </div>
    <div class="box-body">
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        <form action="{{ url('admin/onlinestudent/edit/'.$student['id']) }}" method="post">
            @csrf
            <input type="hidden" name="student_id" value="{{ $student['id'] }}">
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Class</label>
                        <select name="class_id" id="class_id" class="form-control">
                            <option value="">Select</option>
                            @foreach($classlist as $class)
                                <option value="{{ $class['id'] }}" {{ (string) ($old['class_id'] ?? $student['class_id'] ?? '') === (string) $class['id'] ? 'selected' : '' }}>{{ $class['class'] }}</option>
                            @endforeach
                        </select>
                        @if(!empty($formErrors['class_id']))<span class="text-danger">{{ $formErrors['class_id'] }}</span>@endif
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Section</label>
                        <select name="section_id" id="section_id" class="form-control">
                            <option value="">Select</option>
                            @foreach($sectionlist as $section)
                                <option value="{{ $section['id'] }}" {{ (string) ($old['section_id'] ?? $student['class_section_id'] ?? '') === (string) $section['id'] ? 'selected' : '' }}>{{ $section['section'] }}</option>
                            @endforeach
                        </select>
                        @if(!empty($formErrors['section_id']))<span class="text-danger">{{ $formErrors['section_id'] }}</span>@endif
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Admission No</label>
                        <input class="form-control" name="admission_no" value="{{ $old['admission_no'] ?? $student['admission_no'] }}">
                        @if(!empty($formErrors['admission_no']))<span class="text-danger">{{ $formErrors['admission_no'] }}</span>@endif
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>First Name</label>
                        <input class="form-control" name="firstname" value="{{ $old['firstname'] ?? $student['firstname'] }}">
                        @if(!empty($formErrors['firstname']))<span class="text-danger">{{ $formErrors['firstname'] }}</span>@endif
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Last Name</label>
                        <input class="form-control" name="lastname" value="{{ $old['lastname'] ?? $student['lastname'] }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Date Of Birth</label>
                        <input class="form-control" name="dob" value="{{ $old['dob'] ?? $student['dob'] }}">
                        @if(!empty($formErrors['dob']))<span class="text-danger">{{ $formErrors['dob'] }}</span>@endif
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Gender</label>
                        <select name="gender" class="form-control">
                            <option value="">Select</option>
                            @foreach(['Male', 'Female'] as $gender)
                                <option value="{{ $gender }}" {{ (string) ($old['gender'] ?? $student['gender'] ?? '') === $gender ? 'selected' : '' }}>{{ $gender }}</option>
                            @endforeach
                        </select>
                        @if(!empty($formErrors['gender']))<span class="text-danger">{{ $formErrors['gender'] }}</span>@endif
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Email</label>
                        <input class="form-control" name="email" value="{{ $old['email'] ?? $student['email'] }}">
                        @if(!empty($formErrors['email']))<span class="text-danger">{{ $formErrors['email'] }}</span>@endif
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Mobile Number</label>
                        <input class="form-control" name="mobileno" value="{{ $old['mobileno'] ?? $student['mobileno'] }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Father Name</label>
                        <input class="form-control" name="father_name" value="{{ $old['father_name'] ?? $student['father_name'] }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Guardian Name</label>
                        <input class="form-control" name="guardian_name" value="{{ $old['guardian_name'] ?? $student['guardian_name'] }}">
                    </div>
                </div>
            </div>
            <button type="submit" name="save" value="save" class="btn btn-primary">Save</button>
            @if(empty($student['is_enroll']))
                <button type="submit" name="save" value="enroll" class="btn btn-primary">Save and Enroll</button>
            @endif
        </form>
    </div>
</div>
<script>
(function () {
    var classEl = document.getElementById('class_id');
    var sectionEl = document.getElementById('section_id');
    if (!classEl || !sectionEl) {
        return;
    }
    classEl.addEventListener('change', function () {
        var body = new FormData();
        body.append('class_id', classEl.value);
        body.append('_token', '{{ csrf_token() }}');
        fetch('{{ url('admin/onlinestudent/getByClass') }}', {
            method: 'POST',
            body: body,
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        }).then(function (res) { return res.json(); }).then(function (rows) {
            sectionEl.innerHTML = '<option value="">Select</option>';
            (rows || []).forEach(function (row) {
                var opt = document.createElement('option');
                opt.value = row.id;
                opt.textContent = row.section;
                sectionEl.appendChild(opt);
            });
        });
    });
})();
</script>
