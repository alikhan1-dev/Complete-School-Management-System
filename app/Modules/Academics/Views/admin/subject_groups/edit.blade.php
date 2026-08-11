@if($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

@php
    $sectionArray = old('sections', $selectedClassSectionIds);
@endphp

<div class="row">
    <div class="col-md-4">
        <div class="box box-primary">
            <div class="box-header with-border"><h3 class="box-title">Edit Subject Group</h3></div>
            <form method="post" action="{{ route('academics.subject_groups.update', $group->id) }}">
                @csrf
                <input type="hidden" name="id" value="{{ $group->id }}">
                @foreach($selectedClassSectionIds as $csId)
                    <input type="hidden" name="prev_sections[]" value="{{ $csId }}">
                @endforeach
                @foreach($selectedSubjectIds as $subjectId)
                    <input type="hidden" name="prev_subjects[]" value="{{ $subjectId }}">
                @endforeach
                <div class="box-body">
                    <div class="form-group">
                        <label>Name</label> <small class="req">*</small>
                        <input type="text" name="name" class="form-control"
                               value="{{ old('name', $group->name) }}" autofocus required>
                    </div>
                    <div class="form-group">
                        <label>Class</label> <small class="req">*</small>
                        <select id="class_id" name="class_id" class="form-control" required>
                            <option value="">Select</option>
                            @foreach($classes as $class)
                                <option value="{{ $class->id }}"
                                    @selected((string) old('class_id', $classId) === (string) $class->id)>
                                    {{ $class->class }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Sections</label> <small class="req">*</small>
                        <div class="section_checkbox">No section</div>
                    </div>
                    <div class="form-group">
                        <label>Subject</label> <small class="req">*</small>
                        @foreach($subjects as $subject)
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" name="subject[]" value="{{ $subject->id }}"
                                        {{ in_array($subject->id, old('subject', $selectedSubjectIds)) ? 'checked' : '' }}>
                                    {{ $subject->name }}
                                </label>
                            </div>
                        @endforeach
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="3">{{ old('description', $group->description) }}</textarea>
                    </div>
                </div>
                <div class="box-footer">
                    <a href="{{ route('academics.subject_groups.index') }}" class="btn btn-default">Cancel</a>
                    <button type="submit" class="btn btn-info pull-right">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    var post_section_array = @json(array_map('strval', $sectionArray));

    $(function () {
        getSectionByClass('{{ old('class_id', $classId ?? 0) }}', post_section_array);
        $(document).on('change', '#class_id', function () {
            getSectionByClass($(this).val(), []);
        });
    });

    function getSectionByClass(class_id, section_array) {
        $('.section_checkbox').html('No section');
        if (!class_id || class_id === '0') {
            return;
        }
        $.ajax({
            type: 'GET',
            url: '{{ url('sections/getByClass') }}',
            data: {class_id: class_id},
            dataType: 'json',
            success: function (data) {
                var html = '';
                $.each(data, function (i, obj) {
                    var check = (jQuery.inArray(String(obj.id), section_array) !== -1) ? 'checked' : '';
                    html += "<div class='checkbox'><label>";
                    html += "<input type='checkbox' name='sections[]' value='" + obj.id + "' " + check + ">" + obj.section;
                    html += "</label></div>";
                });
                $('.section_checkbox').html(html || 'No section');
            },
            error: function () {
                alert('Error occurred, please try again');
            }
        });
    }
</script>
@endpush
