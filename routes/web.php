<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeploymentController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\MonitoringController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PersonnelController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::middleware(['auth', 'role:admin,supervisor'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::middleware('role:admin')->group(function () {
        Route::resource('users', UserController::class)->except(['show']);
        Route::patch('/users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');
    });

    Route::middleware('role:admin,supervisor')->group(function () {
        Route::resource('personnel', PersonnelController::class);
        Route::resource('locations', LocationController::class)->except(['show']);
        Route::resource('deployments', DeploymentController::class)->except(['show']);

        Route::get('/monitoring', [MonitoringController::class, 'index'])->name('monitoring.index');
        Route::get('/monitoring/live', [MonitoringController::class, 'liveData'])->name('monitoring.live');

        Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');

        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('/', [ReportController::class, 'index'])->name('index');
            Route::get('/deployments', [ReportController::class, 'deployment'])->name('deployments');
            Route::get('/deployments/pdf', [ReportController::class, 'deploymentPdf'])->name('deployments.pdf');
            Route::get('/deployments/export', [ReportController::class, 'exportDeployments'])->name('deployments.export');
            Route::get('/attendance', [ReportController::class, 'attendance'])->name('attendance');
            Route::get('/attendance/pdf', [ReportController::class, 'attendancePdf'])->name('attendance.pdf');
            Route::get('/attendance/export', [ReportController::class, 'exportAttendance'])->name('attendance.export');
            Route::get('/personnel', [ReportController::class, 'personnel'])->name('personnel');
            Route::get('/personnel/pdf', [ReportController::class, 'personnelPdf'])->name('personnel.pdf');
            Route::get('/personnel/export', [ReportController::class, 'exportPersonnel'])->name('personnel.export');
        });
    });

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
