{{-- CI user/student/getcollectfee — Pay Selected modal body --}}
@php
    $studentName = trim(($student->firstname ?? '').' '.($student->middlename ?? '').' '.($student->lastname ?? ''));
@endphp

@if($lines === [])
    <div class="alert alert-danger">{{ __('system.please_select_record') }}</div>
@else
    <form method="post" action="{{ route('user.gateway.payment.pay') }}" id="collect_fee_group">
        @csrf
        <input type="hidden" name="submit_mode" value="online_payment">
        <input type="hidden" name="student_id" value="{{ $student->id }}">

        <p><strong>{{ $studentName }}</strong></p>
        <table class="table table-bordered">
            <thead>
            <tr>
                <th>{{ __('system.fees') }}</th>
                <th class="text-right">{{ __('system.fine_amount') }}</th>
                <th class="text-right">{{ __('system.fees_amount') }}</th>
            </tr>
            </thead>
            <tbody>
            @php $row = 1; $totalFee = 0; $totalFine = 0; @endphp
            @foreach($lines as $line)
                @php
                    $feeAmt = (float) $line->balance;
                    $fineAmt = (float) $line->remaining_fine;
                    $totalFee += $feeAmt;
                    $totalFine += $fineAmt;
                @endphp
                <tr>
                    <td>
                        {{ $line->fee_group_name }}
                        @if($line->fee_code !== '')
                            ({{ $line->fee_code }})
                        @elseif($line->fee_type !== '')
                            ({{ $line->fee_type }})
                        @endif
                        <input type="hidden" name="row_counter[]" value="{{ $row }}">
                        <input type="hidden" name="fee_category_{{ $row }}" value="{{ $line->fee_category }}">
                        <input type="hidden" name="student_fees_master_id_{{ $row }}" value="{{ $line->student_fees_master_id }}">
                        <input type="hidden" name="fee_groups_feetype_id_{{ $row }}" value="{{ $line->fee_groups_feetype_id }}">
                        <input type="hidden" name="trans_fee_id_{{ $row }}" value="{{ $line->student_transport_fee_id }}">
                    </td>
                    <td class="text-right">
                        @if($allowPartialPayment)
                            <input type="text" class="form-control input-sm text-right" style="width:90px;display:inline-block;"
                                   name="fee_groups_feetype_fine_amount_{{ $row }}" value="{{ number_format($fineAmt, 2, '.', '') }}">
                        @else
                            {{ number_format($fineAmt, 2) }}
                            <input type="hidden" name="fee_groups_feetype_fine_amount_{{ $row }}" value="{{ number_format($fineAmt, 2, '.', '') }}">
                        @endif
                    </td>
                    <td class="text-right">
                        @if($allowPartialPayment)
                            <input type="text" class="form-control input-sm text-right" style="width:90px;display:inline-block;"
                                   name="fee_amount_{{ $row }}" value="{{ number_format($feeAmt, 2, '.', '') }}">
                        @else
                            {{ number_format($feeAmt, 2) }}
                            <input type="hidden" name="fee_amount_{{ $row }}" value="{{ number_format($feeAmt, 2, '.', '') }}">
                        @endif
                    </td>
                </tr>
                @php $row++; @endphp
            @endforeach
            <tr>
                <th>{{ __('system.grand_total') }}</th>
                <th class="text-right">{{ $currencySymbol }}{{ number_format($totalFine, 2) }}</th>
                <th class="text-right">{{ $currencySymbol }}{{ number_format($totalFee, 2) }}</th>
            </tr>
            </tbody>
        </table>

        <div class="text-right">
            <button type="submit" class="btn btn-primary payment_collect">
                <i class="fa fa-money"></i> {{ __('system.pay') }}
            </button>
        </div>
    </form>
@endif
