@include('reports::admin.finance.hub')

@php $currency = $reports->currencySymbol(); @endphp

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-search"></i> {{ __('system.select_criteria') }}</h3>
    </div>
    <form action="{{ url('financereports/reportbyname') }}" method="post">
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
                <div class="col-md-4">
                    <div class="form-group">
                        <label>{{ __('system.student') }}</label>
                        <select id="student_id" name="student_id" class="form-control">
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
            <h3 class="box-title"><i class="fa fa-money"></i> {{ __('system.fees_statement') }}</h3>
        </div>
        <div class="box-body table-responsive">
            @forelse($student_due_fee as $student)
                <h4>
                    {{ $reports->fullName((object) $student) }}
                    ({{ $student['admission_no'] }}) — {{ $student['class'] }} ({{ $student['section'] }})
                </h4>
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>{{ __('system.fees_group') }}</th>
                            <th>{{ __('system.fees_code') }}</th>
                            <th>{{ __('system.due_date') }}</th>
                            <th class="text-right">{{ __('system.amount') }} ({{ $currency }})</th>
                            <th class="text-right">{{ __('system.paid') }} ({{ $currency }})</th>
                            <th class="text-right">{{ __('system.discount') }} ({{ $currency }})</th>
                            <th class="text-right">{{ __('system.fine') }} ({{ $currency }})</th>
                            <th class="text-right">{{ __('system.balance') }} ({{ $currency }})</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($student['fees'] as $group)
                            @foreach($group->fees as $line)
                                <tr>
                                    <td>{{ $line->fee_group_name }}</td>
                                    <td>{{ $line->fee_code }} / {{ $line->fee_type }}</td>
                                    <td>{{ $reports->formatDate($line->due_date) }}</td>
                                    <td class="text-right">{{ $reports->formatAmount($line->due_amount) }}</td>
                                    <td class="text-right">{{ $reports->formatAmount($line->paid_amount) }}</td>
                                    <td class="text-right">{{ $reports->formatAmount($line->paid_discount) }}</td>
                                    <td class="text-right">{{ $reports->formatAmount($line->paid_fine) }}</td>
                                    <td class="text-right">{{ $reports->formatAmount($line->balance) }}</td>
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            @empty
                <div class="alert alert-info">{{ __('system.no_record_found') }}</div>
            @endforelse
        </div>
    </div>
@endif

@push('scripts')
<script>
$(function () {
    function loadSections(classId, selected, after) {
        var $section = $('#section_id');
        $section.html('<option value="">{{ __('system.select') }}</option>');
        $('#student_id').html('<option value="">{{ __('system.select') }}</option>');
        if (!classId) return;
        $.getJSON(@json(url('sections/getByClass')), {class_id: classId}, function (data) {
            $.each(data, function (i, obj) {
                var sel = String(selected) === String(obj.section_id) ? ' selected' : '';
                $section.append('<option value="' + obj.section_id + '"' + sel + '>' + obj.section + '</option>');
            });
            if (typeof after === 'function') after();
        });
    }
    function loadStudents(classId, sectionId, selected) {
        var $student = $('#student_id');
        $student.html('<option value="">{{ __('system.select') }}</option>');
        if (!classId || !sectionId) return;
        $.getJSON(@json(url('student/getByClassAndSection')), {class_id: classId, section_id: sectionId}, function (data) {
            $.each(data, function (i, obj) {
                var name = (obj.firstname || '') + ' ' + (obj.lastname || '');
                var sel = String(selected) === String(obj.id) ? ' selected' : '';
                $student.append('<option value="' + obj.id + '"' + sel + '>' + name + '</option>');
            });
        });
    }
    var classId = $('#class_id').val();
    var sectionId = @json($filters['section_id']);
    loadSections(classId, sectionId, function () {
        if (sectionId) loadStudents(classId, sectionId, @json($filters['student_id']));
    });
    $('#class_id').on('change', function () { loadSections($(this).val(), ''); });
    $('#section_id').on('change', function () { loadStudents($('#class_id').val(), $(this).val(), ''); });
});
</script>
@endpush
