@php $currency = $reports->currencySymbol(); @endphp
<p><b>{{ __('system.date') }}:</b> {{ $reports->formatDate($date) }}</p>
<table class="table table-striped table-bordered">
    <thead>
        <tr>
            <th>{{ __('system.admission_no') }}</th>
            <th>{{ __('system.student_name') }}</th>
            <th>{{ __('system.class') }}</th>
            <th>{{ __('system.fees_group') }}</th>
            <th class="text-right">{{ __('system.amount') }} ({{ $currency }})</th>
        </tr>
    </thead>
    <tbody>
        @forelse($student_list as $row)
            @php
                $amt = 0.0;
                if (!empty($row->amount_detail) && $row->amount_detail !== '0') {
                    $decoded = json_decode($row->amount_detail, true);
                    if (is_array($decoded)) {
                        foreach ($decoded as $entry) {
                            if (($entry['date'] ?? '') === $date || empty($date)) {
                                $amt += (float) ($entry['amount'] ?? 0) + (float) ($entry['amount_fine'] ?? 0);
                            }
                        }
                    }
                }
            @endphp
            <tr>
                <td>{{ $row->admission_no }}</td>
                <td>{{ $reports->fullName($row) }}</td>
                <td>{{ $row->class }} ({{ $row->section }})</td>
                <td>{{ $row->name }}</td>
                <td class="text-right">{{ $reports->formatAmount($amt) }}</td>
            </tr>
        @empty
            <tr><td colspan="5" class="text-center">{{ __('system.no_record_found') }}</td></tr>
        @endforelse
    </tbody>
</table>
