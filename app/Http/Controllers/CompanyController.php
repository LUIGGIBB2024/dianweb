<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\JsonResponse;
use Illuminate\Support\Str;

use function PHPUnit\Framework\isNull;

class CompanyController extends Controller
{
    public function getCompanies(Request $request): JsonResponse
    {
        // return response()->json([
        //     'status' => 'success',
        //     'data' => 'Prueba de getCompanies',

        // ]);
        // ✅ Obtiene todos los registros del modelo Company
        //return response()->json($request);

        //'name','nit','dv','email','address','phone','token','technicalkey','endpoint1', 'endpoint2', 'city', 'date_from', 'date_to'

        $fechahoy = now()->format('y-m-d');
        $query = Company::select('id', 'nit', 'dv', 'representativeid', 'name', 'email', 'address', 'phone', 'token', 'technicalkey', 'endpoint1', 'endpoint2', 'city', 'date_from', 'date_to')
            ->selectRaw('DATEDIFF(?, date_to) AS days_difference', [$fechahoy])
            ->get();
        //$q =  Str::upper($request->q);      
        $q = ($request->q);

        if ($request->has('q') && !empty($request->q)) {
               return response()->json([
                            'data q 000' => $q,                       
                        ]);
            $query = Company::select('id', 'nit', 'dv', 'representativeid', 'name', 'email', 'address', 'phone', 'token', 'technicalkey', 'endpoint1', 'endpoint2', 'city', 'date_from', 'date_to')
                ->selectRaw('DATEDIFF(?, date_to) AS days_difference', [$fechahoy])
                ->where('name', 'like', "%{$q}%")
                ->orWhere('email', 'like', "%{$q}%")
                ->orWhere('phone', 'like', "%{$q}%")
                ->orWhere('address', 'like', "%{$q}%")
                ->orWhere('city', 'like', "%{$q}%")->get();
        }

        //$companies = $query->paginate($request->itemsPerPage ?? 10);
        $companies = $query;

        return response()->json([
            'data' => $companies,
            'total' => $companies->count(),
        ]);

        // $empresas = Company::all();

        // // ✅ Devuelve la lista en formato JSON
        // return response()->json([
        //     'data' => $empresas,
        //     'total' => $empresas->count(),
        // ]);
    }

    public function store(Request $request)
    {

        $data = $request->validate([
            'nit' => 'required|string|max:20',
            'dv' => 'nullable|string|max:1',
            'representativeid' => 'nullable|string|max:20',
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'city' => 'nullable|string|max:100',
            'endpoint1' => 'nullable|string|max:255',
            'endpoint2' => 'nullable|string|max:255',
            'token' => 'nullable|string|max:255',
            'date_from' => 'nullable',
            'date_to' => 'nullable',
        ]);

        $company = \App\Models\Company::create($data);
        return response()->json(['message' => 'Empresa creada exitosamente', 'company' => $company]);
    }

    public function update(Request $request, $id)
    {

        //return response()->json(['message' => 'Empresa actualizada exitosamente', 'fecha desde' => $request->date_from,'Fecha Hasta'=>$request->date_to]);
        $company = Company::findOrFail($id);

        $data = $request->validate([
            'nit' => 'required|string|max:20',
            'dv' => 'nullable|string|min:1',
            'representativeid' => 'nullable|string|max:20',
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'city' => 'nullable|string|max:100',
            'endpoint1' => 'nullable|string|max:255',
            'endpoint2' => 'nullable|string|max:255',
            'token' => 'nullable|string|max:255',
            'date_from' => 'nullable',
            'date_to' => 'nullable',
        ]);

        $company->update($data);

        return response()->json(['message' => 'Empresa actualizada exitosamente', 'company' => $company]);
    }

    public function destroy($id)
    {
        $company = Company::findOrFail($id);
        $company->delete();

        return response()->json(['message' => 'Empresa eliminada exitosamente']);
    }
}
