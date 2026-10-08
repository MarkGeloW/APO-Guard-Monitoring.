<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\GuardAttendanceController;
use App\Http\Controllers\Api\GuardDashboardController;
use App\Http\Controllers\Api\GuardDutyPingController;
use App\Http\Controllers\Api\GuardFaceController;
use App\Http\Controllers\Api\GuardNotificationController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware(['auth:sanctum', 'role:guard'])->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);

        Route::get('/dashboard', GuardDashboardController::class);
        Route::get('/attendance', [GuardAttendanceController::class, 'show']);
        Route::post('/attendance/check-in', [GuardAttendanceController::class, 'checkIn']);
        Route::post('/attendance/check-out', [GuardAttendanceController::class, 'checkOut']);
        Route::post('/duty/pings', [GuardDutyPingController::class, 'store']);
        Route::get('/face', [GuardFaceController::class, 'status']);
        Route::post('/face/enroll', [GuardFaceController::class, 'enroll']);
        Route::get('/alerts', [GuardNotificationController::class, 'index']);
        Route::patch('/alerts/{notification}/read', [GuardNotificationController::class, 'markAsRead']);
        Route::post('/alerts/read-all', [GuardNotificationController::class, 'markAllAsRead']);
    });
});
