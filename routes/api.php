<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PuzzleController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/start', [PuzzleController::class, 'startPuzzle']);
Route::post('/submit', [PuzzleController::class, 'submitWord']);
Route::post('/end', [PuzzleController::class, 'endPuzzle']);
Route::get('/leaderboard', [PuzzleController::class, 'leaderboard']);

