@include('onlineexam::admin.reports.hub')

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-search"></i> {{ __('system.select_criteria') }}</h3>
    </div>
    <form action="{{ url('report/onlineexamrank') }}" method="post">
        @csrf
        <div class="box-body">
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>{{ __('system.exam') }} <small class="req">*</small></label>
                        <select class="form-control" name="exam_id" id="exam_id">
                            <option value="">{{ __('system.select') }}</option>
                            @foreach($examList as $examOption)
                                <option value="{{ $examOption->id }}" @selected((string) $filters['exam_id'] === (string) $examOption->id)>
                                    {{ $examOption->exam }}
                                </option>
                            @endforeach
                        </select>
                        @if(!empty($errors['exam_id']))
                            <span class="text-danger">{{ $errors['exam_id'] }}</span>
                        @endif
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>{{ __('system.class') }}</label>
                        <select class="form-control" name="class_id" id="class_id">
                            <option value="">{{ __('system.select') }}</option>
                            @foreach($classlist as $class)
                                <option value="{{ $class->id }}" @selected((string) $filters['class_id'] === (string) $class->id)>
                                    {{ $class->class }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>{{ __('system.section') }}</label>
                        <select class="form-control" name="section_id" id="section_id">
                            <option value="">{{ __('system.select') }}</option>
                            @foreach($sectionOptions as $section)
                                <option value="{{ $section->section_id }}" @selected((string) $filters['section_id'] === (string) $section->section_id)>
                                    {{ $section->section }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class="box-footer">
            <button type="submit" name="action" value="search" class="btn btn-primary btn-sm pull-right">
                <i class="fa fa-search"></i> {{ __('system.search') }}
            </button>
        </div>
    </form>
</div>

@if($searched && empty($errors))
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title"><i class="fa fa-money"></i> {{ __('system.exam_rank_report') }}</h3>
        </div>
        <div class="box-body table-responsive">
            @if($exam && ! (int) $exam->is_rank_generated)
                <div class="alert alert-info">{{ __('system.exam_rank_not_generated') }}</div>
            @endif

            <table class="table table-striped table-bordered table-hover">
                <thead>
                    <tr>
                        <th>{{ __('system.rank') }}</th>
                        <th>{{ __('system.admission_no') }}</th>
                        <th>{{ __('system.student') }}</th>
                        <th>{{ __('system.class') }}</th>
                        @if($reports->settingOn('father_name'))
                            <th>{{ __('system.father_name') }}</th>
                        @endif
                        <th>{{ __('system.exam_submitted') }}</th>
                        <th>{{ __('system.total_questions') }}</th>
                        <th>{{ __('system.descriptive') }}</th>
                        <th>{{ __('system.correct_answer') }}</th>
                        <th>{{ __('system.wrong_answer') }}</th>
                        <th>{{ __('system.not_attempted') }}</th>
                        <th>{{ __('system.total_exam_marks') }}</th>
                        <th>{{ __('system.total_negative_marks') }}</th>
                        <th>{{ __('system.total_scored_marks') }}</th>
                        <th>{{ __('system.score') }} (%)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        @php
                            $student = $row['student'];
                            $summary = $row['summary'];
                        @endphp
                        <tr>
                            <td>{{ $student->exam_rank }}</td>
                            <td>{{ $student->admission_no }}</td>
                            <td>{{ $reports->studentDisplayName($student) }}</td>
                            <td>{{ $student->class }} ({{ $student->section }})</td>
                            @if($reports->settingOn('father_name'))
                                <td>{{ $student->father_name }}</td>
                            @endif
                            <td class="text-center">
                                @if((int) $student->is_attempted)
                                    <i class="fa fa-check-square-o"></i>
                                @else
                                    <i class="fa fa-remove"></i>
                                @endif
                            </td>
                            <td class="text-center">{{ $summary['total_question'] }}</td>
                            <td class="text-center">{{ $summary['exam_total_descriptive'] }}</td>
                            <td class="text-center">{{ $summary['correct_ans'] }}</td>
                            <td class="text-center">{{ $summary['wrong_ans'] }}</td>
                            <td class="text-center">{{ $summary['not_attempted'] }}</td>
                            <td class="text-center">{{ $summary['exam_total_marks'] }}</td>
                            <td class="text-center">{{ $summary['exam_total_neg_marks'] }}</td>
                            <td class="text-center">{{ $summary['exam_total_scored'] }}</td>
                            <td class="text-center">{{ number_format((float) $summary['score_percent'], 2, '.', '') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $reports->settingOn('father_name') ? 15 : 14 }}">{{ __('system.no_record_found') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endif

<script>
(function () {
    function loadSections(classId, selected) {
        var section = document.getElementById('section_id');
        if (!section) return;
        section.innerHTML = '<option value="">{{ __('system.select') }}</option>';
        if (!classId) return;
        fetch(@json(url('sections/getByClass')) + '?class_id=' + encodeURIComponent(classId), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (r) { return r.json(); }).then(function (data) {
            (data || []).forEach(function (obj) {
                var opt = document.createElement('option');
                opt.value = obj.section_id;
                opt.textContent = obj.section;
                if (String(selected) === String(obj.section_id)) {
                    opt.selected = true;
                }
                section.appendChild(opt);
            });
        }).catch(function () {});
    }
    var classSelect = document.getElementById('class_id');
    if (!classSelect) return;
    classSelect.addEventListener('change', function () {
        loadSections(this.value, '');
    });
})();
</script>
