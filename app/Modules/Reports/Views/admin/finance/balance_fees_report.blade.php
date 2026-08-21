@include('reports::admin.finance.hub')

@php $currency = $reports->currencySymbol(); @endphp

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-search"></i> {{ __('system.select_criteria') }}</h3>
    </div>
    <form action="{{ url('financereports/studentacademicreport') }}" method="post">
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
                        <label>{{ __('system.search_type') }}</label>
                        <select name="search_type" class="form-control">
                            @foreach($payment_type as $key => $label)
                                <option value="{{ $key }}" @selected((string) $filters['search_type'] === (string) $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('search_type')<span class="text-danger">{{ $message }}</span>@enderror
                    </div>
                </div>
            </div>
        </div>
        <div class="box-footer">
            <button type="submit" class="btn btn-primary btn-sm pull-right"><i class="fa fa-search"></i> {{ __('system.search') }}</button>
        </div>
    </form>
</div>

@if($searched && !empty($student_due_fee))
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title"><i class="fa fa-users"></i> {{ __('system.balance_fees_report') }}</h3>
        </div>
        <div class="box-body table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>{{ __('system.student_name') }}</th>
                        <th>{{ __('system.class') }}</th>
                        <th>{{ __('system.mobile_no') }}</th>
                        <th>{{ __('system.admission_no') }}</th>
                        @if($show_roll_no)<th>{{ __('system.roll_number') }}</th>@endif
                        @if($show_father_name)<th>{{ __('system.father_name') }}</th>@endif
                        <th class="text-right">{{ __('system.total_fees') }} ({{ $currency }})</th>
                        <th class="text-right">{{ __('system.paid_fees') }} ({{ $currency }})</th>
                        <th class="text-right">{{ __('system.discount') }} ({{ $currency }})</th>
                        <th class="text-right">{{ __('system.fine') }} ({{ $currency }})</th>
                        <th class="text-right">{{ __('system.balance') }} ({{ $currency }})</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $totalfeelabel = 0; $depositfeelabel = 0; $discountlabel = 0; $finelabel = 0; $balancelabel = 0;
                    @endphp
                    @foreach($resultarray as $section)
                        @foreach($section['result'] as $students)
                            @php
                                $totalfeelabel += $students->totalfee;
                                $depositfeelabel += $students->deposit;
                                $discountlabel += $students->discount;
                                $finelabel += $students->fine;
                                $balancelabel += $students->balance;
                            @endphp
                            <tr>
                                <td>{{ $students->name }}</td>
                                <td>{{ $students->class }} ({{ $students->section }})</td>
                                <td>{{ $students->mobileno }}</td>
                                <td>{{ $students->admission_no }}</td>
                                @if($show_roll_no)<td>{{ $students->roll_no }}</td>@endif
                                @if($show_father_name)<td>{{ $students->father_name }}</td>@endif
                                <td class="text-right">{{ $reports->formatAmount($students->totalfee) }}</td>
                                <td class="text-right">{{ $reports->formatAmount($students->deposit) }}</td>
                                <td class="text-right">{{ $reports->formatAmount($students->discount) }}</td>
                                <td class="text-right">{{ $reports->formatAmount($students->fine) }}</td>
                                <td class="text-right">{{ $reports->formatAmount($students->balance) }}</td>
                            </tr>
                        @endforeach
                    @endforeach
                    <tr class="box box-solid total-bg">
                        <td></td><td></td><td></td>
                        @if($show_roll_no)<td></td>@endif
                        @if($show_father_name)<td></td>@endif
                        <td class="text-right"><b>{{ __('system.grand_total') }}</b></td>
                        <td class="text-right"><b>{{ $reports->formatAmount($totalfeelabel) }}</b></td>
                        <td class="text-right"><b>{{ $reports->formatAmount($depositfeelabel) }}</b></td>
                        <td class="text-right"><b>{{ $reports->formatAmount($discountlabel) }}</b></td>
                        <td class="text-right"><b>{{ $reports->formatAmount($finelabel) }}</b></td>
                        <td class="text-right"><b>{{ $reports->formatAmount($balancelabel) }}</b></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
@elseif($searched)
    <div class="alert alert-info">{{ __('system.no_record_found') }}</div>
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
});
</script>
@endpush
