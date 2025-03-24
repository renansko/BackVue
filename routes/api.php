<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\EmailController;
use App\Jobs\ProcessRabbitMQMessage;
use Illuminate\Http\Request;
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

Route::get('/csrf-token', function () {
    return response()->json(['csrf_token' => csrf_token()]);
});

Route::controller(UserController::class)->group(function () {
    Route::get('/users', 'index');//->middleware('auth:sanctum');
    Route::get('/user/{user}', 'show');
    Route::get('/user/{user}/news', 'searchUserNews');
    Route::post('/user', 'store');
    Route::put('/user/{user}', 'update');
    Route::delete('/user/{user}', 'destroy');
});

Route::controller(CompanyController::class)->group(function () {
    Route::get('/company', 'index');
    Route::get('/company/{company}', 'show');
    Route::post('/company', 'store');
    Route::put('/company/{company}', 'update');
    Route::delete('/company/{company}', 'destroy');
});

Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout']);


Route::get('/send-message', function () {
    ProcessRabbitMQMessage::dispatch();
    
    return 'Message sent to RabbitMQ!';
});