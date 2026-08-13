@php
    $sch = $schSetting ?? null;
    $result = $result ?? [];
    $imageFile = !empty($result['image']) ? $result['image'] : 'no_image.png';
    $imageUrl = asset('uploads/staff_images/'.$imageFile);
    $monthAttendance = $monthAttendance ?? [];
    $monthLeaves = $monthLeaves ?? [];
    $attendanceType = $attendanceType ?? collect();
@endphp

<div class="row">
    <div class="col-md-8 col-sm-12">
        <div class="sfborder">
            <div class="col-md-2">
                <img width="115" height="115" class="round5" src="{{ $imageUrl }}" alt="No Image"
                     onerror="this.src='{{ asset('uploads/staff_images/no_image.png') }}'">
            </div>
            <div class="col-md-10">
                <table class="table mb0 font13">
                    <tbody>
                        <tr>
                            <th>Name</th>
                            <td>{{ trim(($result['name'] ?? '').' '.($result['surname'] ?? '')) }}</td>
                            <th>Staff ID</th>
                            <td>{{ $result['employee_id'] ?? '' }}</td>
                        </tr>
                        <tr>
                            @if(!empty($sch?->staff_phone))
                                <th>Phone</th>
                            @endif
                            <td>{{ $result['contact_no'] ?? '' }}</td>
                            <th>Email</th>
                            <td>{{ $result['email'] ?? '' }}</td>
                        </tr>
                        <tr>
                            @if(!empty($sch?->staff_epf_no))
                                <th>EPF No</th>
                                <td>{{ $result['epf_no'] ?? '' }}</td>
                            @endif
                            <th>Role</th>
                            <td>{{ $result['user_type'] ?? '' }}</td>
                        </tr>
                        <tr>
                            @if(!empty($sch?->staff_department))
                                <th>Department</th>
                                <td>{{ $result['department'] ?? '' }}</td>
                            @endif
                            @if(!empty($sch?->staff_designation))
                                <th>Designation</th>
                                <td>{{ $result['designation'] ?? '' }}</td>
                            @endif
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-sm-12">
        <div class="sfborder">
            <h4>Attendance</h4>
            <table class="table mb0 font13">
                <tr>
                    <th>Month</th>
                    @foreach($attendanceType as $value)
                        <th><span title="{{ $value->type }}">{{ strip_tags((string) $value->key_value) }}</span></th>
                    @endforeach
                    <th><span title="Approved Leave">V</span></th>
                </tr>
                @foreach($monthAttendance as $attendenceKey => $attendenceValue)
                    <tr>
                        <td>{{ date('F', strtotime($attendenceKey)) }}</td>
                        <td>{{ $attendenceValue['present'] ?? 0 }}</td>
                        <td>{{ $attendenceValue['late'] ?? 0 }}</td>
                        <td>{{ $attendenceValue['absent'] ?? 0 }}</td>
                        <td>{{ $attendenceValue['half_day'] ?? 0 }}</td>
                        <td>{{ $attendenceValue['holiday'] ?? 0 }}</td>
                        <td>{{ $monthLeaves[date('m', strtotime($attendenceKey))] ?? 0 }}</td>
                    </tr>
                @endforeach
            </table>
            <p class="text-muted" style="margin-top:8px;">Allotted leave: {{ $alloted_leave ?? 0 }}</p>
        </div>
    </div>
</div>
