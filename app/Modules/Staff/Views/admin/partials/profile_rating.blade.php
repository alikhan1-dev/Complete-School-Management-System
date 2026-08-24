@if($isTeacherProfile ?? false)
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">{{ __('system.reviews') }}</h3>
        </div>
        <div class="box-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                    <tr>
                        <th>{{ __('system.name') }}</th>
                        <th>{{ __('system.role') }}</th>
                        <th>{{ __('system.rate') }}</th>
                        <th>{{ __('system.comment') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($staffReviews ?? [] as $review)
                        <tr>
                            <td>{{ $review['reviewer_name'] ?? '' }}</td>
                            <td>{{ $review['role'] ?? '' }}</td>
                            <td>
                                @for($i = 1; $i <= 5; $i++)
                                    <span class="fa fa-star" @if($i <= (int) ($review['rate'] ?? 0)) style="color: orange;" @endif></span>
                                @endfor
                            </td>
                            <td>{{ $review['comment'] ?? '' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted">{{ __('system.no_record_found') }}</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endif
