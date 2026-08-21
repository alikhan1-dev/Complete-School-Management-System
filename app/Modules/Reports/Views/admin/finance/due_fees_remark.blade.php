@include('reports::admin.finance.hub')

@php $currency = $reports->currencySymbol(); @endphp

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-search"></i> {{ __('system.select_criteria') }}</h3>
    </div>
    <form action="{{ url('financereports/duefeesremark') }}" method="post">
        @csrf
        <div class="box-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>{{ __('system.class') }} <small class="req">*</small></label>
                        <select id="class_id" name="class_id" class="form-control">
                            <option value="">{{ __('system.select') }}</option>
                            @foreach($classlist as $class)
                                <option value="{{ $class->id }}" @selected((string) $filters['class_id'] === (string) $class->id)>{{ $class->class }}</option>
                            @endforeach
                        </select>
                        @error('class_id')<span class="text-danger">{{ $message }}</span>@enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>{{ __('system.section') }} <small class="req">*</small></label>
                        <select id="section_id" name="section_id" class="form-control">
                            <option value="">{{ __('system.select') }}</option>
                        </select>
                        @error('section_id')<span class="text-danger">{{ $message }}</span>@enderror
                    </div>
                </div>
            </div>
        </div>
        <div class="box-footer">
            <button type="submit" class="btn btn-primary pull-right"><i class="fa fa-search"></i> {{ __('system.search') }}</button>
        </div>
    </form>
</div>

@if($searched && is_array($student_remain_fees))
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title"><i class="fa fa-users"></i> {{ __('system.balance_fees_report_with_remark') }}</h3>
            @if(!empty($student_remain_fees))
                <button type="button" class="btn btn-primary pull-right print-remark"
                        data-class-id="{{ $filters['class_id'] }}"
                        data-section-id="{{ $filters['section_id'] }}">
                    <i class="fa fa-print"></i> {{ __('system.print') }}
                </button>
            @endif
        </div>
        <div class="box-body table-responsive">
            @if(empty($student_remain_fees))
                <div class="alert alert-info">{{ __('system.no_record_found') }}</div>
            @else
                <table class="table table-striped table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>{{ __('system.student_name') }}<br/>({{ __('system.admission_no') }})</th>
                            <th>{{ __('system.class') }}</th>
                            <th width="30%">{{ __('system.fees') }}</th>
                            <th class="text text-right">{{ __('system.amount') }} ({{ $currency }})</th>
                            <th class="text text-right">{{ __('system.paid') }} ({{ $currency }})</th>
                            <th class="text text-right">{{ __('system.balance') }} ({{ $currency }})</th>
                            <th class="text text-right">{{ __('system.remark') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $totalamount = 0; $totalpaid = 0; $totalbalance = 0; @endphp
                        @foreach($student_remain_fees as $student)
                            @php
                                $amount = 0; $amount_deposite = 0; $amount_discount = 0;
                                foreach ($student['fees'] as $fee) {
                                    $amount += (float) $fee['amount'];
                                    $amount_deposite += (float) $fee['amount_deposite'];
                                    $amount_discount += (float) $fee['amount_discount'];
                                }
                                $paid = $amount_deposite + $amount_discount;
                                $balance = $amount - $paid;
                                $totalamount += $amount;
                                $totalpaid += $paid;
                                $totalbalance += $balance;
                            @endphp
                            <tr>
                                <td>
                                    <a href="{{ url('student/view/'.$student['id']) }}">
                                        {{ $reports->fullName((object) $student) }}<br/>({{ $student['admission_no'] }})
                                    </a>
                                </td>
                                <td>{{ $student['class'] }}-{{ $student['section'] }}</td>
                                <td>
                                    @foreach($student['fees'] as $fee)
                                        @if($fee['is_system'])
                                            {{ __('system.'.$fee['fee_group']) }} ({{ __('system.'.$fee['fee_type']) }})
                                        @else
                                            {{ $fee['fee_group'] }} ({{ $fee['fee_type'] }} : {{ $fee['fee_code'] }})
                                        @endif
                                        @if(!$loop->last)<br/>@endif
                                    @endforeach
                                </td>
                                <td class="text text-right">{{ $reports->formatAmount($amount) }}</td>
                                <td class="text text-right">{{ $reports->formatAmount($paid) }}</td>
                                <td class="text text-right">{{ $reports->formatAmount($balance) }}</td>
                                <td class="text text-right"><div style="height:100px;overflow:hidden;"></div></td>
                            </tr>
                        @endforeach
                        <tr>
                            <th class="text text-right" colspan="2"></th>
                            <th class="text text-right">{{ __('system.grand_total') }}</th>
                            <th class="text text-right">{{ $reports->formatAmount($totalamount) }}</th>
                            <th class="text text-right">{{ $reports->formatAmount($totalpaid) }}</th>
                            <th class="text text-right">{{ $reports->formatAmount($totalbalance) }}</th>
                            <th></th>
                        </tr>
                    </tbody>
                </table>
            @endif
        </div>
    </div>
    <div id="printRemarkModal" class="modal fade" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">{{ __('system.print') }}</h4>
                </div>
                <div class="modal-body" id="printRemarkBody"></div>
            </div>
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
    $(document).on('click', '.print-remark', function () {
        var $btn = $(this);
        $.post(@json(url('financereports/printduefeesremark')), {
            _token: @json(csrf_token()),
            class_id: $btn.data('class-id'),
            section_id: $btn.data('section-id')
        }, function (res) {
            if (res && res.status == 1) {
                $('#printRemarkBody').html(res.page);
                $('#printRemarkModal').modal('show');
            }
        }, 'json');
    });
})(jQuery);
</script>
@endpush
