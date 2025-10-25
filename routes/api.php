<?php
use App\Http\Controllers\Auth\TokenController;
use Illuminate\Support\Facades\Route;

Route::post('token', [TokenController::class, 'token']);
Route::middleware('auth:sanctum')->post('logout', [TokenController::class, 'logout']);

Route::middleware(['auth:sanctum', 'role:Agent'])->get('/agent/orders', function(){});

