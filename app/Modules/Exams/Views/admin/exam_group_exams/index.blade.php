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
        ? route('exams.exam_group_exams.update', [$group->id, $editing->id])
        : route('exams.exam_group_exams.store', $group->id);
@endphp

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">Exam List</h3>
        <div class="box-tools">
            <a href="{{ route('exams.exam_groups.index') }}" class="btn btn-default btn-sm">Exam Groups</a>
        </div>
    </div>
    <div class="box-body">
        <div class="row" style="margin-bottom:15px;">
            <div class="col-sm-3"><strong>Exam Group:</strong> {{ $group->name }}</div>
            <div class="col-sm-3"><strong>Exam Type:</strong> {{ $examTypes[$group->exam_type] ?? $group->exam_type }}</div>
            <div class="col-sm-6"><strong>Description:</strong> {{ $group->description }}</div>
        </div>

        @if($canShowExamForm)
            <div class="box box-default">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ $isEdit ? 'Edit Exam' : 'New Exam' }}</h3>
                </div>
                <form method="post" action="{{ $formAction }}">
                    @csrf
                    <div class="box-body">
                        <div class="row">
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label>Exam</label> <small class="req">*</small>
                                    <input type="text" name="exam" class="form-control"
                                           value="{{ old('exam', $editing->exam ?? '') }}" required>
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="form-group">
                                    <label>Session</label> <small class="req">*</small>
                                    <select name="session_id" class="form-control" required>
                                        <option value="">Select</option>
                                        @foreach($sessions as $session)
                                            <option value="{{ $session->id }}"
                                                @selected((string) old('session_id', $editing->session_id ?? $currentSessionId) === (string) $session->id)>
                                                {{ $session->session }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            @if($group->exam_type === 'average_passing')
                                <div class="col-sm-3">
                                    <div class="form-group">
                                        <label>Passing %</label> <small class="req">*</small>
                                        <input type="number" step="0.01" min="0" max="100" name="passing_percentage"
                                               class="form-control"
                                               value="{{ old('passing_percentage', $editing->passing_percentage ?? '') }}" required>
                                    </div>
                                </div>
                            @endif
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description" class="form-control" rows="2">{{ old('description', $editing->description ?? '') }}</textarea>
                        </div>
                        <div class="form-group">
                            <label class="checkbox-inline">
                                <input type="checkbox" name="use_exam_roll_no" value="1"
                                    @checked((string) old('use_exam_roll_no', $editing->use_exam_roll_no ?? 0) === '1')> Use Exam Roll No
                            </label>
                            <label class="checkbox-inline" style="margin-left:15px;">
                                <input type="checkbox" name="is_publish" value="1"
                                    @checked((string) old('is_publish', $editing->is_publish ?? 0) === '1')> Publish Exam
                            </label>
                            <label class="checkbox-inline" style="margin-left:15px;">
                                <input type="checkbox" name="is_active" value="1"
                                    @checked((string) old('is_active', $editing->is_active ?? 0) === '1')> Publish Result
                            </label>
                        </div>
                    </div>
                    <div class="box-footer">
                        @if($isEdit)
                            <a href="{{ route('exams.exam_group_exams.index', $group->id) }}" class="btn btn-default">Cancel</a>
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
                    <th>Name</th>
                    <th>Session</th>
                    <th>Subjects Included</th>
                    <th class="text-center">Publish Exam</th>
                    <th class="text-center">Publish Result</th>
                    <th>Description</th>
                    <th class="text-right">Action</th>
                </tr>
                </thead>
                <tbody>
                @forelse($exams as $row)
                    <tr>
                        <td>{{ $row->exam }}</td>
                        <td>{{ $row->session }}</td>
                        <td>{{ $row->total_subjects }}</td>
                        <td class="text-center">{{ (int) $row->is_publish === 1 ? 'Yes' : 'No' }}</td>
                        <td class="text-center">{{ (int) $row->is_active === 1 ? 'Yes' : 'No' }}</td>
                        <td>{{ $row->description }}</td>
                        <td class="text-right">
                            @can('privilege', ['exam_subject', 'can_view'])
                                <a href="{{ route('exams.exam_subjects.index', $row->id) }}" class="btn btn-primary btn-xs">Subjects</a>
                            @endcan
                            @can('privilege', ['exam', 'can_edit'])
                                <a href="{{ route('exams.exam_group_exams.edit', [$group->id, $row->id]) }}" class="btn btn-primary btn-xs">Edit</a>
                            @endcan
                            @can('privilege', ['exam', 'can_delete'])
                                <a href="{{ route('exams.exam_group_exams.destroy', [$group->id, $row->id]) }}" class="btn btn-primary btn-xs"
                                   onclick="return confirm('Delete this exam?');">Delete</a>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-danger">No Record Found</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
