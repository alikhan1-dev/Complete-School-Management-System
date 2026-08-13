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
            @can('privilege', ['student_id_card', 'can_view'])
                <a href="{{ route('certificates.idcard_templates.index') }}" class="btn btn-default btn-sm">ID Card Templates</a>
            @endcan
        </div>
    </div>
    <div class="box-body">
        <form method="post" action="{{ route('certificates.idcard_generate.search') }}" class="row">
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
            <div class="col-sm-4">
                <div class="form-group">
                    <label>ID Card Template</label> <small class="req">*</small>
                    <select name="id_card" class="form-control" required>
                        <option value="">Select</option>
                        @foreach($idcards as $card)
                            <option value="{{ $card->id }}" @selected((string) ($filters['id_card'] ?? '') === (string) $card->id)>
                                {{ $card->title }}
                            </option>
                        @endforeach
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
            @if($selectedIdCard)
                <span class="text-muted" style="margin-left:8px;">Template: {{ $selectedIdCard->title }}</span>
            @endif
        </div>
        <div class="box-body">
            <form method="post" action="{{ route('certificates.idcard_generate.print') }}" target="_blank" id="generate-idcard-form">
                @csrf
                <input type="hidden" name="id_card" value="{{ $filters['id_card'] }}">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead>
                        <tr>
                            <th><input type="checkbox" id="select_all"> All</th>
                            <th>Admission No</th>
                            <th>Student Name</th>
                            <th>Class</th>
                            <th>Father Name</th>
                            <th>Date of Birth</th>
                            <th>Gender</th>
                            <th>Category</th>
                            <th>Mobile Number</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($students as $student)
                            <tr>
                                <td>
                                    <input class="checkbox" type="checkbox" name="student_ids[]" value="{{ $student->id }}">
                                </td>
                                <td>{{ $student->admission_no }}</td>
                                <td>{{ trim($student->firstname.' '.($student->middlename ?? '').' '.($student->lastname ?? '')) }}</td>
                                <td>{{ $student->class }} ({{ $student->section }})</td>
                                <td>{{ $student->father_name }}</td>
                                <td>{{ $student->dob && $student->dob !== '0000-00-00' ? $student->dob : '' }}</td>
                                <td>{{ $student->gender }}</td>
                                <td>{{ $student->category }}</td>
                                <td>{{ $student->mobileno }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="text-center text-danger">No Record Found</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                @if($students->isNotEmpty())
                    <button type="submit" class="btn btn-info btn-sm pull-right">Generate</button>
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
        $('#select_all').prop('checked', $('.checkbox:checked').length === $('.checkbox').length);
    });

    $('#generate-idcard-form').on('submit', function (e) {
        if ($('.checkbox:checked').length === 0) {
            e.preventDefault();
            alert('Please select at least one student.');
        }
    });
})();
</script>
@endpush
