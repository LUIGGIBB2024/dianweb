<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;


class ApidianController extends Controller
{
    public function loaddata(Request $request): JsonResponse
    {

        $desde = $request->desdefecha;
        $hasta = $request->hastafecha;

        $id_company   = Auth::user()->company_id;
        $info_control = Company::find($id_company);
        $facturas = []; // 👈 valor por defecto

        $cuantos = 0;

        //return response()->json(['message' => 'Voy Aqui 200', 'Empresa :' => $info_control,'Id:' => $id_company]);

        if ($desde && $hasta) {

            $endpoint = trim($info_control->endpoint1);
            $nit = trim($info_control->nit);
            $endpoint = "{$endpoint}/information/{$nit}/{$desde}/{$hasta}";

            $endpoint = preg_replace('/\\s+/', '', $endpoint);

            $response = Http::withHeaders(['Content-Type' => 'application/json; charset=UTF-8'])
                ->get($endpoint);

            if ($response->successful()) {
                $registros = $response->json();
                $datos = $registros['data'];
                $facturas = collect($datos)->flatMap(function ($tipoDoc) {
                    return collect($tipoDoc['documents'])->map(function ($doc) use ($tipoDoc) {
                        return [
                            'id'            => $doc['id'],
                            'number'        => $doc['number'],
                            'prefix'        => $doc['prefix'],
                            'customer'      => $doc['customer'],
                            'date_issue'    => $doc['date_issue'],
                            'client_name'   => $doc['client']['name'] ?? null,
                            'sale'          => $doc['sale'] ?? null,
                            'document_type' => $tipoDoc['type_document_id'] ?? null, // 👈 ahora se toma correctamente
                            'document_name' => $tipoDoc['name'] ?? null,
                        ];
                    });
                })->sortBy('date_issue')->values();
            }
        }

        // 👇 Siempre enviamos algo a la vista, aunque sea vacío   

        $page = (int) $request->input('page');
        $perPage = (int) $request->input('per_page');

        $total = $facturas->count();
        $offset = ($page - 1) * $perPage;

        $items = $facturas->slice($offset, $perPage)->values();

        // 🔹 Total de ventas
        $totalVentas = $facturas->sum(function ($factura) {
            return (float) $factura['sale'];
        });


        return response()->json([
            'message' => 'Información generada exitosamente',
            'data' => $items,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'totaldctos' => $totalVentas,
        ]);
    }

    public function load_notes(Request $request): JsonResponse
    {

        $desde = $request->desdefecha;
        $hasta = $request->hastafecha;

        $id_company   = Auth::user()->company_id;
        $info_control = Company::find($id_company);
        $facturas = []; // 👈 valor por defecto

        $cuantos = 0;

        //return response()->json(['message' => 'Voy Aqui 200', 'Empresa :' => $info_control,'Id:' => $id_company]);

        if ($desde && $hasta) {

            $endpoint = trim($info_control->endpoint1);
            $nit = trim($info_control->nit);
            $endpoint = "{$endpoint}/information/{$nit}/{$desde}/{$hasta}";

            $endpoint = preg_replace('/\\s+/', '', $endpoint);

            $response = Http::withHeaders(['Content-Type' => 'application/json; charset=UTF-8'])
                ->get($endpoint);

            if ($response->successful()) {
                $registros = $response->json();
                $datos = $registros['data'];

                $notas = collect($datos)
                    ->filter(function ($tipoDoc) {
                        // 👇 Solo incluir si el type_document_id es mayor que 1
                        return $tipoDoc['type_document_id'] > 1 &&  $tipoDoc['type_document_id'] <= 5;
                    })
                    ->flatMap(function ($tipoDoc) {
                        return collect($tipoDoc['documents'])->map(function ($doc) use ($tipoDoc) {
                            return [
                                'id'            => $doc['id'],
                                'number'        => $doc['number'],
                                'prefix'        => $doc['prefix'],
                                'customer'      => $doc['customer'],
                                'date_issue'    => $doc['date_issue'],
                                'client_name'   => $doc['client']['name'] ?? null,
                                'sale'          => $doc['sale'] ?? null,
                                'document_type' => $tipoDoc['type_document_id'] ?? null,
                                'document_name' => $tipoDoc['name'] ?? null,
                            ];
                        });
                    })
                    ->sortBy('date_issue')->values();
            }
        }

        // 👇 Siempre enviamos algo a la vista, aunque sea vacío   

        $page = (int) $request->input('page');
        $perPage = (int) $request->input('per_page');

        $total = $notas->count();
        $offset = ($page - 1) * $perPage;

        $items = $notas->slice($offset, $perPage)->values();

        // 🔹 Total de ventas
        $totalnotas = $notas->sum(function ($factura) {
            return (float) $factura['sale'];
        });


        return response()->json([
            'message' => 'Información generada exitosamente',
            'data' => $items,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'totaldctos' => $totalnotas,
        ]);
    }


