<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-search"></i> Select Criteria</h3>
    </div>
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    <form action="{{ url('admin/enquiry') }}" method="post">
        @csrf
        <div class="box-body row">
            <div class="col-sm-2">
                <div class="form-group">
                    <label>Class</label>
                    <select name="class" class="form-control">
                        <option value="">Select</option>
                        @foreach($class_list as $value)
                            <option value="{{ $value->id }}" @selected((string)$selected_class === (string)$value->id)>{{ $value->class }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-sm-2">
                <div class="form-group">
                    <label>Source</label>
                    <select name="source" class="form-control">
                        <option value="">Select</option>
                        @foreach($sourcelist as $value)
                            <option value="{{ $value->source }}" @selected($source_select === $value->source)>{{ $value->source }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-sm-2">
                <div class="form-group">
                    <label>Enquiry From Date <small class="req">*</small></label>
                    <input type="text" name="from_date" class="form-control" value="{{ $from_date }}">
                </div>
            </div>
            <div class="col-sm-2">
                <div class="form-group">
                    <label>Enquiry To Date <small class="req">*</small></label>
                    <input type="text" name="to_date" class="form-control" value="{{ $to_date }}">
                </div>
            </div>
            <div class="col-sm-2">
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" class="form-control">
                        <option value="">Select</option>
                        <option value="all" @selected($status === 'all')>All</option>
                        @foreach($enquiry_status as $enkey => $envalue)
                            <option value="{{ $enkey }}" @selected($status === $enkey)>{{ $envalue }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-sm-2">
                <div class="form-group">
                    <label>&nbsp;</label>
                    <button type="submit" name="search" value="search_filter" class="btn btn-primary btn-sm">Search</button>
                </div>
            </div>
        </div>
    </form>
</div>

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">{{ $pageTitle }}</h3>
        @if(!empty($canAdd))
            <div class="box-tools pull-right">
                <button type="button" class="btn btn-sm btn-primary openmodal">Add</button>
            </div>
        @endif
    </div>
    <div class="box-body table-responsive">
        <table class="table table-striped table-bordered">
            <thead>
            <tr>
                <th>Name</th>
                <th>Phone</th>
                <th>Source</th>
                <th>Enquiry Date</th>
                <th>Last Follow Up Date</th>
                <th>Next Follow Up Date</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            @foreach($enquiry_list as $value)
                @php
                    $nextDate = $value['next_date'] ?: ($value['follow_up_date'] ?? '');
                    $overdue = $nextDate !== '' && $nextDate !== '0000-00-00' && $nextDate < date('Y-m-d');
                @endphp
                <tr @class(['danger' => $overdue])>
                    <td>{{ $value['name'] }}</td>
                    <td>{{ $value['contact'] }}</td>
                    <td>{{ $value['source'] }}</td>
                    <td>{{ app(\App\Modules\FrontOffice\Services\EnquiryService::class)->formatDate($value['date'] ?? null) }}</td>
                    <td>{{ app(\App\Modules\FrontOffice\Services\EnquiryService::class)->formatDate($value['followupdate'] ?? null) }}</td>
                    <td>{{ app(\App\Modules\FrontOffice\Services\EnquiryService::class)->formatDate($nextDate ?: null) }}</td>
                    <td>{{ $enquiry_status[$value['status']] ?? $value['status'] }}</td>
                    <td>
                        @if(!empty($canFollowView))
                            <a class="btn btn-primary btn-xs" onclick="follow_up('{{ $value['id'] }}', '{{ $value['status'] }}', '{{ $value['created_by'] }}');" data-target="#follow_up" data-toggle="modal">Follow Up</a>
                        @endif
                        @if(!empty($canEdit))
                            <a onclick="getRecord('{{ $value['id'] }}', '{{ $value['status'] }}')" class="btn btn-primary btn-xs" data-target="#myModaledit" data-toggle="modal">Edit</a>
                        @endif
                        @if(!empty($canDelete))
                            <a href="#" class="btn btn-primary btn-xs" onclick="delete_enquiry('{{ $value['id'] }}')">Delete</a>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="myModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Admission Enquiry</h4>
            </div>
            <div class="modal-body">
                <form id="formadd" method="post">
                    @csrf
                    <div class="row">
                        <div class="col-sm-4"><label>Name <small class="req">*</small></label><input type="text" class="form-control" name="name"></div>
                        <div class="col-sm-4"><label>Phone <small class="req">*</small></label><small id="phone_error_message"></small><input id="number" type="number" class="form-control" name="contact"></div>
                        <div class="col-sm-4"><label>Email</label><input type="text" class="form-control" name="email"></div>
                    </div>
                    <div class="row">
                        <div class="col-sm-4"><label>Address</label><textarea name="address" class="form-control"></textarea></div>
                        <div class="col-sm-4"><label>Description</label><textarea name="description" class="form-control"></textarea></div>
                        <div class="col-sm-4"><label>Note</label><textarea name="note" class="form-control"></textarea></div>
                    </div>
                    <div class="row">
                        <div class="col-sm-4"><label>Date <small class="req">*</small></label><input type="text" name="date" class="form-control" value="{{ $today }}"></div>
                        <div class="col-sm-4"><label>Next Follow Up Date <small class="req">*</small></label><input type="text" name="follow_up_date" class="form-control" value="{{ $today }}"></div>
                        <div class="col-sm-4">
                            <label>Assigned</label>
                            <select name="assigned" class="form-control">
                                <option value="">Select</option>
                                @foreach($stff_list as $staff)
                                    <option value="{{ $staff->id }}">{{ $staff->name }} {{ $staff->surname }} ({{ $staff->employee_id }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-3">
                            <label>Reference</label>
                            <select name="reference" class="form-control">
                                <option value="">Select</option>
                                @foreach($Reference as $value)
                                    <option value="{{ $value->reference }}">{{ $value->reference }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-sm-3">
                            <label>Source <small class="req">*</small></label>
                            <select name="source" class="form-control">
                                <option value="">Select</option>
                                @foreach($sourcelist as $value)
                                    <option value="{{ $value->source }}">{{ $value->source }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-sm-3">
                            <label>Class</label>
                            <select name="class" class="form-control">
                                <option value="">Select</option>
                                @foreach($class_list as $value)
                                    <option value="{{ $value->id }}">{{ $value->class }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-sm-3"><label>Number Of Child</label><input type="number" min="1" class="form-control" name="no_of_child"></div>
                    </div>
                    <div class="box-footer" style="margin-top:10px;">
                        <button type="submit" class="btn btn-info pull-right">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="myModaledit" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Edit Admission Enquiry</h4>
            </div>
            <div class="modal-body" id="getdetails"></div>
        </div>
    </div>
</div>

<div class="modal fade" id="follow_up" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" onclick="update()" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Follow Up Admission Enquiry</h4>
            </div>
            <div class="modal-body" id="getdetails_follow_up"></div>
        </div>
    </div>
</div>

@push('scripts')
<script>
$.ajaxSetup({ headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } });
$('.openmodal').click(function () { $('#formadd').trigger('reset'); $('#myModal').modal('show'); });
function getRecord(id, status) {
    $.ajax({ url: '{{ url('admin/enquiry/details') }}/' + id + '/' + status, success: function (result) { $('#getdetails').html(result); } });
}
function postRecord(id) {
    $.ajax({
        url: '{{ url('admin/enquiry/editpost') }}/' + id,
        type: 'POST',
        data: $('#myForm1').serialize(),
        dataType: 'json',
        success: function (data) {
            if (data.status == 'fail') { return; }
            window.location.reload(true);
        }
    });
}
$('#formadd').on('submit', function (e) {
    e.preventDefault();
    $.ajax({
        url: '{{ url('admin/enquiry/add') }}',
        type: 'POST',
        data: new FormData(this),
        dataType: 'json',
        contentType: false,
        cache: false,
        processData: false,
        success: function (res) {
            if (res.status == 'fail') { return; }
            window.location.reload(true);
        }
    });
});
function delete_enquiry(id) {
    if (!confirm('Are you sure?')) { return; }
    $.ajax({
        url: '{{ url('admin/enquiry/delete') }}/' + id,
        type: 'POST',
        dataType: 'json',
        success: function (data) {
            if (data.status != 'fail') { window.location.reload(true); }
        }
    });
}
function follow_up(id, status, created_by) {
    $.ajax({
        url: '{{ url('admin/enquiry/follow_up') }}/' + id + '/' + status + '/' + created_by,
        success: function (data) {
            $('#getdetails_follow_up').html(data);
            $.ajax({ url: '{{ url('admin/enquiry/follow_up_list') }}/' + id, success: function (html) { $('#timeline').html(html); } });
        }
    });
}
function update() { window.location.reload(true); }
$('#number').blur(function () {
    $('#phone_error_message').html('');
    $.ajax({
        url: '{{ url('admin/enquiry/check_number') }}',
        type: 'POST',
        data: { phone_number: $('#number').val() },
        dataType: 'json',
        success: function (data) {
            if (data.status == 'success') { $('#phone_error_message').html('(' + data.message + ')'); }
        }
    });
});
</script>
@endpush
