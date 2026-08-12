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
        <h3 class="box-title">Staff Attendance</h3>
        <div class="box-tools">
            <a href="{{ route('attendance.stuattendence.index') }}" class="btn btn-default btn-sm">Student Attendance</a>
        </div>
    </div>
    <div class="box-body">
        <form method="post" action="{{ route('attendance.staffattendance.index') }}" class="row" id="staff_attendance_search_form">
            @csrf
            <input type="hidden" name="search" value="search">
            <div class="col-sm-6">
                <div class="form-group">
                    <label>Role</label>
                    <select id="user_id" name="user_id" class="form-control" required>
                        <option value="select" @selected(($filters['user_id'] ?? 'select') === 'select')>Select</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->name }}" @selected((string) ($filters['user_id'] ?? '') === (string) $role->name)>{{ $role->name }}</option>
                        @endforeach
                    </select>
                    <p class="help-block" style="margin-bottom:0;">"Select" lists all active staff (CI parity).</p>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="form-group">
                    <label>Attendance Date <span class="text-danger">*</span></label>
                    <input type="date" name="date" class="form-control" value="{{ old('date', $filters['date'] ?? date('Y-m-d')) }}" required>
                </div>
            </div>
            <div class="col-sm-2">
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
            <h3 class="box-title">Staff List</h3>
        </div>
        <div class="box-body">
            @if($resultList->isEmpty())
                <div class="alert alert-info">No record found</div>
            @else
                @if(! empty($resultList->first()->staff_attendance_type_id))
                    <div class="alert alert-success">Attendance already submitted — you can edit the record.</div>
                @endif

                <form method="post" action="{{ route('attendance.staffattendance.index') }}" id="staff_attendance_save_form">
                    @csrf
                    <input type="hidden" name="search" value="saveattendence">
                    <input type="hidden" name="user_id" value="{{ $filters['user_id'] }}">
                    <input type="hidden" name="date" value="{{ $filters['date'] }}">
                    <input type="hidden" name="is_first_time_attendance" value="{{ $isFirstTime ? '1' : '0' }}">

                    @if($canSave && $types->isNotEmpty())
                        <div class="form-group">
                            <label>Set attendance for all staff as &nbsp;</label>
                            @foreach($types as $type)
                                <label class="radio-inline" style="margin-right:10px;">
                                    <input type="radio"
                                           name="attendencetype_all"
                                           class="set_all_type"
                                           value="{{ $type->id }}"
                                           data-record_id="{{ $type->id }}">
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
                                <th>Staff ID</th>
                                <th>Name</th>
                                <th>Role</th>
                                <th>Attendance</th>
                                <th>Source</th>
                                <th>Entry Time</th>
                                <th>Exit Time</th>
                                <th>Note</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($resultList as $i => $row)
                                @php
                                    $staffId = (int) $row->staff_id;
                                    $roleId = (int) ($row->role_id ?: 0);
                                    $currentType = (int) ($row->staff_attendance_type_id ?: 0);
                                    $clearTimes = in_array($currentType, [3, 5], true);
                                    $source = 'N/A';
                                    if ($row->biometric_attendence === null && $row->qrcode_attendance === null && empty($row->id)) {
                                        $source = 'N/A';
                                    } elseif ((int) ($row->biometric_attendence ?? 0) === 0 && (int) ($row->qrcode_attendance ?? 0) === 0 && (int) ($row->id ?? 0) > 0) {
                                        $source = 'Manual';
                                    } elseif ((int) ($row->biometric_attendence ?? 0) === 1) {
                                        $source = 'Biometric';
                                    } elseif ((int) ($row->qrcode_attendance ?? 0) === 1) {
                                        $source = 'QR / Barcode';
                                    }
                                    $inTime = ($row->in_time && $row->in_time !== '00:00:00') ? substr((string) $row->in_time, 0, 5) : '';
                                    $outTime = ($row->out_time && $row->out_time !== '00:00:00') ? substr((string) $row->out_time, 0, 5) : '';
                                @endphp
                                <tr>
                                    <td>
                                        {{ $i + 1 }}
                                        <input type="hidden" name="staff_role[]" value="{{ $roleId }}">
                                        <input type="hidden" name="student_session[]" value="{{ $staffId }}">
                                        <input type="hidden" name="attendendence_id{{ $staffId }}" value="{{ (int) $row->id }}">
                                    </td>
                                    <td>{{ $row->employee_id }}</td>
                                    <td>{{ trim($row->name.' '.$row->surname) }}</td>
                                    <td>{{ $row->user_type }}</td>
                                    <td>
                                        @foreach($types as $type)
                                            <label class="radio-inline" style="margin-right:8px;">
                                                <input type="radio"
                                                       class="staff_att_type radio_{{ $type->id }}"
                                                       name="attendencetype{{ $staffId }}"
                                                       value="{{ $type->id }}"
                                                       data-staff="{{ $staffId }}"
                                                       @checked($currentType === (int) $type->id || ($currentType === 0 && (int) $type->id === 1))
                                                       @disabled(! $canSave)
                                                       required>
                                                {{ $type->type }}
                                            </label>
                                        @endforeach
                                    </td>
                                    <td>{{ $source }}</td>
                                    <td>
                                        <input type="time"
                                               class="form-control in_time in_time_{{ $roleId }}"
                                               name="in_time_{{ $staffId }}"
                                               id="in_time_{{ $staffId }}"
                                               value="{{ $inTime }}"
                                               @disabled(! $canSave || $clearTimes)>
                                    </td>
                                    <td>
                                        <input type="time"
                                               class="form-control out_time out_time_{{ $roleId }}"
                                               name="out_time_{{ $staffId }}"
                                               id="out_time_{{ $staffId }}"
                                               value="{{ $outTime }}"
                                               @disabled(! $canSave || $clearTimes)>
                                    </td>
                                    <td>
                                        <input type="text"
                                               class="form-control"
                                               name="remark{{ $staffId }}"
                                               value="{{ $row->remark }}"
                                               maxlength="200"
                                               @disabled(! $canSave)>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if($canSave)
                        <button type="submit" class="btn btn-primary"
                                onclick="return confirm('Save staff attendance for this role/date?');">
                            <i class="fa fa-save"></i> Save Attendance
                        </button>
                    @endif
                </form>
            @endif
        </div>
    </div>
