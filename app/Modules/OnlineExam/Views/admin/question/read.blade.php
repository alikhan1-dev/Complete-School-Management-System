@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">Question Detail</h3>
        <div class="box-tools pull-right">
            <a href="{{ route('onlineexam.questions.index') }}" class="btn btn-default btn-sm">Back</a>
            @can('privilege', ['question_bank', 'can_edit'])
                <a href="{{ route('onlineexam.questions.edit', $question->id) }}" class="btn btn-primary btn-sm">Edit</a>
            @endcan
        </div>
    </div>
    <div class="box-body">
        <table class="table table-bordered">
            <tr>
                <th width="20%">Subject</th>
                <td>{{ $question->subject_name }}@if($question->subject_code) ({{ $question->subject_code }})@endif</td>
            </tr>
            <tr>
                <th>Type</th>
                <td>{{ $questionTypes[$question->question_type] ?? $question->question_type }}</td>
            </tr>
            <tr>
                <th>Level</th>
                <td>{{ $questionLevels[$question->level] ?? $question->level }}</td>
            </tr>
            <tr>
                <th>Class / Section</th>
                <td>
                    {{ $question->class_name }}
                    @if($question->section_name) / {{ $question->section_name }}@endif
                </td>
            </tr>
            <tr>
                <th>Question</th>
                <td>{!! nl2br(e($question->question)) !!}</td>
            </tr>
            @if(in_array($question->question_type, ['singlechoice', 'multichoice'], true))
                @foreach($optionKeys as $optKey => $optLabel)
                    @if(!empty($question->{$optKey}))
                        <tr>
                            <th>Option {{ $optLabel }}</th>
                            <td>{!! nl2br(e($question->{$optKey})) !!}</td>
                        </tr>
                    @endif
                @endforeach
            @endif
            <tr>
                <th>Answer</th>
                <td>
                    @if($question->question_type === 'multichoice')
                        {{ implode(', ', array_map(fn ($k) => $optionKeys[$k] ?? $k, $selectedAnswers)) }}
                    @elseif($question->question_type === 'singlechoice')
                        {{ $optionKeys[$question->correct] ?? $question->correct }}
                    @elseif($question->question_type === 'true_false')
                        {{ ucfirst((string) $question->correct) }}
                    @else
                        —
                    @endif
                </td>
            </tr>
        </table>
    </div>
</div>
