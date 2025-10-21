<?php

use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\GuideController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Hunting Tour Booking API Routes
Route::get('/guides', [GuideController::class, 'index']);
Route::post('/bookings', [BookingController::class, 'store']);
