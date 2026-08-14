<table class="table table-striped">
    <tr>
        <th>To Title</th>
        <td>{{ $data['to_title'] }}</td>
        <th>Reference No</th>
        <td>{{ $data['reference_no'] }}</td>
    </tr>
    <tr>
        <th>From Title</th>
        <td>{{ $data['from_title'] }}</td>
        <th>Date</th>
        <td>{{ $records->formatDate($data['date'] ?? null) }}</td>
    </tr>
    <tr>
        <th>Address</th>
        <td colspan="3">{{ $data['address'] }}</td>
    </tr>
    <tr>
        <th>Note</th>
        <td colspan="3">{{ $data['note'] }}</td>
    </tr>
</table>