@endif

@push('scripts')
<script>
$(function () {
    var attendanceSetting = @json($staffSettings);

    function toTimeInput(value) {
        if (!value) return '';
        // Accept HH:MM:SS or "h:mm AM/PM"
        var d = new Date('1970-01-01 ' + value);
        if (isNaN(d.getTime())) {
            var ts = Date.parse('1970-01-01 ' + value);
            if (isNaN(ts)) return '';
            d = new Date(ts);
        }
        var h = String(d.getHours()).padStart(2, '0');
        var m = String(d.getMinutes()).padStart(2, '0');
        return h + ':' + m;
    }

    function toggleTimes(typeId, staffId) {
        var clear = (parseInt(typeId, 10) === 3 || parseInt(typeId, 10) === 5);
        var $in = $('#in_time_' + staffId);
        var $out = $('#out_time_' + staffId);
        if (clear) {
            $in.val('').prop('disabled', true);
            $out.val('').prop('disabled', true);
        } else {
            $in.prop('disabled', false);
            $out.prop('disabled', false);
        }
    }

    $('.set_all_type').on('change', function () {
        var typeId = parseInt($(this).val(), 10);
        if (!confirm('Are you sure?')) {
            $(this).prop('checked', false);
            return;
        }

        $('.staff_att_type').each(function () {
            if (parseInt($(this).val(), 10) === typeId) {
                $(this).prop('checked', true);
                toggleTimes(typeId, $(this).data('staff'));
            }
        });

        if (typeId === 3 || typeId === 5) {
            $('.in_time, .out_time').val('').prop('disabled', true);
            return;
        }

        $('.in_time, .out_time').prop('disabled', false);
        var roleIds = $("input[name='staff_role[]']").map(function () { return $(this).val(); }).get();
        for (var i = 0; i < roleIds.length; i++) {
            $.each(attendanceSetting, function (key, value) {
                if (parseInt(value.staff_attendence_type_id, 10) === typeId
                    && String(value.role_id) === String(roleIds[i])) {
                    $('.in_time_' + roleIds[i]).val(toTimeInput(value.entry_time_from));
                    $('.out_time_' + roleIds[i]).val(toTimeInput(value.entry_time_to));
                }
            });
        }
    });

    $(document).on('change', '.staff_att_type', function () {
        toggleTimes($(this).val(), $(this).data('staff'));
    });
});
</script>
@endpush
