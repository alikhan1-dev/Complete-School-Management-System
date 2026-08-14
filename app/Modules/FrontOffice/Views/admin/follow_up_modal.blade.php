@php
    $assigned = $assigned_staff ?? [];
    $created = $created_by ?? [];
@endphp
<div class="row">
    <div class="col-md-8">
        <form id="folow_up_data" method="post">
            @csrf
            <input type="hidden" id="enquiry_id" name="enquiry_id" value="{{ $enquiry_data['id'] }}">
            <input type="hidden" id="enquiry_status" name="enquiry_status" value="{{ $enquiry_data['status'] }}">
            <input type="hidden" id="created_by" name="created_by" value="{{ $enquiry_data['created_by'] }}">
            <div class="row">
                <div class="col-sm-6">
                    <label>Follow Up Date <small class="req">*</small></label>
                    <input type="text" id="follow_date" name="date" class="form-control" value="{{ $today }}">
                </div>
                <div class="col-sm-6">
                    <label>Next Follow Up Date <small class="req">*</small></label>
                    <input type="text" id="follow_date_of_call" name="follow_up_date" class="form-control">
                </div>
                <div class="col-sm-6">
                    <label>Response <small class="req">*</small></label>
                    <textarea name="response" id="response" class="form-control"></textarea>
                </div>
                <div class="col-sm-6">
                    <label>Note</label>
                    <textarea name="note" id="note" class="form-control"></textarea>
                </div>
            </div>
            @if(!empty($canFollowAdd))
                <button type="submit" class="btn btn-info pull-right" style="margin-top:10px;">Save</button>
            @endif
        </form>
        <h4>Follow Up ({{ $enquiry_data['name'] }})</h4>
        <div id="timeline"></div>
    </div>
    <div class="col-md-4">
        <h4>Summary
            <select class="form-control" id="status_data" onchange="changeStatus(this.value, '{{ $enquiry_data['id'] }}','{{ $enquiry_data['created_by'] }}')">
                @foreach($enquiry_status as $enkey => $envalue)
                    <option value="{{ $enkey }}" @selected(($enquiry_data['status'] ?? '') === $enkey)>{{ $envalue }}</option>
                @endforeach
            </select>
        </h4>
        <p><strong>Assigned:</strong>
            @if(!empty($assigned))
                {{ $assigned['name'] ?? '' }} {{ $assigned['surname'] ?? '' }}
                @if(($assigned['employee_id'] ?? '') !== '') ({{ $assigned['employee_id'] }}) @endif
            @endif
        </p>
        <p><strong>Enquiry Date:</strong> {{ $enquiries->formatDate($enquiry_data['date'] ?? null) }}</p>
        <p><strong>Last Follow Up Date:</strong>
            @if(!empty($next_date[0]['date'])) {{ $enquiries->formatDate($next_date[0]['date']) }} @endif
        </p>
        <p><strong>Next Follow Up Date:</strong>
            @if(!empty($next_date[0]['next_date']))
                {{ $enquiries->formatDate($next_date[0]['next_date']) }}
            @elseif(($enquiry_data['follow_up_date'] ?? '') !== '0000-00-00')
                {{ $enquiries->formatDate($enquiry_data['follow_up_date'] ?? null) }}
            @endif
        </p>
        <p><strong>Phone:</strong> {{ $enquiry_data['contact'] }}</p>
        <p><strong>Reference:</strong> {{ $enquiry_data['reference'] }}</p>
        <p><strong>Source:</strong> {{ $enquiry_data['source'] }}</p>
        <p><strong>Email:</strong> {{ $enquiry_data['email'] }}</p>
        <p><strong>Address:</strong> {{ $enquiry_data['address'] }}</p>
        <p><strong>Class:</strong> {{ $enquiry_data['classname'] }}</p>
        <p><strong>Number Of Child:</strong> {{ $enquiry_data['no_of_child'] }}</p>
        <p><strong>Description:</strong> {{ $enquiry_data['description'] }}</p>
        <p><strong>Note:</strong> {{ $enquiry_data['note'] }}</p>
        <p><strong>Created By:</strong>
            @php
                $showCreator = false;
                if ((int) $staff_role === 7) {
                    $showCreator = !empty($created);
                } elseif ($superadmin_rest === 'enabled') {
                    $showCreator = !empty($created);
                } elseif (!empty($created['id']) && (int) $login_staff_id === (int) $created['id']) {
                    $showCreator = true;
                }
            @endphp
            @if($showCreator)
                {{ $created['name'] ?? '' }} {{ $created['surname'] ?? '' }}
                @if(($created['employee_id'] ?? '') !== '') ({{ $created['employee_id'] }}) @endif
            @endif
        </p>
    </div>
</div>
<script>
$("#folow_up_data").on('submit', function (e) {
    e.preventDefault();
    var id = $('#enquiry_id').val();
    var status = $('#enquiry_status').val();
    var created_by = $('#created_by').val();
    $.ajax({
        url: "{{ url('admin/enquiry/follow_up_insert') }}",
        type: "POST",
        data: new FormData(this),
        dataType: 'json',
        contentType: false,
        cache: false,
        processData: false,
        success: function (res) {
            if (res.status == "fail") { return; }
            follow_up_new(id, status, created_by);
        }
    });
});
function follow_up_new(id, status, created_by) {
    $.ajax({
        url: "{{ url('admin/enquiry/follow_up') }}/" + id + "/" + status + "/" + created_by,
        success: function (data) {
            $('#getdetails_follow_up').html(data);
            $.ajax({ url: "{{ url('admin/enquiry/follow_up_list') }}/" + id, success: function (html) { $('#timeline').html(html); } });
        }
    });
}
function changeStatus(status, id, created_by) {
    $.ajax({
        url: "{{ url('admin/enquiry/change_status') }}",
        type: "POST",
        dataType: "json",
        data: { status: status, id: id },
        success: function (data) {
            if (data.status != "fail") { follow_up_new(id, status, created_by); }
        }
    });
}
</script>
