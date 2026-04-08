<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller{
    
    /**
     * Bearer (Sanctum personal access) token-es api auth
     * 
     * lepesek:
     * - login()    ->login adatok validacioja, token letreh, visszater user+token
    */
    public function login(Request $request){
        /**
         * email+passw fogad
         * validalja, h mind2 letezik
         * user megtalalasa email alapjan
         * password check
         * (meg nincs token generalas)
         */

        //a frontendtol jovo input mezok validalasa
        $validated = $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        //user mgtalalasa email alapjan
        $user = \App\Models\User::where('email', $validated['email'])->first();

        // Hibás belépési adatok esetén egységes, biztonságos hibaüzenet (401)
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


        //Bearer token generalasa
        //createToken('api_token') ----> rekordot hoz letre a personal_access_tokens tablaba
        // az 'api_token' csak egy nev, tetszoleges str lehet
        //plainTextToken ----> visszaadjaa plain text tokent, amit el tudokkuldeni a frontendnek + a db-ben ennek a hash-e lesz eltarolva(nem a plain string) - mint fentebb
        $token = $user->createToken('api_token')->plainTextToken;

        //json valasz
        return response()->json([
            'message' => 'login successful',
            'token' => $token,
            'user'    => $user,
        ], 200);    //statuszkod beallitasa
    }

    public function logout(Request $request){
        //torli az access tokent
        $request->user()
            ?->currentAccessToken()
            ?->delete();

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }
}
