@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-search"></i> Verify TC</h3>
        <div class="box-tools pull-right">
            @if(!empty($canPrepare))
                <a href="{{ route('certificates.tc_prepare.index') }}" class="btn btn-default btn-sm">Prepare TC</a>
            @endif
            @if(!empty($canDownload))
                <a href="{{ route('certificates.tc_download.index') }}" class="btn btn-default btn-sm">Download TC</a>
            @endif
            @if(!empty($canViewSettings))
                <a href="{{ route('certificates.tc_settings.index') }}" class="btn btn-default btn-sm">TC Settings</a>
            @endif
        </div>
    </div>
    <div class="box-body">
        <form method="post" action="{{ route('certificates.tc_verify.index') }}" class="form-inline">
            @csrf
            <div class="form-group" style="margin-right:10px;">
                <label for="student_tc_no">Enter TC No</label>
                <small class="req">*</small>
                <input id="student_tc_no" name="student_tc_no" class="form-control"
                       value="{{ old('student_tc_no', $studentTcNo) }}" required>
            </div>
            <button type="submit" name="search" value="1" class="btn btn-primary btn-sm">
                <i class="fa fa-search"></i> Search
            </button>
        </form>
    </div>

    @if($searched && $preview)
        <div class="box-body" style="border-top:1px solid #f4f4f4;">
            <div style="border:1px solid #ddd;padding:8px;">
                @include('certificates::admin.transfercertificate._sheet', $preview)
            </div>
        </div>
    @elseif($searched)
        <div class="box-body">
            <div class="alert alert-danger mb0">No Record Found</div>
        </div>
    @endif
</div>
