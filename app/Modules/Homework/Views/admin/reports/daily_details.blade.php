<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">Daily Assignment Details</h3>
        <div class="box-tools pull-right">
            <a href="{{ $backUrl }}" class="btn btn-default btn-sm">Back</a>
        </div>
    </div>
    <div class="box-body table-responsive">
        @if($assignments->isEmpty())
            <div class="alert alert-info">No record found.</div>
        @else
            @php
                $first = $assignments->first();
                $name = trim(preg_replace('/\s+/', ' ', ($first->firstname ?? '').' '.($first->middlename ?? '').' '.($first->lastname ?? '')) ?? '');
            @endphp
            <p><strong>Student Name:</strong> {{ $name }} ({{ $first->admission_no }})</p>
            <hr>
            <table class="table table-striped table-bordered">
                <thead>
                <tr>
                    <th>Subject</th>
                    <th>Title</th>
                    <th>Description</th>
                    <th>Remark</th>
                    <th>Submission Date</th>
                    <th>Evaluation Date</th>
                    <th>Evaluated By</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                @foreach($assignments as $row)
                    @php
                        $evaluator = trim(($row->staff_name ?? '').' '.($row->staff_surname ?? ''));
                        if ($evaluator !== '' && !empty($row->staff_employee_id)) {
                            $evaluator .= ' ('.$row->staff_employee_id.')';
                        }
                    @endphp
                    <tr>
                        <td>{{ $row->subject_name }}@if(!empty($row->subject_code)) ({{ $row->subject_code }})@endif</td>
                        <td>{{ $row->title }}</td>
                        <td>{!! nl2br(e((string) $row->description)) !!}</td>
                        <td>{{ $row->remark ?: '—' }}</td>
                        <td>{{ $row->date }}</td>
                        <td>{{ $row->evaluation_date ?: '—' }}</td>
                        <td>{{ $evaluator !== '' ? $evaluator : '—' }}</td>
                        <td>
                            @if(!empty($row->attachment))
                                <a href="{{ route('homework.daily.download', $row->id) }}" class="btn btn-primary btn-xs">
                                    <i class="fa fa-download"></i>
                                </a>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
