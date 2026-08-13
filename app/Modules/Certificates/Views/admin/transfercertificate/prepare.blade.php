@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-search"></i> Select Criteria</h3>
        <div class="box-tools pull-right">
            @if(!empty($canDownload))
                <a href="{{ route('certificates.tc_download.index') }}" class="btn btn-default btn-sm">Download TC</a>
            @endif
            @if(!empty($canVerify))
                <a href="{{ route('certificates.tc_verify.index') }}" class="btn btn-default btn-sm">Verify TC</a>
            @endif
            @if(!empty($canViewSettings))
                <a href="{{ route('certificates.tc_settings.index') }}" class="btn btn-default btn-sm">TC Settings</a>
            @endif
        </div>
    </div>
    <div class="box-body">
        <form method="post" action="{{ route('certificates.tc_prepare.search') }}" class="row">
            @csrf
            <div class="col-sm-6">
                <div class="form-group">
                    <label>Class</label> <small class="req">*</small>
                    <select id="class_id" name="class_id" class="form-control" required>
                        <option value="">Select</option>
                        @foreach($classes as $class)
                            <option value="{{ $class->id }}" @selected((string) ($filters['class_id'] ?? '') === (string) $class->id)>
                                {{ $class->class }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="form-group">
                    <label>Section</label>
                    <select id="section_id" name="section_id" class="form-control">
                        <option value="">All Sections</option>
                    </select>
                </div>
            </div>
            <div class="col-sm-12">
                <button type="submit" name="search" value="search_filter" class="btn btn-primary btn-sm pull-right">
                    <i class="fa fa-search"></i> Search
                </button>
            </div>
        </form>
    </div>
</div>

@if($students !== null)
    <div class="box box-info">
        <div class="box-header with-border">
            <h3 class="box-title"><i class="fa fa-users"></i> Student List</h3>
        </div>
        <div class="box-body table-responsive">
            <table class="table table-striped table-bordered">
                <thead>
                <tr>
                    <th>Admission No</th>
                    <th>Student Name</th>
                    <th>Date of Birth</th>
                    <th>Gender</th>
                    <th>Category</th>
                    <th>Mobile Number</th>
                    <th width="8%">Action</th>
                </tr>
                </thead>
                <tbody>
                @forelse($students as $student)
                    @php
                        $fullName = trim($student->firstname.' '.($student->middlename ?? '').' '.($student->lastname ?? ''));
                    @endphp
                    <tr>
                        <td>{{ $student->admission_no }}</td>
                        <td>{{ $fullName }}</td>
                        <td>
                            @if(!empty($student->dob) && $student->dob !== '0000-00-00')
                                {{ \Carbon\Carbon::parse($student->dob)->format('d-m-Y') }}
                            @endif
                        </td>
                        <td>{{ $student->gender }}</td>
                        <td>{{ $student->category }}</td>
                        <td>{{ $student->mobileno }}</td>
                        <td>
                            <a href="{{ route('certificates.tc_prepare.edit', $student->id) }}"
                               class="btn btn-primary btn-xs" title="Fill other details" target="_blank">
                                <i class="fa fa-reorder"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center">No students found.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endif

@push('scripts')
<script>
(function () {
    var oldSection = @json((string) ($filters['section_id'] ?? ''));
    function loadSections(classId, selected) {
        var $section = $('#section_id');
        $section.html('<option value="">All Sections</option>');
        if (!classId) return;
        $.getJSON('{{ url('sections/getByClass') }}', {class_id: classId}, function (data) {
            $.each(data || [], function (i, row) {
                var opt = $('<option>', {value: row.section_id, text: row.section});
                if (String(selected) === String(row.section_id)) opt.prop('selected', true);
                $section.append(opt);
            });
        });
    }
    $('#class_id').on('change', function () { loadSections($(this).val(), ''); });
    loadSections($('#class_id').val(), oldSection);
})();
</script>
@endpush
