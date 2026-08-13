@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">Evaluation — {{ $exam->exam }}</h3>
        <div class="box-tools pull-right">
            <a href="{{ route('onlineexam.results.index', $exam->id) }}" class="btn btn-default btn-sm">Results</a>
            <a href="{{ route('onlineexam.exams.index') }}" class="btn btn-default btn-sm">Back to Exams</a>
        </div>
    </div>
    <div class="box-body">
        @if($descriptiveQuestions->isEmpty())
            <div class="alert alert-warning">No descriptive questions are attached to this exam.</div>
        @endif

        <form method="get" action="{{ route('onlineexam.evaluation.index', $exam->id) }}" class="row" style="margin-bottom:15px;">
            <div class="col-sm-4">
                <div class="form-group">
                    <label>Descriptive Question</label>
                    <select name="question_id" class="form-control">
                        <option value="">All</option>
                        @foreach($descriptiveQuestions as $q)
                            <option value="{{ $q->question_id }}" @selected((string) ($filters['question_id'] ?? '') === (string) $q->question_id)>
                                Q. {{ \Illuminate\Support\Str::limit(strip_tags((string) $q->question), 60) }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-sm-3">
                <div class="form-group">
                    <label>Class</label>
                    <select id="class_id" name="class_id" class="form-control">
                        <option value="">All</option>
                        @foreach($classes as $class)
                            <option value="{{ $class->id }}" @selected((string) ($filters['class_id'] ?? '') === (string) $class->id)>
                                {{ $class->class }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-sm-3">
                <div class="form-group">
                    <label>Section</label>
                    <select id="section_id" name="section_id" class="form-control">
                        <option value="">All</option>
                    </select>
                </div>
            </div>
            <div class="col-sm-2">
                <div class="form-group">
                    <label>&nbsp;</label>
                    <button type="submit" class="btn btn-primary btn-block">Search</button>
                </div>
            </div>
        </form>

        @if($answers->isEmpty())
            <div class="alert alert-info">No descriptive answers found for this filter. Answers appear after students submit the exam.</div>
        @else
            @foreach($answers as $item)
                <div class="row" style="margin-bottom:20px; border-bottom:1px solid #eee; padding-bottom:15px;">
                    <div class="col-md-8">
                        <div><strong>Result ID:</strong> {{ $item->id }}</div>
                        <div style="margin:8px 0;">{!! nl2br(e($item->question)) !!}
                            <span class="text-danger">(Marks: {{ $item->question_marks }})</span>
                        </div>
                        <div><strong>Answer:</strong></div>
                        <div style="margin-bottom:8px;">{!! nl2br(e((string) $item->select_option)) !!}</div>
                        @if($item->attachment_upload_name)
                            <div style="margin-bottom:8px;">
                                <strong>Attachment:</strong>
                                <a href="{{ route('onlineexam.results.attachment', $item->attachment_upload_name) }}">
                                    {{ $item->attachment_name ?: $item->attachment_upload_name }}
                                </a>
                            </div>
                        @endif
                        @if($canGrade)
                            <form method="post" action="{{ route('onlineexam.evaluation.fillmarks', $exam->id) }}">
                                @csrf
                                <input type="hidden" name="onlineexam_student_result_id" value="{{ $item->id }}">
                                <input type="hidden" name="question_marks" value="{{ $item->question_marks }}">
                                <div class="form-group">
                                    <label>Your Marks</label>
                                    <input type="number" step="0.01" min="0" max="{{ $item->question_marks }}" name="fill_mark"
                                           class="form-control" style="max-width:160px;" value="{{ $item->marks }}" required>
                                </div>
                                <div class="form-group">
                                    <label>Remark</label>
                                    <textarea name="remark" class="form-control" rows="2">{{ $item->remark }}</textarea>
                                </div>
                                <button type="submit" class="btn btn-info btn-sm">Save</button>
                            </form>
                        @endif
                    </div>
                    <div class="col-md-4">
                        <ul class="list-unstyled">
                            <li><strong>Name:</strong> {{ trim($item->firstname.' '.($item->middlename ?? '').' '.($item->lastname ?? '')) }}</li>
                            <li><strong>Class:</strong> {{ $item->class }} ({{ $item->section }})</li>
                            <li><strong>Admission No:</strong> {{ $item->admission_no }}</li>
                            <li><strong>Mobile:</strong> {{ $item->mobileno }}</li>
                            <li><strong>Guardian:</strong> {{ $item->guardian_name }}</li>
                            <li><strong>Guardian Phone:</strong> {{ $item->guardian_phone }}</li>
                        </ul>
                    </div>
                </div>
            @endforeach
            <div class="text-center">{{ $answers->links() }}</div>
        @endif
    </div>
</div>

@push('scripts')
<script>
(function () {
    var oldSection = @json((string) ($filters['section_id'] ?? ''));
    function loadSections(classId, selected) {
        var $section = $('#section_id');
        $section.html('<option value="">All</option>');
        if (!classId) return;
        $.getJSON('{{ url('sections/getByClass') }}', {class_id: classId}, function (data) {
            $.each(data || [], function (i, row) {
                var opt = $('<option>', {value: row.section_id, text: row.section});
                if (String(selected) === String(row.section_id)) opt.prop('selected', true);
                $section.append(opt);
            });
        });
    }
    $('#class_id').on('change', function () { loadSections($(this).val(), ''); });
    loadSections($('#class_id').val(), oldSection);
})();
</script>
@endpush
