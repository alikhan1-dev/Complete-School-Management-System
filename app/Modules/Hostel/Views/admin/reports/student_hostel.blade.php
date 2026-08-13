@if($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-search"></i> Select Criteria</h3>
    </div>
    <form method="post" action="{{ route('hostel.reports.student_hostel') }}">
        @csrf
        <div class="box-body row">
            <div class="col-sm-4 col-md-4">
                <div class="form-group">
                    <label>Class <span class="text-danger">*</span></label>
                    <select autofocus id="class_id" name="class_id" class="form-control" required>
                        <option value="">Select</option>
                        @foreach($classes as $class)
                            <option value="{{ $class->id }}" @selected((string) ($filters['class_id'] ?? '') === (string) $class->id)>
                                {{ $class->class }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-sm-4 col-md-4">
                <div class="form-group">
                    <label>Section <span class="text-danger">*</span></label>
                    <select id="section_id" name="section_id" class="form-control" required>
                        <option value="">Select</option>
                    </select>
                </div>
            </div>
            <div class="col-sm-4 col-md-4">
                <div class="form-group">
                    <label>Hostel Name</label>
                    <select class="form-control" name="hostel_name">
                        <option value="">Select</option>
                        @foreach($hostels as $hostel)
                            <option value="{{ $hostel->id }}" @selected((string) ($filters['hostel_name'] ?? '') === (string) $hostel->id)>
                                {{ $hostel->hostel_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="form-group">
                <div class="col-sm-12">
                    <button type="submit" name="search" value="search_filter" class="btn btn-primary btn-sm pull-right">
                        <i class="fa fa-search"></i> Search
                    </button>
                </div>
            </div>
        </div>
    </form>

    @if(!empty($searched))
        <div class="box-header with-border">
            <h3 class="box-title"><i class="fa fa-users"></i> Student Hostel Report</h3>
        </div>
        <div class="box-body table-responsive">
            <table class="table table-striped table-bordered table-hover">
                <thead>
                <tr>
                    <th>Class / Section</th>
                    <th>Admission No</th>
                    <th>Student Name</th>
                    <th>Mobile Number</th>
                    <th>Guardian Phone</th>
                    <th>Hostel Name</th>
                    <th>Room Number / Name</th>
                    <th>Room Type</th>
                    <th class="text-right">Cost Per Bed ({{ $currencySymbol }})</th>
                </tr>
                </thead>
                <tbody>
                @forelse($rows as $student)
                    @php
                        $fullName = trim(implode(' ', array_filter([
                            $student->firstname ?? '',
                            $student->middlename ?? '',
                            $student->lastname ?? '',
                        ])));
                    @endphp
                    <tr>
                        <td>{{ $student->class }} - {{ $student->section }}</td>
                        <td>{{ $student->admission_no }}</td>
                        <td>
                            <a href="{{ url('student/view/'.$student->id) }}">{{ $fullName }}</a>
                        </td>
                        <td>{{ $student->mobileno }}</td>
                        <td>{{ $student->guardian_phone }}</td>
                        <td>{{ $student->hostel_name }}</td>
                        <td>{{ $student->room_no }}</td>
                        <td>{{ $student->room_type }}</td>
                        <td class="text-right">{{ number_format((float) $student->cost_per_bed, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center">No record found.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    @endif
</div>

@push('scripts')
<script>
(function () {
    var oldSection = @json((string) ($filters['section_id'] ?? ''));

    function loadSections(classId, selected) {
        var $section = $('#section_id');
        $section.html('<option value="">Select</option>');
        if (!classId) return;
        $.getJSON(@json(url('sections/getByClass')), {class_id: classId}, function (data) {
            $.each(data || [], function (i, row) {
                var opt = $('<option>', {value: row.section_id, text: row.section});
                if (String(selected) === String(row.section_id)) opt.prop('selected', true);
                $section.append(opt);
            });
        });
    }

    $('#class_id').on('change', function () {
        loadSections($(this).val(), '');
    });
    loadSections($('#class_id').val(), oldSection);
})();
</script>
@endpush
