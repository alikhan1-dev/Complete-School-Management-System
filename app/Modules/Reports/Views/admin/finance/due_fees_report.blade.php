@include('reports::admin.finance.hub')

@php $currency = $reports->currencySymbol(); @endphp

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-search"></i> {{ __('system.select_criteria') }}</h3>
    </div>
    <form action="{{ url('balancefees/index') }}" method="post">
        @csrf
        <input type="hidden" name="search_type" value="all">
        <div class="box-body">
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>{{ __('system.class') }}</label>
                        <select id="class_id" name="class_id" class="form-control">
                            <option value="">{{ __('system.select') }}</option>
                            @foreach($classlist as $class)
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
            <button type="submit" class="btn btn-primary btn-sm pull-right">
                <i class="fa fa-search"></i> {{ __('system.search') }}
            </button>
        </div>
    </form>
</div>

@if($searched)
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title"><i class="fa fa-users"></i> {{ __('system.due_fees_report') }}</h3>
        </div>
        <div class="box-body table-responsive">
            <table class="table table-striped table-bordered table-hover">
                <thead>
                    <tr>
                        <th>{{ __('system.admission_no') }}</th>
                        <th>{{ __('system.student_name') }}</th>
                        @if(!empty($settingOnFatherName))
                            <th>{{ __('system.father_name') }}</th>
                        @endif
                        <th>{{ __('system.class') }}</th>
                        <th class="text text-right">{{ __('system.due_amount') }} ({{ $currency }})</th>
                        <th class="text text-right">{{ __('system.total_due_amount') }} ({{ $currency }})</th>
                        <th>{{ __('system.mobile_no') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $finalBalance = 0;
                        $finalFine = 0;
                        $finalTotalDue = 0;
                        $visible = 0;
                    @endphp
                    @foreach($student_due_fee as $students)
                        @php
                            $totalDue = (float) $students->balance + (float) $students->grand_fine_amount;
                        @endphp
                        @if($totalDue > 0)
                            @php
                                $visible++;
                                $finalBalance += (float) $students->balance;
                                $finalFine += (float) $students->grand_fine_amount;
                                $finalTotalDue += $totalDue;
                            @endphp
                            <tr>
                                <td>{{ $students->admission_no }}</td>
                                <td>{{ $students->name }}</td>
                                @if(!empty($settingOnFatherName))
                                    <td>{{ $students->father_name }}</td>
                                @endif
                                <td>{{ $students->class }} ({{ $students->section }})</td>
                                <td class="text text-right">
                                    {{ $currency }}{{ $reports->formatAmount($students->balance) }}
                                    <span class="text-danger">
                                        + {{ $currency }}{{ $reports->formatAmount($students->grand_fine_amount) }}
                                        ({{ __('system.fine') }})
                                    </span>
                                </td>
                                <td class="text text-right">{{ $currency }}{{ $reports->formatAmount($totalDue) }}</td>
                                <td>{{ $students->mobileno }}</td>
                            </tr>
                        @endif
                    @endforeach
                    @if($visible === 0)
                        <tr><td colspan="{{ !empty($settingOnFatherName) ? 7 : 6 }}">{{ __('system.no_record_found') }}</td></tr>
                    @else
                        <tr>
                            <th colspan="{{ !empty($settingOnFatherName) ? 4 : 3 }}" class="text text-right">{{ __('system.grand_total') }}</th>
                            <th class="text text-right">
                                {{ $currency }}{{ $reports->formatAmount($finalBalance) }}
                                <span class="text-danger">+ {{ $currency }}{{ $reports->formatAmount($finalFine) }}</span>
                            </th>
                            <th class="text text-right">{{ $currency }}{{ $reports->formatAmount($finalTotalDue) }}</th>
                            <th></th>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
@endif

@push('scripts')
<script>
(function ($) {
    function loadSections(classId, selected) {
        var $section = $('#section_id');
        $section.html('<option value="">{{ __('system.select') }}</option>');
        if (!classId) return;
        $.getJSON(@json(url('sections/getByClass')), {class_id: classId}, function (data) {
            $.each(data || [], function (_, obj) {
                var sel = String(selected) === String(obj.section_id) ? ' selected' : '';
                $section.append('<option value="' + obj.section_id + '"' + sel + '>' + obj.section + '</option>');
            });
        });
    }
    loadSections($('#class_id').val(), @json($filters['section_id']));
    $('#class_id').on('change', function () { loadSections($(this).val(), ''); });
})(jQuery);
</script>
@endpush
