@extends('frontcms::public.layout')

@section('content')
    <h1>Online Admission Form</h1>
    @if($instruction !== '')
        <div>{!! $instruction !!}</div>
    @endif
    @if($applicationForm !== '')
        <p><a href="{{ url('welcome/download/'.$schSetting->id) }}">Download Application Form</a></p>
    @endif
    <form method="post" action="{{ $formAction ?? url('online_admission') }}" enctype="multipart/form-data">
        @csrf
        @if(!empty($admissionId))
            <input type="hidden" name="admission_id" value="{{ $admissionId }}">
        @endif
        <div class="form-group">
            <label>Class</label>
            <select name="class_id" id="class_id" class="form-control">
                <option value="">Select</option>
                @foreach($classlist as $class)
                    <option value="{{ $class['id'] }}" {{ (string) ($old['class_id'] ?? '') === (string) $class['id'] ? 'selected' : '' }}>{{ $class['class'] }}</option>
                @endforeach
            </select>
            @if(!empty($formErrors['class_id']))<span class="text-danger">{{ $formErrors['class_id'] }}</span>@endif
        </div>
        <div class="form-group">
            <label>Section</label>
            <select name="section_id" id="section_id" class="form-control">
                <option value="">Select</option>
                @foreach($sectionlist as $section)
                    <option value="{{ $section['id'] }}" {{ (string) ($old['section_id'] ?? '') === (string) $section['id'] ? 'selected' : '' }}>{{ $section['section'] }}</option>
                @endforeach
            </select>
            @if(!empty($formErrors['section_id']))<span class="text-danger">{{ $formErrors['section_id'] }}</span>@endif
        </div>
        <div class="form-group">
            <label>First Name</label>
            <input class="form-control" name="firstname" value="{{ $old['firstname'] ?? '' }}">
            @if(!empty($formErrors['firstname']))<span class="text-danger">{{ $formErrors['firstname'] }}</span>@endif
        </div>
        <div class="form-group">
            <label>Last Name</label>
            <input class="form-control" name="lastname" value="{{ $old['lastname'] ?? '' }}">
        </div>
        <div class="form-group">
            <label>Date Of Birth</label>
            <input class="form-control" name="dob" value="{{ $old['dob'] ?? '' }}">
            @if(!empty($formErrors['dob']))<span class="text-danger">{{ $formErrors['dob'] }}</span>@endif
        </div>
        <div class="form-group">
            <label>Gender</label>
            <select name="gender" class="form-control">
                <option value="">Select</option>
                @foreach(['Male', 'Female'] as $gender)
                    <option value="{{ $gender }}" {{ (string) ($old['gender'] ?? '') === $gender ? 'selected' : '' }}>{{ $gender }}</option>
                @endforeach
            </select>
            @if(!empty($formErrors['gender']))<span class="text-danger">{{ $formErrors['gender'] }}</span>@endif
        </div>
        <div class="form-group">
            <label>Email</label>
            <input class="form-control" name="email" value="{{ $old['email'] ?? '' }}">
            @if(!empty($formErrors['email']))<span class="text-danger">{{ $formErrors['email'] }}</span>@endif
        </div>
        <div class="form-group">
            <label>Mobile Number</label>
            <input class="form-control" name="mobileno" value="{{ $old['mobileno'] ?? '' }}">
        </div>
        @if($guardianRequired)
            <div class="form-group">
                <label>If Guardian Is</label>
                <select name="guardian_is" class="form-control">
                    <option value="">Select</option>
                    @foreach(['father' => 'Father', 'mother' => 'Mother', 'other' => 'Other'] as $value => $label)
                        <option value="{{ $value }}" {{ (string) ($old['guardian_is'] ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                @if(!empty($formErrors['guardian_is']))<span class="text-danger">{{ $formErrors['guardian_is'] }}</span>@endif
            </div>
            <div class="form-group">
                <label>Guardian Name</label>
                <input class="form-control" name="guardian_name" value="{{ $old['guardian_name'] ?? '' }}">
                @if(!empty($formErrors['guardian_name']))<span class="text-danger">{{ $formErrors['guardian_name'] }}</span>@endif
            </div>
            <div class="form-group">
                <label>Guardian Relation</label>
                <input class="form-control" name="guardian_relation" value="{{ $old['guardian_relation'] ?? '' }}">
                @if(!empty($formErrors['guardian_relation']))<span class="text-danger">{{ $formErrors['guardian_relation'] }}</span>@endif
            </div>
        @endif
        @if(!empty($showStudentPhoto))
            <div class="form-group">
                <label>Student Photo</label>
                <input type="file" class="form-control" name="file">
                @if(!empty($formErrors['file']))<span class="text-danger">{{ $formErrors['file'] }}</span>@endif
            </div>
        @endif
        @if(!empty($showFatherPic))
            <div class="form-group">
                <label>Father Photo</label>
                <input type="file" class="form-control" name="father_pic">
                @if(!empty($formErrors['father_pic']))<span class="text-danger">{{ $formErrors['father_pic'] }}</span>@endif
            </div>
        @endif
        @if(!empty($showMotherPic))
            <div class="form-group">
                <label>Mother Photo</label>
                <input type="file" class="form-control" name="mother_pic">
                @if(!empty($formErrors['mother_pic']))<span class="text-danger">{{ $formErrors['mother_pic'] }}</span>@endif
            </div>
        @endif
        @if(!empty($showGuardianPic))
            <div class="form-group">
                <label>Guardian Photo</label>
                <input type="file" class="form-control" name="guardian_pic">
                @if(!empty($formErrors['guardian_pic']))<span class="text-danger">{{ $formErrors['guardian_pic'] }}</span>@endif
            </div>
        @endif
        @if(!empty($showDocuments))
            <div class="form-group">
                <label>Upload Documents</label>
                <input type="file" class="form-control" name="document">
                @if(!empty($formErrors['document']))<span class="text-danger">{{ $formErrors['document'] }}</span>@endif
            </div>
        @endif
        <button type="submit" class="btn btn-primary">Submit</button>
    </form>
    <hr>
    <h3>Check Admission Status</h3>
    <form id="status-form">
        @csrf
        <div class="form-group">
            <label>Reference No</label>
            <input class="form-control" name="refno" id="refno">
        </div>
        <div class="form-group">
            <label>Date Of Birth</label>
            <input class="form-control" name="student_dob" id="student_dob">
        </div>
        <button type="button" id="check-status" class="btn btn-default">Check</button>
        <p id="status-msg"></p>
    </form>
<script>
document.getElementById('class_id').addEventListener('change', function () {
    var body = new FormData();
    body.append('class_id', this.value);
    body.append('_token', '{{ csrf_token() }}');
    fetch('{{ url('welcome/getSections') }}', { method: 'POST', body: body, headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
        .then(function (res) { return res.json(); })
        .then(function (rows) {
            var sectionEl = document.getElementById('section_id');
            sectionEl.innerHTML = '<option value="">Select</option>';
            (rows || []).forEach(function (row) {
                var opt = document.createElement('option');
                opt.value = row.id;
                opt.textContent = row.section;
                sectionEl.appendChild(opt);
            });
        });
});
document.getElementById('check-status').addEventListener('click', function () {
    var body = new FormData();
    body.append('refno', document.getElementById('refno').value);
    body.append('student_dob', document.getElementById('student_dob').value);
    body.append('_token', '{{ csrf_token() }}');
    fetch('{{ url('welcome/checkadmissionstatus') }}', { method: 'POST', body: body, headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
        .then(function (res) { return res.json(); })
        .then(function (data) {
            var msg = document.getElementById('status-msg');
            if (data.status === '1') {
                window.location.href = '{{ url('welcome/online_admission_review') }}/' + data.refno;
            } else if (data.status === '0') {
                msg.textContent = (data.error && data.error.refno) ? data.error.refno : (data.msg || 'Something went wrong');
            } else {
                msg.textContent = data.error || '';
            }
        });
});
</script>
@endsection
