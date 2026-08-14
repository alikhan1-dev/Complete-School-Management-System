<input type="hidden" name="visitor_id" value="{{ $visitor_data['id'] }}">
<div class="row">
    <div class="col-sm-4">
        <label>Purpose <small class="req">*</small></label>
        <select name="purpose" class="form-control">
            <option value="">Select</option>
            @foreach($Purpose as $value)
                <option value="{{ $value->visitors_purpose }}" @selected($value->visitors_purpose === ($visitor_data['purpose'] ?? ''))>{{ $value->visitors_purpose }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-sm-4">
        <label>Meeting With <small class="req">*</small></label>
        <select name="edit_meeting_with" id="edit_meeting_with" class="form-control">
            <option value="">Select</option>
            @foreach($meeting_with as $key => $label)
                <option value="{{ $key }}" @selected($key === ($visitor_data['meeting_with'] ?? ''))>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div id="edit_visible_staff">
        <div class="col-sm-4">
            <label>Staff <small class="req">*</small></label>
            <select name="edit_staff_id" class="form-control">
                <option value="">Select</option>
                @foreach($stafflist as $staff)
                    <option value="{{ $staff->id }}" @selected((string)$staff->id === (string)($visitor_data['staff_id'] ?? ''))>{{ $staff->name }} {{ $staff->surname }} ({{ $staff->employee_id }})</option>
                @endforeach
            </select>
        </div>
    </div>
    <div id="edit_visible_student">
        <div class="col-sm-4">
            <label>Class <small class="req">*</small></label>
            <select id="edit_class_id" name="edit_class_id" class="form-control">
                <option value="">Select</option>
                @foreach($classlist as $class)
                    <option value="{{ $class->id }}" @selected((string)$class->id === (string)($visitor_data['class_id'] ?? ''))>{{ $class->class }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-sm-4">
            <label>Section <small class="req">*</small></label>
            <select id="edit_section_id" name="edit_class_section_id" class="form-control">
                <option value="">Select</option>
            </select>
        </div>
        <div class="col-sm-4">
            <label>Student <small class="req">*</small></label>
            <select id="edit_student_session_id" name="edit_student_session_id" class="form-control"></select>
        </div>
    </div>
    <div class="col-sm-4"><label>Visitor Name <small class="req">*</small></label><input type="text" class="form-control" name="name" value="{{ $visitor_data['name'] }}"></div>
    <div class="col-sm-4"><label>Phone</label><input type="text" class="form-control" name="contact" value="{{ $visitor_data['contact'] }}"></div>
    <div class="col-sm-4"><label>ID Card</label><input type="text" class="form-control" name="id_proof" value="{{ $visitor_data['id_proof'] }}"></div>
    <div class="col-sm-4"><label>Number Of Person</label><input type="text" class="form-control" name="pepples" value="{{ $visitor_data['no_of_people'] }}"></div>
    <div class="col-sm-4"><label>Date <small class="req">*</small></label><input type="text" class="form-control" name="date" value="{{ $visitors->formatDate($visitor_data['date'] ?? null) }}"></div>
    <div class="col-sm-4"><label>In Time</label><input type="text" class="form-control" name="time" value="{{ $visitor_data['in_time'] }}"></div>
    <div class="col-sm-4"><label>Out Time</label><input type="text" class="form-control" name="out_time" value="{{ $visitor_data['out_time'] }}"></div>
    <div class="col-sm-4"><label>Attach Document</label><input type="file" class="form-control" name="file"></div>
    <div class="col-sm-12"><label>Note</label><textarea class="form-control" name="note" rows="3">{{ $visitor_data['note'] }}</textarea></div>
</div>
<script>
$(function () {
    var class_id = $('#edit_class_id').val();
    var section_id = @json($visitor_data['section_id'] ?? '');
    getsectionbyclass(class_id, section_id);
    studentbysection(class_id, section_id, @json($visitor_data['student_session_id'] ?? ''));
    var meeting_with = @json($visitor_data['meeting_with'] ?? '');
    $('#edit_visible_staff').toggle(meeting_with === 'staff');
    $('#edit_visible_student').toggle(meeting_with === 'student');
});
$('#edit_meeting_with').change(function () {
    var v = $(this).val();
    $('#edit_visible_staff').toggle(v === 'staff');
    $('#edit_visible_student').toggle(v === 'student');
});
$('#edit_class_id').change(function () { getsectionbyclass($(this).val(), ''); });
$('#edit_section_id').change(function () {
    studentbysection($('#edit_class_id').val(), $(this).val(), '');
});
</script>
