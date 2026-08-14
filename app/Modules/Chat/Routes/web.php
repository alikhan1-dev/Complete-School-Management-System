<?php

use App\Modules\Chat\Controllers\ChatController;
use App\Modules\Chat\Controllers\ModuleStatusController;
use Illuminate\Support\Facades\Route;

Route::get('migration-status/chat', [ModuleStatusController::class, 'status'])->name('chat.migration_status');

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
