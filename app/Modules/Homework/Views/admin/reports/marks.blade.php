@include('homework::admin.reports._nav')
@include('homework::admin.reports._filters', [
    'action' => route('homework.reports.marks'),
    'requireClassOnly' => true,
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
        <h3 class="box-title">Homework Marks Report</h3>
    </div>
    <div class="box-body table-responsive">
        <table class="table table-striped table-bordered">
            <thead>
            <tr>
                <th>Admission No</th>
                <th>Student</th>
                <th>Class</th>
                <th>Section</th>
                <th>Subject Group</th>
                <th>Subject</th>
                <th>Homework Date</th>
                <th>Max Marks</th>
                <th>Marks Obtain</th>
                <th>Note</th>
                <th>Evaluation Date</th>
            </tr>
            </thead>
            <tbody>
            @forelse($rows as $row)
                @php
                    $name = trim(preg_replace('/\s+/', ' ', ($row->firstname ?? '').' '.($row->middlename ?? '').' '.($row->lastname ?? '')) ?? '');
                @endphp
                <tr>
                    <td>{{ $row->admission_no }}</td>
                    <td>{{ $name }}</td>
                    <td>{{ $row->class }}</td>
                    <td>{{ $row->section }}</td>
                    <td>{{ $row->subject_group_name }}</td>
                    <td>{{ $row->subject_name }}@if(!empty($row->subject_code)) ({{ $row->subject_code }})@endif</td>
                    <td>{{ $row->homework_date }}</td>
                    <td>{{ $row->max_marks ?? '—' }}</td>
                    <td>{{ $row->marks_obtain ?? '—' }}</td>
                    <td>{{ $row->note ?: '—' }}</td>
                    <td>{{ $row->eval_date ?: '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="11" class="text-center">No record found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endif
