<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Admit Card - {{ $student->admission_no }}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 24px; color: #222; }
        .header { text-align: center; margin-bottom: 16px; }
        .header h2, .header h3, .header h4 { margin: 4px 0; }
        .meta { width: 100%; margin-bottom: 16px; }
        .meta td { padding: 4px 8px; vertical-align: top; }
        table.schedule { width: 100%; border-collapse: collapse; margin-top: 12px; }
        table.schedule th, table.schedule td { border: 1px solid #444; padding: 6px 8px; }
        .footer { margin-top: 24px; }
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
    <div>Admit Card — {{ $exam->exam }} ({{ $group->name }})</div>
    @if($template->exam_center)<div>Center: {{ $template->exam_center }}</div>@endif
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
        @if((int) $template->is_mother_name === 1)
            <td><strong>Mother Name:</strong> {{ $student->mother_name }}</td>
        @endif
    </tr>
    <tr>
        @if((int) $template->is_roll_no === 1)
            <td><strong>Roll No:</strong> {{ $student->exam_roll_no ?: $student->roll_no }}</td>
        @endif
        @if((int) $template->is_gender === 1)
            <td><strong>Gender:</strong> {{ $student->gender }}</td>
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
        @if((int) $template->is_address === 1)
            <td><strong>Address:</strong> {{ $student->current_address }}</td>
        @endif
    </tr>
</table>

<table class="schedule">
    <thead>
    <tr>
        <th>Subject</th>
        <th>Date</th>
        <th>Time</th>
        <th>Duration</th>
        <th>Room</th>
    </tr>
    </thead>
    <tbody>
    @forelse($subjects as $row)
        <tr>
            <td>{{ $row->subject_name }}@if($row->subject_code) ({{ $row->subject_code }})@endif</td>
            <td>{{ $row->date_from }}</td>
            <td>{{ $row->time_from }}</td>
            <td>{{ $row->duration }}</td>
            <td>{{ $row->room_no }}</td>
        </tr>
    @empty
        <tr><td colspan="5">No exam subjects found</td></tr>
    @endforelse
    </tbody>
</table>

@if($template->content_footer)
    <div class="footer">{!! $template->content_footer !!}</div>
@endif
</body>
</html>
