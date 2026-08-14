<table class="table table-striped">
    <tr>
        <th>Name</th>
        <td>{{ $Call_data['name'] }}</td>
        <th>Phone</th>
        <td>{{ $Call_data['contact'] }}</td>
    </tr>
    <tr>
        <th>Date</th>
        <td>{{ $calls->formatDate($Call_data['date'] ?? null) }}</td>
        <th>Next Follow Up Date</th>
        <td>{{ $calls->formatFollowUpDate($Call_data['follow_up_date'] ?? null) }}</td>
    </tr>
    <tr>
        <th>Call Duration</th>
        <td>{{ $Call_data['call_duration'] }}</td>
        <th>Call Type</th>
        <td>{{ $Call_data['call_type'] }}</td>
    </tr>
    <tr>
        <th>Description</th>
        <td colspan="3">{{ $Call_data['description'] }}</td>
    </tr>
    <tr>
        <th>Note</th>
        <td colspan="3">{{ $Call_data['note'] }}</td>
    </tr>
</table>
