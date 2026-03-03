<?php

namespace App\Http\Controllers;

use Symfony\Component\HttpFoundation\JsonResponse;
use App\Models\User;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function getUsers(Request $request): JsonResponse
    {
        $companies = Company::all();
        $q = $request->q;
        $users = [];
        $query = [];
        
        if ($request->has('q') && !empty($request->q)) 
        {
            $query = User::SelectRaw('users.id, users.name, users.email, companies.name as empresa, users.type, users.company_id')
                ->leftJoin('companies', 'users.company_id', '=', 'companies.id')
                ->where('users.name', 'like', "%{$q}%")
                ->orWhere('users.email', 'like', "%{$q}%")
                ->orWhere('users.type', 'like', "%{$q}%")            
                ->orWhere('companies.name', 'like', "%{$q}%")->get();
        } else 
        {
            $query = User::SelectRaw('users.id, users.name, users.email, companies.name as empresa, users.type, users.company_id')
                ->leftJoin('companies', 'users.company_id', '=', 'companies.id')->get();
        }

        $users = $query;

        return response()->json([
            'data' => $users,
            'companies' => $companies,
            'total' => $users->count(),
        ]);;
    }

    public function store(Request $request): JsonResponse
    {        
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'password' => 'nullable|string|min:6|max:255',
            'company_id' => 'nullable|exists:companies,id',
        ]);

        // 🔒 Si viene el campo 'password', lo convertimos con hashing
        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $user = User::create($data);
        return response()->json(['message' => 'Usuario creado exitosamente', 'user' => $user]);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $user = User::findOrFail($id);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'company_id' => 'nullable|exists:companies,id',
        ]);

        $user->update($data);
        return response()->json(['message' => 'Usuario actualizado exitosamente', 'user' => $user]);
    }

    public function updatePassword(Request $request, $id): JsonResponse
    {
        $user = User::findOrFail($id);    

        $data = $request->validate([
            'password' => 'required|string|min:6|max:255',
        ]);

        // 🔒 Si viene el campo 'password', lo convertimos con hashing
        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $user->update($data);
        return response()->json(['message' => 'Usuario actualizado exitosamente', 'user' => $user]);
    }

    public function destroy($id): JsonResponse
    {
        $user = User::findOrFail($id);
        $user->delete();

        return response()->json(['message' => 'Usuario eliminado exitosamente']);
    }   
}
