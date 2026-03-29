<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        //return response()->json(['message' => $request->email . " - " . $request->password]);
        $credentials = $request->validate(['email' => ['required', 'email'], 'password' => ['required'],]);
        if (!Auth::attempt($credentials, $request->remember)) {
            return response()->json(['message' => 'Credenciales inválidas'], 401);
        }


        $user = Auth::user();
        //$token = $request->$user->createToken('auth_token')->plainTextToken;
        $token = $request->user()->createToken($user->email . '_Token')->plainTextToken;
        $companyname = Auth::user()->company->name;
        $username = Auth::user()->name;

        //return response()->json(['message' => $request->email . " - 300 - " . $request->password]);

        return response()->json([
            'message' => 'Login exitoso',
            'user' => $user,
            'token' => $token,
            'company_name' => $companyname,
            'user_id' => Auth::user()->id,
            'company_id' => Auth::user()->company->id,
            'url_n8n' => Auth::user()->company->endpoint2,
            'nit_empresa' => Auth::user()->company->nit,
            'representante_legal' => Auth::user()->company->representativeid,
            'user_name' => $username
        ]);
    }

    public function loginn8n(Request $request)
    {
        $request->validate([
            'email'    => ['required', 'email'],
            'code_n8n' => ['required'],
        ]);

        // Mapear code_n8n → password para que Auth::attempt lo entienda
        $credentials = [
            'email'    => $request->email,
            'password' => $request->code_n8n,
        ];

        // if (!Auth::attempt($credentials, $request->remember)) {
        //     return response()->json(['message' => 'Credenciales inválidas'], 401);
        // }

        $user        = Auth::user();
        $token       = $request->user()->createToken($user->email . '_Token')->plainTextToken;
        $companyname = $user->company->name;
        $username    = $user->name;

        return response()->json([
            'message'      => 'Login exitoso',
            'user'         => $user,
            'token'        => $token,
            'company_name' => $companyname,
            'user_name'    => $username,
            'url_dian'     => $request->url_dian,
            'nit_dian'     => $request->nit_dian,
            'code_n8n'     => $request->code_n8n
        ]);
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
