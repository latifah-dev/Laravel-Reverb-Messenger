<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;


Route::get('/users/search', [UserController::class, 'search'])->name('users.search');

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
