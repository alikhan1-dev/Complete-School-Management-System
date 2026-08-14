<ul class="timeline">
    @foreach($follow_up_list as $value)
        <li>
            <span>{{ $enquiries->formatDate($value['date'] ?? null) }}</span>
            <div>
                @if(!empty($canFollowDelete))
                    <a onclick="delete_next_call({{ $value['id'] }},{{ $id }},'{{ $value['created_by'] }}')">Delete</a>
                @endif
                <strong>{{ $value['name'] }} {{ $value['surname'] }} ({{ $value['employee_id'] }})</strong>
                <div>{{ $value['response'] }}</div>
                <div>{{ $value['note'] }}</div>
            </div>
        </li>
    @endforeach
</ul>
<script>
var status = $('#status_data').val();
function delete_next_call(follow_up_id, enquiry_id, created_by) {
    if (!confirm('Are you sure you want to delete?')) { return; }
    $.ajax({
        url: '{{ url('admin/enquiry/follow_up_delete') }}/' + follow_up_id + '/' + enquiry_id,
        success: function () { follow_up(enquiry_id, created_by); }
    });
}
function follow_up(id, created_by) {
    $.ajax({
        url: '{{ url('admin/enquiry/follow_up') }}/' + id + '/' + status + '/' + created_by,
        success: function (data) {
            $('#getdetails_follow_up').html(data);
            $.ajax({ url: '{{ url('admin/enquiry/follow_up_list') }}/' + id, success: function (html) { $('#timeline').html(html); } });
        }
    });
}
</script>
