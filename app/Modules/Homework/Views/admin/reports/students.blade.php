<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">{{ $typeLabel }}</h3>
        <div class="box-tools pull-right">
            <a href="{{ $backUrl }}" class="btn btn-default btn-sm">Back</a>
        </div>
    </div>
    <div class="box-body table-responsive">
        <table class="table table-striped table-bordered">
            <thead>
            <tr>
                <th>Admission No</th>
                <th>Student</th>
                <th>Class</th>
                <th>Section</th>
                @if($type === 'homework_submitted')
                    <th>Message</th>
                    <th>Document</th>
                @endif
            </tr>
            </thead>
            <tbody>
            @forelse($students as $student)
                @php
                    $name = trim(preg_replace('/\s+/', ' ', ($student->firstname ?? '').' '.($student->middlename ?? '').' '.($student->lastname ?? '')) ?? '');
                @endphp
                <tr>
                    <td>{{ $student->admission_no }}</td>
                    <td>{{ $name }}</td>
                    <td>{{ $student->class }}</td>
                    <td>{{ $student->section }}</td>
                    @if($type === 'homework_submitted')
                        <td>{{ $student->message ?: '—' }}</td>
                        <td>{{ $student->docs ?: '—' }}</td>
                    @endif
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $type === 'homework_submitted' ? 6 : 4 }}" class="text-center">No record found.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
