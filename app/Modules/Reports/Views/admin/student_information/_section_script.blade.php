@php
    $selectedClass = $filters['class_id'] ?? '';
    $selectedSection = $filters['section_id'] ?? '';
@endphp
<script>
$(function () {
    function loadSections(classId, selected) {
        var $section = $('#section_id');
        $section.html('<option value="">{{ __('system.select') }}</option>');
        if (!classId) {
            return;
        }
        $.getJSON(@json(url('sections/getByClass')), {class_id: classId}, function (data) {
            $.each(data, function (i, obj) {
                var sel = String(selected) === String(obj.section_id) ? ' selected' : '';
                $section.append('<option value="' + obj.section_id + '"' + sel + '>' + obj.section + '</option>');
            });
        });
    }
    loadSections($('#class_id').val(), @json($selectedSection));
    $('#class_id').on('change', function () {
        loadSections($(this).val(), '');
    });
});
</script>