    public function load_payroll(Request $request): JsonResponse
    {

        $desde = $request->desdefecha;
        $hasta = $request->hastafecha;

        $id_company   = Auth::user()->company_id;
        $info_control = Company::find($id_company);
        $token      = trim($info_control->token);
        $facturas = []; // 👈 valor por defecto

        $response = "";

        //return response()->json(['message' => 'Voy Aqui 200', 'Empresa :' => $info_control,'Id:' => $id_company]);

        if ($desde && $hasta) 
        {

            $endpoint = trim($info_control->endpoint1);
            $nit = trim($info_control->nit);
            //$endpoint = "{$endpoint}/information/{$nit}/{$desde}/{$hasta}";
            $endpoint = "{$endpoint}/table/document_payrolls/identification_number/{$nit}";

            //$endpoint = "http://89.116.31.33:81/api/table/document_payrolls/identification_number/901148547";

            $endpoint = preg_replace('/\\s+/', '', $endpoint);

            $response = Http::withHeaders([
                'Content-Type' => 'application/json; charset=UTF-8',
                'Authorization' => 'Bearer ' . $token
            ])->get($endpoint);

            //return response()->json($response->data);
           
            if ($response->successful())
            {
                // Decodificar el JSON
                //return response()->json(['Data 100' => ('data'),'nit'=>$nit,'endpoint:'=>$endpoint], 200);

                $registros = $response->json();
                //$registros = $response;

                $datos = $registros['data'];                   

                $payroll = collect($datos)
                    ->filter(function ($tipoDoc) use ($desde, $hasta) 
                    {
                        // 👇 Solo incluir si el type_document_id es mayor que 1
                        $fecha = Carbon::parse($tipoDoc['date_issue']);             

                        return $tipoDoc['state_document_id'] == 1 && $fecha->between(Carbon::parse($desde)->startOfDay(),Carbon::parse($hasta)->endOfDay());

                    })->sortBy('date_issue')->values();

                $mapped = $payroll->map(function ($item) 
                {
                    $requestApi = json_decode($item['request_api'], true);       

                    $typeDocumentName = ($item['type_document_id'] == 9)
                        ? 'Nómina Individual'
                        : 'Nómina de Reemplazo / Eliminación';

                    return [
                        'id' => $item['id'],
                        'identification_number' => $item['identification_number'],
                        'state_document_id' => $item['state_document_id'],
                        'type_document_id' => $item['type_document_id'],
                        'type_document_name' => $typeDocumentName,
                        'prefix' => $item['prefix'],
                        'consecutive' =>  $item['prefix'] . $item['consecutive'],
                        'employee_id' => $item['employee_id'],
                        'date_issue' => $item['date_issue'],
                        'accrued_total' => $item['accrued_total'],
                        'deductions_total' => $item['deductions_total'],
                        'total_payroll' => $item['total_payroll'],
                        'settlement_start_date' => $requestApi['period']['settlement_start_date'] ?? null,
                        'settlement_end_date' => $requestApi['period']['settlement_end_date'] ?? null,
                        'surname' => $requestApi['worker']['surname'] ?? null,
                        'second_surname' => $requestApi['worker']['second_surname'] ?? null,
                        'employee_name' => $requestApi['worker']['surname'] . " " . $requestApi['worker']['first_name'] . " " .  $requestApi['worker']['second_surname'] ?? null,
                        'first_name' => $requestApi['worker']['first_name'] ?? null,
                    ];
                });    

                // Ordenar por apellido
                $sorted = $mapped->sortBy('employee_name')->values();

                //return response()->json($sorted);
                $page = (int) $request->input('page');
                $perPage = (int) $request->input('per_page');

                $total = $sorted->count();
                $offset = ($page - 1) * $perPage;

                $items = $sorted->slice($offset, $perPage)->values();

                // 🔹 Total de ventas
                $totalNomina = $sorted->sum(function ($sorted) {
                    return (float) $sorted['total_payroll'];
                });

                return response()->json([
                    // 'message' => 'Información generada exitosamente',
                    'data' => $items,
                    'total' => $total,
                    'page' => $page,
                    'per_page' => $perPage,
                    'totaldctos' => $totalNomina,
                ]);   
            }
             return response()->json([
                'error' => 'Error de Cargue de Información',
                'status' => $response->status(),
            ], $response->status());
        }
                
        return response()->json([
            'error' => 'No Existe Información en este Rango de Fechas',
            'status' => $response()->status(),
        ], '201');
    }

