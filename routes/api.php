<?php

use App\Http\Controllers\User\Auth\Api\LoginController;
use App\Http\Controllers\User\Auth\Api\RegisterController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
Route::prefix('v1')->group(function () {
    
    // Register new user
    Route::post('register', [RegisterController::class, 'register']);

    // Check if email/username/mobile exists
    Route::post('check-user', [RegisterController::class, 'checkUser']);

});

Route::prefix('v1')->group(function () {
    Route::post('login', [LoginController::class, 'login']);
    Route::middleware('auth:sanctum')->post('logout', [LoginController::class, 'logout']);
});