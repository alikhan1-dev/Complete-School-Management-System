@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
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
        <h3 class="box-title">Fees Carry Forward</h3>
        <div class="box-tools">
            <a href="{{ route('fees.studentfee.index') }}" class="btn btn-default btn-sm">Collect Fees</a>
        </div>
    </div>
    <div class="box-body">
        @if($previousSession)
            <p class="text-muted">Previous session: <strong>{{ $previousSession }}</strong></p>
        @else
            <div class="alert alert-warning">No previous academic session found. Create an older session before carrying balances forward.</div>
        @endif

        <form method="post" action="{{ route('fees.feesforward.index') }}" class="row">
            @csrf
            <input type="hidden" name="action" value="search">
            <div class="col-sm-4">
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
            <div class="col-sm-4">
                <div class="form-group">
                    <label>Section <span class="text-danger">*</span></label>
                    <select id="section_id" name="section_id" class="form-control" required>
                        <option value="">Select</option>
                    </select>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="form-group">
                    <label>&nbsp;</label>
                    <button type="submit" class="btn btn-primary btn-sm btn-block"><i class="fa fa-search"></i> Search</button>
                </div>
            </div>
        </form>
    </div>
</div>

@if($students !== null)
    <div class="box box-primary">
        <div class="box-header with-border"><h3 class="box-title">Students</h3></div>
        <div class="box-body table-responsive">
            <form method="post" action="{{ route('fees.feesforward.index') }}">
                @csrf
                <input type="hidden" name="action" value="fee_submit">
                <input type="hidden" name="class_id" value="{{ $filters['class_id'] }}">
                <input type="hidden" name="section_id" value="{{ $filters['section_id'] }}">

                <div class="form-group" style="max-width:220px;">
                    <label>Due Date <span class="text-danger">*</span></label>
                    <input type="date" name="due_date" class="form-control" value="{{ old('due_date', $dueDateDefault) }}" required>
                </div>

                <table class="table table-bordered table-striped">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>Admission No</th>
                        <th>Student</th>
                        <th>Father</th>
                        <th>Previous Balance</th>
                        <th>Status</th>
                        <th>Amount to Carry</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($students as $i => $row)
                        @php $idx = $i + 1; @endphp
                        <tr>
                            <td>
                                {{ $idx }}
                                <input type="hidden" name="student_counter[]" value="{{ $idx }}">
                                <input type="hidden" name="student_sesion[{{ $idx }}]" value="{{ $row->student_session_id }}">
                            </td>
                            <td>{{ $row->admission_no }}</td>
                            <td>{{ $row->name }}</td>
                            <td>{{ $row->father_name }}</td>
                            <td>{{ number_format($row->balance, 2) }}</td>
                            <td>
                                @if($row->assigned)
                                    <span class="label label-success">Assigned</span>
                                @else
                                    <span class="label label-default">Not Assigned</span>
                                @endif
                            </td>
                            <td style="max-width:140px;">
                                <input type="number" step="0.01" min="0" class="form-control"
                                       name="amount[{{ $idx }}]"
                                       value="{{ old('amount.'.$idx, number_format((float) ($row->assigned_amount ?? $row->balance), 2, '.', '')) }}">
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center">No students found</td></tr>
                    @endforelse
                    </tbody>
                </table>

                @if(count($students))
                    <button type="submit" class="btn btn-primary"
                            onclick="return confirm('Save fees carry forward for these students?');">Save Carry Forward</button>
                @endif
            </form>
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