    public function load_support(Request $request): JsonResponse
    {

        $desde = $request->desdefecha;
        $hasta = $request->hastafecha;

        $id_company   = Auth::user()->company_id;
        $info_control = Company::find($id_company);
        $facturas = []; // 👈 valor por defecto

        $cuantos = 0;

        //return response()->json(['message' => 'Voy Aqui 200', 'Empresa :' => $info_control,'Id:' => $id_company]);

        if ($desde && $hasta) {

            $endpoint = trim($info_control->endpoint1);
            $nit = trim($info_control->nit);
            $endpoint = "{$endpoint}/information/{$nit}/{$desde}/{$hasta}";

            $endpoint = preg_replace('/\\s+/', '', $endpoint);

            $response = Http::withHeaders(['Content-Type' => 'application/json; charset=UTF-8'])
                ->get($endpoint);

            if ($response->successful()) {
                $registros = $response->json();
                $datos = $registros['data'];

                $datos = $registros['data'];

                $notas = collect($datos)
                    ->filter(function ($tipoDoc) {
                        // 👇 Solo incluir si el type_document_id es mayor que 1
                        return $tipoDoc['type_document_id'] > 1 &&  $tipoDoc['type_document_id'] == 11;
                    })
                    ->flatMap(function ($tipoDoc) {
                        return collect($tipoDoc['documents'])->map(function ($doc) use ($tipoDoc) {
                            return [
                                'id'            => $doc['id'],
                                'number'        => $doc['number'],
                                'prefix'        => $doc['prefix'],
                                'customer'      => $doc['customer'],
                                'date_issue'    => $doc['date_issue'],
                                'client_name'   => $doc['client']['name'] ?? null,
                                'sale'          => $doc['sale'] ?? null,
                                'document_type' => $tipoDoc['type_document_id'] ?? null,
                                'document_name' => $tipoDoc['name'] ?? null,
                            ];
                        });
                    })
                    ->sortBy('date_issue')->values();
            }
        }

        // 👇 Siempre enviamos algo a la vista, aunque sea vacío   

        $page = (int) $request->input('page');
        $perPage = (int) $request->input('per_page');

        $total = $notas->count();
        $offset = ($page - 1) * $perPage;

        $items = $notas->slice($offset, $perPage)->values();

        // 🔹 Total de ventas
        $totalnotas = $notas->sum(function ($factura) {
            return (float) $factura['sale'];
        });


        return response()->json([
            'message' => 'Información generada exitosamente',
            'data' => $items,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'totaldctos' => $totalnotas,
        ]);
    }



    public function downxml(Request $request)
    {
        // return response()->json(['NumeroFactura' => $request->numberdocument,'prefijo'=>$request->prefix]);

        $numberdocument  = $request->numberdocument;
        $prefix         = $request->prefix;

        $id_company   = Auth::user()->company_id;
        $info_control = Company::find($id_company);
        $document = []; // 👈 valor por defecto

        //return response()->json(['message' => 'Voy Aqui 200','Fecha Desde:'=>$request->input('desdefecha')]);

        if ($numberdocument && $prefix) {

            $endpoint   = trim($info_control->endpoint1);
            $token      = trim($info_control->token);
            $nit = trim($info_control->nit);
            $endpoint = "{$endpoint}/download/{$nit}/Attachment-{$prefix}{$numberdocument}.xml/BASE64";

            $endpoint = preg_replace('/\\s+/', '', $endpoint);

            $response = Http::withHeaders([
                'Content-Type' => 'application/json; charset=UTF-8',
                'Authorization' => 'Bearer ' . $token
            ]) // 👈 tu token aquí])
                ->get($endpoint);

            $registros  = $response->json();


            $infobase64 = $registros['filebase64'] ?? null;

            if ($response->successful()) {
                $registros  = $response->json();
                $infobase64 = $registros['filebase64'] ?? null;

                if ($infobase64) {
                    $contenidoXml = base64_decode($infobase64);
                    $nombreArchivo = 'factura_' . now()->format('Ymd_His') . '.xml';
                    $ruta = storage_path("app/public/xml/{$nombreArchivo}");

                    // Guardar archivo
                    file_put_contents($ruta, $contenidoXml);

                    // Devolver como descarga
                    return response()->download($ruta, $nombreArchivo, [
                        'Content-Type' => 'application/xml',
                    ])->deleteFileAfterSend(true);
                }
            }
        }
    }


