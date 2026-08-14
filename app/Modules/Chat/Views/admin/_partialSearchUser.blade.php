@if(!empty($chat_user))
    <ul class="list-group" id="contact-list">
        @foreach($chat_user as $user_value)
            @php
                $isStudent = !empty($user_value->student_id);
                $userId = $isStudent ? $user_value->student_id : $user_value->staff_id;
                $img = empty($user_value->image)
                    ? asset('uploads/staff_images/no_image.png')
                    : ($isStudent ? asset($user_value->image) : asset('uploads/staff_images/'.$user_value->image));
                $name = $isStudent
                    ? trim(($user_value->first_name ?? '').' '.((!empty($useMiddle) ? ($user_value->middle_name ?? '') : '')).' '.((!empty($useLast) ? ($user_value->last_name ?? '') : '')))
                    : (empty($user_value->surname) ? ($user_value->name ?? '') : ($user_value->name.' '.$user_value->surname));
            @endphp
            <li class="list-group-item" data-user-type="{{ $isStudent ? 'Student' : 'Staff' }}" data-user-id="{{ $userId }}">
                <div class="col-xs-2 col-sm-1">
                    <img src="{{ $img }}" alt="" class="img-responsive">
                </div>
                <div class="col-xs-10 col-sm-9">
                    <span class="name">{{ $name }}</span><br>
                    <span>({{ $isStudent ? 'Student' : 'Staff' }})</span>
                </div>
                <div class="clearfix"></div>
            </li>
        @endforeach
    </ul>
@endif
