<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\PersonalAccessToken;

Route::get('/debug-token', function (Request $request) {
    $plainToken = $request->bearerToken();
    $plainToken = trim($plainToken, '"');

    // Sanctum only hashes the part after "id|" from plainTextToken.
    $tokenParts = $plainToken ? explode('|', $plainToken, 2) : [];
    $tokenForHash = count($tokenParts) === 2 ? $tokenParts[1] : $plainToken;
    $hashedToken = $tokenForHash ? hash('sha256', $tokenForHash) : null;
    
    $dbToken = $hashedToken ? PersonalAccessToken::where('token', $hashedToken)->first() : null;
    
    // Összes token az adatbázisból teljes értékkel
    $allTokens = PersonalAccessToken::limit(5)->get(['id', 'token', 'tokenable_id'])->map(function($token) {
        return [
            'id' => $token->id,
            'tokenable_id' => $token->tokenable_id,
            'stored_token' => substr($token->token, 0, 20) . '...',
            'token_length' => strlen($token->token),
        ];
    })->toArray();
    
    return response()->json([
        'auth_header'      => $request->header('Authorization'),
        'bearer_token'     => $request->bearerToken(),
        'cleaned_token'    => $plainToken,
        'cleaned_length'   => strlen($plainToken),
        'token_for_hash'   => $tokenForHash,
        'hashed_token'     => $hashedToken,
        'found_in_db'      => $dbToken ? 'YES' : 'NO',
        'all_db_tokens'    => $allTokens,
    ]);
});


// SAJÁT token alapú auth teszt – NEM auth:sanctum middleware-rel
Route::get('/test', function (Request $request) {

    $plainToken = $request->bearerToken();

    if (!$plainToken) {
        return response()->json(['message' => 'No token provided'], 401);
    }

    // Távolítsd el az extra idézőjeleket, ha vannak
    $plainToken = trim($plainToken, '"');

    // Sanctum only hashes the part after "id|" from plainTextToken.
    $tokenParts = explode('|', $plainToken, 2);
    $tokenForHash = count($tokenParts) === 2 ? $tokenParts[1] : $plainToken;

    // Sanctum a token SHA-256 hash-ét tárolja az adatbázisban
    $hashedToken = hash('sha256', $tokenForHash);

    $pat = PersonalAccessToken::where('token', $hashedToken)->first();

    if (!$pat) {
        return response()->json(['message' => 'Invalid token'], 401);
    }

    $user = $pat->tokenable; // ez lesz a User model példány

    return response()->json([
        'message' => 'custom_auth_ok',
        'user'    => [
            'id'    => $user->id,
            'name'  => $user->name,
            'email' => $user->email,
            'role'  => $user->role ?? null,
        ],
    ]);
});




