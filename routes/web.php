<?php

use App\Http\Controllers\AdminDegreeController;
use App\Http\Controllers\AdminFacultyController;
use App\Http\Controllers\AdminSemesterController;
use App\Http\Controllers\AdminUserAutomationCheckController;
use App\Http\Controllers\AdminUserAutomationController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AttendanceController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function (Request $request) {
    if ($request->user()) {
        return redirect()->route('attendances.index');
    }

    return redirect()->route('login');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::redirect('dashboard', '/attendances')->name('dashboard');
    Route::get('attendances', [AttendanceController::class, 'index'])->name('attendances.index');
    Route::post('attendances', [AttendanceController::class, 'store'])->name('attendances.store');
    Route::put('attendances/{attendance}', [AttendanceController::class, 'update'])
        ->name('attendances.update');
    Route::delete('attendances/{attendance}', [AttendanceController::class, 'destroy'])
        ->name('attendances.destroy');
    Route::get('admin/attendances', [AttendanceController::class, 'adminIndex'])
        ->middleware('can:view-admin-attendances')
        ->name('admin.attendances.index');
    Route::get('admin/statistics', [AttendanceController::class, 'statistics'])
        ->middleware('can:view-admin-attendances')
        ->name('admin.statistics.index');
    Route::get('admin/statistics/csv', [AttendanceController::class, 'statisticsCsv'])
        ->middleware('can:view-admin-attendances')
        ->name('admin.statistics.csv');
    Route::get('admin/users', [AdminUserController::class, 'index'])
        ->middleware('can:manage-admin-users')
        ->name('admin.users.index');
    Route::post('admin/users', [AdminUserController::class, 'store'])
        ->middleware('can:manage-admin-users')
        ->name('admin.users.store');
    Route::patch('admin/users/automation', AdminUserAutomationController::class)
        ->middleware('can:manage-admin-users')
        ->name('admin.users.automation.update');
    Route::post('admin/users/automation/check', AdminUserAutomationCheckController::class)
        ->middleware('can:manage-admin-users')
        ->name('admin.users.automation.check');
    Route::put('admin/users/{user}', [AdminUserController::class, 'update'])
        ->middleware('can:manage-admin-users')
        ->name('admin.users.update');
    Route::delete('admin/users/{user}', [AdminUserController::class, 'destroy'])
        ->middleware('can:manage-admin-users')
        ->name('admin.users.destroy');
    Route::patch('admin/users/{user}/restore', [AdminUserController::class, 'restore'])
        ->middleware('can:manage-admin-users')
        ->name('admin.users.restore');
    Route::get('admin/semesters', [AdminSemesterController::class, 'index'])
        ->middleware('can:manage-semesters')
        ->name('admin.semesters.index');
    Route::post('admin/semesters', [AdminSemesterController::class, 'store'])
        ->middleware('can:manage-semesters')
        ->name('admin.semesters.store');
    Route::put('admin/semesters/{semester}', [AdminSemesterController::class, 'update'])
        ->middleware('can:manage-semesters')
        ->name('admin.semesters.update');
    Route::delete('admin/semesters/{semester}', [AdminSemesterController::class, 'destroy'])
        ->middleware('can:manage-semesters')
        ->name('admin.semesters.destroy');
    Route::patch('admin/semesters/{semester}/restore', [AdminSemesterController::class, 'restore'])
        ->middleware('can:manage-semesters')
        ->name('admin.semesters.restore');
    Route::get('admin/degrees', [AdminDegreeController::class, 'index'])
        ->middleware('can:manage-degrees')
        ->name('admin.degrees.index');
    Route::post('admin/degrees', [AdminDegreeController::class, 'store'])
        ->middleware('can:manage-degrees')
        ->name('admin.degrees.store');
    Route::put('admin/degrees/{degree}', [AdminDegreeController::class, 'update'])
        ->middleware('can:manage-degrees')
        ->name('admin.degrees.update');
    Route::delete('admin/degrees/{degree}', [AdminDegreeController::class, 'destroy'])
        ->middleware('can:manage-degrees')
        ->name('admin.degrees.destroy');
    Route::patch('admin/degrees/{degree}/restore', [AdminDegreeController::class, 'restore'])
        ->middleware('can:manage-degrees')
        ->name('admin.degrees.restore');
    Route::get('admin/faculties', [AdminFacultyController::class, 'index'])
        ->middleware('can:manage-faculties')
        ->name('admin.faculties.index');
    Route::post('admin/faculties', [AdminFacultyController::class, 'store'])
        ->middleware('can:manage-faculties')
        ->name('admin.faculties.store');
    Route::put('admin/faculties/{faculty}', [AdminFacultyController::class, 'update'])
        ->middleware('can:manage-faculties')
        ->name('admin.faculties.update');
    Route::delete('admin/faculties/{faculty}', [AdminFacultyController::class, 'destroy'])
        ->middleware('can:manage-faculties')
        ->name('admin.faculties.destroy');
    Route::patch('admin/faculties/{faculty}/restore', [AdminFacultyController::class, 'restore'])
        ->middleware('can:manage-faculties')
        ->name('admin.faculties.restore');
});

require __DIR__.'/settings.php';
