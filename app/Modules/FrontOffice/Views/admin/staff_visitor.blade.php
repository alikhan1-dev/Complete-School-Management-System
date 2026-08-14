<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">{{ $pageTitle }}</h3>
    </div>
    <div class="box-body table-responsive">
        <table class="table table-striped table-bordered">
            <thead>
            <tr>
                <th>Purpose</th>
                <th>Visitor Name</th>
                <th>Phone</th>
                <th>ID Card</th>
                <th>Number Of Person</th>
                <th>Date</th>
                <th>In Time</th>
                <th>Out Time</th>
            </tr>
            </thead>
            <tbody>
            @foreach($visitor_list as $value)
                <tr>
                    <td>{{ $value['purpose'] }}</td>
                    <td>{{ $value['name'] }}</td>
                    <td>{{ $value['contact'] }}</td>
                    <td>{{ $value['id_proof'] }}</td>
                    <td>{{ $value['no_of_people'] }}</td>
                    <td>{{ $visitors->formatDate($value['date'] ?? null) }}</td>
                    <td>{{ $value['in_time'] }}</td>
                    <td>{{ $value['out_time'] }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
