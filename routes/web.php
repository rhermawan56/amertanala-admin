<?php

use App\Http\Controllers\ChatRecoveryController;
use App\Http\Controllers\ContentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GuestChatController;
use App\Http\Controllers\InboxController;
use App\Http\Controllers\MediaController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn () => redirect(auth()->check() ? '/dashboard' : '/login'))->name('home');
Route::get('/api/public/landing', [ContentController::class, 'published'])->middleware('throttle:120,1');
Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/content', [ContentController::class, 'edit'])->name('content');
    Route::put('/content', [ContentController::class, 'save']);
    Route::post('/content/publish', [ContentController::class, 'publish']);
    Route::post('/content/restore/{revision}', [ContentController::class, 'restore']);
    Route::get('/inbox', [InboxController::class, 'index'])->name('inbox');
    Route::get('/inbox/{conversation}/messages', [InboxController::class, 'messages']);
    Route::post('/inbox/{conversation}/read', [InboxController::class, 'read']);
    Route::post('/inbox/{conversation}/messages', [InboxController::class, 'send'])->middleware('throttle:60,1');
    Route::post('/inbox/{conversation}/close', [InboxController::class, 'close']);
    Route::get('/media', [MediaController::class, 'index'])->name('media');
    Route::post('/media', [MediaController::class, 'store'])->middleware('throttle:20,1');
    Route::get('/security', fn () => Inertia::render('workspace/Security'))->name('security');
});
Route::prefix('chat')->group(function () {
    Route::get('/', [GuestChatController::class, 'page']);
    Route::get('/current', [GuestChatController::class, 'current'])->middleware('throttle:120,1');
    Route::post('/start', [GuestChatController::class, 'start'])->middleware('throttle:10,60');
    Route::get('/messages', [GuestChatController::class, 'messages'])->middleware('throttle:120,1');
    Route::post('/messages', [GuestChatController::class, 'send'])->middleware('throttle:60,1');
    Route::post('/broadcasting/auth', [GuestChatController::class, 'broadcastAuth'])->middleware('throttle:60,1');
    Route::post('/access', [ChatRecoveryController::class, 'request'])->middleware('throttle:10,60');
    Route::get('/access/{token}', [ChatRecoveryController::class, 'show'])->middleware('throttle:30,1');
    Route::post('/access/{token}', [ChatRecoveryController::class, 'confirm'])->middleware('throttle:10,1');
});
