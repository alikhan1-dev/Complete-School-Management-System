@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">Select Criteria</h3>
        @can('privilege', ['student', 'can_add'])
            <a href="{{ route('students.create') }}" class="btn btn-primary btn-sm pull-right">Add Student</a>
        @endcan
    </div>
    <div class="box-body">
        <div class="row">
            <form id="class_search_form" class="class_search_form">
                @csrf
                <div class="col-md-6">
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label>Class</label> <small class="req">*</small>
                                <select id="class_id" name="class_id" class="form-control">
                                    <option value="">Select</option>
                                    @foreach($classes as $class)
                                        <option value="{{ $class->id }}">{{ $class->class }}</option>
                                    @endforeach
                                </select>
                                <span class="text-danger" id="error_class_id"></span>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label>Section</label>
                                <select id="section_id" name="section_id" class="form-control">
                                    <option value="">Select</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-sm-12">
                            <button type="submit" name="search" value="search_filter" class="btn btn-primary btn-sm pull-right">Search</button>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="row">
                        <div class="col-sm-12">
                            <div class="form-group">
                                <label>Search By Keyword</label>
                                <input type="text" name="search_text" id="search_text" class="form-control" placeholder="Search by student name / admission no">
                            </div>
                        </div>
                        <div class="col-sm-12">
                            <button type="submit" name="search" value="search_full" class="btn btn-primary btn-sm pull-right">Search</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="box box-primary">
    <div class="box-header with-border"><h3 class="box-title">Student List</h3></div>
    <div class="box-body table-responsive">
        <table class="table table-striped table-bordered" id="students-table" style="width:100%">
            <thead>
            <tr>
                <th>Admission No</th>
                <th>Student Name</th>
                @if((int) ($schSetting->roll_no ?? 1) === 1)
                    <th>Roll No</th>
                @endif
                <th>Class</th>
                @if((int) ($schSetting->father_name ?? 1) === 1)
                    <th>Father Name</th>
                @endif
                <th>Date Of Birth</th>
                <th>Gender</th>
                @if((int) ($schSetting->category ?? 1) === 1)
                    <th>Category</th>
                @endif
                @if((int) ($schSetting->mobile_no ?? 1) === 1)
                    <th>Mobile Number</th>
                @endif
                <th>Action</th>
            </tr>
            </thead>
        </table>
    </div>
</div>

@push('scripts')
<script src="{{ asset('backend/dist/datatables/2.2.2/js/dataTables.min.js') }}"></script>
<script>
(function () {
    var searchParams = { class_id: '', section_id: '', search_text: '', srch_type: 'search_filter' };
    var table = null;

    $('#class_id').on('change', function () {
        var classId = $(this).val();
        $('#section_id').html('<option value="">Select</option>');
        if (!classId) return;
        $.getJSON('{{ url('sections/getByClass') }}', {class_id: classId}, function (data) {
            $.each(data, function (i, row) {
                $('#section_id').append($('<option>', {value: row.section_id, text: row.section}));
            });
        });
    });

    $('.class_search_form').on('submit', function (e) {
        e.preventDefault();
        var btn = $(document.activeElement);
        var searchType = btn.val() || 'search_filter';
        $('#error_class_id').text('');

        $.post('{{ route('students.search_validation') }}', {
            _token: '{{ csrf_token() }}',
            search_type: searchType,
            class_id: $('#class_id').val(),
            section_id: $('#section_id').val(),
            search_text: $('#search_text').val()
        }).done(function (res) {
            if (res.status != 1) {
                if (res.error && res.error.class_id) {
                    $('#error_class_id').html(res.error.class_id);
                }
                return;
            }
            searchParams = {
                class_id: res.params.class_id || '',
                section_id: res.params.section_id || '',
                search_text: res.params.search_text || '',
                srch_type: res.params.search_type || searchType
            };
            if (table) {
                table.ajax.reload();
            } else {
                initTable();
            }
        }).fail(function (xhr) {
            if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                $('#error_class_id').text((xhr.responseJSON.errors.class_id || []).join(' '));
            }
        });
    });

    function initTable() {
        table = $('#students-table').DataTable({
            processing: true,
            serverSide: false,
            ajax: {
                url: '{{ route('students.datatable') }}',
                type: 'POST',
                data: function (d) {
                    d._token = '{{ csrf_token() }}';
                    d.class_id = searchParams.class_id;
                    d.section_id = searchParams.section_id;
                    d.search_text = searchParams.search_text;
                    d.srch_type = searchParams.srch_type;
                }
            }
        });
    }
})();
</script>
@endpush
