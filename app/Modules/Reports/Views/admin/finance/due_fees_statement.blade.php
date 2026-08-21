@include('reports::admin.finance.hub')

@php $currency = $reports->currencySymbol(); @endphp

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-search"></i> {{ __('system.select_criteria') }}</h3>
    </div>
    <form id="duefeesform" action="{{ url('financereports/reportduefees') }}" method="post">
        @csrf
        <div class="box-body">
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>{{ __('system.class') }}</label>
                        <select id="class_id" name="class_id" class="form-control">
                            <option value="">{{ __('system.select') }}</option>
                            @foreach($classes as $class)
                                <option value="{{ $class->id }}" @selected((string) $filters['class_id'] === (string) $class->id)>{{ $class->class }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>{{ __('system.section') }}</label>
                        <select id="section_id" name="section_id" class="form-control">
                            <option value="">{{ __('system.select') }}</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class="box-footer">
            <button type="submit" class="btn btn-primary btn-sm pull-right"><i class="fa fa-search"></i> {{ __('system.search') }}</button>
        </div>
    </form>
</div>

@if($searched)
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title"><i class="fa fa-users"></i> {{ __('system.balance_fees_statement') }}</h3>
            <button type="button" id="printDueFees" class="btn btn-default btn-xs pull-right"><i class="fa fa-print"></i> {{ __('system.print') }}</button>
        </div>
        <div class="box-body table-responsive" id="duefeesresult">
            @include('reports::admin.finance._print_due_fees', ['student_due_fee' => $student_due_fee, 'reports' => $reports])
        </div>
    </div>
@endif

@push('scripts')
<script>
$(function () {
    function loadSections(classId, selected) {
        var $section = $('#section_id');
        $section.html('<option value="">{{ __('system.select') }}</option>');
        if (!classId) return;
        $.getJSON(@json(url('sections/getByClass')), {class_id: classId}, function (data) {
            $.each(data, function (i, obj) {
                var sel = String(selected) === String(obj.section_id) ? ' selected' : '';
                $section.append('<option value="' + obj.section_id + '"' + sel + '>' + obj.section + '</option>');
            });
        });
    }
    loadSections($('#class_id').val(), @json($filters['section_id']));
    $('#class_id').on('change', function () { loadSections($(this).val(), ''); });

    $('#printDueFees').on('click', function () {
        $.post(@json(url('financereports/printreportduefees')), {
            _token: @json(csrf_token()),
            class_id: $('#class_id').val(),
            section_id: $('#section_id').val()
        }, function (res) {
            if (res.status == 1) {
                var w = window.open('', 'Print');
                w.document.write(res.page);
                w.document.close();
                w.print();
            }
        }, 'json');
    });
});
</script>
@endpush
