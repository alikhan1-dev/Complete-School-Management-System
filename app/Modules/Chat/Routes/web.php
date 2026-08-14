<?php

use App\Modules\Chat\Controllers\ChatController;
use App\Modules\Chat\Controllers\ModuleStatusController;
use App\Modules\Chat\Controllers\UserChatController;
use Illuminate\Support\Facades\Route;

Route::get('migration-status/chat', [ModuleStatusController::class, 'status'])->name('chat.migration_status');

Route::middleware([
    'student_parent.auth',
    'student_parent.login_token',
    'student_parent.selected_class',
    'student_parent.permission:chat',
])->group(function () {
    Route::get('user/chat', [UserChatController::class, 'index'])->name('user.chat.index');
    Route::get('user/chat/index', [UserChatController::class, 'index']);
    Route::post('user/chat/searchuser', [UserChatController::class, 'searchuser'])->name('user.chat.searchuser');
    Route::post('user/chat/myuser', [UserChatController::class, 'myuser'])->name('user.chat.myuser');
    Route::post('user/chat/getChatRecord', [UserChatController::class, 'getChatRecord'])->name('user.chat.getChatRecord');
    Route::post('user/chat/newMessage', [UserChatController::class, 'newMessage'])->name('user.chat.newMessage');
    Route::post('user/chat/chatUpdate', [UserChatController::class, 'chatUpdate'])->name('user.chat.chatUpdate');
    Route::post('user/chat/adduser', [UserChatController::class, 'adduser'])->name('user.chat.adduser');
    Route::post('user/chat/mychatnotification', [UserChatController::class, 'mychatnotification'])->name('user.chat.mychatnotification');
    Route::post('user/chat/mynewuser', [UserChatController::class, 'mynewuser'])->name('user.chat.mynewuser');
    Route::post('user/chat/delete_msg', [UserChatController::class, 'delete_msg'])->name('user.chat.delete_msg');
    Route::post('user/chat/get_active_chat_msg', [UserChatController::class, 'get_active_chat_msg'])->name('user.chat.get_active_chat_msg');
    Route::post('user/chat/get_student_parent_chat_msg_count', [UserChatController::class, 'get_student_parent_chat_msg_count'])
        ->name('user.chat.get_student_parent_chat_msg_count');
});

Route::middleware(['staff.auth'])->group(function () {
    Route::get('admin/chat', [ChatController::class, 'index'])->name('chat.index');
    Route::get('admin/chat/index', [ChatController::class, 'index']);
    Route::post('admin/chat/searchuser', [ChatController::class, 'searchuser'])->name('chat.searchuser');
    Route::post('admin/chat/myuser', [ChatController::class, 'myuser'])->name('chat.myuser');
    Route::post('admin/chat/getChatRecord', [ChatController::class, 'getChatRecord'])->name('chat.getChatRecord');
    Route::post('admin/chat/newMessage', [ChatController::class, 'newMessage'])->name('chat.newMessage');
    Route::post('admin/chat/chatUpdate', [ChatController::class, 'chatUpdate'])->name('chat.chatUpdate');
    Route::post('admin/chat/adduser', [ChatController::class, 'adduser'])->name('chat.adduser');
    Route::post('admin/chat/mychatnotification', [ChatController::class, 'mychatnotification'])->name('chat.mychatnotification');
    Route::post('admin/chat/mynewuser', [ChatController::class, 'mynewuser'])->name('chat.mynewuser');
    Route::post('admin/chat/delete_msg', [ChatController::class, 'delete_msg'])->name('chat.delete_msg');
    Route::post('admin/chat/get_active_chat_msg', [ChatController::class, 'get_active_chat_msg'])->name('chat.get_active_chat_msg');
    Route::post('admin/chat/get_chat_msg_count', [ChatController::class, 'get_chat_msg_count'])->name('chat.get_chat_msg_count');
});
