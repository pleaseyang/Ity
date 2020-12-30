<?php

use App\Http\Controllers\WebSocket\GroupChatController;
use Illuminate\Support\Facades\Route;

// 测试内容可删除 GroupChatController WebSocket
Route::any('send', [GroupChatController::class, 'sendChat'])->name('gc.send');
Route::any('online', [GroupChatController::class, 'online'])->name('gc.online');
Route::any('chatRecord', [GroupChatController::class, 'getChatRecord'])->name('gc.chatRecord');
