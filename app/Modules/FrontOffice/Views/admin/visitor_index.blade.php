<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">{{ $pageTitle }}</h3>
        @if(!empty($canAdd))
            <div class="box-tools pull-right">
                <button type="button" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#myModal">Add</button>
            </div>
        @endif
    </div>
    <div class="box-body table-responsive">
        <table class="table table-striped table-bordered">
            <thead>
            <tr>
                <th>Purpose</th>
                <th>Meeting With</th>
                <th>Visitor Name</th>
                <th>Phone</th>
                <th>ID Card</th>
                <th>Number Of Person</th>
                <th>Date</th>
                <th>In Time</th>
                <th>Out Time</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            @foreach($visitor_list as $value)
                <tr>
                    <td>{{ $value['purpose'] }}</td>
                    <td>
                        {{ $meeting_with[$value['meeting_with']] ?? $value['meeting_with'] }}
                        @if(!empty($value['staff_id']))
                            ({{ $value['staff_name'] }} {{ $value['staff_surname'] }} - {{ $value['staff_employee_id'] }})
                        @endif
                        @if(!empty($value['student_session_id']))
                            ({{ $value['student_firstname'] }} {{ $value['student_middlename'] }} {{ $value['student_lastname'] }} - {{ $value['admission_no'] }})
                        @endif
                    </td>
                    <td>{{ $value['name'] }}</td>
                    <td>{{ $value['contact'] }}</td>
                    <td>{{ $value['id_proof'] }}</td>
                    <td>{{ $value['no_of_people'] }}</td>
                    <td>{{ $visitors->formatDate($value['date'] ?? null) }}</td>
                    <td>{{ $value['in_time'] }}</td>
                    <td>{{ $value['out_time'] }}</td>
                    <td>
                        <a onclick="getRecord({{ $value['id'] }})" class="btn btn-primary btn-xs" data-target="#visitordetails" data-toggle="modal">View</a>
                        @if(($value['image'] ?? '') !== '')
                            <a href="{{ url('admin/visitors/download/'.$value['id']) }}" class="btn btn-primary btn-xs">Download</a>
                        @endif
                        @if(!empty($canEdit))
                            <a data-id="{{ $value['id'] }}" class="btn btn-primary btn-xs editvisitor">Edit</a>
                        @endif
                        @if(!empty($canDelete))
                            <a class="btn btn-primary btn-xs delete_visitor" data-id="{{ $value['id'] }}">Delete</a>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

<div id="visitordetails" class="modal fade">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Details</h4>
            </div>
            <div class="modal-body" id="getdetails"></div>
        </div>
    </div>
</div>

