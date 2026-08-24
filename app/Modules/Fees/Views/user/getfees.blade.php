@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@php
    $studentName = trim(($student->firstname ?? '').' '.($student->middlename ?? '').' '.($student->lastname ?? ''));
@endphp

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">{{ __('system.student_fees') }}</h3>
        <div class="box-tools">
            @if($hasProcessingFees ?? false)
                <button type="button" class="btn btn-primary btn-sm" id="btn_get_processing_fees"
                        data-loading-text="<i class='fa fa-spinner fa-spin'></i> {{ __('system.please_wait') }}">
                    <i class="fa fa-money"></i> {{ __('system.processing_fees') }}
                </button>
            @endif
            @if($offlineEnabled)
                <a href="{{ route('user.offlinepayment.requests') }}" class="btn btn-primary btn-sm">
                    <i class="fa fa-money"></i> {{ __('system.offline_bank_payments') }}
                </a>
            @endif
        </div>
    </div>
    <div class="box-body">
        <div class="row">
            <div class="col-md-3"><strong>{{ __('system.admission_no') }}:</strong> {{ $student->admission_no }}</div>
            <div class="col-md-3"><strong>{{ __('system.name') }}:</strong> {{ $studentName }}</div>
            <div class="col-md-3"><strong>{{ __('system.class') }}:</strong> {{ $student->class }} ({{ $student->section }})</div>
            <div class="col-md-3"><strong>{{ __('system.date') }}:</strong> {{ now()->format('Y-m-d') }}</div>
        </div>
    </div>
</div>

