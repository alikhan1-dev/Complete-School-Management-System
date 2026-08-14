@php
    $chatUsers = $userList['chat_users'] ?? [];
    $notifications = $userList['chat_user_notification'] ?? [];
@endphp
@foreach($chatUsers as $user_value)
    @continue(empty($user_value->messages))
    @php
        $count = 0;
        foreach ($notifications as $note) {
            if ((int) $note->chat_connection_id === (int) $user_value->id) {
                $count = (int) $note->no_of_notification;
                break;
            }
        }
        $details = $user_value->user_details;
        $type = $details->user_type ?? '';
        $label = $type === 'student' ? 'Student' : ($type === 'parent' ? 'Parent' : 'Staff');
        $name = $chat->displayName($details);
        $img = $chat->imageUrl($details);
        $you = ($chat_user && (int) $chat_user->id !== (int) ($user_value->messages->chat_user_id ?? 0));
    @endphp
    <li class="contact" data-chat-connection-id="{{ $user_value->id }}">
        <div class="wrap">
            <img src="{{ $img }}" alt="">
            <div class="meta">
                <p class="name">{{ $name }} ({{ $label }})</p>
                <p class="preview">@if($you)<span>You: </span>@endif{{ $user_value->messages->message ?? '' }}</p>
            </div>
        </div>
        @if($count > 0)
            <span class="chatbadge notification_count">{{ $count }}</span>
        @else
            <span class="chatbadge notification_count displaynone">0</span>
        @endif
    </li>
@endforeach
