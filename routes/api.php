<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/debug-token', function (Request $request) {
    return response()->json([
        'auth_header' => $request->header('Authorization'),
        'bearer_token' => $request->bearerToken(),
    ]);
});

Route::middleware('auth:sanctum')->get('/test', function (Request $request) {
    $user = $request->user();

    return response()->json([
        'message' => 'sanctum_auth_ok',
        'user' => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role ?? null,
        ],
    ]);
});




