@php
    $editing = $editing ?? null;
    $isEdit = $editing !== null;
@endphp

@if($errors->any())
    <div class="alert alert-danger">
        <ul style="margin:0;padding-left:18px;">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="row">
    <div class="col-md-8 col-md-offset-2">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">{{ $isEdit ? 'Edit Student Leave' : 'Add Student Leave' }}</h3>
                <div class="box-tools pull-right">
                    <a href="{{ route('leave.student_approve.index') }}" class="btn btn-default btn-sm">Back</a>
                </div>
            </div>
            <form method="post" enctype="multipart/form-data"
                  action="{{ $isEdit ? route('leave.student_approve.update', $editing['id']) : route('leave.student_approve.store') }}">
                @csrf
                <div class="box-body">
                    <div class="form-group">
                        <label>Class <span class="text-danger">*</span></label>
                        <select name="class" id="class_id" class="form-control" required
                            @if(! $isEdit)
                                onchange="window.location='{{ route('leave.student_approve.create') }}?class_id='+this.value"
                            @endif>
                            <option value="">Select</option>
                            @foreach($classes as $class)
                                <option value="{{ $class->id }}" @selected((int) old('class', $class_id) === (int) $class->id)>{{ $class->class }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Section <span class="text-danger">*</span></label>
                        <select name="section" id="section_id" class="form-control" required
                            @if(! $isEdit)
                                onchange="window.location='{{ route('leave.student_approve.create') }}?class_id={{ (int)$class_id }}&section_id='+this.value"
                            @endif>
                            <option value="">Select</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Student <span class="text-danger">*</span></label>
                        <select name="student" class="form-control" required>
                            <option value="">Select</option>
                            @foreach($students as $stu)
                                @php
                                    $label = trim(($stu['firstname'] ?? '').' '.($stu['middlename'] ?? '').' '.($stu['lastname'] ?? ''));
                                @endphp
                                <option value="{{ $stu['student_session_id'] }}"
                                    @selected((int) old('student', $editing['student_session_id'] ?? 0) === (int) $stu['student_session_id'])>
                                    {{ $label }} ({{ $stu['admission_no'] ?? '' }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Apply Date <span class="text-danger">*</span></label>
                        <input type="date" name="apply_date" class="form-control" required
                               value="{{ old('apply_date', $editing['apply_date'] ?? date('Y-m-d')) }}">
                    </div>
                    <div class="form-group">
                        <label>From Date <span class="text-danger">*</span></label>
                        <input type="date" name="from_date" class="form-control" required
                               value="{{ old('from_date', $editing['from_date'] ?? '') }}">
                    </div>
                    <div class="form-group">
                        <label>To Date <span class="text-danger">*</span></label>
                        <input type="date" name="to_date" class="form-control" required
                               value="{{ old('to_date', $editing['to_date'] ?? '') }}">
                    </div>
                    <div class="form-group">
                        <label>Leave Status <span class="text-danger">*</span></label>
                        <select name="leave_status" class="form-control" required>
                            <option value="0" @selected((string) old('leave_status', $editing['status'] ?? '0') === '0')>Pending</option>
                            <option value="1" @selected((string) old('leave_status', $editing['status'] ?? '') === '1')>Approved</option>
                            <option value="2" @selected((string) old('leave_status', $editing['status'] ?? '') === '2')>Disapproved</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Reason</label>
                        <textarea name="message" class="form-control" rows="3">{{ old('message', $editing['reason'] ?? '') }}</textarea>
                    </div>
                    <div class="form-group">
                        <label>Attach Document</label>
                        <input type="file" name="userfile" class="form-control">
                        @if(!empty($editing['docs']))
                            <p class="help-block">Current: {{ $editing['docs'] }}</p>
                        @endif
                    </div>
                </div>
                <div class="box-footer">
                    <button type="submit" class="btn btn-info pull-right">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function () {
    var oldSection = @json((string) old('section', $section_id));
    function loadSections(classId, selected) {
        var $sec = $('#section_id');
        $sec.html('<option value="">Select</option>');
        if (!classId) return;
        $.getJSON('{{ url('sections/getByClass') }}', {class_id: classId}, function (data) {
            $.each(data, function (_, row) {
                var id = row.id || row.section_id;
                var name = row.section || row.name;
                var opt = $('<option>').val(id).text(name);
                if (String(selected) === String(id)) opt.prop('selected', true);
                $sec.append(opt);
            });
        });
    }
    @if($isEdit)
    loadSections($('#class_id').val(), oldSection);
    @else
    loadSections($('#class_id').val(), oldSection);
    @endif
})();
</script>
