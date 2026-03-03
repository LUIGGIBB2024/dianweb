<?php

namespace App\Http\Controllers;

use App\Models\Control;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Js;

class ControlController extends Controller
{
    public function getControl($id):JsonResponse
    {
        $control = Control::find($id);

        if (!$control) {
            return response()->json([
                'message' => 'Control not found',
            ], 404);
        }

        return response()->json([
            'message' => 'Control retrieved successfully',
            'data' => $control,
        ]);
    }

    public function index(Request $request):JsonResponse
    {
        // Lógica para manejar la solicitud y devolver los datos necesarios
        $control = Control::all();
        if ($control.count() == 0) {
            $data = $request->validate([
                'name' => 'required|string|max:255',
                'address' => 'nullable|string|max:255',
                'email' => 'nullable|email|max:255',
                'phone' => 'nullable|string|max:100',
                'nit' => 'nullable|string|max:20',
                'token' => 'nullable|string|max:255',
                'idsoftware' => 'nullable|string|max:255',
                'technicalkey' => 'nullable|string|max:255',
                'testsetid' => 'nullable|string|max:255',
                'email' => 'nullable|string|max:255',
                'password' => 'nullable|string|max:255',
                'path' => 'nullable|string|max:255',
            ]);
            $control = Control::create($data);            
        }           

        return response()->json([
                'message' => 'Tabla de Control - Datos creados exitosamente',
                'control' => $control,]);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:100',
            'nit' => 'nullable|string|max:20',
            'token' => 'nullable|string|max:255',
            'idsoftware' => 'nullable|string|max:255',
            'technicalkey' => 'nullable|string|max:255',
            'testsetid' => 'nullable|string|max:255',
            'email' => 'nullable|string|max:255',
            'password' => 'nullable|string|max:255',
            'path' => 'nullable|string|max:255',
        ]);

        $control = Control::findOrFail($id);    

        $control->update($data);
        
        return response()->json([
            'message' => 'Control created successfully',
            'control' => $control,
        ], 201);
    }

}
