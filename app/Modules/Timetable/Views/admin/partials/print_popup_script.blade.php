@push('scripts')
<script>
function timetablePrintPopup(html) {
    var frameDoc = window.open('', 'Print-Window');
    if (!frameDoc) {
        alert('Popup blocked. Allow popups to print.');
        return;
    }
    frameDoc.document.open();
    frameDoc.document.write(html);
    frameDoc.document.close();
    frameDoc.onload = function () {
        frameDoc.focus();
        frameDoc.print();
    };
}

$(document).on('click', '.print_class_timetable', function (e) {
    e.preventDefault();
    var $btn = $(this);
    var classId = $btn.data('class_id');
    var sectionId = $btn.data('section_id');
    if (!classId || !sectionId) return;
    $btn.prop('disabled', true);
    $.ajax({
        type: 'POST',
        url: '{{ route('timetable.print_class') }}',
        data: {
            _token: '{{ csrf_token() }}',
            class_id: classId,
            section_id: sectionId
        },
        dataType: 'json',
        success: function (data) {
            if (String(data.status) === '1' && data.page) {
                timetablePrintPopup(data.page);
            } else {
                alert(data.error || 'Print failed.');
            }
        },
        error: function () {
            alert('{{ __('system.error_occurred_please_try_again') }}');
        },
        complete: function () {
            $btn.prop('disabled', false);
        }
    });
});

$(document).on('click', '.print_teacher_timetable', function (e) {
    e.preventDefault();
    var $btn = $(this);
    var staffId = $btn.data('staff_id');
    if (!staffId) return;
    $btn.prop('disabled', true);
    $.ajax({
        type: 'POST',
        url: '{{ route('timetable.print_teacher') }}',
        data: {
            _token: '{{ csrf_token() }}',
            staff_id: staffId
        },
        dataType: 'json',
        success: function (data) {
            if (String(data.status) === '1' && data.page) {
                timetablePrintPopup(data.page);
            } else {
                alert(data.error || 'Print failed.');
            }
        },
        error: function () {
            alert('{{ __('system.error_occurred_please_try_again') }}');
        },
        complete: function () {
            $btn.prop('disabled', false);
        }
    });
});
</script>
@endpush
