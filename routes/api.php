<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\BarberController;

Route::get('/unauthorized', [AuthController::class, 'unauthorized'])->name('login');

Route::group(['prefix' => 'auth'], function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
});

Route::post('/register', [AuthController::class, 'register']);

Route::get('/profile', [UserController::class, 'details']);
Route::put('/profile', [UserController::class, 'update']);
Route::post('/user/avatar', [UserController::class, 'setAvatar']);
Route::get('/users/favorites', [UserController::class, 'getFavorites']);
Route::post('/users/favorites', [UserController::class, 'setFavorites']);
Route::get('/users/appointments', [UserController::class, 'getAppointments']);

Route::get('/barbers', [BarberController::class, 'getBarbers']);
Route::get('/barbers/{barber}', [BarberController::class, 'getBarber']);
Route::post('/barbers/{barber}/appointment', [BarberController::class, 'setAppointment']);

Route::get('/search', [BarberController::class, 'searchBarbers']); 
