<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\TeacherAvailabilityController;

/*
    autentikacios utvonalak
    -> ezek az utvonalak kezelik az api auth-ot, sanctum bearer token-t hasznalva
        - login -> PUBLIC
        - minden mas auth:sanctummal vedve
*/

// API login – returns bearer token + user
Route::post('/login', [AuthController::class, 'login']);

// API logout – optional, removes current token
Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);

// Return currently authenticated user
Route::middleware('auth:sanctum')->get('/profile', function (Request $request) {
    return $request->user();
});

/*
    student utvonalak
    (csak bejelentk role stud)
*/

Route::middleware(['auth:sanctum', 'student'])->group(function () {

    // List all teachers
    Route::get('/teachers', [TeacherController::class, 'index']);

    // Show a single teacher
    Route::get('/teachers/{id}', [TeacherController::class, 'show']);

    // Book an appointment with a teacher
    Route::post('/teachers/{id}/appointments', [AppointmentController::class, 'studentBook']);

    // Get student's own appointments
    Route::get('/student/appointments', [AppointmentController::class, 'studentIndex']);

    // NEW: teacher available timeslots
    Route::get('/teachers/{id}/available-slots', [TeacherAvailabilityController::class, 'availableSlots']);

});

/*
    teacher utvonalak
    (csak bejelentk role teach)
*/

Route::middleware(['auth:sanctum', 'teacher'])->group(function () {

    // Get teacher's own appointments
    Route::get('/teacher/appointments', [AppointmentController::class, 'teacherIndex']); 
    Route::get('/teacher/availability', [TeacherAvailabilityController::class, 'index']);
    Route::post('/teacher/availability', [TeacherAvailabilityController::class, 'store']);
    Route::delete('/teacher/availability/{id}', [TeacherAvailabilityController::class, 'destroy']);

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