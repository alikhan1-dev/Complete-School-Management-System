<table class="table table-striped">
    <tr>
        <th>Complain #</th>
        <td>{{ $complaint_data['id'] }}</td>
        <th>Complaint Type</th>
        <td>{{ $complaint_data['complaint_type'] }}</td>
    </tr>
    <tr>
        <th>Source</th>
        <td>{{ $complaint_data['source'] }}</td>
        <th>Name</th>
        <td>{{ $complaint_data['name'] }}@if(!empty($complaint_data['email'])) ({{ $complaint_data['email'] }}) @endif</td>
    </tr>
    <tr>
        <th>Phone</th>
        <td>{{ $complaint_data['contact'] }}</td>
        <th>Date</th>
        <td>{{ $complaints->formatDate($complaint_data['date'] ?? null) }}</td>
    </tr>
    <tr>
        <th>Assigned</th>
        <td>{{ $complaint_data['assigned'] }}</td>
        <th>Action Taken</th>
        <td>{{ $complaint_data['action_taken'] }}</td>
    </tr>
    <tr>
        <th>Description</th>
        <td colspan="3">{{ $complaint_data['description'] }}</td>
    </tr>
    <tr>
        <th>Note</th>
        <td colspan="3">{{ $complaint_data['note'] }}</td>
    </tr>
</table>
