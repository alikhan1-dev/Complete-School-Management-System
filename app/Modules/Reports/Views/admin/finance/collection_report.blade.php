@include('reports::admin.finance.hub')

@php $currency = $reports->currencySymbol(); @endphp

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-search"></i> {{ __('system.select_criteria') }}</h3>
    </div>
    <form action="{{ url('financereports/collection_report') }}" method="post">
        @csrf
        <div class="box-body">
            <div class="row">
                <div class="col-sm-2">
                    <div class="form-group">
                        <label>{{ __('system.search_duration') }} <small class="req">*</small></label>
                        <select class="form-control" name="search_type" id="search_type">
                            @foreach($searchlist as $key => $label)
                                <option value="{{ $key }}" @selected((string) $filters['search_type'] === (string) $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('search_type')<span class="text-danger">{{ $message }}</span>@enderror
                    </div>
                </div>
                <div class="col-sm-2">
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
                <div class="col-sm-2">
                    <div class="form-group">
                        <label>{{ __('system.section') }}</label>
                        <select id="section_id" name="section_id" class="form-control">
                            <option value="">{{ __('system.select') }}</option>
                        </select>
                    </div>
                </div>
                <div class="col-sm-2">
                    <div class="form-group">
                        <label>{{ __('system.fees_type') }}</label>
                        <select id="feetype_id" name="feetype_id" class="form-control">
                            <option value="">{{ __('system.select') }}</option>
                            @foreach($feetypeList as $feetype)
                                <option value="{{ $feetype->id }}" @selected((string) $filters['feetype_id'] === (string) $feetype->id)>{{ $feetype->type }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-sm-2">
                    <div class="form-group">
                        <label>{{ __('system.collect_by') }}</label>
                        <select class="form-control" name="collect_by">
                            <option value="">{{ __('system.select') }}</option>
                            @foreach($collect_by_list as $id => $label)
                                <option value="{{ $id }}" @selected((string) $filters['collect_by'] === (string) $id)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-sm-2">
                    <div class="form-group">
                        <label>{{ __('system.group_by') }}</label>
                        <select class="form-control" name="group">
                            @foreach($group_by as $key => $label)
                                <option value="{{ $key }}" @selected((string) $filters['group'] === (string) $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-sm-2 period-dates" style="{{ ($filters['search_type'] ?? '') === 'period' ? '' : 'display:none;' }}">
                    <div class="form-group">
                        <label>{{ __('system.date_from') }}</label>
                        <input type="text" name="date_from" class="form-control" value="{{ $filters['date_from'] }}">
                    </div>
                </div>
                <div class="col-sm-2 period-dates" style="{{ ($filters['search_type'] ?? '') === 'period' ? '' : 'display:none;' }}">
                    <div class="form-group">
                        <label>{{ __('system.date_to') }}</label>
                        <input type="text" name="date_to" class="form-control" value="{{ $filters['date_to'] }}">
                    </div>
                </div>
            </div>
        </div>
        <div class="box-footer">
            <button type="submit" name="search" value="search_filter" class="btn btn-primary btn-sm pull-right">
                <i class="fa fa-search"></i> {{ __('system.search') }}
            </button>
        </div>
    </form>
</div>

@if($searched && empty($results))
    <div class="alert alert-info">{{ __('system.no_record_found') }}</div>
@elseif($searched)
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title"><i class="fa fa-money"></i> {{ __('system.fees_collection_report') }}</h3>
        </div>
        <div class="box-body table-responsive">
            <table class="table table-striped table-bordered table-hover">
                <thead>
                    <tr>
                        <th>{{ __('system.payment_id') }}</th>
                        <th>{{ __('system.date') }}</th>
                        <th>{{ __('system.admission_no') }}</th>
                        <th>{{ __('system.name') }}</th>
                        <th>{{ __('system.class') }}</th>
                        <th>{{ __('system.fee_type') }}</th>
                        <th>{{ __('system.collect_by') }}</th>
                        <th>{{ __('system.mode') }}</th>
                        <th class="text text-right">{{ __('system.paid') }} ({{ $currency }})</th>
                        <th class="text text-right">{{ __('system.discount') }} ({{ $currency }})</th>
                        <th class="text text-right">{{ __('system.fine') }} ({{ $currency }})</th>
                        <th class="text text-right">{{ __('system.total') }} ({{ $currency }})</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $grdamount = [];
                        $grddiscount = [];
                        $grdfine = [];
                        $grdtotal = [];
                    @endphp
                    @foreach($results as $groupRows)
                        @php
                            $amountLabel = [];
                            $discountLabel = [];
                            $fineLabel = [];
                            $totalLabel = [];
                        @endphp
                        @foreach($groupRows as $collect)
                            @php
                                $paid = (float) $collect['amount'];
                                $disc = (float) $collect['amount_discount'];
                                $fine = (float) $collect['amount_fine'];
                                $lineTotal = $paid + $fine;
                                $amountLabel[] = $paid;
                                $discountLabel[] = $disc;
                                $fineLabel[] = $fine;
                                $totalLabel[] = $lineTotal;
                                $staff = $collect['received_byname'] ?? [];
                            @endphp
                            <tr>
                                <td>{{ $collect['id'] }}/{{ $collect['inv_no'] }}</td>
                                <td>{{ $reports->formatDate($collect['date']) }}</td>
                                <td>{{ $collect['admission_no'] }}</td>
                                <td>{{ $reports->fullName((object) $collect) }}</td>
                                <td>{{ $collect['class'] }} ({{ $collect['section'] }})</td>
                                <td>
                                    @if(!empty($collect['is_system']))
                                        {{ __('system.'.$collect['type']) }}
                                    @else
                                        {{ $collect['type'] }} ({{ $collect['code'] }})
                                    @endif
                                </td>
                                <td>
                                    @if(!empty($staff['name']))
                                        {{ $staff['name'] }} ({{ $staff['employee_id'] }})
                                    @endif
                                </td>
                                <td>{{ $collect['payment_mode'] }}</td>
                                <td class="text text-right">{{ $reports->formatAmount($paid) }}</td>
                                <td class="text text-right">{{ $reports->formatAmount($disc) }}</td>
                                <td class="text text-right">{{ $reports->formatAmount($fine) }}</td>
                                <td class="text text-right">{{ $reports->formatAmount($lineTotal) }}</td>
                            </tr>
                        @endforeach
                        @if(!empty($subtotal))
                            <tr>
                                <td colspan="7"></td>
                                <td style="font-weight:bold">{{ __('system.sub_total') }}</td>
                                <td class="text text-right" style="font-weight:bold">{{ $currency }}{{ $reports->formatAmount(array_sum($amountLabel)) }}</td>
                                <td class="text text-right" style="font-weight:bold">{{ $currency }}{{ $reports->formatAmount(array_sum($discountLabel)) }}</td>
                                <td class="text text-right" style="font-weight:bold">{{ $currency }}{{ $reports->formatAmount(array_sum($fineLabel)) }}</td>
                                <td class="text text-right" style="font-weight:bold">{{ $currency }}{{ $reports->formatAmount(array_sum($totalLabel)) }}</td>
                            </tr>
                        @endif
                        @php
                            $grdamount[] = array_sum($amountLabel);
                            $grddiscount[] = array_sum($discountLabel);
                            $grdfine[] = array_sum($fineLabel);
                            $grdtotal[] = array_sum($totalLabel);
                        @endphp
                    @endforeach
                    <tr>
                        <td colspan="7"></td>
                        <td style="font-weight:bold">{{ __('system.grand_total') }}</td>
                        <td class="text text-right" style="font-weight:bold">{{ $currency }}{{ $reports->formatAmount(array_sum($grdamount)) }}</td>
                        <td class="text text-right" style="font-weight:bold">{{ $currency }}{{ $reports->formatAmount(array_sum($grddiscount)) }}</td>
                        <td class="text text-right" style="font-weight:bold">{{ $currency }}{{ $reports->formatAmount(array_sum($grdfine)) }}</td>
                        <td class="text text-right" style="font-weight:bold">{{ $currency }}{{ $reports->formatAmount(array_sum($grdtotal)) }}</td>
                    </tr>
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
    $('#search_type').on('change', function () {
        $('.period-dates').toggle($(this).val() === 'period');
    });
})(jQuery);
</script>
@endpush
