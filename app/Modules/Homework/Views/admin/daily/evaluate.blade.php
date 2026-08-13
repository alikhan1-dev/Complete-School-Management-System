@php
    $name = trim(preg_replace('/\s+/', ' ', ($row->firstname ?? '').' '.($row->middlename ?? '').' '.($row->lastname ?? '')) ?? '');
@endphp

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
        <h3 class="box-title">Evaluate Daily Assignment</h3>
        <div class="box-tools pull-right">
            <a href="{{ route('homework.daily.index') }}" class="btn btn-default btn-sm">Back</a>
        </div>
    </div>
    <div class="box-body">
        <table class="table table-bordered">
            <tr><th width="30%">Student</th><td>{{ $name }} ({{ $row->admission_no }})</td></tr>
            <tr><th>Class / Section</th><td>{{ $row->class }} / {{ $row->section }}</td></tr>
            <tr><th>Subject</th><td>{{ $row->subject_name }}@if(!empty($row->subject_code)) ({{ $row->subject_code }})@endif</td></tr>
            <tr><th>Title</th><td>{{ $row->title }}</td></tr>
            <tr><th>Date</th><td>{{ $row->date }}</td></tr>
            <tr><th>Description</th><td>{!! nl2br(e((string) $row->description)) !!}</td></tr>
            <tr>
                <th>Attachment</th>
                <td>
                    @if(!empty($row->attachment))
                        <a href="{{ route('homework.daily.download', $row->id) }}">Download</a>
                    @else
                        —
                    @endif
                </td>
            </tr>
        </table>

        <form method="post" action="{{ route('homework.daily.remark') }}">
            @csrf
            <input type="hidden" name="assigment_id" value="{{ $row->id }}">
            <div class="form-group" style="max-width:240px;">
                <label>Evaluation Date <span class="text-danger">*</span></label>
                <input type="date" name="evaluation_date" class="form-control" required
                       value="{{ old('evaluation_date', $row->evaluation_date ?: now()->format('Y-m-d')) }}">
            </div>
            <div class="form-group">
                <label>Remark</label>
                <textarea name="remark" class="form-control" rows="4">{{ old('remark', $row->remark) }}</textarea>
            </div>
            <button type="submit" class="btn btn-primary">Save Evaluation</button>
        </form>
    </div>
</div>
