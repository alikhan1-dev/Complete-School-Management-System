@php
    $average = (float) ($staffRatingSummary['average'] ?? 0);
    $stage = (int) $average;
    $half = fmod($average, 1.0);
    $stageHalf = ($half > 0 && $half < 1) ? $stage + 1 : null;
    $averageDisplay = \App\Modules\Staff\Services\StaffRatingService::formatAverageDisplay($average);
@endphp
<div class="text-center" style="margin-bottom: 15px;">
    <h3 style="margin-bottom: 5px;">
        @for($i = 1; $i <= 5; $i++)
            @php
                $starClass = 'fa fa-star';
                if ($stageHalf !== null && $i === $stageHalf) {
                    $starClass = 'fa fa-star-half-o';
                }
                $starStyle = $stage >= $i ? 'color: orange;' : '';
            @endphp
            <span class="{{ $starClass }}" style="{{ $starStyle }}"></span>
        @endfor
    </h3>
    <h5>{{ $averageDisplay }} average based on {{ (int) ($staffRatingSummary['total'] ?? 0) }} {{ __('system.reviews') }}.</h5>
</div>
