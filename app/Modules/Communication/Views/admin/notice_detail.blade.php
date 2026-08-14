<div>
    <h4 class="box-title mt0 mb0">{{ $notification['title'] ?? '' }}</h4>
</div>
<div class="dividerhr"></div>
<p>{!! $notification['message'] ?? '' !!}</p>

@if(!empty($notification['attachment']))
    <div class="ptt10">
        <a href="{{ url('admin/notification/download/'.$notification['id']) }}">
            <i class="fa fa-download pr-1"></i> Download Attachment
        </a>
    </div>
@endif

<ul class="email-list-group">
    <li><i class="fa fa-calendar-check-o pr-1"></i> Publish Date: {{ $publishDate }}</li>
    <li><i class="fa fa-calendar pr-1"></i> Notice Date: {{ $noticeDate }}</li>
    {!! $createdByHtml !!}
</ul>
<div class="dividerhr"></div>
<h4 class="box-title">Message To</h4>
<ul class="email-list-group">
    @foreach($roleNames as $role)
        <li><i class="fa fa-user-secret"></i> {{ $role['name'] }}</li>
    @endforeach
    @if(($notification['visible_student'] ?? '') === 'Yes')
        <li><i class="fa fa-user"></i> Student</li>
    @endif
    @if(($notification['visible_parent'] ?? '') === 'Yes')
        <li><i class="fa fa-user"></i> Parent</li>
    @endif
</ul>
