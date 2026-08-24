@if($multiClassEnabled ?? false)
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">{{ __('system.multi_class_student') }}</h3>
            <button type="button" class="btn btn-primary btn-sm pull-right" id="add-multiclass-row">
                <i class="fa fa-plus"></i>
            </button>
        </div>
        <div class="box-body">
            <div id="multiclass-rows">
                @foreach(($multiClassRows ?? []) as $index => $row)
                    <div class="row multiclass-admit-row" style="margin-bottom: 8px;">
                        <div class="col-sm-5">
                            <label>{{ __('system.class') }}</label>
                            <select name="multiclass[{{ $index }}][class]" class="form-control multiclass-class">
                                <option value="">{{ __('system.select') }}</option>
                                @foreach($classes as $class)
                                    <option value="{{ $class->id }}" @selected((string) ($row['class'] ?? '') === (string) $class->id)>
                                        {{ $class->class }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-sm-5">
                            <label>{{ __('system.section') }}</label>
                            <select name="multiclass[{{ $index }}][section]"
                                    class="form-control multiclass-section"
                                    data-selected="{{ $row['section'] ?? '' }}">
                                <option value="">{{ __('system.select') }}</option>
                            </select>
                        </div>
                        <div class="col-sm-2" style="padding-top: 24px;">
                            <button type="button" class="btn btn-danger btn-sm remove-multiclass-row">
                                <i class="fa fa-remove"></i>
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
    $(function () {
        var multiIndex = {{ count($multiClassRows ?? []) }};
        var classOptions = @json($classes->map(fn ($c) => ['id' => $c->id, 'class' => $c->class])->values());

        function loadMultiSections($row) {
            var classId = $row.find('.multiclass-class').val();
            var $section = $row.find('.multiclass-section');
            var selected = $section.data('selected') || '';
            $section.html('<option value="">{{ __('system.select') }}</option>');
            if (!classId) return;
            $.getJSON('{{ url('sections/getByClass') }}', {class_id: classId}, function (data) {
                $.each(data, function (i, row) {
                    var opt = $('<option>', {value: row.section_id, text: row.section});
                    if (String(selected) === String(row.section_id)) opt.prop('selected', true);
                    $section.append(opt);
                });
            });
        }

        $('#multiclass-rows .multiclass-admit-row').each(function () {
            loadMultiSections($(this));
        });

        $(document).on('change', '.multiclass-class', function () {
            var $row = $(this).closest('.multiclass-admit-row');
            $row.find('.multiclass-section').data('selected', '');
            loadMultiSections($row);
        });

        $('#add-multiclass-row').on('click', function () {
            var classHtml = '<option value="">{{ __('system.select') }}</option>';
            $.each(classOptions, function (i, row) {
                classHtml += '<option value="' + row.id + '">' + row.class + '</option>';
            });
            var html = '<div class="row multiclass-admit-row" style="margin-bottom: 8px;">'
                + '<div class="col-sm-5"><label>{{ __('system.class') }}</label>'
                + '<select name="multiclass[' + multiIndex + '][class]" class="form-control multiclass-class">' + classHtml + '</select></div>'
                + '<div class="col-sm-5"><label>{{ __('system.section') }}</label>'
                + '<select name="multiclass[' + multiIndex + '][section]" class="form-control multiclass-section"><option value="">{{ __('system.select') }}</option></select></div>'
                + '<div class="col-sm-2" style="padding-top: 24px;"><button type="button" class="btn btn-danger btn-sm remove-multiclass-row"><i class="fa fa-remove"></i></button></div>'
                + '</div>';
            $('#multiclass-rows').append(html);
            multiIndex++;
        });

        $(document).on('click', '.remove-multiclass-row', function () {
            $(this).closest('.multiclass-admit-row').remove();
        });
    });
    </script>
    @endpush
@endif
