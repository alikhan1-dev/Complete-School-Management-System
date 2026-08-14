@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger">
        <ul style="margin:0;padding-left:18px;">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">Lesson Plan</h3>
        <div class="box-tools pull-right">
            @if(! empty($canEdit))
                <a href="{{ route('lessonplan.syllabus.edit', (int) $row['id']) }}" class="btn btn-primary btn-sm">Edit</a>
            @endif
            <a href="{{ route('lessonplan.syllabus.manage', ['staff_id' => $row['created_for']]) }}"
               class="btn btn-default btn-sm">Back</a>
        </div>
    </div>
    <div class="box-body">
        <div class="row">
            <div class="col-md-{{ ! empty($canViewComments) ? '6' : '12' }}">
                <table class="table table-bordered">
                    <tr>
                        <th width="25%">Class</th>
                        <td>{{ $row['cname'] }} ({{ $row['sname'] }})</td>
                    </tr>
                    <tr>
                        <th>Subject Group</th>
                        <td>{{ $row['sgname'] }}</td>
                    </tr>
                    <tr>
                        <th>Subject</th>
                        <td>
                            {{ $row['subname'] }}
                            @if(! empty($row['scode'])) ({{ $row['scode'] }}) @endif
                        </td>
                    </tr>
                    <tr>
                        <th>Date</th>
                        <td>{{ $row['date'] }}</td>
                    </tr>
                    <tr>
                        <th>Time</th>
                        <td>{{ $row['time_from'] }} - {{ $row['time_to'] }}</td>
                    </tr>
                    <tr>
                        <th>Lesson</th>
                        <td>{{ $row['lessonname'] }}</td>
                    </tr>
                    <tr>
                        <th>Topic</th>
                        <td>{{ $row['topic_name'] }}</td>
                    </tr>
                    <tr>
                        <th>Sub Topic</th>
                        <td>{!! nl2br(e($row['sub_topic'] ?? '')) !!}</td>
                    </tr>
                    <tr>
                        <th>Teaching Method</th>
                        <td>{!! nl2br(e($row['teaching_method'] ?? '')) !!}</td>
                    </tr>
                    <tr>
                        <th>General Objectives</th>
                        <td>{!! nl2br(e($row['general_objectives'] ?? '')) !!}</td>
                    </tr>
                    <tr>
                        <th>Previous Knowledge</th>
                        <td>{!! nl2br(e($row['previous_knowledge'] ?? '')) !!}</td>
                    </tr>
                    <tr>
                        <th>Comprehensive Questions</th>
                        <td>{!! nl2br(e($row['comprehensive_questions'] ?? '')) !!}</td>
                    </tr>
                    <tr>
                        <th>Presentation</th>
                        <td>{!! $row['presentation'] !!}</td>
                    </tr>
                    <tr>
                        <th>Lecture YouTube URL</th>
                        <td>
                            @if(! empty($row['lacture_youtube_url']))
                                <a href="{{ $row['lacture_youtube_url'] }}" target="_blank" rel="noopener">{{ $row['lacture_youtube_url'] }}</a>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>Attachment</th>
                        <td>
                            @if(! empty($row['attachment']))
                                <a href="{{ route('lessonplan.syllabus.download', (int) $row['id']) }}">Download</a>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>Lecture Video</th>
                        <td>
                            @if(! empty($row['lacture_video']))
                                <a href="{{ route('lessonplan.syllabus.video', (int) $row['id']) }}">Download</a>
                            @endif
                        </td>
                    </tr>
                </table>
            </div>

            @if(! empty($canViewComments))
                <div class="col-md-6">
                    <h4 class="box-title">Comments</h4>
                    <hr>
                    @if(! empty($canAddComments))
                        <form method="post" action="{{ route('lessonplan.syllabus.forum.store') }}" accept-charset="utf-8">
                            @csrf
                            <input type="hidden" name="subject_syllabus_id" value="{{ (int) $row['id'] }}">
                            <div class="form-group">
                                <textarea name="message" rows="2" class="form-control" required
                                          placeholder="Type your comment">{{ old('message') }}</textarea>
                            </div>
                            <button type="submit" class="btn btn-info btn-sm">Send</button>
                        </form>
                    @endif

                    <ul class="list-unstyled" style="margin-top:15px;">
                        @forelse($messages as $msg)
                            <li style="margin-bottom:14px;padding-bottom:10px;border-bottom:1px solid #eee;">
                                <div>
                                    <strong>
                                        @if(($msg['type'] ?? '') === 'staff')
                                            {{ trim(($msg['staff_name'] ?? '').' '.($msg['staff_surname'] ?? '')) }}
                                            @if(! empty($msg['staff_employee_id']))
                                                ({{ $msg['staff_employee_id'] }})
                                            @endif
                                        @else
                                            {{ trim(($msg['firstname'] ?? '').' '.($msg['middlename'] ?? '').' '.($msg['lastname'] ?? '')) }}
                                            @if(! empty($msg['admission_no']))
                                                ({{ $msg['admission_no'] }})
                                            @endif
                                        @endif
                                    </strong>
                                    <span class="text-muted" style="margin-left:8px;">{{ $msg['created_date'] ?? '' }}</span>
                                    @if(! empty($canDeleteComments)
                                        && ($msg['type'] ?? '') === 'staff'
                                        && (int) ($msg['staff_id'] ?? 0) === (int) $loginStaffId)
                                        <a href="{{ route('lessonplan.syllabus.forum.destroy', (int) $msg['fourm_id']) }}"
                                           class="btn btn-primary btn-xs pull-right"
                                           onclick="return confirm('Are you sure you want to delete this?')">
                                            <i class="fa fa-trash"></i>
                                        </a>
                                    @endif
                                </div>
                                <div>{{ $msg['message'] ?? '' }}</div>
                            </li>
                        @empty
                            <li class="text-muted">No comments yet.</li>
                        @endforelse
                    </ul>
                </div>
            @endif
        </div>
    </div>
</div>
