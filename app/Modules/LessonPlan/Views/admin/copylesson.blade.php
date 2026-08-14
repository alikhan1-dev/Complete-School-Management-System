@php
    $oldSessionId = old('old_session_id', $filters['old_session_id'] ?? '');
    $oldClassId = old('old_class_id', $filters['old_class_id'] ?? '');
    $oldSectionId = old('old_section_id', $filters['old_section_id'] ?? '');
    $oldGroupId = old('old_subject_group_id', $filters['old_subject_group_id'] ?? '');
    $oldSubjectId = old('old_subject_id', $filters['old_subject_id'] ?? '');
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
        <h3 class="box-title"><i class="fa fa-search"></i> Select Old Session Details</h3>
    </div>
    <form method="post" action="{{ route('lessonplan.copy.index') }}" accept-charset="utf-8">
        @csrf
        <div class="box-body">
            <div class="row">
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Session <span class="text-danger">*</span></label>
                        <select id="old_session_id" name="old_session_id" class="form-control" required>
                            <option value="">Select</option>
                            @foreach($sessions as $session)
                                <option value="{{ $session->id }}" @selected((string) $oldSessionId === (string) $session->id)>
                                    {{ $session->session }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Class <span class="text-danger">*</span></label>
                        <select id="old_class_id" name="old_class_id" class="form-control" required>
                            <option value="">Select</option>
                            @foreach($classes as $class)
                                <option value="{{ $class->id }}" @selected((string) $oldClassId === (string) $class->id)>
                                    {{ $class->class }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Section <span class="text-danger">*</span></label>
                        <select id="old_section_id" name="old_section_id" class="form-control" required>
                            <option value="">Select</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Subject Group <span class="text-danger">*</span></label>
                        <select id="old_subject_group_id" name="old_subject_group_id" class="form-control" required>
                            <option value="">Select</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Subject <span class="text-danger">*</span></label>
                        <select id="old_subject_id" name="old_subject_id" class="form-control" required>
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

    @if($searched && empty($tree['lessons']))
        <div class="box-header">
            <div class="alert alert-danger"><center>No record found.</center></div>
        </div>
    @endif

    @if($searched && ! empty($tree['lessons']))
        <div class="box-header">
            <h3 class="box-title">Syllabus Status for: {{ $tree['subject_name'] }}</h3>
        </div>
        <div class="box-body">
            <form method="post" action="{{ route('lessonplan.copy.store') }}" accept-charset="utf-8">
                @csrf
                <div class="row">
                    <div class="col-md-8">
                        <h4>Lesson Topics — select topics to copy</h4>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th width="20%">#</th>
                                        <th>Lesson / Topic</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php $lessonCount = 1; @endphp
                                    @foreach($tree['lessons'] as $lesson)
                                        <tr>
                                            <td>{{ $lessonCount }}</td>
                                            <td>
                                                <h4>{{ $lesson['name'] }}</h4>
                                                <ul style="list-style:none;padding-left:0;">
                                                    @forelse($lesson['topic'] ?? [] as $topic)
                                                        <li>
                                                            <label class="checkbox-inline">
                                                                <input type="checkbox"
                                                                       name="topic_id[{{ (int) $lesson['id'] }}][]"
                                                                       value="{{ (int) $topic['id'] }}">
                                                                {{ $lessonCount }}.{{ $loop->iteration }} {{ $topic['name'] }}
                                                            </label>
                                                        </li>
                                                    @empty
                                                        <li class="text-muted">No topics</li>
                                                    @endforelse
                                                </ul>
                                            </td>
                                        </tr>
                                        @php $lessonCount++; @endphp
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <h4>Select destination subject</h4>
                        <div class="form-group">
                            <label>Class <span class="text-danger">*</span></label>
                            <select id="class_id" name="class_id" class="form-control" required>
                                <option value="">Select</option>
                                @foreach($classes as $class)
                                    <option value="{{ $class->id }}">{{ $class->class }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Section <span class="text-danger">*</span></label>
                            <select id="section_id" name="section_id" class="form-control" required>
                                <option value="">Select</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Subject Group <span class="text-danger">*</span></label>
                            <select id="subject_group_id" name="subject_group_id" class="form-control" required>
                                <option value="">Select</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Subject <span class="text-danger">*</span></label>
                            <select id="subject_id" name="subject_group_subject_id" class="form-control" required>
                                <option value="">Select</option>
                            </select>
                        </div>
                        @if(! empty($canSave))
                            <button type="submit" class="btn btn-primary pull-right">Save</button>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    @endif
</div>

@push('scripts')
<script>
(function () {
    var selectedOldClass = @json((string) $oldClassId);
    var selectedOldSection = @json((string) $oldSectionId);
    var selectedOldGroup = @json((string) $oldGroupId);
    var selectedOldSubject = @json((string) $oldSubjectId);
    var sectionUrl = @json(url('sections/getByClass'));
    var groupUrl = @json(route('academics.subject_groups.getByClassSection'));
    var subjectUrl = @json(route('academics.subject_groups.getGroupSubjects'));
    var csrf = @json(csrf_token());

    function loadSections(classSelect, sectionSelect, selectedSection, preserve, after) {
        var classId = $(classSelect).val();
        var $section = $(sectionSelect);
        $section.html('<option value="">Select</option>');
        if (!classId) {
            if (after) after();
            return;
        }
        $.getJSON(sectionUrl, {class_id: classId}, function (rows) {
            (rows || []).forEach(function (row) {
                var id = row.section_id || row.id;
                var selected = preserve && String(id) === String(selectedSection) ? ' selected' : '';
                $section.append('<option value="' + id + '"' + selected + '>' + (row.section || '') + '</option>');
            });
            if (after) after();
        });
    }

    function loadGroups(classSelect, sectionSelect, groupSelect, subjectSelect, sessionId, selectedGroup, selectedSubject, preserve) {
        var classId = $(classSelect).val();
        var sectionId = $(sectionSelect).val();
        var $group = $(groupSelect);
        $group.html('<option value="">Select</option>');
        $(subjectSelect).html('<option value="">Select</option>');
        if (!classId || !sectionId) return;

        var payload = {_token: csrf, class_id: classId, section_id: sectionId};
        if (sessionId) payload.session_id = sessionId;

        $.post(groupUrl, payload, function (rows) {
            (rows || []).forEach(function (row) {
                var id = row.subject_group_id || row.id;
                var selected = preserve && String(id) === String(selectedGroup) ? ' selected' : '';
                $group.append('<option value="' + id + '"' + selected + '>' + (row.name || '') + '</option>');
            });
            if (preserve && selectedGroup) {
                loadSubjects(groupSelect, subjectSelect, sessionId, selectedSubject, true);
            }
        });
    }

    function loadSubjects(groupSelect, subjectSelect, sessionId, selectedSubject, preserve) {
        var groupId = $(groupSelect).val();
        var $subject = $(subjectSelect);
        $subject.html('<option value="">Select</option>');
        if (!groupId) return;
        var payload = {_token: csrf, subject_group_id: groupId};
        if (sessionId) payload.session_id = sessionId;
        $.post(subjectUrl, payload, function (rows) {
            (rows || []).forEach(function (row) {
                var label = (row.name || '') + (row.code ? ' (' + row.code + ')' : '');
                var selected = preserve && String(row.id) === String(selectedSubject) ? ' selected' : '';
                $subject.append('<option value="' + row.id + '"' + selected + '>' + label + '</option>');
            });
        });
    }

    // Old session cascade
    $('#old_class_id').on('change', function () {
        selectedOldSection = '';
        selectedOldGroup = '';
        selectedOldSubject = '';
        $('#old_subject_group_id').html('<option value="">Select</option>');
        $('#old_subject_id').html('<option value="">Select</option>');
        loadSections('#old_class_id', '#old_section_id', '', false);
    });
    $('#old_section_id').on('change', function () {
        selectedOldGroup = '';
        selectedOldSubject = '';
        loadGroups('#old_class_id', '#old_section_id', '#old_subject_group_id', '#old_subject_id',
            $('#old_session_id').val(), '', '', false);
    });
    $('#old_subject_group_id').on('change', function () {
        selectedOldSubject = '';
        loadSubjects('#old_subject_group_id', '#old_subject_id', $('#old_session_id').val(), '', false);
    });
    $('#old_session_id').on('change', function () {
        selectedOldGroup = '';
        selectedOldSubject = '';
        if ($('#old_class_id').val() && $('#old_section_id').val()) {
            loadGroups('#old_class_id', '#old_section_id', '#old_subject_group_id', '#old_subject_id',
                $(this).val(), '', '', false);
        }
    });

    // Destination cascade (current session)
    $('#class_id').on('change', function () {
        $('#subject_group_id').html('<option value="">Select</option>');
        $('#subject_id').html('<option value="">Select</option>');
        loadSections('#class_id', '#section_id', '', false);
    });
    $('#section_id').on('change', function () {
        loadGroups('#class_id', '#section_id', '#subject_group_id', '#subject_id', null, '', '', false);
    });
    $('#subject_group_id').on('change', function () {
        loadSubjects('#subject_group_id', '#subject_id', null, '', false);
    });

    if (selectedOldClass) {
        loadSections('#old_class_id', '#old_section_id', selectedOldSection, true, function () {
            if (selectedOldSection) {
                loadGroups('#old_class_id', '#old_section_id', '#old_subject_group_id', '#old_subject_id',
                    $('#old_session_id').val(), selectedOldGroup, selectedOldSubject, true);
            }
        });
    }
})();
</script>
@endpush
