@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">Assign / View Student — {{ $exam->exam }}</h3>
        <div class="box-tools pull-right">
            <a href="{{ route('onlineexam.exams.index') }}" class="btn btn-default btn-sm">Back to Exams</a>
        </div>
    </div>
    <div class="box-body">
        <h4>Select Criteria</h4>
        <form method="post" action="{{ route('onlineexam.assign.index', $exam->id) }}" class="row">
            @csrf
            <div class="col-sm-4">
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
            <div class="col-sm-4">
                <div class="form-group">
                    <label>Section</label>
                    <select id="section_id" name="section_id" class="form-control">
                        <option value="">All Sections</option>
                    </select>
                </div>
            </div>
            <div class="col-sm-12">
                <button type="submit" class="btn btn-primary btn-sm pull-right"><i class="fa fa-search"></i> Search</button>
            </div>
        </form>
    </div>
</div>

@if($resultList !== null)
    <div class="box box-info">
        <div class="box-header with-border">
            <h3 class="box-title">Students ({{ $resultList->count() }})</h3>
        </div>
        <div class="box-body">
            <form method="post" action="{{ route('onlineexam.assign.save', $exam->id) }}">
                @csrf
                <input type="hidden" name="class_id" value="{{ $filters['class_id'] }}">
                <input type="hidden" name="section_id" value="{{ $filters['section_id'] ?? '' }}">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead>
                        <tr>
                            <th><input type="checkbox" id="select_all"> All</th>
                            <th>Admission No</th>
                            <th>Student Name</th>
                            <th>Class (Section)</th>
                            <th>Father Name</th>
                            <th>Category</th>
                            <th>Gender</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($resultList as $student)
                            <tr>
                                <td>
                                    <input class="checkbox" type="checkbox" name="students_id[]"
                                           value="{{ $student->student_session_id }}"
                                           @checked((int) $student->onlineexam_student_id !== 0)>
                                </td>
                                <td>{{ $student->admission_no }}</td>
                                <td>
                                    {{ trim($student->firstname.' '.($student->middlename ?? '').' '.($student->lastname ?? '')) }}
                                </td>
                                <td>{{ $student->class }} ({{ $student->section }})</td>
                                <td>{{ $student->father_name }}</td>
                                <td>{{ $student->category }}</td>
                                <td>{{ $student->gender }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-danger">No Record Found</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                @if($canSave && $resultList->isNotEmpty())
                    <button type="submit" class="btn btn-primary btn-sm pull-right"
                            onclick="return confirm('Save student assignment for this class/section? Unchecked students will be removed from this exam.');">
                        Save
                    </button>
                @endif
            </form>
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

    $('#select_all').on('change', function () {
        $('.checkbox').prop('checked', $(this).prop('checked'));
    });
    $(document).on('change', '.checkbox', function () {
        if ($('.checkbox:checked').length === $('.checkbox').length) {
            $('#select_all').prop('checked', true);
        } else {
            $('#select_all').prop('checked', false);
        }
    });
})();
</script>
@endpush
