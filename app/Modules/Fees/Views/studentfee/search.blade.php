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
        <h3 class="box-title">Collect Fees</h3>
        <div class="box-tools">
            <a href="{{ route('fees.studentfee.searchpayment') }}" class="btn btn-default btn-sm">Search Payment</a>
        </div>
    </div>
    <div class="box-body">
        <ul class="nav nav-tabs" style="margin-bottom:15px;">
            <li class="{{ ($filters['search_type'] ?? 'class_search') === 'class_search' ? 'active' : '' }}">
                <a href="#class_search" data-toggle="tab">Class Search</a>
            </li>
            <li class="{{ ($filters['search_type'] ?? '') === 'keyword_search' ? 'active' : '' }}">
                <a href="#keyword_search" data-toggle="tab">Keyword Search</a>
            </li>
        </ul>

        <div class="tab-content">
            <div class="tab-pane {{ ($filters['search_type'] ?? 'class_search') === 'class_search' ? 'active' : '' }}" id="class_search">
                <form method="post" action="{{ route('fees.studentfee.index') }}" class="row">
                    @csrf
                    <input type="hidden" name="search_type" value="class_search">
                    <div class="col-sm-4">
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
                    <div class="col-sm-4">
                        <div class="form-group">
                            <label>Section</label>
                            <select id="section_id" name="section_id" class="form-control">
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
            <div class="tab-pane {{ ($filters['search_type'] ?? '') === 'keyword_search' ? 'active' : '' }}" id="keyword_search">
                <form method="post" action="{{ route('fees.studentfee.index') }}" class="row">
                    @csrf
                    <input type="hidden" name="search_type" value="keyword_search">
                    <div class="col-sm-8">
                        <div class="form-group">
                            <label>Keyword</label>
                            <input type="text" name="search_text" class="form-control" value="{{ $filters['search_text'] ?? '' }}" placeholder="Admission no, name, mobile…">
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
    </div>
</div>

@if($resultList !== null)
    <div class="box box-primary">
        <div class="box-header with-border"><h3 class="box-title">Students</h3></div>
        <div class="box-body table-responsive">
            <table class="table table-striped table-bordered">
                <thead>
                <tr>
                    <th>Class</th>
                    <th>Section</th>
                    <th>Admission No</th>
                    <th>Student Name</th>
                    <th>Father Name</th>
                    <th>Mobile</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                @forelse($resultList as $row)
                    <tr>
                        <td>{{ $row->class }}</td>
                        <td>{{ $row->section }}</td>
                        <td>{{ $row->admission_no }}</td>
                        <td>{{ trim($row->firstname.' '.($row->middlename ?? '').' '.$row->lastname) }}</td>
                        <td>{{ $row->father_name }}</td>
                        <td>{{ $row->mobileno }}</td>
                        <td>
                            <a href="{{ route('fees.studentfee.addfee', $row->student_session_id) }}" class="btn btn-info btn-xs">Collect Fees</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center">No student found</td></tr>
                @endforelse
                </tbody>
            </table>
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
