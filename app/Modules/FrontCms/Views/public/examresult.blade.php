@extends('frontcms::public.layout')

@section('content')
    @if(! $is_exam_result)
        <div class="alert alert-danger">{{ __('system.exam_result_disable_please_contact_to_administrator') }}</div>
    @else
        <h3>{{ __('system.exam_result') }}</h3>
        <form method="post" action="{{ url('welcome/examresult') }}" id="form1">
            @csrf
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>{{ __('system.admission_no') }} <small class="req">*</small></label>
                        <input type="text" class="form-control" id="admission_no" name="admission_no" value="{{ old('admission_no') }}">
                        @error('admission_no')<span class="text-danger">{{ $message }}</span>@enderror
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>{{ __('system.exam') }} <small class="req">*</small></label>
                        <select id="exam_id" name="exam_id" class="form-control">
                            <option value="">{{ __('system.select') }}</option>
                        </select>
                        @error('exam_id')<span class="text-danger">{{ $message }}</span>@enderror
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary" name="search" id="search_btn" value="1">{{ __('system.search') }}</button>
                    </div>
                </div>
            </div>
        </form>

        @if(session('msg'))
            {!! session('msg') !!}
        @endif

        @if($searched && !empty($exam_result) && !empty($student_details))
            @php
                $student = $student_details[0];
            @endphp
            <div id="divtoprint">
                <ul>
                    <li>{{ __('system.student_name') }} <span>{{ $examResultService->studentDisplayName($student) }}</span></li>
                    <li>{{ __('system.admission_no') }} <span>{{ $student['admission_no'] }}</span></li>
                    @if($show_roll_no)
                        <li>{{ __('system.roll_number') }} <span>{{ $student['roll_no'] }}</span></li>
                    @endif
                    <li>{{ __('system.class') }} <span>{{ $student['class_name'] }}</span></li>
                    <li>{{ __('system.section') }} <span>{{ $student['section_name'] }}</span></li>
                </ul>
                <button type="button" id="printbtn" class="btn btn-sm" onclick="printDiv()">{{ __('system.print') }}</button>

                @foreach($exam_result as $exam_value)
                    <h4>{{ $exam_value->exam }}</h4>
                    @php
                        $nested = $exam_value->exam_result ?? [];
                        $rows = [];
                        if ((int) ($nested['exam_connection'] ?? 0) === 0) {
                            $rows = $nested['result'] ?? [];
                        } else {
                            $key = 'exam_result_'.$exam_value->exam_group_class_batch_exam_id;
                            $rows = $nested['exam_result'][$key] ?? [];
                        }
                    @endphp
                    @if(!empty($rows))
                        @php
                            $exam_quality_points = 0;
                            $exam_credit_hour = 0;
                            $exam_grand_total = 0;
                            $exam_get_total = 0;
                            $exam_pass_status = 1;
                            $exam_absent_status = 0;
                            $total_exams = 1;
                        @endphp
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>{{ __('system.subject') }}</th>
                                    @if($exam_value->exam_type === 'gpa')
                                        <th>{{ __('system.grade_point') }}</th>
                                        <th>{{ __('system.credit_hours') }}</th>
                                        <th>{{ __('system.quality_points') }}</th>
                                    @else
                                        <th>{{ __('system.max_marks') }}</th>
                                        <th>{{ __('system.min_marks') }}</th>
                                        <th>{{ __('system.marks_obtained') }}</th>
                                    @endif
                                    @if($exam_value->exam_type === 'coll_grade_system' || $exam_value->exam_type === 'school_grade_system')
                                        <th>{{ __('system.grade') }}</th>
                                    @endif
                                    @if($exam_value->exam_type === 'basic_system')
                                        <th>{{ __('system.result') }}</th>
                                    @endif
                                    <th>{{ __('system.note') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($rows as $exam_result_value)
                                    @php
                                        $maxMarks = (float) $exam_result_value->max_marks;
                                        $getMarks = (float) $exam_result_value->get_marks;
                                        $exam_grand_total += $maxMarks;
                                        $exam_get_total += $getMarks;
                                        $percentage_grade = $maxMarks > 0 ? ($getMarks * 100) / $maxMarks : 0;
                                        if ($getMarks < (float) $exam_result_value->min_marks) {
                                            $exam_pass_status = 0;
                                        }
                                        if (($exam_result_value->attendence ?? '') === 'absent') {
                                            $exam_absent_status = 1;
                                        }
                                    @endphp
                                    <tr>
                                        <td>{{ $exam_result_value->name }} ({{ $exam_result_value->code }})</td>
                                        @if($exam_value->exam_type !== 'gpa')
                                            <td>{{ $exam_result_value->max_marks }}</td>
                                            <td>{{ $exam_result_value->min_marks }}</td>
                                            <td>
                                                {{ $exam_result_value->get_marks }}
                                                @if(($exam_result_value->attendence ?? '') === 'absent')
                                                    &nbsp;{{ __('system.abs') }}
                                                @endif
                                            </td>
                                        @else
                                            @php
                                                $point = $examResultService->findGradePoints($exam_grade, $exam_value->exam_type, $percentage_grade);
                                                $credit = (float) $exam_result_value->credit_hours;
                                                $exam_credit_hour += $credit;
                                                $exam_quality_points += ($credit * $point);
                                            @endphp
                                            <td>{{ number_format($point, 2, '.', '') }}</td>
                                            <td>{{ $exam_result_value->credit_hours }}</td>
                                            <td>{{ number_format($credit * $point, 2, '.', '') }}</td>
                                        @endif
                                        @if($exam_value->exam_type === 'coll_grade_system' || $exam_value->exam_type === 'school_grade_system')
                                            <td>{{ $examResultService->findExamGrade($exam_grade, $exam_value->exam_type, $percentage_grade) }}</td>
                                        @endif
                                        @if($exam_value->exam_type === 'basic_system')
                                            <td>
                                                @if($getMarks < (float) $exam_result_value->min_marks)
                                                    <label class="label label-danger">{{ __('system.fail') }}</label>
                                                @else
                                                    <label class="label label-success">{{ __('system.pass') }}</label>
                                                @endif
                                            </td>
                                        @endif
                                        <td>{{ $exam_result_value->note }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        @if($exam_value->exam_type !== 'gpa')
                            @php
                                $exam_percentage = $exam_grand_total > 0 ? ($exam_get_total * 100) / $exam_grand_total : 0;
                            @endphp
                            <p>{{ __('system.percentage') }} : {{ number_format($exam_percentage, 2, '.', '') }}</p>
                            <p>{{ __('system.rank') }} : {{ $exam_value->rank }}</p>
                            <p>{{ __('system.result') }} :
                                @if($exam_value->exam_type === 'average_passing')
                                    @if((float) $exam_value->passing_percentage <= $exam_percentage)
                                        <span class="label label-success">{{ __('system.pass') }}</span>
                                    @else
                                        <span class="label label-danger">{{ __('system.fail') }}</span>
                                    @endif
                                    {{ __('system.division') }} : {{ $examResultService->findExamDivision($marks_division, $exam_percentage) }}
                                @elseif($exam_absent_status)
                                    <span class="label label-danger">{{ __('system.fail') }}</span>
                                    {{ __('system.division') }} : {{ $examResultService->findExamDivision($marks_division, $exam_percentage) }}
                                @elseif($exam_pass_status)
                                    <span class="text-success">{{ __('system.pass') }}</span>
                                    {{ __('system.division') }} : {{ $examResultService->findExamDivision($marks_division, $exam_percentage) }}
                                @else
                                    <span class="text-danger">{{ __('system.fail') }}</span>
                                    {{ __('system.division') }} : {{ $examResultService->findExamDivision($marks_division, $exam_percentage) }}
                                @endif
                            </p>
                            <p>{{ __('system.grand_total') }} : {{ $exam_grand_total }}</p>
                            <p>{{ __('system.total_obtain_marks') }} : {{ $exam_get_total }}</p>
                        @else
                            <p>{{ __('system.credit_hours') }} : {{ $exam_credit_hour }}</p>
                            <p>{{ __('system.rank') }} : {{ $exam_value->rank }}</p>
                            <p>{{ __('system.quality_points') }} :
                                @if($exam_credit_hour <= 0)
                                    --
                                @else
                                    @php
                                        $exam_grade_percentage = $exam_grand_total > 0 ? ($exam_get_total * 100) / $exam_grand_total : 0;
                                    @endphp
                                    {{ $exam_quality_points }}/{{ $exam_credit_hour }}={{ number_format($exam_quality_points / $exam_credit_hour, 2, '.', '') }}
                                    [{{ $examResultService->findExamGrade($exam_grade, $exam_value->exam_type, $exam_grade_percentage) }}]
                                @endif
                            </p>
                        @endif
                    @endif
                @endforeach
            </div>
        @endif

        <script src="{{ asset('backend/themes/default/js/jquery.min.js') }}"></script>
        <script>
            $(function () {
                @if($searched)
                    $('#admission_no').trigger($.Event('keyup', {which: 32}));
                @endif
            });
            $(document).on('keyup', '#admission_no', function () {
                var admission_no = $('#admission_no').val();
                $.ajax({
                    url: '{{ url('welcome/getstudentexam') }}',
                    type: 'POST',
                    dataType: 'JSON',
                    data: {admission_no: admission_no},
                    success: function (res) {
                        var div_data = '';
                        var exam_id = @json((string) $exam_id);
                        $.each(res, function (i, obj) {
                            var sel = ((exam_id !== '') && (exam_id == obj.id)) ? 'selected' : '';
                            div_data += '<option value="' + obj.id + '" ' + sel + '>' + obj.exam + '</option>';
                        });
                        $('#exam_id').html("<option value=''>{{ __('system.select') }}</option>");
                        $('#exam_id').append(div_data);
                    }
                });
            });
            function printDiv() {
                $('#printbtn').css('display', 'none');
                var printContents = document.getElementById('divtoprint').innerHTML;
                var originalContents = document.body.innerHTML;
                document.body.innerHTML = printContents;
                window.print();
                document.body.innerHTML = originalContents;
                location.reload(true);
            }
        </script>
    @endif
@endsection