    public function downpdf(Request $request)
    {
        $numberdocument = $request->numberdocument;
        $prefix         = $request->prefix;

        //return response()->json(['message' => 'Voy Aqui 200','Fecha Desde:'=>$numberdocument]);


        $id_company   = Auth::user()->company_id;
        $info_control = Company::find($id_company);

        if ($numberdocument && $prefix) {

            $endpoint   = trim($info_control->endpoint1);
            $token      = trim($info_control->token);
            $nit        = trim($info_control->nit);

            // Endpoint que devuelve el PDF binario
            $endpoint = "{$endpoint}/invoice/{$nit}/FES-{$prefix}{$numberdocument}.pdf";

            $endpoint = preg_replace('/\\s+/', '', $endpoint);

            $response = Http::withHeaders([
                'Accept'        => 'application/pdf',
                'Authorization' => 'Bearer ' . $token,
            ])->get($endpoint);

            // Si el servidor respondió correctamente con 200 OK
            if ($response->successful()) {
                // Obtenemos el contenido binario del PDF
                $contenidoPdf = $response->body();

                // Nombre y ruta del archivo
                $nombreArchivo = 'factura_' . now()->format('Ymd_His') . '.pdf';
                $ruta = storage_path("app/public/pdf/{$nombreArchivo}");

                // Crear carpeta si no existe
                if (!is_dir(dirname($ruta))) {
                    mkdir(dirname($ruta), 0755, true);
                }

                // Guardar el PDF en disco
                file_put_contents($ruta, $contenidoPdf);

                // Devolverlo como descarga
                return response()->download($ruta, $nombreArchivo, [
                    'Content-Type' => 'application/pdf',
                ])->deleteFileAfterSend(true);
            }

            return response()->json([
                'error' => 'No se pudo obtener el PDF desde el endpoint remoto',
                'status' => $response->status(),
            ], $response->status());
        }

        return response()->json(['error' => 'Parámetros inválidos'], 400);
    }


    public function sendpackage(Request $request): JsonResponse
    {
        $numberdocument     = $request->number;
        $prefix             = $request->prefix;
        $email              = $request->email;

        $id_company   = Auth::user()->company_id;
        $info_control = Company::find($id_company);

        //return response()->json(['message' => 'Voy Aqui 200','Fecha Desde:'=>$request->input('desdefecha')]);

        if ($numberdocument && $prefix) {
            $endpoint   = trim($info_control->endpoint1);
            $token      = trim($info_control->token);
            $nit = trim($info_control->nit);
            $endpoint = "{$endpoint}/download/{$nit}/Attachment-{$prefix}{$numberdocument}.xml/BASE64";

            $endpoint = preg_replace('/\\s+/', '', $endpoint);

            $response = Http::withHeaders([
                'Content-Type' => 'application/json; charset=UTF-8',
                'Authorization' => 'Bearer ' . $token
            ])->get($endpoint);

            $registros  = $response->json();

            $infobase64 = $registros['filebase64'] ?? null;

            //return response()->json(['message' => 'Voy Aqui Base64','Base64:'=>$infobase64,'']);

            //$endpoint = trim($info_control->endpoint1);      
            $endpoint   = trim($info_control->endpoint1);
            $endpoint = "{$endpoint}/ubl2.1/send-email";

            $endpoint = preg_replace('/\\s+/', '', $endpoint);

            $request['base64graphicrepresentation'] = $infobase64;

            $validated = $request->validate([
                'prefix' => 'required|string',
                'number' => 'required|string',
                'showacceptrejectbuttons' => 'boolean',
                'email_cc_list' => 'array',
                'email_cc_list.*.email' => 'email',
                'base64graphicrepresentation' => 'required|string',
            ]);

            //return response()->json(['message' => 'Estoy Aquí 222', 'number' => $validated, 'id_company' => $id_company, 'endpoint' => $endpoint, 'token' => $token], 200);


            $response = Http::withHeaders([
                'Content-Type' => 'application/json; charset=UTF-8',
                'Authorization' => 'Bearer ' . $token
            ])->post($endpoint, $validated);

            // 🔹 Si el servidor respondió correctamente (código 2xx)
            if ($response->successful()) {
                $data = $response->json(); // cuerpo JSON real

                return response()->json([
                    'message'  => '📤 Envío exitoso',
                    'status'   => $response->status(),
                    'endpoint' => $endpoint,
                    'data'     => $data,
                ], 200);
            }
            //return response()->json(['message' => 'Envío Exitoso', 'response' => $response], 200);
        }

        return response()->json(['error' => 'Parámetros inválidos '], 400);
    }
}
