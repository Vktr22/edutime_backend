<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\TeacherAvailabilityController;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);

Route::middleware('auth:sanctum')->get('/profile', function (Request $request) {
    return $request->user();
});


Route::middleware(['auth:sanctum', 'student'])->group(function () {

    Route::get('/teachers', [TeacherController::class, 'index']);

    Route::get('/teachers/{id}', [TeacherController::class, 'show']);

    Route::post('/teachers/{id}/appointments', [AppointmentController::class, 'studentBook']);

    Route::get('/student/appointments', [AppointmentController::class, 'studentIndex']);

    Route::get('/teachers/{id}/available-slots', [TeacherAvailabilityController::class, 'availableSlots']);

    Route::delete('/student/appointments/{id}', [AppointmentController::class, 'studentCancel']);
});


Route::middleware(['auth:sanctum', 'teacher'])->group(function () {

    Route::get('/teacher/appointments', [AppointmentController::class, 'teacherIndex']);
    Route::get('/teacher/availability', [TeacherAvailabilityController::class, 'index']);
    Route::post('/teacher/availability', [TeacherAvailabilityController::class, 'store']);
    Route::delete('/teacher/availability/{id}', [TeacherAvailabilityController::class, 'destroy']);
    Route::delete('/teacher/appointments/{id}', [AppointmentController::class, 'teacherCancel']);
});

/*
|--------------------------------------------------------------------------
| DEBUG ROUTES (optional, dev only)
|--------------------------------------------------------------------------
*/

Route::get('/debug-token', function (Request $request) {
    return response()->json([
        'auth_header'  => $request->header('Authorization'),
        'bearer_token' => $request->bearerToken(),
    ]);
});
