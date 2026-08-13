@php
    $hw = $homework;
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

<div class="row">
    <div class="col-md-4">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">Homework Summary</h3>
            </div>
            <div class="box-body">
                <table class="table table-bordered">
                    <tr><th>Homework Date</th><td>{{ $hw->homework_date }}</td></tr>
                    <tr><th>Submission Date</th><td>{{ $hw->submit_date }}</td></tr>
                    <tr><th>Evaluation Date</th><td>{{ $hw->evaluation_date ?: '—' }}</td></tr>
                    <tr><th>Subject</th><td>{{ $hw->subject_name }}@if(!empty($hw->subject_code)) ({{ $hw->subject_code }})@endif</td></tr>
                    <tr><th>Max Marks</th><td>{{ $hasMaxMarks ? $maxMarks : '—' }}</td></tr>
                    <tr><th>Description</th><td>{!! nl2br(e((string) $hw->description)) !!}</td></tr>
                </table>
                @if(!empty($hw->document))
                    <a href="{{ route('homework.download', $hw->id) }}" class="btn btn-default btn-sm">
                        <i class="fa fa-download"></i> Teacher Document
                    </a>
                @endif
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="box box-info">
            <div class="box-header with-border">
                <h3 class="box-title">Evaluate Students</h3>
                <div class="box-tools pull-right">
                    <a href="{{ route('homework.index', ['class_id' => $hw->class_id, 'section_id' => $hw->section_id]) }}"
                       class="btn btn-default btn-sm">Back</a>
                </div>
            </div>
            <form method="post" action="{{ route('homework.evaluation.store') }}">
                @csrf
                <input type="hidden" name="homework_id" value="{{ $hw->id }}">
                <div class="box-body">
                    <div class="form-group" style="max-width:240px;">
                        <label>Evaluation Date <span class="text-danger">*</span></label>
                        <input type="date" name="evaluation_date" class="form-control" required
                               value="{{ old('evaluation_date', $hw->evaluation_date ?: now()->format('Y-m-d')) }}">
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead>
                            <tr>
                                <th style="width:40px;">
                                    <input type="checkbox" id="hw_eval_check_all" title="Select all">
                                </th>
                                <th>Admission No</th>
                                <th>Student</th>
                                <th>Submission</th>
                                @if($hasMaxMarks)
                                    <th>Marks</th>
                                @endif
                                <th>Note</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($students as $student)
                                @php
                                    $ssId = (int) $student->student_session_id;
                                    $evalId = (int) $student->homework_evaluation_id;
                                    $checked = old('student_list.'.$ssId) !== null || $evalId > 0;
                                    $name = trim(preg_replace('/\s+/', ' ', ($student->firstname ?? '').' '.($student->middlename ?? '').' '.($student->lastname ?? '')) ?? '');
                                @endphp
                                <tr>
                                    <td>
                                        <input type="checkbox"
                                               class="hw-eval-student"
                                               name="student_list[{{ $ssId }}]"
                                               value="{{ $evalId }}"
                                               @checked($checked)>
                                        <input type="hidden" name="student_id[{{ $ssId }}]" value="{{ $student->student_id }}">
                                    </td>
                                    <td>{{ $student->admission_no }}</td>
                                    <td>{{ $name }}</td>
                                    <td>
                                        @forelse($student->assignments as $assignment)
                                            @if(!empty($assignment->message))
                                                <div>{{ $assignment->message }}</div>
                                            @endif
                                            @if(!empty($assignment->docs))
                                                <a href="{{ route('homework.assignment.download', $assignment->id) }}">
                                                    {{ $assignment->file_name ?: $assignment->docs }}
                                                </a>
                                            @endif
                                        @empty
                                            <span class="text-muted">No submission</span>
                                        @endforelse
                                    </td>
                                    @if($hasMaxMarks)
                                        <td style="width:100px;">
                                            <input type="number"
                                                   step="0.01"
                                                   min="0"
                                                   max="{{ $maxMarks }}"
                                                   name="marks[{{ $ssId }}]"
                                                   class="form-control input-sm"
                                                   value="{{ old('marks.'.$ssId, $student->evaluation_marks) }}">
                                        </td>
                                    @endif
                                    <td>
                                        <input type="text"
                                               name="note[{{ $ssId }}]"
                                               class="form-control input-sm"
                                               maxlength="255"
                                               value="{{ old('note.'.$ssId, $student->note) }}">
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $hasMaxMarks ? 6 : 5 }}" class="text-center">
                                        No active students found for this class/section.
                                    </td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                    <p class="help-block">
                        Checked students are marked Complete. Unchecked previously evaluated students will be removed.
                    </p>
                </div>
                @if(!empty($canSave))
                    <div class="box-footer">
                        <button type="submit" class="btn btn-primary">Save Evaluation</button>
                    </div>
                @endif
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    $('#hw_eval_check_all').on('change', function () {
        $('.hw-eval-student').prop('checked', $(this).is(':checked'));
    });
})();
</script>
@endpush
