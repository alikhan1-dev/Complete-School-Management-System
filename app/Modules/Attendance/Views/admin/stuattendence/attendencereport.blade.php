@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">Attendance By Date</h3>
        <div class="box-tools">
            <a href="{{ route('attendance.stuattendence.index') }}" class="btn btn-default btn-sm">Student Attendance</a>
        </div>
    </div>
    <div class="box-body">
        <p class="text-muted" style="margin-top:0;">
            Shows students who already have attendance prepared for the selected date
            (same as CI <code>searchAttendenceClassSectionPrepare</code>).
        </p>
        <form method="post" action="{{ route('attendance.stuattendence.attendencereport') }}" class="row">
            @csrf
            <div class="col-sm-3">
                <div class="form-group">
                    <label>Class <span class="text-danger">*</span></label>
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
                    <label>Section <span class="text-danger">*</span></label>
                    <select id="section_id" name="section_id" class="form-control" required>
                        <option value="">Select</option>
                    </select>
                </div>
            </div>
            <div class="col-sm-3">
                <div class="form-group">
                    <label>Attendance Date <span class="text-danger">*</span></label>
                    <input type="date" name="date" class="form-control" value="{{ old('date', $filters['date'] ?? date('Y-m-d')) }}" required>
                </div>
            </div>
            <div class="col-sm-3">
                <div class="form-group">
                    <label>&nbsp;</label>
                    <button type="submit" class="btn btn-primary btn-sm btn-block"><i class="fa fa-search"></i> Search</button>
                </div>
            </div>
        </form>
    </div>
</div>

@if($resultList !== null)
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">Attendance List</h3>
        </div>
        <div class="box-body table-responsive">
            @if($resultList->isEmpty())
                <div class="alert alert-info" style="margin-bottom:0;">No attendance prepared</div>
            @else
                <table class="table table-striped table-bordered table-hover">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>Admission No</th>
                        <th>Roll No</th>
                        <th>Name</th>
                        <th>Attendance</th>
                        <th>Note</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($resultList as $i => $row)
                        @php
                            $typeId = (int) ($row->attendence_type_id ?? 0);
                            $labelClass = match ($typeId) {
                                1 => 'label-success',   // Present
                                2 => 'label-primary',   // Late With Excuse
                                3 => 'label-warning',   // Late
                                5 => 'label-default',   // Holiday
                                6 => 'label-info',      // Half Day
                                default => 'label-danger', // Absent etc.
                            };
                            $typeName = $row->att_type ?: '—';
                        @endphp
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>{{ $row->admission_no }}</td>
                            <td>{{ $row->roll_no }}</td>
                            <td>{{ trim($row->firstname.' '.($row->middlename ?? '').' '.$row->lastname) }}</td>
                            <td>
                                <span class="label {{ $labelClass }}">{{ $typeName }}</span>
                            </td>
                            <td>{{ $row->remark }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
@endif

@push('scripts')
<script>
$(function () {
    var oldSection = '{{ $filters['section_id'] ?? '' }}';
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
    loadSections($('#class_id').val(), oldSection);
});
</script>
@endpush
