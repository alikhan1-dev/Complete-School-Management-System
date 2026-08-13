<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Marksheet - {{ $student->admission_no }}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 24px; color: #222; }
        .header { text-align: center; margin-bottom: 16px; }
        .header h2, .header h3, .header h4 { margin: 4px 0; }
        .meta { width: 100%; margin-bottom: 16px; }
        .meta td { padding: 4px 8px; vertical-align: top; }
        table.marks { width: 100%; border-collapse: collapse; margin-top: 12px; }
        table.marks th, table.marks td { border: 1px solid #444; padding: 6px 8px; }
        .footer { margin-top: 24px; }
        .signs { display: flex; justify-content: space-between; margin-top: 40px; }
        .signs div { text-align: center; width: 30%; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
<button class="no-print" onclick="window.print()">Print</button>

<div class="header">
    @if($template->heading)<h3>{{ $template->heading }}</h3>@endif
    @if($template->title)<h4>{{ $template->title }}</h4>@endif
    @if($template->school_name)<h2>{{ $template->school_name }}</h2>@endif
    @if($template->exam_name)<h3>{{ $template->exam_name }}</h3>@endif
    <div>{{ $exam->exam }} — {{ $group->name }}</div>
</div>

<table class="meta">
    <tr>
        @if((int) $template->is_name === 1)
            <td><strong>Name:</strong> {{ trim($student->firstname.' '.($student->middlename ?? '').' '.($student->lastname ?? '')) }}</td>
        @endif
        @if((int) $template->is_admission_no === 1)
            <td><strong>Admission No:</strong> {{ $student->admission_no }}</td>
        @endif
    </tr>
    <tr>
        @if((int) $template->is_father_name === 1)
            <td><strong>Father Name:</strong> {{ $student->father_name }}</td>
        @endif
        @if((int) $template->is_roll_no === 1)
            <td><strong>Roll No:</strong> {{ $student->exam_roll_no ?: $student->roll_no }}</td>
        @endif
    </tr>
    <tr>
        @if((int) $template->is_class === 1)
            <td><strong>Class:</strong> {{ $student->class }}</td>
        @endif
        @if((int) $template->is_section === 1)
            <td><strong>Section:</strong> {{ $student->section }}</td>
        @endif
    </tr>
    <tr>
        @if((int) $template->is_dob === 1)
            <td><strong>DOB:</strong> {{ $student->dob }}</td>
        @endif
        @if((int) $template->exam_session === 1)
            <td><strong>Exam:</strong> {{ $exam->exam }}</td>
        @endif
    </tr>
</table>

@if($template->content)
    <div>{!! nl2br(e($template->content)) !!}</div>
@endif

<table class="marks">
    <thead>
    <tr>
        <th>Subject</th>
        <th>Max</th>
        <th>Min</th>
        <th>Obtained</th>
        <th>Attendance</th>
    </tr>
    </thead>
    <tbody>
    @forelse($marks as $row)
        <tr>
            <td>{{ $row->subject_name }}@if($row->subject_code) ({{ $row->subject_code }})@endif</td>
            <td>{{ $row->max_marks }}</td>
            <td>{{ $row->min_marks }}</td>
            <td>{{ $row->attendence === 'absent' ? 'AB' : $row->get_marks }}</td>
            <td>{{ $row->attendence }}</td>
        </tr>
    @empty
        <tr><td colspan="5">No subjects / marks found</td></tr>
    @endforelse
    </tbody>
</table>

@if((int) $template->is_teacher_remark === 1 && $student->teacher_remark)
    <p><strong>Teacher Remark:</strong> {{ $student->teacher_remark }}</p>
@endif

@if($template->content_footer)
    <div class="footer">{!! nl2br(e($template->content_footer)) !!}</div>
@endif

@if($template->date)
    <p><strong>Date:</strong> {{ $template->date }}</p>
@endif
</body>
</html>
