<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

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

        //ha nincs user, 422 hibakoddal ter vissza
        //422 hiba: a server ertette a kerest, de nem tudja elveg ervenytelen v hianyzo adatok miatt
        if (! $user) {
            return response()->json([
                'message' => 'Invalid credentials',
            ], 422);
        }

        //ha a jelszo nem egyezik, akk is 422
        //Hash::check => osszehasonlitja a plaintext passw az ab hash-evel
        //VISZONT itt ha rossz, biztonsagi okokbol nem lathato, h melyik
        if (! \Illuminate\Support\Facades\Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials',
            ], 422);
        }

        //json valasz
        return response()->json([
            'message' => 'valid login',
            'user'    => $user,
        ]);
    }
}