<div class="modal fade" id="myModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Add Visitor</h4>
            </div>
            <form id="addvisitorform" method="post" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-sm-4">
                            <label>Purpose <small class="req">*</small></label>
                            <select name="purpose" class="form-control">
                                <option value="">Select</option>
                                @foreach($Purpose as $value)
                                    <option value="{{ $value->visitors_purpose }}">{{ $value->visitors_purpose }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-sm-4">
                            <label>Meeting With <small class="req">*</small></label>
                            <select name="meeting_with" id="meeting_with" class="form-control">
                                <option value="">Select</option>
                                @foreach($meeting_with as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div id="visible_staff" style="display:none;">
                            <div class="col-sm-4">
                                <label>Staff <small class="req">*</small></label>
                                <select name="staff_id" class="form-control">
                                    <option value="">Select</option>
                                    @foreach($stafflist as $staff)
                                        <option value="{{ $staff->id }}">{{ $staff->name }} {{ $staff->surname }} ({{ $staff->employee_id }})</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div id="visible_student" style="display:none;">
                            <div class="col-sm-4">
                                <label>Class <small class="req">*</small></label>
                                <select id="class_id" name="class_id" class="form-control">
                                    <option value="">Select</option>
                                    @foreach($classlist as $class)
                                        <option value="{{ $class->id }}">{{ $class->class }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-sm-4">
                                <label>Section <small class="req">*</small></label>
                                <select id="section_id" name="class_section_id" class="form-control">
                                    <option value="">Select</option>
                                </select>
                            </div>
                            <div class="col-sm-4">
                                <label>Student <small class="req">*</small></label>
                                <select id="student_id" name="student_session_id" class="form-control">
                                    <option value="">Select</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-sm-4"><label>Visitor Name <small class="req">*</small></label><input type="text" class="form-control" name="name"></div>
                        <div class="col-sm-4"><label>Phone</label><input type="text" class="form-control" name="contact"></div>
                        <div class="col-sm-4"><label>ID Card</label><input type="text" class="form-control" name="id_proof"></div>
                        <div class="col-sm-4"><label>Number Of Person</label><input type="text" class="form-control" name="pepples"></div>
                        <div class="col-sm-4"><label>Date <small class="req">*</small></label><input type="text" class="form-control" name="date" value="{{ $today }}"></div>
                        <div class="col-sm-4"><label>In Time</label><input type="text" class="form-control" name="time"></div>
                        <div class="col-sm-4"><label>Out Time</label><input type="text" class="form-control" name="out_time"></div>
                        <div class="col-sm-4"><label>Attach Document</label><input type="file" class="form-control" name="file"></div>
                        <div class="col-sm-12"><label>Note</label><textarea class="form-control" name="note" rows="3"></textarea></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-info">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editvisitormodal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Edit Visitor</h4>
            </div>
            <form id="editvisitorform" method="post" enctype="multipart/form-data">
                @csrf
                <div class="modal-body" id="editvisitordata"></div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-info">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
$.ajaxSetup({ headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } });
function getRecord(id) {
    $.ajax({ url: '{{ url('admin/visitors/details') }}/' + id, success: function (result) { $('#getdetails').html(result); } });
}
$('#addvisitorform').on('submit', function (e) {
    e.preventDefault();
    $.ajax({
        url: '{{ url('admin/visitors/add') }}',
        type: 'POST',
        data: new FormData(this),
        dataType: 'json',
        contentType: false,
        cache: false,
        processData: false,
        success: function (res) { if (res.status != 'fail') { window.location.reload(true); } }
    });
});
$('.editvisitor').click(function () {
    $('#editvisitormodal').modal('show');
    $.ajax({
        url: '{{ url('admin/visitors/editvisitor') }}',
        type: 'post',
        data: { visitorid: $(this).attr('data-id') },
        dataType: 'json',
        success: function (response) { $('#editvisitordata').html(response.page); }
    });
});
$('#editvisitorform').on('submit', function (e) {
    e.preventDefault();
    $.ajax({
        url: '{{ url('admin/visitors/edit') }}',
        type: 'POST',
        data: new FormData(this),
        dataType: 'json',
        contentType: false,
        cache: false,
        processData: false,
        success: function (res) { if (res.status != 'fail') { window.location.reload(true); } }
    });
});
$('#meeting_with').change(function () {
    var v = $(this).val();
    $('#visible_staff').toggle(v === 'staff');
    $('#visible_student').toggle(v === 'student');
});
$('#class_id').change(function () { getsectionbyclass($(this).val(), ''); });
function getsectionbyclass(class_id, section_id) {
    $('#section_id').html('');
    $('#edit_section_id').html('');
    var div_data = '<option value="">Select</option>';
    $.ajax({
        type: 'GET',
        url: '{{ url('sections/getByClass') }}',
        data: { class_id: class_id },
        dataType: 'json',
        success: function (data) {
            $.each(data, function (i, obj) {
                var selected = section_id == obj.section_id ? 'selected' : '';
                div_data += '<option value="' + obj.section_id + '" ' + selected + '>' + obj.section + '</option>';
            });
            $('#section_id').append(div_data);
            $('#edit_section_id').append(div_data);
        }
    });
}
$('#section_id').change(function () {
    studentbysection($('#class_id').val(), $(this).val(), '');
});
function studentbysection(class_id, section_id, student_id) {
    var div_data = '<option value="">Select</option>';
    $.ajax({
        type: 'post',
        url: '{{ url('admin/visitors/getstudent') }}',
        data: { class_id: class_id, section_id: section_id },
        dataType: 'json',
        success: function (data) {
            $.each(data.studentlist, function (i, obj) {
                if (obj.middlename == null) { obj.middlename = ''; }
                if (obj.lastname == null) { obj.lastname = ''; }
                div_data += '<option value="' + obj.id + '">' + obj.firstname + ' ' + obj.middlename + ' ' + obj.lastname + ' (' + obj.admission_no + ')</option>';
            });
            $('#student_id').html(div_data);
            $('#edit_student_session_id').html(div_data);
            $('#edit_student_session_id').val(student_id);
        }
    });
}
$('.delete_visitor').click(function () {
    if (!confirm('Are you sure want to delete?')) { return; }
    $.ajax({
        method: 'post',
        url: '{{ url('admin/visitors/delete') }}',
        data: { id: $(this).attr('data-id') },
        dataType: 'json',
        success: function () { location.reload(); }
    });
});
</script>
@endpush
