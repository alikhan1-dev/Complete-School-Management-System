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

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">Create Class Timetable</h3>
        <div class="box-tools">
            <a href="{{ route('timetable.classreport') }}" class="btn btn-default btn-sm">Class Timetable</a>
        </div>
    </div>
    <div class="box-body">
        <form method="post" action="{{ route('timetable.create') }}" class="row" id="timetable_search_form">
            @csrf
            <input type="hidden" name="search" value="search">
            <div class="col-sm-4">
                <div class="form-group">
                    <label>Class <span class="text-danger">*</span></label>
                    <select id="class_id" name="class_id" class="form-control" required>
                        <option value="">Select</option>
                        @foreach($classes as $class)
                            <option value="{{ $class->id }}" @selected((string) ($filters['class_id'] ?? '') === (string) $class->id)>{{ $class->class }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="form-group">
                    <label>Section <span class="text-danger">*</span></label>
                    <select id="section_id" name="section_id" class="form-control" required>
                        <option value="">Select</option>
                    </select>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="form-group">
                    <label>Subject Group <span class="text-danger">*</span></label>
                    <select id="subject_group_id" name="subject_group_id" class="form-control" required>
                        <option value="">Select</option>
                    </select>
                </div>
            </div>
            <div class="col-sm-12 text-right">
                <button type="submit" class="btn btn-primary btn-sm"><i class="fa fa-search"></i> Search</button>
            </div>
        </form>
    </div>
</div>

@if($week !== null)
    @foreach($days as $day)
        @php $periods = $week[$day] ?? collect(); @endphp
        <div class="box box-primary day-timetable-box">
            <div class="box-header with-border">
                <h3 class="box-title">{{ $day }}</h3>
            </div>
            <div class="box-body">
                @include('timetable::admin.partials.quick_period_generator')

                <form method="post" action="{{ route('timetable.save_day') }}" class="day-form" data-day="{{ $day }}">
                    @csrf
                    <input type="hidden" name="class_id" value="{{ $filters['class_id'] }}">
                    <input type="hidden" name="section_id" value="{{ $filters['section_id'] }}">
                    <input type="hidden" name="subject_group_id" value="{{ $filters['subject_group_id'] }}">
                    <input type="hidden" name="day" value="{{ $day }}">

                    <div class="table-responsive">
                        <table class="table table-bordered period-table">
                            <thead>
                            <tr>
                                <th>Subject</th>
                                <th>Time From</th>
                                <th>Time To</th>
                                <th>Teacher</th>
                                <th>Room No</th>
                                <th style="width:70px;"></th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($periods as $i => $period)
                                <tr>
                                    <td>
                                        <input type="hidden" name="periods[{{ $i }}][id]" value="{{ $period->id }}">
                                        <select name="periods[{{ $i }}][subject_group_subject_id]" class="form-control" required>
                                            <option value="">Select</option>
                                            @foreach($subjects as $subject)
                                                <option value="{{ $subject->id }}" @selected((int) $period->subject_group_subject_id === (int) $subject->id)>
                                                    {{ $subject->name }}@if($subject->code) ({{ $subject->code }})@endif
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <input type="time" name="periods[{{ $i }}][time_from]" class="form-control time_from"
                                               value="{{ $service->toTimeInput($period->time_from, $period->start_time) }}" required>
                                    </td>
                                    <td>
                                        <input type="time" name="periods[{{ $i }}][time_to]" class="form-control time_to"
                                               value="{{ $service->toTimeInput($period->time_to, $period->end_time) }}" required>
                                    </td>
                                    <td>
                                        <select name="periods[{{ $i }}][staff_id]" class="form-control staff" required>
                                            <option value="">Select</option>
                                            @foreach($teachers as $teacher)
                                                <option value="{{ $teacher->id }}" @selected((int) $period->staff_id === (int) $teacher->id)>
                                                    {{ trim($teacher->name.' '.$teacher->surname) }} ({{ $teacher->employee_id }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <input type="text" name="periods[{{ $i }}][room_no]" class="form-control room_no"
                                               value="{{ $period->room_no }}" maxlength="100" required>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-danger btn-sm remove-row"><i class="fa fa-trash"></i></button>
                                    </td>
                                </tr>
                            @empty
                                <tr class="blank-row">
                                    <td>
                                        <input type="hidden" name="periods[0][id]" value="0">
                                        <select name="periods[0][subject_group_subject_id]" class="form-control">
                                            <option value="">Select</option>
                                            @foreach($subjects as $subject)
                                                <option value="{{ $subject->id }}">{{ $subject->name }}@if($subject->code) ({{ $subject->code }})@endif</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td><input type="time" name="periods[0][time_from]" class="form-control time_from"></td>
                                    <td><input type="time" name="periods[0][time_to]" class="form-control time_to"></td>
                                    <td>
                                        <select name="periods[0][staff_id]" class="form-control staff">
                                            <option value="">Select</option>
                                            @foreach($teachers as $teacher)
                                                <option value="{{ $teacher->id }}">{{ trim($teacher->name.' '.$teacher->surname) }} ({{ $teacher->employee_id }})</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td><input type="text" name="periods[0][room_no]" class="form-control room_no" maxlength="100"></td>
                                    <td>
                                        <button type="button" class="btn btn-danger btn-sm remove-row"><i class="fa fa-trash"></i></button>
                                    </td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($canSave)
                        <button type="button" class="btn btn-default btn-sm add-row"><i class="fa fa-plus"></i> Add Period</button>
                        <button type="submit" class="btn btn-primary btn-sm pull-right"
                                onclick="return confirm('Save {{ $day }} timetable? Empty rows are ignored; removed rows are deleted.');">
                            <i class="fa fa-save"></i> Save {{ $day }}
                        </button>
                    @endif
                </form>
            </div>
        </div>
    @endforeach

    <template id="period_row_template">
        <tr>
            <td>
                <input type="hidden" name="periods[__INDEX__][id]" value="0">
                <select name="periods[__INDEX__][subject_group_subject_id]" class="form-control">
                    <option value="">Select</option>
                    @foreach($subjects as $subject)
                        <option value="{{ $subject->id }}">{{ $subject->name }}@if($subject->code) ({{ $subject->code }})@endif</option>
                    @endforeach
                </select>
            </td>
            <td><input type="time" name="periods[__INDEX__][time_from]" class="form-control time_from"></td>
            <td><input type="time" name="periods[__INDEX__][time_to]" class="form-control time_to"></td>
            <td>
                <select name="periods[__INDEX__][staff_id]" class="form-control staff">
                    <option value="">Select</option>
                    @foreach($teachers as $teacher)
                        <option value="{{ $teacher->id }}">{{ trim($teacher->name.' '.$teacher->surname) }} ({{ $teacher->employee_id }})</option>
                    @endforeach
                </select>
            </td>
            <td><input type="text" name="periods[__INDEX__][room_no]" class="form-control room_no" maxlength="100"></td>
            <td>
                <button type="button" class="btn btn-danger btn-sm remove-row"><i class="fa fa-trash"></i></button>
            </td>
        </tr>
    </template>
@endif

@push('scripts')
<script>
$(function () {
    var oldSection = '{{ $filters['section_id'] ?? '' }}';
    var oldGroup = '{{ $filters['subject_group_id'] ?? '' }}';
    var csrfToken = $('meta[name="csrf-token"]').attr('content') || '{{ csrf_token() }}';

    function loadSections(classId, selected, thenLoadGroups) {
        $('#section_id').html('<option value="">Select</option>');
        $('#subject_group_id').html('<option value="">Select</option>');
        if (!classId) return;
        $.getJSON('{{ url('sections/getByClass') }}', {class_id: classId}, function (data) {
            $.each(data, function (i, row) {
                var opt = $('<option>', {value: row.section_id, text: row.section});
                if (String(selected) === String(row.section_id)) opt.prop('selected', true);
                $('#section_id').append(opt);
            });
            if (thenLoadGroups) {
                loadGroups(classId, $('#section_id').val(), oldGroup);
            }
        });
    }

    function loadGroups(classId, sectionId, selected) {
        $('#subject_group_id').html('<option value="">Select</option>');
        if (!classId || !sectionId) return;
        $.ajax({
            type: 'POST',
            url: '{{ url('admin/subjectgroup/getGroupByClassandSection') }}',
            data: {_token: csrfToken, class_id: classId, section_id: sectionId},
            dataType: 'json',
            success: function (data) {
                $.each(data, function (i, row) {
                    var opt = $('<option>', {value: row.subject_group_id || row.id, text: row.name});
                    if (String(selected) === String(row.subject_group_id || row.id)) opt.prop('selected', true);
                    $('#subject_group_id').append(opt);
                });
            }
        });
    }

    $('#class_id').on('change', function () {
        oldGroup = '';
        loadSections($(this).val(), '', false);
    });
    $('#section_id').on('change', function () {
        oldGroup = '';
        loadGroups($('#class_id').val(), $(this).val(), '');
    });

    loadSections($('#class_id').val(), oldSection, true);

    $(document).on('click', '.add-row', function () {
        var $form = $(this).closest('form');
        var $tbody = $form.find('tbody');
        var index = Date.now();
        var html = $('#period_row_template').html().replace(/__INDEX__/g, String(index));
        $tbody.append(html);
    });

    $(document).on('click', '.remove-row', function () {
        var $tbody = $(this).closest('tbody');
        if ($tbody.find('tr').length <= 1) {
            $(this).closest('tr').find('input, select').val('');
            $(this).closest('tr').find('input[type=hidden]').val('0');
            return;
        }
        $(this).closest('tr').remove();
    });

    function checkDuplicateStaffSlot($row) {
        var $form = $row.closest('form');
        var timeFrom = $row.find('.time_from').val();
        var timeTo = $row.find('.time_to').val();
        var staffId = $row.find('.staff').val();
        var day = $form.find('input[name=day]').val();
        if (!staffId || !timeFrom || !timeTo || !day) {
            return;
        }
        $.ajax({
            type: 'POST',
            url: '{{ route('timetable.check_duplicate_record') }}',
            data: {
                _token: csrfToken,
                time_from: timeFrom,
                time_to: timeTo,
                staff_id: staffId,
                day: day
            },
            dataType: 'json',
            success: function (res) {
                if (String(res.status) === '1' || res.status === 1) {
                    alert(res.error || '{{ __('system.is_already_allotted_to_other_class_section_or_period_for_the_same_time') }}');
                }
            }
        });
    }

    $(document).on('change', '.staff', function () {
        checkDuplicateStaffSlot($(this).closest('tr'));
    });

    function parseTimeToMinutes(value) {
        var parts = String(value || '').split(':');
        if (parts.length < 2) {
            return null;
        }
        var hours = parseInt(parts[0], 10);
        var minutes = parseInt(parts[1], 10);
        if (isNaN(hours) || isNaN(minutes)) {
            return null;
        }
        return (hours * 60) + minutes;
    }

    function formatMinutesToTime(totalMinutes) {
        totalMinutes = ((totalMinutes % (24 * 60)) + (24 * 60)) % (24 * 60);
        var hours = Math.floor(totalMinutes / 60);
        var minutes = totalMinutes % 60;
        return String(hours).padStart(2, '0') + ':' + String(minutes).padStart(2, '0');
    }

    $(document).on('click', '.apply-quick-periods', function () {
        var $box = $(this).closest('.day-timetable-box');
        var startTime = $box.find('.quick-start-time').val();
        var duration = parseInt($box.find('.quick-duration').val(), 10);
        var interval = parseInt($box.find('.quick-interval').val(), 10);
        var roomNo = $box.find('.quick-room-no').val();

        if (!startTime || !duration || duration <= 0) {
            alert('{{ __('system.required') }}');
            return;
        }
        if (isNaN(interval) || interval < 0) {
            interval = 0;
        }

        var cursor = parseTimeToMinutes(startTime);
        if (cursor === null) {
            alert('{{ __('system.required') }}');
            return;
        }

        $box.find('tbody tr').each(function () {
            var from = cursor;
            var to = from + duration;
            $(this).find('.time_from').val(formatMinutesToTime(from));
            $(this).find('.time_to').val(formatMinutesToTime(to));
            $(this).find('.room_no').val(roomNo);
            cursor = to + interval;
        });
    });
});
</script>
@endpush
