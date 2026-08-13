@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">
            Add Questions — {{ $exam->exam }}
            @if((int) $exam->is_quiz === 1)
                <span class="label label-info">Quiz</span>
            @endif
        </h3>
        <div class="box-tools pull-right">
            <a href="{{ route('onlineexam.exams.index') }}" class="btn btn-default btn-sm">Back to Exams</a>
        </div>
    </div>
    <div class="box-body">
        @if((int) $exam->is_quiz === 1)
            <div class="alert alert-info">Quiz exams cannot include descriptive questions.</div>
        @endif

        <h4>Attached Questions ({{ $attached->count() }})</h4>
        <div class="table-responsive">
            <table class="table table-striped table-bordered">
                <thead>
                <tr>
                    <th>Subject</th>
                    <th>Question</th>
                    <th>Type</th>
                    <th>Level</th>
                    <th style="width:110px;">Marks</th>
                    <th style="width:110px;">Neg Marks</th>
                    <th class="text-right">Action</th>
                </tr>
                </thead>
                <tbody>
                @forelse($attached as $row)
                    <tr>
                        <td>{{ $row->subject_name }}@if($row->subject_code) ({{ $row->subject_code }})@endif</td>
                        <td>{{ \Illuminate\Support\Str::limit(strip_tags((string) $row->question), 90) }}</td>
                        <td>{{ $questionTypes[$row->question_type] ?? $row->question_type }}</td>
                        <td>{{ $questionLevels[$row->level] ?? $row->level }}</td>
                        @if($canManage)
                            <td colspan="3">
                                <form method="post" action="{{ route('onlineexam.exam_questions.update_marks', [$exam->id, $row->onlineexam_question_id]) }}" class="form-inline">
                                    @csrf
                                    <input type="number" step="0.01" min="0" name="marks" class="form-control input-sm" style="width:90px;"
                                           value="{{ old('marks', $row->marks) }}" required>
                                    <input type="number" step="0.01" min="0" name="neg_marks" class="form-control input-sm" style="width:90px;"
                                           value="{{ old('neg_marks', $row->neg_marks) }}" required>
                                    <button type="submit" class="btn btn-default btn-xs">Update</button>
                                    <a href="{{ route('onlineexam.exam_questions.detach', [$exam->id, $row->onlineexam_question_id]) }}"
                                       class="btn btn-primary btn-xs"
                                       onclick="return confirm('Remove this question from the exam?');">Remove</a>
                                </form>
                            </td>
                        @else
                            <td>{{ $row->marks }}</td>
                            <td>{{ $row->neg_marks }}</td>
                            <td></td>
                        @endif
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-danger">No questions attached yet</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <hr>

        <h4>Question Bank</h4>
        <form method="get" action="{{ route('onlineexam.exam_questions.index', $exam->id) }}" class="row" style="margin-bottom:15px;">
            <div class="col-sm-3">
                <select name="subject_id" class="form-control">
                    <option value="">All Subjects</option>
                    @foreach($subjects as $subject)
                        <option value="{{ $subject->id }}" @selected((string) ($filters['subject_id'] ?? '') === (string) $subject->id)>
                            {{ $subject->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-2">
                <select name="question_type" class="form-control">
                    <option value="">All Types</option>
                    @foreach($questionTypes as $key => $label)
                        @if((int) $exam->is_quiz === 1 && $key === 'descriptive')
                            @continue
                        @endif
                        <option value="{{ $key }}" @selected(($filters['question_type'] ?? '') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-2">
                <select name="question_level" class="form-control">
                    <option value="">All Levels</option>
                    @foreach($questionLevels as $key => $label)
                        <option value="{{ $key }}" @selected(($filters['question_level'] ?? '') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-2">
                <select name="class_id" class="form-control">
                    <option value="">All Classes</option>
                    @foreach($classes as $class)
                        <option value="{{ $class->id }}" @selected((string) ($filters['class_id'] ?? '') === (string) $class->id)>
                            {{ $class->class }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-2">
                <input type="text" name="keyword" class="form-control" placeholder="Keyword"
                       value="{{ $filters['keyword'] ?? '' }}">
            </div>
            <div class="col-sm-1">
                <button type="submit" class="btn btn-primary btn-block">Filter</button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-striped table-bordered">
                <thead>
                <tr>
                    <th>Subject</th>
                    <th>Question</th>
                    <th>Type</th>
                    <th>Level</th>
                    <th>Class</th>
                    @if($canManage)
                        <th style="width:100px;">Marks</th>
                        <th style="width:100px;">Neg</th>
                        <th class="text-right">Action</th>
                    @endif
                </tr>
                </thead>
                <tbody>
                @forelse($available as $row)
                    <tr>
                        <td>{{ $row->subject_name }}@if($row->subject_code) ({{ $row->subject_code }})@endif</td>
                        <td>{{ \Illuminate\Support\Str::limit(strip_tags((string) $row->question), 90) }}</td>
                        <td>{{ $questionTypes[$row->question_type] ?? $row->question_type }}</td>
                        <td>{{ $questionLevels[$row->level] ?? $row->level }}</td>
                        <td>{{ $row->class_name }}</td>
                        @if($canManage)
                            <td colspan="3">
                                <form method="post" action="{{ route('onlineexam.exam_questions.attach', $exam->id) }}" class="form-inline">
                                    @csrf
                                    <input type="hidden" name="question_id" value="{{ $row->id }}">
                                    <input type="number" step="0.01" min="0" name="marks" class="form-control input-sm" style="width:80px;" value="1" required>
                                    <input type="number" step="0.01" min="0" name="neg_marks" class="form-control input-sm" style="width:80px;" value="0.25" required>
                                    <button type="submit" class="btn btn-info btn-xs">Attach</button>
                                </form>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $canManage ? 8 : 5 }}" class="text-center text-danger">
                            No available questions found
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="text-center">{{ $available->links() }}</div>
    </div>
</div>
