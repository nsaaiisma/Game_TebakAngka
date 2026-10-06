<?php
 
use App\Http\Controllers\GameController;
use Illuminate\Support\Facades\Route;
 
Route::get('/', [GameController::class, 'index'])->name('game');
Route::post('/tebak', [GameController::class, 'guess'])->name('game.guess');
Route::post('/ulang', [GameController::class, 'reset'])->name('game.reset');
 