@foreach($updated_chat as $chat_value)
    @php
        $chatType = ((int) $chat_value->chat_user_id === (int) $chat_user_id) ? 'replies' : 'sent';
        $dateClass = $chatType === 'replies' ? 'time_date_send' : 'time_date';
    @endphp
    <li class="{{ $chatType }} chat_msg" data-msg_id="{{ $chat_value->id }}" id="reply_{{ $chat_value->id }}">
        <p>
            @if($chatType === 'replies')
                <i class="fa fa-remove text-danger point remove_btn" onclick="delete_msg({{ $chat_value->id }})"></i>
            @endif
            {{ $chat_value->message }}
        </p>
        <span class="{{ $dateClass }}">
            {{ !empty($chat_value->created_at) ? \Carbon\Carbon::parse($chat_value->created_at)->format(($dateFormat ?? 'd/m/Y').' H:i:s') : '' }}
        </span>
    </li>
@endforeach
