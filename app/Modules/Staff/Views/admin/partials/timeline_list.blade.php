@if($timelineList->isEmpty())
    <div class="alert alert-info">{{ __('system.no_record_found') }}</div>
@else
    <ul class="timeline timeline-inverse">
        @foreach($timelineList as $item)
            <li class="time-label">
                <span class="bg-blue">{{ $item->timeline_date }}</span>
            </li>
            <li>
                <i class="fa fa-list-alt bg-blue"></i>
                <div class="timeline-item">
                    @if($canDeleteTimeline ?? false)
                        <span class="time">
                            <a href="{{ route('staff.timeline.destroy', $item->id) }}"
                               title="{{ __('system.delete') }}"
                               onclick="return confirm(@json(__('system.delete_confirm')));">
                                <i class="fa fa-trash"></i>
                            </a>
                        </span>
                    @endif
                    @if($canEditTimeline ?? false)
                        <span class="time">
                            <a href="{{ route('staff.profile', [$staffId, 'edit_timeline' => $item->id]) }}"
                               title="{{ __('system.edit') }}">
                                <i class="fa fa-pencil"></i>
                            </a>
                        </span>
                    @endif
                    @if(!empty($item->document))
                        <span class="time">
                            <a href="{{ route('staff.timeline.download', $item->id) }}" title="{{ __('system.download') }}">
                                <i class="fa fa-download"></i>
                            </a>
                        </span>
                    @endif
                    <h3 class="timeline-header text-aqua">{{ $item->title }}</h3>
                    <div class="timeline-body">{{ $item->description }}</div>
                    @if($item->status === 'yes')
                        <div class="timeline-footer"><span class="label label-success">{{ __('system.visible_to_this_person') }}</span></div>
                    @endif
                </div>
            </li>
        @endforeach
        <li><i class="fa fa-clock-o bg-gray"></i></li>
    </ul>
@endif
