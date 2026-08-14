@push('styles')
<style>
    #frame { min-height: 480px; background: #fff; border: 1px solid #ddd; display: flex; }
    #sidepanel { width: 280px; border-right: 1px solid #eee; }
    #contacts ul { list-style: none; margin: 0; padding: 0; max-height: 400px; overflow: auto; }
    #contacts .contact { padding: 10px; cursor: pointer; border-bottom: 1px solid #f4f4f4; }
    #contacts .contact.active { background: #f7f7f7; }
    .chatcontent { flex: 1; display: flex; flex-direction: column; }
    .messages { flex: 1; min-height: 280px; overflow: auto; padding: 12px; }
    .messages ul { list-style: none; margin: 0; padding: 0; }
    .message-input { padding: 10px; border-top: 1px solid #eee; }
    .chatbadge { float: right; background: #dd4b39; color: #fff; border-radius: 10px; padding: 0 6px; }
    .displaynone { display: none; }
</style>
@endpush

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">{{ $pageTitle ?? 'Chat System' }}</h3>
    </div>
    <div class="box-body">
        <div id="frame">
            <div id="sidepanel">
                <input type="hidden" name="delete_chat_enable" id="delete_chat_enable" value="{{ $delete_chat_enable ?? 0 }}">
                <input type="hidden" name="chat_connection_id" value="0">
                <input type="hidden" name="chat_to_user" value="0">
                <input type="hidden" name="last_chat_id" value="0">
                <div id="search" style="padding:10px;">
                    <label>Chat System</label>
                    <div id="bottom-bar">
                        <button type="button" class="btn btn-primary btn-sm" id="addcontact" data-toggle="modal" data-target="#myModal"><i class="fa fa-plus"></i></button>
                    </div>
                </div>
                <div id="contacts">
                    <ul></ul>
                </div>
            </div>
            <div class="chatcontent">
                <div class="contact-profile" style="padding:10px;">
                    <img src="{{ asset('uploads/student_images/no_image.png') }}" alt="" style="height:32px;">
                    <p style="display:inline;margin-left:8px;">Select any user to start your chat</p>
                </div>
                <div class="messages">
                    <ul></ul>
                </div>
                <div class="message-input">
                    <div class="wrap relative">
                        <input type="text" placeholder="Write your message" class="chat_input form-control" style="width:calc(100% - 50px);display:inline-block;">
                        <button class="submit input_submit btn btn-primary" disabled="disabled"><i class="fa fa-paper-plane"></i></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="myModal" class="modal fade" role="dialog">
    <div class="modal-dialog">
        <form id="addUser" action="{{ url('admin/chat/adduser') }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Add Contact</h4>
                </div>
                <div class="modal-body">
                    <input type="text" class="search-query form-control" placeholder="Search">
                    <div class="usersearchlist" style="margin-top:10px;"></div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary"><i class="fa fa-plus"></i> Add</button>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
var base_url = @json(rtrim(url('/'), '/').'/');
var branch_base_url = @json(url('/'));
$.ajaxSetup({ headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } });
var timestamp = {{ time() }};
var date_time_temp = '';
function updateTime() { date_time_temp = js_yyyy_mm_dd_hh_mm_ss(); timestamp++; }
$(function () { setInterval(updateTime, 1000); });
$(document).on('input', '.chat_input', function () {
    $('.input_submit').prop('disabled', $.trim($(this).val()) === '');
});
$(document).on('click', '.input_submit', function (e) {
    if ($.trim($('.message-input input').val()) === '') { return false; }
    newChatMessage();
    e.preventDefault();
});
$(document).on('keyup', '.search-query', function () {
    var $this = $(this), keyword = $(this).val();
    if (keyword.length >= 2) {
        $.post(base_url + 'admin/chat/searchuser', { keyword: keyword }, function (data) {
            $('.usersearchlist').html(data.page);
        }, 'json');
    } else {
        $('.usersearchlist').html('');
    }
});
$(document).ready(function () {
    $.post(base_url + 'admin/chat/myuser', {}, function (data) {
        $('#contacts ul').html(data.page);
        if (data.status === '1') {
            setInterval(getChatNotification, 15000);
            setInterval(mynewUser, 25000);
        }
    }, 'json');
});
var interval;
$(document).on('click', '.contact', function () {
    var chat_connection_id = $(this).data('chatConnectionId');
    var $this = $(this);
    $.post(base_url + 'admin/chat/getChatRecord', { chat_connection_id: chat_connection_id }, function (data) {
        $this.find('span.notification_count').css('display', 'none');
        $('.messages ul').html(data.page);
        $("input[name='chat_connection_id']").val(data.chat_connection_id);
        $("input[name='chat_to_user']").val(data.chat_to_user);
        $("input[name='last_chat_id']").val(data.user_last_chat.id);
        $('.contact-profile p').html($this.find('.name').text());
        $('.contact-profile img').attr('src', $this.find('img').attr('src'));
        $this.addClass('active').siblings().removeClass('active');
        clearInterval(interval);
        interval = setInterval(getChatsUpdates, 2000);
    }, 'json');
});
$(document).off('keydown.chatInput').on('keydown.chatInput', '.chat_input', function (e) {
    if (e.which === 13 && !e.shiftKey) { e.preventDefault(); newChatMessage(); }
});
var isSending = false;
function htmlEncode(str) {
    return String(str).replace(/[^\w. ]/gi, function (c) { return '&#' + c.charCodeAt(0) + ';'; });
}
function newChatMessage() {
    if (isSending) { return; }
    var $input = $('.message-input input');
    var message = htmlEncode($input.val().trim());
    if (message === '') { return; }
    isSending = true;
    var chat_connection_id = $("input[name='chat_connection_id']").val();
    var chat_to_user = $("input[name='chat_to_user']").val();
    if (chat_connection_id > 0 && chat_to_user > 0) {
        $.post(base_url + 'admin/chat/newMessage', {
            chat_connection_id: chat_connection_id, message: message, chat_to_user: chat_to_user, time: date_time_temp
        }, function (data) {
            $('.messages ul').append('<li class="replies chat_msg" data-msg_id="' + data.last_insert_id + '" id="reply_' + data.last_insert_id + '"><p>' + message + '</p></li>');
            $input.val('');
            $("input[name='last_chat_id']").val(data.last_insert_id);
        }, 'json').always(function () { isSending = false; $('.input_submit').prop('disabled', false); });
    } else {
        isSending = false;
    }
}
function delete_msg(msg_id) {
    if (!confirm('Are you sure?')) { return; }
    $.post(base_url + 'admin/chat/delete_msg', { msg_id: msg_id }, function () {
        $('#reply_' + msg_id).html('');
    });
}
function get_active_chat_msg() {
    $.post(base_url + 'admin/chat/get_active_chat_msg', { chat_connection_id: $("input[name='chat_connection_id']").val() }, function (data) {
        var idArray = (data.chatList || []).map(function (chat) { return chat.id; });
        $('.chat_msg').each(function () {
            if (jQuery.inArray(String($(this).data('msg_id')), idArray.map(String)) === -1) {
                $('#reply_' + $(this).data('msg_id')).html('');
            }
        });
    }, 'json');
}
function getChatsUpdates() {
    get_active_chat_msg();
    $.post(base_url + 'admin/chat/chatUpdate', {
        chat_connection_id: $("input[name='chat_connection_id']").val(),
        chat_to_user: $("input[name='chat_to_user']").val(),
        last_chat_id: $("input[name='last_chat_id']").val()
    }, function (data) {
        $("input[name='last_chat_id']").val(data.user_last_chat.id);
        $('.messages ul').append(data.page);
    }, 'json');
}
$(document).on('click', '.usersearchlist ul li', function () {
    $(this).addClass('active').siblings().removeClass('active');
});
$('#addUser').submit(function (event) {
    event.preventDefault();
    var userrecord = $('.usersearchlist').find('ul li.active');
    $.post($(this).attr('action'), { user_type: userrecord.data('userType'), user_id: userrecord.data('userId') }, function (data) {
        if (data.status == 0) { return; }
        $('#contacts ul').prepend(newUserLi(data.new_user, data.chat_connection_id));
        $('.messages ul').html(data.chat_records);
        $("input[name='chat_connection_id']").val(data.chat_connection_id);
        $("input[name='chat_to_user']").val(data.new_user.chat_user_id);
        $("input[name='last_chat_id']").val(data.user_last_chat.id);
        $('#myModal').modal('hide');
    }, 'json');
});
function newUserLi(user_array, chat_connection_id) {
    return "<li class='contact' data-chat-connection-id='" + chat_connection_id + "'><div class='wrap'><div class='meta'><p class='name'>" + user_array.name + "</p><p class='preview'></p></div></div></li>";
}
function getChatNotification() {
    $.post(base_url + 'admin/chat/mychatnotification', {}, function (data) {
        $.each(data.notifications || [], function (index, value) {
            $('#contacts ul li[data-chat-connection-id="' + value.chat_connection_id + '"]').find('span.notification_count').text(value.no_of_notification).css('display', 'block');
        });
    }, 'json');
}
function js_yyyy_mm_dd_hh_mm_ss() {
    var now = new Date();
    var d = String(now.getDate()).padStart(2, '0');
    var m = String(now.getMonth() + 1).padStart(2, '0');
    var y = now.getFullYear();
    var h = String(now.getHours()).padStart(2, '0');
    var i = String(now.getMinutes()).padStart(2, '0');
    var s = String(now.getSeconds()).padStart(2, '0');
    return d + '/' + m + '/' + y + ' ' + h + ':' + i + ':' + s;
}
function mynewUser() {
    var users_Array = [];
    $('#contacts ul li').each(function () { users_Array.push($(this).data('chatConnectionId')); });
    $.post(base_url + 'admin/chat/mynewuser', { users: users_Array }, function (data) {
        $('#contacts ul').prepend(data.new_user_list);
    }, 'json');
}
function get_chat_msg_count() {
    $.post(base_url + 'admin/chat/get_chat_msg_count', {}, function () {}, 'json');
}
</script>
@endpush
