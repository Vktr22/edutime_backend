<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $user = \App\Models\User::where('email', $validated['email'])->first();

        if (! $user) {
            return response()->json([
                'message' => 'Hibás email cím vagy jelszó',
            ], 401);
        }


        if (! Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'message' => 'Hibás email cím vagy jelszó',
            ], 401);
        }

        $token = $user->createToken('api_token')->plainTextToken;

        return response()->json([
            'message' => 'login successful',
            'token' => $token,
            'user'    => $user,
        ], 200);
    }

    public function logout(Request $request)
    {
        $request->user()
            ?->currentAccessToken()
            ?->delete();

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }
}
