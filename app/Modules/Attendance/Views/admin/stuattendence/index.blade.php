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
        <h3 class="box-title">Student Attendance</h3>
        <div class="box-tools">
            <a href="{{ route('attendance.stuattendence.attendencereport') }}" class="btn btn-default btn-sm">Attendance By Date</a>
        </div>
    </div>
    <div class="box-body">
        <form method="post" action="{{ route('attendance.stuattendence.index') }}" class="row" id="attendance_search_form">
            @csrf
            <input type="hidden" name="search" value="search">
            <div class="col-sm-3">
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
            <div class="col-sm-3">
                <div class="form-group">
                    <label>Section <span class="text-danger">*</span></label>
                    <select id="section_id" name="section_id" class="form-control" required>
                        <option value="">Select</option>
                    </select>
                </div>
            </div>
            <div class="col-sm-3">
                <div class="form-group">
                    <label>Date <span class="text-danger">*</span></label>
                    <input type="date" name="date" class="form-control" value="{{ old('date', $filters['date'] ?? date('Y-m-d')) }}" required>
                </div>
            </div>
            <div class="col-sm-3">
                <div class="form-group">
                    <label>&nbsp;</label>
                    <button type="submit" class="btn btn-primary btn-sm btn-block"><i class="fa fa-search"></i> Search</button>
                </div>
            </div>
        </form>
    </div>
</div>

@if($resultList !== null)
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">Student List</h3>
        </div>
        <div class="box-body">
            <form method="post" action="{{ route('attendance.stuattendence.index') }}" id="attendance_save_form">
                @csrf
                <input type="hidden" name="search" value="saveattendence">
                <input type="hidden" name="class_id" value="{{ $filters['class_id'] }}">
                <input type="hidden" name="section_id" value="{{ $filters['section_id'] }}">
                <input type="hidden" name="date" value="{{ $filters['date'] }}">
                <input type="hidden" name="is_first_time_attendance" value="{{ $isFirstTime ? '1' : '0' }}">

                @if($canAdd && $types->isNotEmpty())
                    <div class="form-group">
                        <label>Set attendance for all students as &nbsp;</label>
                        @foreach($types as $type)
                            <label class="radio-inline" style="margin-right:10px;">
                                <input type="radio" name="attendencetype_all" class="set_all_type" value="{{ $type->id }}">
                                {{ $type->type }}
                            </label>
                        @endforeach
                    </div>
                @endif

                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>Admission No</th>
                            <th>Roll No</th>
                            <th>Name</th>
                            <th>Attendance</th>
                            <th>Source</th>
                            <th>Entry Time</th>
                            <th>Exit Time</th>
                            <th>Note</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($resultList as $i => $row)
                            @php
                                $ssid = (int) $row->student_session_id;
                                $currentType = (int) ($row->attendence_type_id ?: 0);
                                $source = 'Manual';
                                if ((int) ($row->biometric_attendence ?? 0) === 1) {
                                    $source = 'Biometric';
                                } elseif ((int) ($row->qrcode_attendance ?? 0) === 1) {
                                    $source = 'QR';
                                }
                            @endphp
                            <tr>
                                <td>
                                    {{ $i + 1 }}
                                    <input type="hidden" name="student_session[]" value="{{ $ssid }}">
                                    <input type="hidden" name="attendendence_id{{ $ssid }}" value="{{ (int) $row->attendence_id }}">
                                </td>
                                <td>{{ $row->admission_no }}</td>
                                <td>{{ $row->roll_no }}</td>
                                <td>{{ trim($row->firstname.' '.($row->middlename ?? '').' '.$row->lastname) }}</td>
                                <td>
                                    @foreach($types as $type)
                                        <label class="radio-inline" style="margin-right:8px;">
                                            <input type="radio"
                                                   class="student_att_type"
                                                   name="attendencetype{{ $ssid }}"
                                                   value="{{ $type->id }}"
                                                   data-student="{{ $ssid }}"
                                                   @checked($currentType === (int) $type->id || ($currentType === 0 && (int) $type->id === 1))
                                                   @disabled(! $canAdd)
                                                   required>
                                            {{ $type->type }}
                                        </label>
                                    @endforeach
                                </td>
                                <td>{{ $source }}</td>
                                <td>
                                    <input type="time" class="form-control in_time"
                                           name="in_time_{{ $ssid }}"
                                           value="{{ $row->in_time ? substr((string) $row->in_time, 0, 5) : '' }}"
                                           @disabled(! $canAdd)>
                                </td>
                                <td>
                                    <input type="time" class="form-control out_time"
                                           name="out_time_{{ $ssid }}"
                                           value="{{ $row->out_time ? substr((string) $row->out_time, 0, 5) : '' }}"
                                           @disabled(! $canAdd)>
                                </td>
                                <td>
                                    <input type="text" class="form-control"
                                           name="remark{{ $ssid }}"
                                           value="{{ $row->remark }}"
                                           maxlength="200"
                                           @disabled(! $canAdd)>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="text-center">No students found</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>

                @if($canAdd && $resultList->isNotEmpty())
                    <button type="submit" class="btn btn-primary"
                            onclick="return confirm('Save attendance for this class/section/date?');">
                        <i class="fa fa-save"></i> Save Attendance
                    </button>
                @endif
            </form>
        </div>
    </div>
@endif

@push('scripts')
<script>
$(function () {
    var oldSection = '{{ $filters['section_id'] ?? '' }}';
    function loadSections(classId, selected) {
        $('#section_id').html('<option value="">Select</option>');
        if (!classId) return;
        $.getJSON('{{ url('sections/getByClass') }}', {class_id: classId}, function (data) {
            $.each(data, function (i, row) {
                var opt = $('<option>', {value: row.section_id, text: row.section});
                if (String(selected) === String(row.section_id)) opt.prop('selected', true);
                $('#section_id').append(opt);
            });
        });
    }
    $('#class_id').on('change', function () { loadSections($(this).val(), ''); });
    loadSections($('#class_id').val(), oldSection);

    $('.set_all_type').on('change', function () {
        var typeId = $(this).val();
        $('.student_att_type').each(function () {
            if (String($(this).val()) === String(typeId)) {
                $(this).prop('checked', true).trigger('change');
            }
        });
    });

    function toggleTimes($radio) {
        var typeId = parseInt($radio.val(), 10);
        var student = $radio.data('student');
        var clear = (typeId === 4 || typeId === 5); // Absent / Holiday
        var $row = $radio.closest('tr');
        if (clear) {
            $row.find('.in_time, .out_time').val('').prop('disabled', true);
        } else {
            $row.find('.in_time, .out_time').prop('disabled', false);
        }
    }
    $(document).on('change', '.student_att_type', function () {
        toggleTimes($(this));
    });
    $('.student_att_type:checked').each(function () { toggleTimes($(this)); });
});
</script>
@endpush
