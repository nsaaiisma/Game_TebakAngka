<?php

use App\Http\Controllers\GameController;
use Illuminate\Support\Facades\Route;

Route::get('/', [GameController::class, 'index'])->name('game');
Route::post('/mulai', [GameController::class, 'start'])->name('game.start');
Route::post('/tebak', [GameController::class, 'guess'])->name('game.guess');
Route::post('/petunjuk', [GameController::class, 'hint'])->name('game.hint');
Route::post('/ulang', [GameController::class, 'restart'])->name('game.restart');
Route::post('/menu', [GameController::class, 'menu'])->name('game.menu');