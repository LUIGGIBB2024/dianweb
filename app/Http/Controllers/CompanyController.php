<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\JsonResponse;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

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
        $query = Company::select('id', 'nit', 'dv', 'representativeid', 'name', 'email', 'address', 'phone', 'token', 'technicalkey', 'endpoint1', 'endpoint2', 'endpoint3', 'certificatename', 'certificatekey', 'city', 'date_from', 'date_to', 'dian_email')
            ->selectRaw('DATEDIFF(?, date_to) AS days_difference', [$fechahoy])
            ->get();
        //$q =  Str::upper($request->q);      
        $q = ($request->q);

        if ($request->has('q') && !empty($request->q)) {
            return response()->json([
                'data q 000' => $q,
            ]);
            $query = Company::select('id', 'nit', 'dv', 'representativeid', 'name', 'email', 'address', 'phone', 'token', 'technicalkey', 'endpoint1', 'endpoint2', 'city', 'date_from', 'date_to', 'dian_email')
                ->selectRaw('DATEDIFF(?, date_to) AS days_difference', [$fechahoy])
                ->where('name', 'like', "%{$q}%")
                ->orWhere('email', 'like', "%{$q}%")
                ->orWhere('dian_email', 'like', "%{$q}%")
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

    public function store1(Request $request)
    {

        $data = $request->validate([
            'nit' => 'required|string|max:20',
            'dv' => 'nullable|string|max:1',
            'representativeid' => 'nullable|string|max:20',
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'dian_email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'city' => 'nullable|string|max:100',
            'endpoint1' => 'nullable|string|max:255',
            'endpoint2' => 'nullable|string|max:255',
            'endpoint3' => 'nullable|string|max:255',
            'token' => 'nullable|string|max:255',
            'certificatename' => 'nullable|string|max:255',
            'certificatekey' => 'nullable|string|max:255',
            'certificate_file' => 'nullable|file|mimes:pfx,p12,pem,crt,cer|max:2048',
            'date_from' => 'nullable',
            'date_to' => 'nullable',
        ]);

        $company = \App\Models\Company::create($data);
        return response()->json(['message' => 'Empresa creada exitosamente', 'company' => $company]);
    }

    public function store(Request $request)
    {
        Log::info('ENTRANDO A STORE', ['method' => $request->method()]);
        $data = $request->validate([
            'nit'             => 'required|string|max:20',
            'dv'              => 'nullable|string|max:1',
            'representativeid' => 'nullable|string|max:20',
            'name'            => 'required|string|max:255',
            'address'         => 'nullable|string|max:255',
            'email'           => 'nullable|email|max:255',
            'dian_email'      => 'nullable|email|max:255',
            'phone'           => 'nullable|string|max:20',
            'city'            => 'nullable|string|max:100',
            'endpoint1'       => 'nullable|string|max:255',
            'endpoint2'       => 'nullable|string|max:255',
            'endpoint3'       => 'nullable|string|max:255',
            'token'           => 'nullable|string|max:255',
            'certificatename' => 'nullable|string|max:255',
            'certificatekey'  => 'nullable|string|max:255',
            'date_from'       => 'nullable',
            'date_to'         => 'nullable',
            'certificate_file' => 'nullable|file|mimes:pfx,p12,pem,crt,cer|max:2048',
        ]);

        // Guardar el archivo si viene en el request
        if ($request->hasFile('certificate_file')) {
            $nit      = $request->input('nit');
            $file     = $request->file('certificate_file');
            $fileName = $file->getClientOriginalName();

            // Guarda en: storage/app/certificates/{nit}/{nombreOriginal}
            $path = $file->storeAs("certificates/{$nit}", $fileName);

            // Opcional: guardar la ruta en los datos
            $data['certificatename'] = $fileName;
            // $data['certificate_path'] = $path; // si tienes esta columna en la tabla
        }

        // Remover el archivo del array antes de crear el modelo
        unset($data['certificate_file']);

        $company = \App\Models\Company::create($data);

        return response()->json([
            'message' => 'Empresa creada exitosamente',
            'company' => $company,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        Log::info('ENTRANDO A UPDATE', ['id' => $id, 'method' => $request->method()]);

        Log::info('ENTRANDO A UPDATE', ['id' => $id]);

        if ($request->hasFile('certificate_file')) {
            $file = $request->file('certificate_file');
            Log::info('MIME TYPE:', [
                'mime'      => $file->getMimeType(),
                'extension' => $file->getClientOriginalExtension(),
            ]);
        }

        $company = Company::findOrFail($id);

        $data = $request->validate([
            'nit'              => 'required|string|max:20',
            'dv'               => 'nullable|string|min:1',
            'representativeid' => 'nullable|string|max:20',
            'name'             => 'required|string|max:255',
            'address'          => 'nullable|string|max:255',
            'email'            => 'nullable|email|max:255',
            'dian_email'       => 'nullable|email|max:255',
            'phone'            => 'nullable|string|max:20',
            'city'             => 'nullable|string|max:100',
            'endpoint1'        => 'nullable|string|max:255',
            'endpoint2'        => 'nullable|string|max:255',
            'endpoint3'        => 'nullable|string|max:255',
            'certificatename'  => 'nullable|string|max:255',
            'certificatekey'   => 'nullable|string|max:255',
            'certificate_file' => 'nullable|file|max:2048',
            'token'            => 'nullable|string|max:255',
            'date_from'        => 'nullable',
            'date_to'          => 'nullable',
        ]);

        if ($request->hasFile('certificate_file')) {
            $file = $request->file('certificate_file');

            // Validar extensión manualmente
            $extension = strtolower($file->getClientOriginalExtension());
            $allowed   = ['pfx', 'p12', 'pem', 'crt', 'cer'];
            $fileName = $file->getClientOriginalName();

            if (!in_array($extension, $allowed)) {
                return response()->json([
                    'message' => 'Formato de certificado no permitido.',
                    'errors'  => ['certificate_file' => ['Solo se permiten archivos: .pfx, .p12, .pem, .crt, .cer']]
                ], 422);
            }

            // ✅ 2. Verificar que el archivo llegó correctamente
            Log::info('ARCHIVO:', [
                'nombre'   => $fileName,
                'tamaño'   => $file->getSize(),
                'mime'     => $file->getMimeType(),
                'valido'   => $file->isValid(),
            ]);

            // Eliminar el archivo anterior si existe
            $nit = $company->nit;
            $oldPath = "certificates/{$company->nit}/{$company->certificatename}";
            if ($company->certificatename && Storage::exists($oldPath)) {
                Storage::delete($oldPath);
            }

            // ✅ 3. Verificar que se guardó correctamente
            $path = $file->storeAs("certificates/{$nit}", $fileName);
            Log::info('RUTA GUARDADA:', ['path' => $path]);

            if (!$path) {
                return response()->json(['message' => 'Error al guardar el archivo'], 500);
            }

            $data['certificatename'] = $fileName;
        }

        unset($data['certificate_file']);
        $company->update($data);

        return response()->json([
            'message' => 'Empresa actualizada exitosamente',
            'company' => $company->fresh(),
        ], 201);
    }


    public function destroy($id)
    {
        $company = Company::findOrFail($id);
        $company->delete();

        return response()->json(['message' => 'Empresa eliminada exitosamente']);
    }
}
