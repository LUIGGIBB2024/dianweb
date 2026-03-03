<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        //return response()->json(['message' => $request->email ." - " . $request->password ]);
        $credentials = $request->validate(['email' => ['required', 'email'], 'password' => ['required'],]);
        if (!Auth::attempt($credentials, $request->remember)) {
            return response()->json(['message' => 'Credenciales inválidas'], 401);
        }

        $user = Auth::user();
        //$token = $request->$user->createToken('auth_token')->plainTextToken;
        $token = $request->user()->createToken($user->email . '_Token')->plainTextToken;
        $companyname = Auth::user()->company->name;
        return response()->json(['message' => 'Login exitoso', 'user' => $user, 'token' => $token, 'company_name' => $companyname]);
    }

    public function register(Request $request)
    {
        // Lógica de registro de usuario
    }

    public function user(Request $request)
    {
        // Retornar información del usuario autenticado
    }
}
