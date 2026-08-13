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

<div class="row">
    <div class="col-md-3">
        <div class="box box-primary" @if(($student->is_active ?? '') === 'no') style="background-color:#f0dddd;" @endif>
            <div class="box-body box-profile">
                <h3 class="profile-username text-center" style="margin-top:0;">{{ $studentName }}</h3>
                <p class="text-muted text-center">Admission No: {{ $student->admission_no }}</p>
                <ul class="list-group list-group-unbordered">
                    @if(!empty($student->class))
                        <li class="list-group-item">
                            <b>Class</b>
                            <a class="pull-right">{{ $student->class }}@if(!empty($student->session)) ({{ $student->session }})@endif</a>
                        </li>
                    @endif
                    @if(!empty($student->section))
                        <li class="list-group-item">
                            <b>Section</b> <a class="pull-right">{{ $student->section }}</a>
                        </li>
                    @endif
                    @if(!empty($student->gender))
                        <li class="list-group-item">
                            <b>Gender</b> <a class="pull-right">{{ $student->gender }}</a>
                        </li>
                    @endif
                    @if(!empty($student->dob) && $student->dob !== '0000-00-00')
                        <li class="list-group-item">
                            <b>Date of Birth</b>
                            <a class="pull-right">{{ \Carbon\Carbon::parse($student->dob)->format('d-m-Y') }}</a>
                        </li>
                    @endif
                    @if(!empty($student->category))
                        <li class="list-group-item">
                            <b>Category</b> <a class="pull-right">{{ $student->category }}</a>
                        </li>
                    @endif
                    @if(!empty($student->mobileno))
                        <li class="list-group-item">
                            <b>Mobile Number</b> <a class="pull-right">{{ $student->mobileno }}</a>
                        </li>
                    @endif
                </ul>
                <a href="{{ route('certificates.tc_prepare.index') }}" class="btn btn-default btn-block">Back to Prepare TC</a>
            </div>
        </div>
    </div>

    <div class="col-md-9">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fa fa-gear"></i> Fill Other Details</h3>
            </div>
            <form method="post" action="{{ route('certificates.tc_prepare.save') }}">
                @csrf
                <input type="hidden" name="student_id" value="{{ $student->id }}">
                <div class="box-body">
                    @if($customFields->isEmpty())
                        <div class="alert alert-info mb0">
                            No transfer certificate custom fields are configured yet.
                            Add fields under <strong>Custom Fields</strong> with belong-to
                            <code>transfer_certificate</code>, then enable them in TC Settings if they should print.
                        </div>
                    @else
                        @include('academics::partials.custom_fields_form', [
                            'customFields' => $customFields,
                            'customFieldValues' => $customFieldValues,
                            'belongTo' => 'transfer_certificate',
                        ])
                    @endif
                </div>
                @if($canEdit && $customFields->isNotEmpty())
                    <div class="box-footer">
                        <button type="submit" class="btn btn-primary pull-right">Save</button>
                    </div>
                @endif
            </form>
        </div>
    </div>
</div>
