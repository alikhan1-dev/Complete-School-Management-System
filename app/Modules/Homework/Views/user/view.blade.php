@php
    $extList = implode(', ', $uploadMeta['extensions'] ?? []);
@endphp

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger">
        <ul style="margin:0;padding-left:18px;">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">Homework Detail</h3>
        <div class="box-tools pull-right">
            <a href="{{ route('user.homework.index') }}" class="btn btn-default btn-sm">Back</a>
        </div>
    </div>
    <div class="box-body">
        <table class="table table-bordered">
            <tr><th width="30%">Subject</th><td>{{ $homework->subject_name }}@if(!empty($homework->subject_code)) ({{ $homework->subject_code }})@endif</td></tr>
            <tr><th>Homework Date</th><td>{{ $homework->homework_date }}</td></tr>
            <tr><th>Submission Date</th><td>{{ $homework->submit_date }}</td></tr>
            <tr><th>Max Marks</th><td>{{ $homework->marks !== null && $homework->marks !== '' ? $homework->marks : '—' }}</td></tr>
            <tr><th>Description</th><td>{!! nl2br(e((string) $homework->description)) !!}</td></tr>
            <tr>
                <th>Teacher Document</th>
                <td>
                    @if(!empty($homework->document))
                        <a href="{{ route('user.homework.download', $homework->id) }}">Download</a>
                    @else
                        —
                    @endif
                </td>
            </tr>
        </table>

        @if($evaluated && $evaluation)
            <div class="alert alert-info">
                Evaluated
                @if($evaluation->marks !== null && $evaluation->marks !== '')
                    — Marks: {{ $evaluation->marks }}
                @endif
                @if(!empty($evaluation->note))
                    — Note: {{ $evaluation->note }}
                @endif
            </div>
        @endif

        @if($submission)
            <h4>Your Submission</h4>
            <p>{!! nl2br(e((string) $submission->message)) !!}</p>
            @if(!empty($submission->docs))
                <p>
                    <a href="{{ route('user.homework.assignment', $homework->id) }}">
                        {{ $submission->file_name ?: $submission->docs }}
                    </a>
                </p>
            @endif
        @endif

        @if($canSubmit)
            <hr>
            <h4>{{ $submission ? 'Update Submission' : 'Submit Homework' }}</h4>
            <form method="post" action="{{ route('user.homework.submit') }}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="homework_id" value="{{ $homework->id }}">
                <div class="form-group">
                    <label>Message <span class="text-danger">*</span></label>
                    <textarea name="message" class="form-control" rows="5" required>{{ old('message', $submission->message ?? '') }}</textarea>
                </div>
                <div class="form-group">
                    <label>Attach Document</label>
                    <input type="file" name="file" class="form-control">
                    @if($extList !== '')
                        <p class="help-block">Allowed: {{ $extList }} (max {{ (int) ($uploadMeta['max_kb'] ?? 0) }} KB)</p>
                    @endif
                </div>
                <button type="submit" class="btn btn-primary">Submit</button>
            </form>
        @elseif(! $evaluated)
            <div class="alert alert-warning">Submission is locked.</div>
        @endif
    </div>
</div>
