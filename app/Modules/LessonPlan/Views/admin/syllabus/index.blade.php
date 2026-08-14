@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-search"></i> Manage Lesson Plan</h3>
    </div>
    <div class="box-body">
        @if(! $isTeacher)
            <form method="get" action="{{ route('lessonplan.syllabus.manage') }}" class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Teachers <span class="text-danger">*</span></label>
                        <select name="staff_id" class="form-control" required>
                            <option value="">Select</option>
                            @foreach($teachers as $teacher)
                                <option value="{{ $teacher->id }}" @selected((int) $staffId === (int) $teacher->id)>
                                    {{ $teacher->name }} {{ $teacher->surname }} ({{ $teacher->employee_id }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label style="visibility:hidden;display:block;">Search</label>
                        <button type="submit" class="btn btn-primary btn-sm">Search</button>
                    </div>
                </div>
            </form>
        @endif

        @if($staffId > 0 && $grid !== null)
            <div class="text-center" style="margin:15px 0;">
                <a class="btn btn-default btn-sm"
                   href="{{ route('lessonplan.syllabus.manage', ['staff_id' => $staffId, 'week_start' => $meta['prev_week_start']]) }}">
                    <i class="fa fa-angle-left"></i>
                </a>
                <strong style="margin:0 12px;">
                    {{ $meta['week_start'] }} to {{ $meta['week_end'] }}
                </strong>
                <a class="btn btn-default btn-sm"
                   href="{{ route('lessonplan.syllabus.manage', ['staff_id' => $staffId, 'week_start' => $meta['next_week_start']]) }}">
                    <i class="fa fa-angle-right"></i>
                </a>
            </div>

            <div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            @foreach($meta['days'] as $i => $day)
                                @php $colDate = \Carbon\Carbon::parse($meta['week_start'])->addDays($i)->toDateString(); @endphp
                                <th class="text-center" style="width:14%;">
                                    {{ $day }}<br>
                                    <small>{{ $colDate }}</small>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            @foreach($meta['days'] as $day)
                                <td class="v-align-top" style="vertical-align:top;">
                                    @forelse($grid[$day] ?? [] as $slot)
                                        <div style="margin-bottom:12px;border:1px solid #ddd;padding:8px;">
                                            <div class="text-right" style="margin-bottom:6px;">
                                                @if((int) $slot['syllabus_id'] > 0)
                                                    <a href="{{ route('lessonplan.syllabus.show', $slot['syllabus_id']) }}"
                                                       class="btn btn-primary btn-xs" title="View">
                                                        <i class="fa fa-reorder"></i>
                                                    </a>
                                                    @if(! empty($canEdit))
                                                        <a href="{{ route('lessonplan.syllabus.edit', [
                                                                $slot['syllabus_id'],
                                                                'week_start' => $meta['week_start'],
                                                            ]) }}"
                                                           class="btn btn-primary btn-xs" title="Edit">
                                                            <i class="fa fa-pencil"></i>
                                                        </a>
                                                    @endif
                                                    @if(! empty($canDelete))
                                                        <a href="{{ route('lessonplan.syllabus.destroy', [
                                                                $slot['syllabus_id'],
                                                                'week_start' => $meta['week_start'],
                                                            ]) }}"
                                                           class="btn btn-primary btn-xs" title="Delete"
                                                           onclick="return confirm('Are you sure you want to delete this?')">
                                                            <i class="fa fa-remove"></i>
                                                        </a>
                                                    @endif
                                                @elseif(! empty($canAdd) && (int) $slot['subject_group_class_sections_id'] > 0)
                                                    <a href="{{ route('lessonplan.syllabus.create', [
                                                            'subject_group_subject_id' => $slot['subject_group_subject_id'],
                                                            'subject_group_class_sections_id' => $slot['subject_group_class_sections_id'],
                                                            'time_from' => $slot['time_from'],
                                                            'time_to' => $slot['time_to'],
                                                            'date' => $slot['date'],
                                                            'created_for' => $staffId,
                                                            'week_start' => $meta['week_start'],
                                                        ]) }}"
                                                       class="btn btn-primary btn-xs" title="Add">
                                                        <i class="fa fa-plus"></i>
                                                    </a>
                                                @endif
                                            </div>
                                            <div><i class="fa fa-book"></i>
                                                Subject: {{ $slot['subject_name'] }}
                                                @if($slot['subject_code'] !== '')
                                                    ({{ $slot['subject_code'] }})
                                                @endif
                                            </div>
                                            <div><i class="fa fa-clock-o"></i>
                                                Class: {{ $slot['class'] }} ({{ $slot['section'] }})
                                                <strong>{{ $slot['time_from'] }}</strong> - <strong>{{ $slot['time_to'] }}</strong>
                                            </div>
                                            <div><i class="fa fa-building"></i> Room No: {{ $slot['room_no'] }}</div>
                                        </div>
                                    @empty
                                        <div class="text-danger"><i class="fa fa-times-circle"></i> Not Scheduled</div>
                                    @endforelse
                                </td>
                            @endforeach
                        </tr>
                    </tbody>
                </table>
            </div>
        @elseif($isTeacher)
            <div class="alert alert-info">No timetable found for this week.</div>
        @endif
    </div>
</div>
