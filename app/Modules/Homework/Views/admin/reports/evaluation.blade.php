@include('homework::admin.reports._nav')
@include('homework::admin.reports._filters', [
    'action' => route('homework.reports.evaluation'),
    'requireAllFilters' => true,
])

@if($errors->any())
    <div class="alert alert-danger">
        <ul style="margin:0;padding-left:18px;">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@if(!empty($filters['class_id']))
<div class="box box-info">
    <div class="box-header with-border">
        <h3 class="box-title">Homework Evaluation Report</h3>
    </div>
    <div class="box-body table-responsive">
        <table class="table table-striped table-bordered">
            <thead>
            <tr>
                <th>Subject</th>
                <th>Homework Date</th>
                <th>Submission Date</th>
                <th>Complete / Incomplete</th>
                <th>Complete %</th>
            </tr>
            </thead>
            <tbody>
            @forelse($rows as $row)
                @php $stat = $stats[$row->id] ?? ['completed' => 0, 'incomplete' => 0, 'percentage' => 0]; @endphp
                <tr>
                    <td>{{ $row->subject_name }}@if(!empty($row->subject_code)) ({{ $row->subject_code }})@endif</td>
                    <td>{{ $row->homework_date }}</td>
                    <td>{{ $row->submit_date }}</td>
                    <td>{{ $stat['completed'] }}/{{ $stat['incomplete'] }}</td>
                    <td>{{ $stat['percentage'] }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center">No record found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endif
