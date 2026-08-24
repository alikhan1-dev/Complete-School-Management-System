@php
    $points = $points ?? collect();
@endphp
@foreach($points as $index => $value)
    <tr style="cursor: all-scroll" id="{{ $value->id }}">
        <td>{{ $index + 1 }}</td>
        <td>{{ $value->pickup_point_name }}</td>
        <td>{{ $value->destination_distance }}</td>
        <td>{{ $value->pickup_time ? substr((string) $value->pickup_time, 0, 5) : '—' }}</td>
        <td class="text-right">{{ number_format((float) $value->fees, 2, '.', '') }}</td>
    </tr>
@endforeach