@forelse($sessionFees as $block)
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">{{ __('system.session') }} : {{ $block['session'] }}</h3>
            <div class="box-tools">
                <button type="button" class="btn btn-default btn-sm btn_print_selected" data-session="{{ $block['student_session_id'] }}">
                    {{ __('system.print_selected') }}
                </button>
            </div>
        </div>
        <div class="box-body table-responsive">
            <table class="table table-striped table-bordered">
                <thead>
                <tr>
                    <th style="width:36px;"><input type="checkbox" class="select_all_portal_fee_lines" data-target=".portal_fee_line_cb_{{ $block['student_session_id'] }}"></th>
                    <th>{{ __('system.fees') }}</th>
                    <th>{{ __('system.due_date') }}</th>
                    <th>{{ __('system.status') }}</th>
                    <th class="text-right">{{ __('system.amount') }} ({{ $currencySymbol }})</th>
                    <th class="text-right">{{ __('system.discount') }}</th>
                    <th class="text-right">{{ __('system.fine') }}</th>
                    <th class="text-right">{{ __('system.paid') }}</th>
                    <th class="text-right">{{ __('system.balance') }}</th>
                    <th class="text-right">{{ __('system.action') }}</th>
                </tr>
                </thead>
                <tbody>
                @php
                    $grandDue = 0; $grandDiscount = 0; $grandFine = 0; $grandPaid = 0; $grandBalance = 0;
                @endphp
                @forelse($block['fees'] as $line)
                    @php
                        $status = $line->balance <= 0 ? 'Paid' : ($line->paid_amount > 0 ? 'Partial' : 'Unpaid');
                        $grandDue += $line->due_amount;
                        $grandDiscount += $line->paid_discount;
                        $grandFine += $line->paid_fine;
                        $grandPaid += $line->paid_amount;
                        $grandBalance += max(0, $line->balance);
                    @endphp
                    <tr>
                        <td>
                            <input type="checkbox"
                                   class="portal_fee_line_cb portal_fee_line_cb_{{ $block['student_session_id'] }}"
                                   data-fee_category="fees"
                                   data-fee_session_group_id="{{ $line->fee_session_group_id }}"
                                   data-fee_master_id="{{ $line->student_fees_master_id }}"
                                   data-fee_groups_feetype_id="{{ $line->fee_groups_feetype_id }}"
                                   data-trans_fee_id="0">
                        </td>
                        <td>{{ $line->fee_group_name }} — {{ $line->fee_type }} ({{ $line->fee_code }})</td>
                        <td>{{ $line->due_date ?: '—' }}</td>
                        <td>
                            @if($status === 'Paid')
                                <span class="label label-success">{{ __('system.paid') }}</span>
                            @elseif($status === 'Partial')
                                <span class="label label-info">Partial</span>
                            @else
                                <span class="label label-danger">{{ __('system.unpaid') }}</span>
                            @endif
                        </td>
                        <td class="text-right">{{ number_format($line->due_amount, 2) }}</td>
                        <td class="text-right">{{ number_format($line->paid_discount, 2) }}</td>
                        <td class="text-right">
                            {{ number_format($line->paid_fine, 2) }}
                            @if($line->remaining_fine > 0)
                                <small class="text-danger">(+{{ number_format($line->remaining_fine, 2) }})</small>
                            @endif
                        </td>
                        <td class="text-right">{{ number_format($line->paid_amount, 2) }}</td>
                        <td class="text-right">{{ number_format(max(0, $line->balance), 2) }}</td>
                        <td class="text-right">
                            @if($offlineEnabled && $block['is_current'] && $line->balance > 0)
                                <form method="post" action="{{ route('user.offlinepayment.start') }}" style="display:inline;">
                                    @csrf
                                    <input type="hidden" name="fee_category" value="fees">
                                    <input type="hidden" name="student_fees_master_id" value="{{ $line->student_fees_master_id }}">
                                    <input type="hidden" name="fee_groups_feetype_id" value="{{ $line->fee_groups_feetype_id }}">
                                    <input type="hidden" name="student_transport_fee_id" value="0">
                                    <button type="submit" class="btn btn-xs btn-primary">
                                        <i class="fa fa-money"></i> {{ __('system.offline_payment') }}
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                    @foreach($line->payments as $pay)
                        <tr class="bg-gray-light">
                            <td></td>
                            <td colspan="4" class="text-right">
                                {{ __('system.payment_id') }} {{ $pay->payment_id }} — {{ $pay->date }} — {{ $pay->payment_mode }}
                                @if($pay->description)<br><em>{{ $pay->description }}</em>@endif
                            </td>
                            <td class="text-right">{{ number_format($pay->amount_discount, 2) }}</td>
                            <td class="text-right">{{ number_format($pay->amount_fine, 2) }}</td>
                            <td class="text-right">{{ number_format($pay->amount, 2) }}</td>
                            <td></td>
                            <td class="text-right">
                                <a class="btn btn-default btn-xs"
                                   href="{{ route('user.fees.printFeesByName.page', [
                                       'main_invoice' => $pay->invoice_id,
                                       'sub_invoice' => $pay->sub_invoice_id,
                                       'fee_category' => $pay->fee_category ?? 'fees',
                                   ]) }}"
                                   target="_blank"
                                   title="{{ __('system.print') }}">{{ __('system.print') }}</a>
                            </td>
                        </tr>
                    @endforeach
                @empty
                    <tr><td colspan="10" class="text-center text-muted">{{ __('system.no_record_found') }}</td></tr>
                @endforelse

                @foreach($block['transport_fees'] as $line)
                    @php
                        $status = $line->balance <= 0 ? 'Paid' : ($line->paid_amount > 0 ? 'Partial' : 'Unpaid');
                        $grandDue += $line->due_amount;
                        $grandDiscount += $line->paid_discount;
                        $grandFine += $line->paid_fine;
                        $grandPaid += $line->paid_amount;
                        $grandBalance += max(0, $line->balance);
                    @endphp
                    <tr>
                        <td>
                            <input type="checkbox"
                                   class="portal_fee_line_cb portal_fee_line_cb_{{ $block['student_session_id'] }}"
                                   data-fee_category="transport"
                                   data-fee_session_group_id="0"
                                   data-fee_master_id="0"
                                   data-fee_groups_feetype_id="0"
                                   data-trans_fee_id="{{ $line->student_transport_fee_id }}">
                        </td>
                        <td>{{ $line->fee_group_name }} — {{ $line->fee_type }}</td>
                        <td>{{ $line->due_date ?: '—' }}</td>
                        <td>
                            @if($status === 'Paid')
                                <span class="label label-success">{{ __('system.paid') }}</span>
                            @elseif($status === 'Partial')
                                <span class="label label-info">Partial</span>
                            @else
                                <span class="label label-danger">{{ __('system.unpaid') }}</span>
                            @endif
                        </td>
                        <td class="text-right">{{ number_format($line->due_amount, 2) }}</td>
                        <td class="text-right">{{ number_format($line->paid_discount, 2) }}</td>
                        <td class="text-right">
                            {{ number_format($line->paid_fine, 2) }}
                            @if($line->remaining_fine > 0)
                                <small class="text-danger">(+{{ number_format($line->remaining_fine, 2) }})</small>
                            @endif
                        </td>
                        <td class="text-right">{{ number_format($line->paid_amount, 2) }}</td>
                        <td class="text-right">{{ number_format(max(0, $line->balance), 2) }}</td>
                        <td class="text-right">
                            @if($offlineEnabled && $block['is_current'] && $line->balance > 0)
                                <form method="post" action="{{ route('user.offlinepayment.start') }}" style="display:inline;">
                                    @csrf
                                    <input type="hidden" name="fee_category" value="transport">
                                    <input type="hidden" name="student_fees_master_id" value="0">
                                    <input type="hidden" name="fee_groups_feetype_id" value="0">
                                    <input type="hidden" name="student_transport_fee_id" value="{{ $line->student_transport_fee_id }}">
                                    <button type="submit" class="btn btn-xs btn-primary">
                                        <i class="fa fa-money"></i> {{ __('system.offline_payment') }}
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                    @foreach($line->payments as $pay)
                        <tr class="bg-gray-light">
                            <td></td>
                            <td colspan="4" class="text-right">
                                {{ __('system.payment_id') }} {{ $pay->payment_id }} — {{ $pay->date }} — {{ $pay->payment_mode }}
                            </td>
                            <td class="text-right">{{ number_format($pay->amount_discount, 2) }}</td>
                            <td class="text-right">{{ number_format($pay->amount_fine, 2) }}</td>
                            <td class="text-right">{{ number_format($pay->amount, 2) }}</td>
                            <td></td>
                            <td class="text-right">
                                <a class="btn btn-default btn-xs"
                                   href="{{ route('user.fees.printFeesByName.page', [
                                       'main_invoice' => $pay->invoice_id,
                                       'sub_invoice' => $pay->sub_invoice_id,
                                       'fee_category' => $pay->fee_category ?? 'transport',
                                   ]) }}"
                                   target="_blank"
                                   title="{{ __('system.print') }}">{{ __('system.print') }}</a>
                            </td>
                        </tr>
                    @endforeach
                @endforeach

                @if(count($block['fees']) || count($block['transport_fees']))
                    <tr>
                        <th></th>
                        <th colspan="3" class="text-right">{{ __('system.grand_total') }}</th>
                        <th class="text-right">{{ number_format($grandDue, 2) }}</th>
                        <th class="text-right">{{ number_format($grandDiscount, 2) }}</th>
                        <th class="text-right">{{ number_format($grandFine, 2) }}</th>
                        <th class="text-right">{{ number_format($grandPaid, 2) }}</th>
                        <th class="text-right">{{ number_format($grandBalance, 2) }}</th>
                        <th></th>
                    </tr>
                @endif
                </tbody>
            </table>

            @if($block['discounts']->isNotEmpty())
                <h4>{{ __('system.discount') }}</h4>
                <table class="table table-bordered">
                    <thead>
                    <tr>
                        <th>{{ __('system.name') }}</th>
                        <th>{{ __('system.code') }}</th>
                        <th>{{ __('system.status') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($block['discounts'] as $d)
                        <tr>
                            <td>{{ $d->name }}</td>
                            <td>{{ $d->code }}</td>
                            <td>{{ $d->status }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
@empty
    <div class="alert alert-danger">{{ __('system.no_record_found') }}</div>
@endforelse

<form method="post" action="{{ route('user.fees.printFeesByGroupArray') }}" id="portal_multi_print_form" target="_blank" style="display:none;">
    @csrf
    <input type="hidden" name="data" id="portal_multi_print_data" value="">
</form>

@if($hasProcessingFees ?? false)
<div id="processing_fess_modal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title">{{ __('system.processing_fees') }}</h4>
            </div>
            <div class="modal-body scroll-area"></div>
        </div>
    </div>
</div>
@endif

@push('scripts')
<script>
$(function () {
    $('.select_all_portal_fee_lines').on('change', function () {
        var target = $(this).data('target');
        $(target).prop('checked', $(this).prop('checked'));
    });
    $('.btn_print_selected').on('click', function () {
        var sessionId = $(this).data('session');
        var $checked = $('.portal_fee_line_cb_' + sessionId + ':checked');
        if ($checked.length === 0) {
            alert(@json(__('system.no_record_selected')));
            return;
        }
        var items = [];
        $checked.each(function () {
            items.push({
                fee_category: $(this).data('fee_category'),
                trans_fee_id: $(this).data('trans_fee_id'),
                fee_session_group_id: $(this).data('fee_session_group_id'),
                fee_master_id: $(this).data('fee_master_id'),
                fee_groups_feetype_id: $(this).data('fee_groups_feetype_id')
            });
        });
        $('#portal_multi_print_data').val(JSON.stringify(items));
        $('#portal_multi_print_form').trigger('submit');
    });

    @if($hasProcessingFees ?? false)
    $('#btn_get_processing_fees').on('click', function () {
        var $this = $(this);
        $this.prop('disabled', true);
        $.ajax({
            type: 'POST',
            url: @json(route('user.fees.getProcessingfees')),
            data: { _token: @json(csrf_token()) },
            dataType: 'JSON',
            success: function (data) {
                $('#processing_fess_modal .modal-body').html(data.view);
                $('#processing_fess_modal').modal({ backdrop: 'static', keyboard: false });
            },
            error: function () {
                alert(@json(__('system.error_occurred_please_try_again')));
            },
            complete: function () {
                $this.prop('disabled', false);
            }
        });
    });
    @endif
});
</script>
@endpush
