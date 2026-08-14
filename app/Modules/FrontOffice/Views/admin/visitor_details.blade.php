<div class="table-responsive">
    <table class="table table-striped">
        <tr>
            <th>Purpose</th>
            <td>{{ $data['purpose'] }}</td>
            <th>Meeting With</th>
            <td>
                {{ $data['meeting_with'] }}
                @if(!empty($data['staff_id']))
                    ({{ $data['staff_name'] }} {{ $data['staff_surname'] }} - {{ $data['staff_employee_id'] }})
                @endif
                @if(!empty($data['student_session_id']))
                    ({{ $data['student_firstname'] }} {{ $data['student_middlename'] }} {{ $data['student_lastname'] }} - {{ $data['admission_no'] }})
                @endif
            </td>
        </tr>
        @if(!empty($data['student_session_id']))
            <tr>
                <th>Class</th><td>{{ $data['class'] }}</td>
                <th>Section</th><td>{{ $data['section'] }}</td>
            </tr>
        @endif
        <tr>
            <th>Visitor Name</th><td>{{ $data['name'] }}</td>
            <th>Phone</th><td>{{ $data['contact'] }}</td>
        </tr>
        <tr>
            <th>Number Of Person</th><td>{{ $data['no_of_people'] }}</td>
            <th>Date</th><td>{{ $visitors->formatDate($data['date'] ?? null) }}</td>
        </tr>
        <tr>
            <th>In Time</th><td>{{ $data['in_time'] }}</td>
            <th>Out Time</th><td>{{ $data['out_time'] }}</td>
        </tr>
        <tr>
            <th>ID Card</th><td>{{ $data['id_proof'] }}</td>
            <th></th><td></td>
        </tr>
        <tr>
            <th>Note</th>
            <td colspan="3">{{ $data['note'] }}</td>
        </tr>
    </table>
</div>
