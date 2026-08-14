@php
    $filterClassId = old('class_id', $filters['class_id'] ?? '');
    $filterSectionId = old('section_id', $filters['section_id'] ?? '');
    $filterGroupId = old('subject_group_id', $filters['subject_group_id'] ?? '');
    $filterSubjectId = old('subject_id', $filters['subject_id'] ?? '');
    $redirectUrl = url()->full();
@endphp

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
    <div class="box-header">
        <h3 class="box-title"><i class="fa fa-search"></i> Select Criteria</h3>
    </div>
    <form method="post" action="{{ route('lessonplan.syllabus.status') }}" accept-charset="utf-8">
        @csrf
        <div class="box-body">
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Class <span class="text-danger">*</span></label>
                        <select autofocus id="st_class" name="class_id" class="form-control" required>
                            <option value="">Select</option>
                            @foreach($classes as $class)
                                <option value="{{ $class->id }}" @selected((string) $filterClassId === (string) $class->id)>
                                    {{ $class->class }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Section <span class="text-danger">*</span></label>
                        <select id="st_section" name="section_id" class="form-control" required>
                            <option value="">Select</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Subject Group <span class="text-danger">*</span></label>
                        <select id="st_subject_group" name="subject_group_id" class="form-control" required>
                            <option value="">Select</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Subject <span class="text-danger">*</span></label>
                        <select id="st_subject" name="subject_id" class="form-control" required>
                            <option value="">Select</option>
                        </select>
                    </div>
                </div>
            </div>
            <button type="submit" name="search" value="search_filter" class="btn btn-primary btn-sm pull-right">
                <i class="fa fa-search"></i> Search
            </button>
        </div>
    </form>

    @if($searched)
        <div class="box-header">
            <h3 class="box-title">
                Syllabus Status for: {{ $tree['subject_name'] ?? '' }}
            </h3>
        </div>
        <div class="box-body">
            @if(empty($tree['lessons']))
                <p class="text-center">No record found.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th width="40">#</th>
                                <th>Lesson / Topic</th>
                                <th>Completion Date</th>
                                <th>Status</th>
                                @if(! empty($canEdit))
                                    <th class="text-right">Action</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @php $lessonCount = 1; @endphp
                            @foreach($tree['lessons'] as $lesson)
                                <tr>
                                    <td>{{ $lessonCount }}</td>
                                    <td colspan="{{ ! empty($canEdit) ? 4 : 3 }}">
                                        <strong>{{ $lesson['name'] }}</strong>
                                    </td>
                                </tr>
                                @forelse($lesson['topic'] ?? [] as $topic)
                                    @php $topicCount = $loop->iteration; @endphp
                                    <tr>
                                        <td></td>
                                        <td>{{ $lessonCount }}.{{ $topicCount }} {{ $topic['name'] }}</td>
                                        <td>
                                            @if((int) ($topic['status'] ?? 0) === 1 && ! empty($topic['complete_date']) && $topic['complete_date'] !== '0000-00-00')
                                                {{ $topic['complete_date'] }}
                                            @else
                                                &nbsp;
                                            @endif
                                        </td>
                                        <td>
                                            @if((int) ($topic['status'] ?? 0) === 1)
                                                <span class="label" style="background:#0e0e0e">Complete</span>
                                            @else
                                                <span class="label" style="background:#b3b3b3">Incomplete</span>
                                            @endif
                                        </td>
                                        @if(! empty($canEdit))
                                            <td class="text-right">
                                                @if((int) ($topic['status'] ?? 0) === 1)
                                                    <form method="post"
                                                          action="{{ route('lessonplan.topics.incomplete', (int) $topic['id']) }}"
                                                          style="display:inline;">
                                                        @csrf
                                                        <input type="hidden" name="redirect" value="{{ $redirectUrl }}">
                                                        <button type="submit" class="btn btn-xs btn-default">Mark Incomplete</button>
                                                    </form>
                                                @else
                                                    <form method="post"
                                                          action="{{ route('lessonplan.topics.complete', (int) $topic['id']) }}"
                                                          class="form-inline"
                                                          style="display:inline-block;">
                                                        @csrf
                                                        <input type="hidden" name="redirect" value="{{ $redirectUrl }}">
                                                        <input type="date" name="date" class="form-control input-sm"
                                                               value="{{ date('Y-m-d') }}" required style="width:auto;display:inline-block;">
                                                        <button type="submit" class="btn btn-xs btn-primary">Mark Complete</button>
                                                    </form>
                                                @endif
                                            </td>
                                        @endif
                                    </tr>
                                @empty
                                    <tr>
                                        <td></td>
                                        <td colspan="{{ ! empty($canEdit) ? 4 : 3 }}" class="text-muted">No topics</td>
                                    </tr>
                                @endforelse
                                @php $lessonCount++; @endphp
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @endif
</div>

@push('scripts')
<script>
(function () {
    var selectedClass = @json((string) $filterClassId);
    var selectedSection = @json((string) $filterSectionId);
    var selectedGroup = @json((string) $filterGroupId);
    var selectedSubject = @json((string) $filterSubjectId);
    var sectionUrl = @json(url('sections/getByClass'));
    var groupUrl = @json(route('academics.subject_groups.getByClassSection'));
    var subjectUrl = @json(route('academics.subject_groups.getGroupSubjects'));
    var csrf = @json(csrf_token());

    function loadSections(preserve) {
        var classId = $('#st_class').val();
        var $section = $('#st_section');
        $section.html('<option value="">Select</option>');
        $('#st_subject_group').html('<option value="">Select</option>');
        $('#st_subject').html('<option value="">Select</option>');
        if (!classId) return;

        $.getJSON(sectionUrl, {class_id: classId}, function (rows) {
            (rows || []).forEach(function (row) {
                var id = row.section_id || row.id;
                var selected = preserve && String(id) === String(selectedSection) ? ' selected' : '';
                $section.append('<option value="' + id + '"' + selected + '>' + (row.section || '') + '</option>');
            });
            if (preserve && selectedSection) {
                loadGroups(true);
            }
        });
    }

    function loadGroups(preserve) {
        var classId = $('#st_class').val();
        var sectionId = $('#st_section').val();
        var $group = $('#st_subject_group');
        $group.html('<option value="">Select</option>');
        $('#st_subject').html('<option value="">Select</option>');
        if (!classId || !sectionId) return;

        $.post(groupUrl, {_token: csrf, class_id: classId, section_id: sectionId}, function (rows) {
            (rows || []).forEach(function (row) {
                var id = row.subject_group_id || row.id;
                var selected = preserve && String(id) === String(selectedGroup) ? ' selected' : '';
                $group.append('<option value="' + id + '"' + selected + '>' + (row.name || '') + '</option>');
            });
            if (preserve && selectedGroup) {
                loadSubjects(selectedGroup, true);
            }
        });
    }

    function loadSubjects(groupId, preserve) {
        var $subject = $('#st_subject');
        $subject.html('<option value="">Select</option>');
        if (!groupId) return;
        $.post(subjectUrl, {_token: csrf, subject_group_id: groupId}, function (rows) {
            (rows || []).forEach(function (row) {
                var label = (row.name || '') + (row.code ? ' (' + row.code + ')' : '');
                var selected = preserve && String(row.id) === String(selectedSubject) ? ' selected' : '';
                $subject.append('<option value="' + row.id + '"' + selected + '>' + label + '</option>');
            });
        });
    }

    $('#st_class').on('change', function () {
        selectedSection = '';
        selectedGroup = '';
        selectedSubject = '';
        loadSections(false);
    });
    $('#st_section').on('change', function () {
        selectedGroup = '';
        selectedSubject = '';
        loadGroups(false);
    });
    $('#st_subject_group').on('change', function () {
        selectedSubject = '';
        loadSubjects($(this).val(), false);
    });

    if (selectedClass) {
        loadSections(true);
    }
})();
</script>
@endpush
