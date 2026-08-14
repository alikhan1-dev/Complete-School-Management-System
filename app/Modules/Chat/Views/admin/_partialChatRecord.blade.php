@foreach($chatList as $chat_value)
    @if(!empty($chat_value->is_first))
        <li class="text text-center" style="margin:0;">
            <h4 class="chattitle"><span>You are now connected on chat</span></h4>
        </li>
    @else
        @php
            $mine = (int) ($chat_user->id ?? 0);
            $chatType = ((int) $chat_value->chat_user_id !== $mine) ? 'replies' : 'sent';
            $dateClass = $chatType === 'replies' ? 'time_date_send' : 'time_date';
        @endphp
        <li class="{{ $chatType }} chat_msg" data-msg_id="{{ $chat_value->id }}" id="reply_{{ $chat_value->id }}">
            <p>
                @if($chatType === 'replies' && !empty($delete_chat_enable))
                    <i class="fa fa-remove text-danger point remove_btn" onclick="delete_msg({{ $chat_value->id }})"></i>
                @endif
                {{ $chat_value->message }}
            </p>
            <span class="{{ $dateClass }}">
                {{ !empty($chat_value->created_at) ? \Carbon\Carbon::parse($chat_value->created_at)->format(($dateFormat ?? 'd/m/Y').' H:i:s') : '' }}
            </span>
        </li>
    @endif
@endforeach
