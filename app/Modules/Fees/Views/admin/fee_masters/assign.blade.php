@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">Assign Fees Group — {{ $sessionGroup->feeGroup->name ?? '' }}</h3>
        <div class="box-tools">
            <a href="{{ route('fees.fee_masters.index') }}" class="btn btn-default btn-sm">Back</a>
        </div>
    </div>
    <div class="box-body">
        <h4>Fee Types</h4>
        <table class="table table-bordered" style="max-width:480px;">
            <thead><tr><th>Code</th><th class="text-right">Amount</th></tr></thead>
            <tbody>
            @forelse($sessionGroup->feeTypes as $ft)
                <tr>
                    <td>{{ $ft->feeType->code ?? '' }}</td>
                    <td class="text-right">{{ number_format((float) $ft->amount, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="2" class="text-danger text-center">No fee types</td></tr>
            @endforelse
            </tbody>
        </table>

        <hr>
        <h4>Select Criteria</h4>
        <form method="post" action="{{ route('fees.fee_masters.assign', $feeSessionGroupId) }}" class="row">
            @csrf
            <div class="col-sm-3">
                <div class="form-group">
                    <label>Class</label>
                    <select id="class_id" name="class_id" class="form-control">
                        <option value="">Select</option>
                        @foreach($classes as $class)
                            <option value="{{ $class->id }}" @selected((string) ($filters['class_id'] ?? '') === (string) $class->id)>{{ $class->class }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-sm-3">
                <div class="form-group">
                    <label>Section</label>
                    <select id="section_id" name="section_id" class="form-control">
                        <option value="">Select</option>
                    </select>
                </div>
            </div>
            <div class="col-sm-2">
                <div class="form-group">
                    <label>Category</label>
                    <select name="category_id" class="form-control">
                        <option value="">Select</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" @selected((string) ($filters['category_id'] ?? '') === (string) $cat->id)>{{ $cat->category }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-sm-2">
                <div class="form-group">
                    <label>Gender</label>
                    <select name="gender" class="form-control">
                        <option value="">Select</option>
                        <option value="Male" @selected(($filters['gender'] ?? '') === 'Male')>Male</option>
                        <option value="Female" @selected(($filters['gender'] ?? '') === 'Female')>Female</option>
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
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">Assign Fees Group</h3>
        </div>
        <div class="box-body">
            <form method="post" action="{{ route('fees.fee_masters.assign_save') }}" id="assign_form">
                @csrf
                <input type="hidden" name="fee_session_groups" value="{{ $feeSessionGroupId }}">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead>
                        <tr>
                            <th><input type="checkbox" id="select_all"></th>
                            <th>Admission No</th>
                            <th>Student Name</th>
                            <th>Class</th>
                            <th>Father Name</th>
                            <th>Gender</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($resultList as $student)
                            <tr>
                                <td>
                                    <input class="checkbox" type="checkbox" name="student_session_id[]"
                                           value="{{ $student->student_session_id }}"
                                           @checked((int) $student->student_fees_master_id !== 0)>
                                    <input type="hidden" name="student_ids[]" value="{{ $student->student_session_id }}">
                                </td>
                                <td>{{ $student->admission_no }}</td>
                                <td>{{ trim($student->firstname.' '.($student->middlename ?? '').' '.($student->lastname ?? '')) }}</td>
                                <td>{{ $student->class }} ({{ $student->section }})</td>
                                <td>{{ $student->father_name }}</td>
                                <td>{{ $student->gender }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-danger text-center">No Record Found</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                @if($resultList->isNotEmpty())
                    <button type="submit" class="btn btn-primary btn-sm pull-right"
                            onclick="return confirm('Are you sure?');">Save</button>
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

    $('#select_all').on('change', function () {
        $('.checkbox').prop('checked', $(this).prop('checked'));
    });
});
</script>
@endpush
