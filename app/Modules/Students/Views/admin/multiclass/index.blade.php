@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-search"></i> {{ __('system.select_criteria') }}</h3>
    </div>
    <form method="post" action="{{ route('students.multiclass.index') }}">
        @csrf
        <div class="box-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>{{ __('system.class') }} <small class="req">*</small></label>
                        <select id="class_id" name="class_id" class="form-control" required>
                            <option value="">{{ __('system.select') }}</option>
                            @foreach($classes as $class)
                                <option value="{{ $class->id }}" @selected((string) old('class_id', $selectedClassId) === (string) $class->id)>
                                    {{ $class->class }}
                                </option>
                            @endforeach
                        </select>
                        @error('class_id')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>{{ __('system.section') }} <small class="req">*</small></label>
                        <select id="section_id" name="section_id" class="form-control" required>
                            <option value="">{{ __('system.select') }}</option>
                        </select>
                        @error('section_id')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
                <div class="col-sm-12">
                    <button type="submit" class="btn btn-primary btn-sm pull-right">
                        <i class="fa fa-search"></i> {{ __('system.search') }}
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

@if(! empty($students))
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">{{ __('system.multi_class_student') }}</h3>
        </div>
        <div class="box-body">
            <div class="row">
                @foreach($students as $student)
                    @php
                        $sessions = $student['student_sessions'] ?? [];
                        $displayName = trim(($student['firstname'] ?? '').' '.($student['middlename'] ?? '').' '.($student['lastname'] ?? ''));
                    @endphp
                    <div class="col-md-6">
                        <form method="post" action="{{ route('students.multiclass.save') }}" class="multiclass-update" data-student="{{ $student['id'] }}">
                            @csrf
                            <div class="panel panel-info">
                                <div class="panel-body">
                                    <strong>{{ $displayName }} ({{ $student['admission_no'] ?? '' }})</strong>
                                    <input type="hidden" name="student_id" value="{{ $student['id'] }}">
                                    <input type="hidden" name="nxt_row" class="nxt_row" value="{{ count($sessions) + 1 }}">
                                    @if($canAdd ?? false)
                                        <button type="button" class="btn btn-primary btn-sm pull-right addrow">
                                            <i class="fa fa-plus"></i>
                                        </button>
                                    @endif
                                    <div class="append_row" style="margin-top: 12px;">
                                        @foreach($sessions as $index => $session)
                                            @php $count = $index + 1; @endphp
                                            <div class="row multiclass-row" style="margin-bottom: 8px;">
                                                <input type="hidden" name="row_count[]" value="{{ $count }}">
                                                <div class="col-sm-5">
                                                    <label>{{ __('system.class') }}</label>
                                                    <select name="class_id_{{ $count }}" class="form-control class_id">
                                                        <option value="">{{ __('system.select') }}</option>
                                                        @foreach($classes as $class)
                                                            <option value="{{ $class->id }}" @selected((int) $session->class_id === (int) $class->id)>
                                                                {{ $class->class }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="col-sm-5">
                                                    <label>{{ __('system.section') }}</label>
                                                    <select name="section_id_{{ $count }}"
                                                            class="form-control section_id"
                                                            data-selected="{{ $session->section_id }}">
                                                        <option value="">{{ __('system.select') }}</option>
                                                    </select>
                                                </div>
                                                <div class="col-sm-2" style="padding-top: 24px;">
                                                    @if(($canEdit ?? false) && count($sessions) > 1)
                                                        <button type="button" class="btn btn-danger btn-sm removerow">
                                                            <i class="fa fa-remove"></i>
                                                        </button>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                    <div class="text-right">
                                        @if($canEdit ?? false)
                                            <button type="submit" class="btn btn-info btn-sm">{{ __('system.save') }}</button>
                                        @endif
                                    </div>
                                    <div class="multiclass-msg" style="margin-top: 8px;"></div>
                                </div>
                            </div>
                        </form>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endif

@push('scripts')
<script>
$(function () {
    var oldSection = '{{ old('section_id', $selectedSectionId) }}';
    var classOptions = @json($classes->map(fn ($c) => ['id' => $c->id, 'class' => $c->class])->values());

    function loadSections(classId, selected, $target) {
        $target.html('<option value="">{{ __('system.select') }}</option>');
        if (!classId) return;
        $.getJSON('{{ url('sections/getByClass') }}', {class_id: classId}, function (data) {
            $.each(data, function (i, row) {
                var opt = $('<option>', {value: row.section_id, text: row.section});
                if (String(selected) === String(row.section_id)) opt.prop('selected', true);
                $target.append(opt);
            });
        });
    }

    $('#class_id').on('change', function () {
        loadSections($(this).val(), '', $('#section_id'));
    });
    loadSections($('#class_id').val(), oldSection, $('#section_id'));

    $('.append_row').each(function () {
        $(this).find('.multiclass-row').each(function () {
            var $row = $(this);
            loadSections($row.find('.class_id').val(), $row.find('.section_id').data('selected'), $row.find('.section_id'));
        });
    });

    $(document).on('change', '.class_id', function () {
        var $row = $(this).closest('.multiclass-row');
        loadSections($(this).val(), '', $row.find('.section_id'));
    });

    $(document).on('click', '.addrow', function () {
        var $form = $(this).closest('form');
        var nxt = parseInt($form.find('.nxt_row').val(), 10) || 1;
        var classHtml = '<option value="">{{ __('system.select') }}</option>';
        $.each(classOptions, function (i, row) {
            classHtml += '<option value="' + row.id + '">' + row.class + '</option>';
        });
        var html = '<div class="row multiclass-row" style="margin-bottom: 8px;">'
            + '<input type="hidden" name="row_count[]" value="' + nxt + '">'
            + '<div class="col-sm-5"><label>{{ __('system.class') }}</label>'
            + '<select name="class_id_' + nxt + '" class="form-control class_id">' + classHtml + '</select></div>'
            + '<div class="col-sm-5"><label>{{ __('system.section') }}</label>'
            + '<select name="section_id_' + nxt + '" class="form-control section_id"><option value="">{{ __('system.select') }}</option></select></div>'
            + '<div class="col-sm-2" style="padding-top: 24px;"><button type="button" class="btn btn-danger btn-sm removerow"><i class="fa fa-remove"></i></button></div>'
            + '</div>';
        $form.find('.append_row').append(html);
        $form.find('.nxt_row').val(nxt + 1);
    });

    $(document).on('click', '.removerow', function () {
        $(this).closest('.multiclass-row').remove();
    });

    $(document).on('submit', '.multiclass-update', function (e) {
        e.preventDefault();
        var $form = $(this);
        var $msg = $form.find('.multiclass-msg');
        $msg.html('');
        $.ajax({
            url: $form.attr('action'),
            type: 'POST',
            data: $form.serialize(),
            dataType: 'json',
            success: function (res) {
                if (String(res.status) === '1') {
                    $msg.html('<div class="alert alert-success">' + res.message + '</div>');
                } else {
                    $msg.html('<div class="alert alert-danger">' + (res.message || '{{ __('system.something_went_wrong') }}') + '</div>');
                }
            },
            error: function () {
                $msg.html('<div class="alert alert-danger">{{ __('system.something_went_wrong') }}</div>');
            }
        });
    });
});
</script>
@endpush
