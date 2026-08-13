@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@php
    $isEdit = $editing !== null;
    $formAction = $isEdit
        ? route('exams.exam_subjects.update', [$exam->id, $editing->id])
        : route('exams.exam_subjects.store', $exam->id);
@endphp

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">Exam Subjects</h3>
        <div class="box-tools">
            <a href="{{ route('exams.exam_group_exams.index', $group->id) }}" class="btn btn-default btn-sm">Back to Exams</a>
        </div>
    </div>
    <div class="box-body">
        <div class="row" style="margin-bottom:15px;">
            <div class="col-sm-3"><strong>Exam:</strong> {{ $exam->exam }}</div>
            <div class="col-sm-3"><strong>Exam Group:</strong> {{ $group->name }}</div>
            <div class="col-sm-3"><strong>Exam Type:</strong> {{ $examTypes[$group->exam_type] ?? $group->exam_type }}</div>
        </div>

        @if($canShowForm)
            <div class="box box-default">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ $isEdit ? 'Edit Subject' : 'Add Exam Subject' }}</h3>
                </div>
                <form method="post" action="{{ $formAction }}">
                    @csrf
                    <div class="box-body">
                        <div class="row">
                            <div class="col-sm-3">
                                <div class="form-group">
                                    <label>Subject</label> <small class="req">*</small>
                                    <select name="subject_id" class="form-control" required>
                                        <option value="">Select</option>
                                        @foreach($availableSubjects as $subject)
                                            @php
                                                $label = $subject->name.($subject->code ? ' ('.$subject->code.')' : '');
                                            @endphp
                                            <option value="{{ $subject->id }}"
                                                @selected((string) old('subject_id', $editing->subject_id ?? '') === (string) $subject->id)>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-sm-2">
                                <div class="form-group">
                                    <label>Date</label> <small class="req">*</small>
                                    <input type="date" name="date_from" class="form-control" required
                                           value="{{ old('date_from', $editing ? substr((string) $editing->date_from, 0, 10) : '') }}">
                                </div>
                            </div>
                            <div class="col-sm-2">
                                <div class="form-group">
                                    <label>Start Time</label> <small class="req">*</small>
                                    @php
                                        $timeVal = old('time_from', $editing->time_from ?? '');
                                        if ($timeVal !== '' && strlen((string) $timeVal) >= 5) {
                                            $timeVal = substr((string) $timeVal, 0, 5);
                                        }
                                    @endphp
                                    <input type="time" name="time_from" class="form-control" required value="{{ $timeVal }}">
                                </div>
                            </div>
                            <div class="col-sm-2">
                                <div class="form-group">
                                    <label>Duration</label> <small class="req">*</small>
                                    <input type="text" name="duration" class="form-control" required
                                           placeholder="e.g. 02:00"
                                           value="{{ old('duration', $editing->duration ?? '') }}">
                                </div>
                            </div>
                            <div class="col-sm-1">
                                <div class="form-group">
                                    <label>Credit Hrs</label> <small class="req">*</small>
                                    <input type="number" step="0.01" min="0" name="credit_hours" class="form-control" required
                                           value="{{ old('credit_hours', $editing->credit_hours ?? '0') }}">
                                </div>
                            </div>
                            <div class="col-sm-2">
                                <div class="form-group">
                                    <label>Room No</label> <small class="req">*</small>
                                    <input type="text" name="room_no" class="form-control" required
                                           value="{{ old('room_no', $editing->room_no ?? '') }}">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-2">
                                <div class="form-group">
                                    <label>Max Marks</label> <small class="req">*</small>
                                    <input type="number" step="0.01" min="0.01" name="max_marks" class="form-control" required
                                           value="{{ old('max_marks', $editing->max_marks ?? '') }}">
                                </div>
                            </div>
                            <div class="col-sm-2">
                                <div class="form-group">
                                    <label>Min Marks</label> <small class="req">*</small>
                                    <input type="number" step="0.01" min="0.01" name="min_marks" class="form-control" required
                                           value="{{ old('min_marks', $editing->min_marks ?? '') }}">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="box-footer">
                        @if($isEdit)
                            <a href="{{ route('exams.exam_subjects.index', $exam->id) }}" class="btn btn-default">Cancel</a>
                        @endif
                        <button type="submit" class="btn btn-info pull-right">Save</button>
                    </div>
                </form>
            </div>
        @endif

        <div class="table-responsive">
            <table class="table table-striped table-bordered">
                <thead>
                <tr>
                    <th>Subject</th>
                    <th>Date</th>
                    <th>Start Time</th>
                    <th>Duration</th>
                    <th>Credit Hours</th>
                    <th>Room No</th>
                    <th>Max Marks</th>
                    <th>Min Marks</th>
                    <th class="text-right">Action</th>
                </tr>
                </thead>
                <tbody>
                @forelse($subjects as $row)
                    <tr>
                        <td>
                            {{ $row->subject_name }}
                            @if($row->subject_code)
                                ({{ $row->subject_code }})
                            @endif
                        </td>
                        <td>{{ $row->date_from }}</td>
                        <td>{{ $row->time_from }}</td>
                        <td>{{ $row->duration }}</td>
                        <td>{{ $row->credit_hours }}</td>
                        <td>{{ $row->room_no }}</td>
                        <td>{{ $row->max_marks }}</td>
                        <td>{{ $row->min_marks }}</td>
                        <td class="text-right">
                            @can('privilege', ['exam_subject', 'can_edit'])
                                <a href="{{ route('exams.exam_subjects.edit', [$exam->id, $row->id]) }}" class="btn btn-primary btn-xs">Edit</a>
                            @endcan
                            @can('privilege', ['exam_subject', 'can_delete'])
                                <a href="{{ route('exams.exam_subjects.destroy', [$exam->id, $row->id]) }}" class="btn btn-primary btn-xs"
                                   onclick="return confirm('Delete this exam subject?');">Delete</a>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center text-danger">No Record Found</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
