@php $currency = $reports->currencySymbol(); @endphp
@if(empty($student_due_fee))
    <div class="alert alert-info">{{ __('system.no_record_found') }}</div>
@else
    @foreach($student_due_fee as $ssid => $student)
        <h4>
            {{ $reports->fullName((object) $student) }}
            ({{ $student['admission_no'] }}) — {{ $student['class'] }} ({{ $student['section'] }})
        </h4>
        <table class="table table-striped table-bordered" style="margin-bottom:20px;">
            <thead>
                <tr>
                    <th>{{ __('system.fees_group') }}</th>
                    <th>{{ __('system.fees_code') }}</th>
                    <th>{{ __('system.due_date') }}</th>
                    <th class="text-right">{{ __('system.amount') }} ({{ $currency }})</th>
                    <th class="text-right">{{ __('system.paid') }} ({{ $currency }})</th>
                    <th class="text-right">{{ __('system.balance') }} ({{ $currency }})</th>
                </tr>
            </thead>
            <tbody>
                @foreach($student['fees_list'] as $fee)
                    @php
                        $due = ((int) $fee->is_system === 1) ? (float) $fee->previous_amount : (float) $fee->amount;
                        $paid = 0.0;
                        if (!empty($fee->amount_detail) && $fee->amount_detail !== '0') {
                            $decoded = json_decode($fee->amount_detail, true);
                            if (is_array($decoded)) {
                                foreach ($decoded as $entry) {
                                    $paid += (float) ($entry['amount'] ?? 0) + (float) ($entry['amount_discount'] ?? 0);
                                }
                            }
                        }
                        $balance = $due - $paid;
                    @endphp
                    <tr>
                        <td>{{ $fee->fee_group_name }}</td>
                        <td>{{ $fee->code }} / {{ $fee->type }}</td>
                        <td>{{ $reports->formatDate($fee->due_date) }}</td>
                        <td class="text-right">{{ $reports->formatAmount($due) }}</td>
                        <td class="text-right">{{ $reports->formatAmount($paid) }}</td>
                        <td class="text-right">{{ $reports->formatAmount($balance) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endforeach
@endif
