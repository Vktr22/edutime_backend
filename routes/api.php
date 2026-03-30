<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\TeacherController;
use App\Http\Controllers\AppointmentController;


Route::get('/debug-token', function (Request $request) {
    return response()->json([
        'auth_header' => $request->header('Authorization'),
        'bearer_token' => $request->bearerToken(),
    ]);
});

Route::middleware('auth:sanctum')->get('/test', function (Request $request) {
    $user = $request->user();

    //->group() = minden benne lévő route megkapja ugyan azokat a middleware-ket
    //auth:sanctum=csak érvenyes tokennel lehet hivni az endpointot vaagyis bejelentkezés szükséges
    //tanulo utvonalai
    Route::middleware(['auth:sanctum', 'student'])->group(function () {
        //le tudja kerni az osszes tanart az adataival egyutt(ugye meghivja a teacher contoller index fuggvenyet, ami minden tanart leker listaba a nev+email+id-val)
        //es ezt ugy tudja megtenni, h a keresobe a vegen oda irja, h /teachers
        Route::get('/teachers', [TeacherController::class, 'index']);
        //a bongeszobe /teachers/{id} és a teach oszt. show fuggvenyet kapja, vagyis annak a tanarnak az adatait kapja vissza(id,email,nev)
        Route::get('/teachers/{id}', [TeacherController::class, 'show']);
        //az elozoekhez hasonloan, csak ez az idopont foglalas
        Route::post('/teachers/{id}/appointments', [AppointmentController::class, 'studentBook']);
        //ez meg a sjat idopontjai
        Route::get('/student/appointments', [AppointmentController::class, 'studentIndex']);
    });

    //ez ugye a tanaroknak van
    Route::middleware(['auth:sanctum', 'teacher'])->group(function () {
        //ez meg csak az adott tanar meglevo idopontjait keri le
        Route::get('/teacher/appointments', [AppointmentController::class, 'teacherIndex']);
    });

    /*
    return response()->json([
        'message' => 'sanctum_auth_ok',
        'user' => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role ?? null,
        ],
    ]);
    */
});




