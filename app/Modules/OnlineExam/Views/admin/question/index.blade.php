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

@php
    $isEdit = $editing !== null;
    $formAction = $isEdit
        ? route('onlineexam.questions.update', $editing->id)
        : route('onlineexam.questions.store');
    $selectedAnswers = $selectedAnswers ?? [];
    $currentType = old('question_type', $editing->question_type ?? '');
@endphp

<div class="row">
    <div class="col-md-5">
        @if($canAdd || $isEdit)
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ $isEdit ? 'Edit Question' : 'Add Question' }}</h3>
                </div>
                <form method="post" action="{{ $formAction }}" id="question_bank_form">
                    @csrf
                    <div class="box-body">
                        <div class="form-group">
                            <label>Subject</label> <small class="req">*</small>
                            <select name="subject_id" class="form-control" required>
                                <option value="">Select</option>
                                @foreach($formData['subjects'] as $subject)
                                    <option value="{{ $subject->id }}"
                                        @selected((string) old('subject_id', $editing->subject_id ?? '') === (string) $subject->id)>
                                        {{ $subject->name }}@if($subject->code) ({{ $subject->code }})@endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label>Question Type</label> <small class="req">*</small>
                                    <select name="question_type" id="question_type" class="form-control" required>
                                        <option value="">Select</option>
                                        @foreach($formData['questionTypes'] as $key => $label)
                                            <option value="{{ $key }}" @selected($currentType === $key)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label>Level</label> <small class="req">*</small>
                                    <select name="question_level" class="form-control" required>
                                        <option value="">Select</option>
                                        @foreach($formData['questionLevels'] as $key => $label)
                                            <option value="{{ $key }}"
                                                @selected(old('question_level', $editing->level ?? '') === $key)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label>Class</label> <small class="req">*</small>
                                    <select name="class_id" id="class_id" class="form-control" required>
                                        <option value="">Select</option>
                                        @foreach($formData['classes'] as $class)
                                            <option value="{{ $class->id }}"
                                                @selected((string) old('class_id', $editing->class_id ?? '') === (string) $class->id)>
                                                {{ $class->class }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label>Section</label>
                                    <select name="section_id" id="section_id" class="form-control">
                                        <option value="">Select</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Question</label> <small class="req">*</small>
                            <textarea name="question" class="form-control" rows="3" required>{{ old('question', $editing->question ?? '') }}</textarea>
                        </div>

                        <div id="options_block" class="option-type-block" style="display:none;">
                            @foreach($formData['optionKeys'] as $optKey => $optLabel)
                                <div class="form-group">
                                    <label>Option {{ $optLabel }}@if(in_array($optKey, ['opt_a', 'opt_b'], true)) <small class="req">*</small>@endif</label>
                                    <textarea name="{{ $optKey }}" class="form-control" rows="2">{{ old($optKey, $editing->{$optKey} ?? '') }}</textarea>
                                </div>
                            @endforeach
                        </div>

                        <div id="answer_single" class="option-type-block" style="display:none;">
                            <div class="form-group">
                                <label>Correct Answer</label> <small class="req">*</small>
                                <select name="correct" class="form-control">
                                    <option value="">Select</option>
                                    @foreach($formData['optionKeys'] as $optKey => $optLabel)
                                        <option value="{{ $optKey }}"
                                            @selected(old('correct', ($editing && $editing->question_type === 'singlechoice') ? $editing->correct : '') === $optKey)>
                                            {{ $optLabel }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div id="answer_true_false" class="option-type-block" style="display:none;">
                            <div class="form-group">
                                <label>Correct Answer</label> <small class="req">*</small>
                                <select name="correct_true_false" class="form-control">
                                    <option value="">Select</option>
                                    @foreach($formData['trueFalseOptions'] as $key => $label)
                                        <option value="{{ $key }}"
                                            @selected(old('correct_true_false', ($editing && $editing->question_type === 'true_false') ? $editing->correct : '') === $key)>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div id="answer_multi" class="option-type-block" style="display:none;">
                            <div class="form-group">
                                <label>Correct Answers</label> <small class="req">*</small>
                                <div>
                                    @foreach($formData['optionKeys'] as $optKey => $optLabel)
                                        <label class="checkbox-inline">
                                            <input type="checkbox" name="ans[]" value="{{ $optKey }}"
                                                @checked(in_array($optKey, old('ans', $selectedAnswers), true))>
                                            {{ $optLabel }}
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="box-footer">
                        @if($isEdit)
                            <a href="{{ route('onlineexam.questions.index') }}" class="btn btn-default">Cancel</a>
                        @endif
                        <button type="submit" class="btn btn-info pull-right">Save</button>
                    </div>
                </form>
            </div>
        @endif
    </div>

    <div class="col-md-7">
        <div class="box box-primary">
            <div class="box-header with-border"><h3 class="box-title">Question List</h3></div>
            <div class="box-body">
                <form method="get" action="{{ route('onlineexam.questions.index') }}" class="form-inline" style="margin-bottom: 15px;">
                    <div class="form-group">
                        <label for="created_by">Created By</label>
                        <select name="created_by" id="created_by" class="form-control" style="min-width: 220px;">
                            <option value="">All</option>
                            @foreach($formData['creators'] as $creator)
                                <option value="{{ $creator->id }}"
                                    @selected((string) ($filters['created_by'] ?? '') === (string) $creator->id)>
                                    {{ trim($creator->name.' '.$creator->surname) }} ({{ $creator->employee_id }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary" style="margin-left: 8px;">Filter</button>
                    @if(($filters['created_by'] ?? '') !== '')
                        <a href="{{ route('onlineexam.questions.index') }}" class="btn btn-default" style="margin-left: 4px;">Reset</a>
                    @endif
                </form>
            </div>
            <div class="box-body table-responsive" style="padding-top: 0;">
                <table class="table table-striped table-bordered">
                    <thead>
                    <tr>
                        <th>Subject</th>
                        <th>Question</th>
                        <th>Type</th>
                        <th>Level</th>
                        <th>Class / Section</th>
                        <th>Created By</th>
                        <th class="text-right">Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($questions as $row)
                        <tr>
                            <td>{{ $row->subject_name }}@if($row->subject_code) ({{ $row->subject_code }})@endif</td>
                            <td>{{ \Illuminate\Support\Str::limit(strip_tags((string) $row->question), 80) }}</td>
                            <td>{{ $formData['questionTypes'][$row->question_type] ?? $row->question_type }}</td>
                            <td>{{ $formData['questionLevels'][$row->level] ?? $row->level }}</td>
                            <td>
                                {{ $row->class_name }}
                                @if($row->section_name) / {{ $row->section_name }}@endif
                            </td>
                            <td>{{ $row->creator_label ?? '' }}</td>
                            <td class="text-right">
                                @can('privilege', ['question_bank', 'can_view'])
                                    <a href="{{ route('onlineexam.questions.read', $row->id) }}" class="btn btn-default btn-xs">View</a>
                                @endcan
                                @can('privilege', ['question_bank', 'can_edit'])
                                    <a href="{{ route('onlineexam.questions.edit', $row->id) }}" class="btn btn-primary btn-xs">Edit</a>
                                @endcan
                                @can('privilege', ['question_bank', 'can_delete'])
                                    <a href="{{ route('onlineexam.questions.destroy', $row->id) }}" class="btn btn-primary btn-xs"
                                       onclick="return confirm('Delete this question?');">Delete</a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-danger">No Record Found</td></tr>
                    @endforelse
                    </tbody>
                </table>
                <div class="text-center">{{ $questions->links() }}</div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    var typeSelect = document.getElementById('question_type');
    var classSelect = document.getElementById('class_id');
    var sectionSelect = document.getElementById('section_id');
    var oldSection = @json((string) old('section_id', $editing->section_id ?? ''));

    function toggleTypeBlocks() {
        var type = typeSelect ? typeSelect.value : '';
        document.querySelectorAll('.option-type-block').forEach(function (el) {
            el.style.display = 'none';
        });
        if (type === 'singlechoice') {
            document.getElementById('options_block').style.display = 'block';
            document.getElementById('answer_single').style.display = 'block';
        } else if (type === 'multichoice') {
            document.getElementById('options_block').style.display = 'block';
            document.getElementById('answer_multi').style.display = 'block';
        } else if (type === 'true_false') {
            document.getElementById('answer_true_false').style.display = 'block';
        }
    }

    function loadSections(classId, selected) {
        sectionSelect.innerHTML = '<option value="">Select</option>';
        if (!classId) return;
        $.getJSON('{{ url('sections/getByClass') }}', {class_id: classId}, function (data) {
            (data || []).forEach(function (row) {
                var opt = document.createElement('option');
                opt.value = row.section_id;
                opt.textContent = row.section;
                if (String(selected) === String(row.section_id)) opt.selected = true;
                sectionSelect.appendChild(opt);
            });
        });
    }

    if (typeSelect) {
        typeSelect.addEventListener('change', toggleTypeBlocks);
        toggleTypeBlocks();
    }
    if (classSelect) {
        classSelect.addEventListener('change', function () {
            loadSections(this.value, '');
        });
        loadSections(classSelect.value, oldSection);
    }
})();
</script>
@endpush
