@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">Staff Details</h3>
        <div class="box-tools">
            <a href="{{ route('staff.index') }}" class="btn btn-default btn-sm">{{ __('system.back') }}</a>
            @if($canEditStaff)
                <a href="{{ route('staff.edit', $staffProfile->id) }}" class="btn btn-primary btn-sm">{{ __('system.edit') }}</a>
            @endif
        </div>
    </div>
    <div class="box-body">
        <div class="row">
            <div class="col-md-6">
                <table class="table table-bordered">
                    <tr>
                        <th>{{ __('system.staff_id') }}</th>
                        <td>{{ $staffProfile->employee_id }}</td>
                    </tr>
                    <tr>
                        <th>{{ __('system.name') }}</th>
                        <td>{{ trim($staffProfile->name.' '.$staffProfile->surname) }}</td>
                    </tr>
                    <tr>
                        <th>{{ __('system.role') }}</th>
                        <td>{{ $staffProfile->role_name ?? '' }}</td>
                    </tr>
                    <tr>
                        <th>{{ __('system.department') }}</th>
                        <td>{{ $staffProfile->department_label ?? '' }}</td>
                    </tr>
                    <tr>
                        <th>{{ __('system.designation') }}</th>
                        <td>{{ $staffProfile->designation_label ?? '' }}</td>
                    </tr>
                    <tr>
                        <th>{{ __('system.email') }}</th>
                        <td>{{ $staffProfile->email }}</td>
                    </tr>
                    <tr>
                        <th>{{ __('system.phone') }}</th>
                        <td>{{ $staffProfile->contact_no }}</td>
                    </tr>
                    <tr>
                        <th>{{ __('system.gender') }}</th>
                        <td>{{ $staffProfile->gender }}</td>
                    </tr>
                    <tr>
                        <th>{{ __('system.date_of_birth') }}</th>
                        <td>{{ $staffProfile->dob }}</td>
                    </tr>
                    <tr>
                        <th>{{ __('system.date_of_joining') }}</th>
                        <td>{{ $staffProfile->date_of_joining }}</td>
                    </tr>
                    <tr>
                        <th>{{ __('system.status') }}</th>
                        <td>{{ (int) $staffProfile->is_active === 1 ? __('system.active') : __('system.disabled') }}</td>
                    </tr>
                    @if(!empty($staffProfile->disable_at) && $staffProfile->disable_at !== '0000-00-00')
                        <tr>
                            <th>{{ __('system.disable_date') }}</th>
                            <td>{{ $staffProfile->disable_at }}</td>
                        </tr>
                    @endif
                </table>
            </div>
            <div class="col-md-6">
                @if($customFields->isNotEmpty())
                    <h4>{{ __('system.custom_fields') }}</h4>
                    <table class="table table-bordered">
                        @foreach($customFields as $field)
                            <tr>
                                <th>{{ $field->name }}</th>
                                <td>{{ $customFieldValues[$field->id] ?? '' }}</td>
                            </tr>
                        @endforeach
                    </table>
                @endif
            </div>
        </div>

        @if($enableDisable && $canDisableStaff)
            <div class="row">
                <div class="col-md-12">
                    @if((int) $staffProfile->is_active === 1)
                        <form method="post" action="{{ route('staff.disable', $staffProfile->id) }}" class="form-inline">
                            @csrf
                            <button type="submit" class="btn btn-warning">{{ __('system.disable') }}</button>
                        </form>
                    @else
                        <a href="{{ route('staff.enable', $staffProfile->id) }}" class="btn btn-success">{{ __('system.enable') }}</a>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">{{ __('system.attendance') }}</h3>
    </div>
    <div class="box-body">
        <div class="row">
            <div class="col-md-3">
                <label for="attendance_year">{{ __('system.year') }}</label>
                <select class="form-control" id="attendance_year" name="year">
                    @php($years = $attendanceYears ?? [])
                    @if($years === [])
                        <option value="{{ $defaultAttendanceYear ?? date('Y') }}">{{ $defaultAttendanceYear ?? date('Y') }}</option>
                    @else
                        @foreach($years as $yearOption)
                            <option value="{{ $yearOption }}" @selected((int) $yearOption === (int) ($defaultAttendanceYear ?? date('Y')))>{{ $yearOption }}</option>
                        @endforeach
                    @endif
                </select>
            </div>
            <div class="col-md-9">
                <table class="table table-bordered" style="margin-top: 24px;">
                    <tr>
                        <th>{{ __('system.present') }}</th>
                        <td class="total_present">0</td>
                        <th>{{ __('system.late') }}</th>
                        <td class="total_late">0</td>
                        <th>{{ __('system.absent') }}</th>
                        <td class="total_absent">0</td>
                    </tr>
                    <tr>
                        <th>{{ __('system.half_day') }}</th>
                        <td class="total_half_day">0</td>
                        <th>{{ __('system.holiday') }}</th>
                        <td class="total_holiday">0</td>
                        <th>{{ __('system.half_day_second_shift') }}</th>
                        <td class="total_half_day_second_shift">0</td>
                    </tr>
                </table>
            </div>
        </div>
        <div id="ajaxattendance"></div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    var staffId = {{ (int) $staffProfile->id }};
    var url = @json(route('staff.ajax_attendance'));

    function loadAttendance(year) {
        $.ajax({
            url: url,
            type: 'POST',
            data: {
                id: staffId,
                year: year,
                _token: @json(csrf_token())
            },
            dataType: 'json',
            success: function (result) {
                $('#ajaxattendance').html(result.page || '');
                if (result.countAttendance) {
                    $('.total_present').text(result.countAttendance.present || 0);
                    $('.total_late').text(result.countAttendance.late || 0);
                    $('.total_absent').text(result.countAttendance.absent || 0);
                    $('.total_half_day').text(result.countAttendance.half_day || 0);
                    $('.total_holiday').text(result.countAttendance.holiday || 0);
                    $('.total_half_day_second_shift').text(result.countAttendance.half_day_second_shift || 0);
                }
            }
        });
    }

    $('#attendance_year').on('change', function () {
        loadAttendance(this.value);
    });

    loadAttendance($('#attendance_year').val());
})();
</script>
@endpush
