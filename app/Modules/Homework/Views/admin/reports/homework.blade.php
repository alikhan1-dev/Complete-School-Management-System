@include('homework::admin.reports._nav')
@include('homework::admin.reports._filters', ['action' => route('homework.reports.homework')])

@if($errors->any())
    <div class="alert alert-danger">
        <ul style="margin:0;padding-left:18px;">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@if(request()->filled('search') || request()->filled('class_id'))
<div class="box box-info">
    <div class="box-header with-border">
        <h3 class="box-title">Homework Report</h3>
    </div>
    <div class="box-body table-responsive">
        <table class="table table-striped table-bordered">
            <thead>
            <tr>
                <th>Class</th>
                <th>Section</th>
                <th>Subject Group</th>
                <th>Subject</th>
                <th>Homework Date</th>
                <th>Submission Date</th>
                <th>Student Count</th>
                <th>Homework Submitted</th>
                <th>Pending Student</th>
            </tr>
            </thead>
            <tbody>
            @forelse($rows as $row)
                @php
                    $studentCount = (int) ($row->student_count ?? 0);
                    $submitted = (int) ($row->assignments ?? 0);
                    $pending = max(0, $studentCount - $submitted);
                    $qs = array_filter([
                        'homework_id' => $row->id,
                        'class_id' => $row->class_id,
                        'section_id' => $row->section_id,
                        'search' => 1,
                        'subject_group_id' => $filters['subject_group_id'] ?? null,
                        'subject_id' => $filters['subject_id'] ?? null,
                    ], fn ($v) => $v !== null && $v !== '');
                @endphp
                <tr>
                    <td>{{ $row->class }}</td>
                    <td>{{ $row->section }}</td>
                    <td>{{ $row->subject_group_name }}</td>
                    <td>{{ $row->subject_name }}@if(!empty($row->subject_code)) ({{ $row->subject_code }})@endif</td>
                    <td>{{ $row->homework_date }}</td>
                    <td>{{ $row->submit_date }}</td>
                    <td>
                        <a href="{{ route('homework.reports.homework.students', $qs + ['type' => 'student_count']) }}">
                            {{ $studentCount }}
                        </a>
                    </td>
                    <td>
                        <a href="{{ route('homework.reports.homework.students', $qs + ['type' => 'homework_submitted']) }}">
                            {{ $submitted }}
                        </a>
                    </td>
                    <td>
                        <a href="{{ route('homework.reports.homework.students', $qs + ['type' => 'pending_student']) }}">
                            {{ $pending }}
                        </a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="9" class="text-center">No record found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endif
