<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Control;
use App\Models\PurchasesInvoice;
use App\Models\SalesInvoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
#use Symfony\Component\Panther\Client;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Illuminate\Support\Facades\Http;
use Spatie\Browsershot\Browsershot;
use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Http\JsonResponse as HttpJsonResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Process\Process;

class ScrapingDianController extends Controller
{

    public function extraerTabla(Request $request, $type)
    {
        $url = $request->url_token;
        //$url = "https://catalogo-vpfe.dian.gov.co/User/AuthToken?pk=10910094|77193886&rk=901148547&token=35fbfb5d-668f-4403-ba63-50c8c76f69bc";
        $company = Company::find($request->company_id);
        $endpoint = preg_replace('/\\s+/', '', $company->endpoint3);

        try {
            $response = Http::withoutVerifying()
                ->timeout(180)
                ->connectTimeout(10)
                ->withHeaders([
                    'X-API-KEY'    => '6ed6d9ae8423598a5287ab60df52442f1d60c3ae5fcf877bcdbc1fedd1d24316',
                    'Content-Type' => 'application/json; charset=UTF-8',
                    'Accept'       => 'application/json',
                ])->post($endpoint, [
                    'nitempresa'             => $company->nit,
                    'nitrepresentantelegal'  => $company->nit_representante_legal,
                    'fechadesde'             => $request->fechadesde,
                    'fechahasta'             => $request->fechahasta,
                    'type'                   => (intval($type) == 1) ? "1" : "2",
                    'headless'               => true,
                    'url_dian'               => $url,
                ]);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            return response()->json([
                'message' => 'El servidor de la DIAN tardó demasiado en responder.',
                'error' => $e->getMessage()
            ], 408);
        }

        if ($response->successful()) {
            $rawRecords = collect($response->json('data') ?? []);

            $filteredData = $rawRecords
                // 1. Filtrar solo los tipos de documento permitidos
                ->filter(function ($item) {
                    return in_array($item['TipoDocumento'], ['Factura electrónica', 'Nota de crédito electrónica']);
                })
                // 2. Mapear y transformar valores
                ->map(function ($item) {
                    $esNotaCredito = ($item['TipoDocumento'] === 'Nota de crédito electrónica');

                    // Campos a convertir a numérico
                    $camposNumericos = ['ValorTotal', 'ValorImptos', 'ValorRetefuente', 'ValorReteiva', 'ValorReteica'];

                    foreach ($camposNumericos as $campo) {
                        $valor = floatval($item[$campo] ?? 0);
                        // Si es Nota de Crédito, multiplicamos por -1
                        $item[$campo] = $esNotaCredito ? ($valor * -1) : $valor;
                    }

                    return $item;
                })
                // 3. Ordenar por Fecha (de la más antigua a la más reciente)
                ->sortBy('Fecha')
                ->values(); // Resetear índices del array

            if (intval($request->tipoproceso) == 1) {
                try {
                    $this->updateSales($filteredData, $request->company_id);
                } catch (\Throwable $e) {
                    dd($e->getMessage(), $e->getLine(), $e->getFile());
                }
            } else {
                try {
                    $this->updatePurchasesInvoices($filteredData, $request->company_id);
                } catch (\Throwable $e) {
                    dd($e->getMessage(), $e->getLine(), $e->getFile());
                }
            }


            return response()->json([
                'status'          => 'success',
                'type'            => $request->type,
                'TotalDocumentos' => $filteredData->count(),
                'TotalValor'      => $filteredData->sum('ValorTotal'),
                'TotalIva'        => $filteredData->sum('ValorImptos'),
                'data'            => $filteredData,
            ], 200);
        }

        return response()->json(['error' => 'Error al conectar con el servicio externo'], 500);
    }


    public function extraerTabla_ventas(Request $request)
    {
        $url = $request->url_token;
        //$url = "https://catalogo-vpfe.dian.gov.co/User/AuthToken?pk=10910094|77193886&rk=901148547&token=abe167ee-c7c5-4cba-be6b-0f28ff25679d";
        $company = Company::find($request->company_id);
        $endpoint = preg_replace('/\\s+/', '', $company->endpoint3);

        try {
            $response = Http::withoutVerifying()
                ->timeout(180)
                ->connectTimeout(10)
                ->withHeaders([
                    'X-API-KEY'    => '6ed6d9ae8423598a5287ab60df52442f1d60c3ae5fcf877bcdbc1fedd1d24316',
                    'Content-Type' => 'application/json; charset=UTF-8',
                    'Accept'       => 'application/json',
                ])->post($endpoint, [
                    'nitempresa'             => $company->nit,
                    'nitrepresentantelegal'  => $company->nit_representante_legal,
                    'fechadesde'             => $request->fechadesde,
                    'fechahasta'             => $request->fechahasta,
                    'type'                   => "1",
                    'headless'               => true,
                    'url_dian'               => $url,
                ]);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            return response()->json([
                'message' => 'El servidor de la DIAN tardó demasiado en responder.',
                'error' => $e->getMessage()
            ], 408);
        }

        if ($response->successful()) {
            $rawRecords = collect($response->json('data') ?? []);

            $filteredData = $rawRecords
                // 1. Filtrar solo los tipos de documento permitidos
                ->filter(function ($item) {
                    return in_array($item['TipoDocumento'], ['Factura electrónica', 'Nota de crédito electrónica']);
                })
                // 2. Mapear y transformar valores
                ->map(function ($item) {
                    $esNotaCredito = ($item['TipoDocumento'] === 'Nota de crédito electrónica');

                    // Campos a convertir a numérico
                    $camposNumericos = ['ValorTotal', 'ValorImptos', 'ValorRetefuente', 'ValorReteiva', 'ValorReteica'];

                    foreach ($camposNumericos as $campo) {
                        $valor = floatval($item[$campo] ?? 0);
                        // Si es Nota de Crédito, multiplicamos por -1
                        $item[$campo] = $esNotaCredito ? ($valor * -1) : $valor;
                    }

                    return $item;
                })
                // 3. Ordenar por Fecha (de la más antigua a la más reciente)
                ->sortBy('Fecha')
                ->values(); // Resetear índices del array

            try {
                $this->updateSales($filteredData, $request->company_id);
            } catch (\Throwable $e) {
                dd($e->getMessage(), $e->getLine(), $e->getFile());
            }

            return response()->json([
                'status'          => 'success',
                'type'            => $request->type,
                'TotalDocumentos' => $filteredData->count(),
                'TotalValor'      => $filteredData->sum('ValorTotal'),
                'TotalIva'        => $filteredData->sum('ValorImptos'),
                'data'            => $filteredData,
            ], 200);
        }

        return response()->json(['error' => 'Error al conectar con el servicio externo'], 500);
    }

    public function extraerTabla_compras(Request $request)
    {
        $url = $request->url_token;
        //$url = "https://catalogo-vpfe.dian.gov.co/User/AuthToken?pk=10910094|77193886&rk=901148547&token=abe167ee-c7c5-4cba-be6b-0f28ff25679d";
        $company = Company::find($request->company_id);
        $endpoint = preg_replace('/\\s+/', '', $company->endpoint3);

        try {
            $response = Http::withoutVerifying()
                ->timeout(180)
                ->connectTimeout(10)
                ->withHeaders([
                    'X-API-KEY'    => '6ed6d9ae8423598a5287ab60df52442f1d60c3ae5fcf877bcdbc1fedd1d24316',
                    'Content-Type' => 'application/json; charset=UTF-8',
                    'Accept'       => 'application/json',
                ])->post($endpoint, [
                    'nitempresa'             => $company->nit,
                    'nitrepresentantelegal'  => $company->nit_representante_legal,
                    'fechadesde'             => $request->fechadesde,
                    'fechahasta'             => $request->fechahasta,
                    'type'                   => "2",
                    'headless'               => true,
                    'url_dian'               => $url,
                ]);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            return response()->json([
                'message' => 'El servidor de la DIAN tardó demasiado en responder.',
                'error' => $e->getMessage()
            ], 408);
        }


        if ($response->successful()) {
            $rawRecords = collect($response->json('data') ?? []);

            $filteredData = $rawRecords
                // 1. Filtrar solo los tipos de documento permitidos
                ->filter(function ($item) {
                    return in_array($item['TipoDocumento'], ['Factura electrónica', 'Nota de crédito electrónica']);
                })
                // 2. Mapear y transformar valores
                ->map(function ($item) {
                    $esNotaCredito = ($item['TipoDocumento'] === 'Nota de crédito electrónica');

                    // Campos a convertir a numérico
                    $camposNumericos = ['ValorTotal', 'ValorImptos', 'ValorRetefuente', 'ValorReteiva', 'ValorReteica'];

                    foreach ($camposNumericos as $campo) {
                        $valor = floatval($item[$campo] ?? 0);
                        // Si es Nota de Crédito, multiplicamos por -1
                        $item[$campo] = $esNotaCredito ? ($valor * -1) : $valor;
                    }

                    return $item;
                })
                // 3. Ordenar por Fecha (de la más antigua a la más reciente)
                ->sortBy('Fecha')
                ->values(); // Resetear índices del array

            try {
                $this->updatePurchasesInvoices($filteredData, $request->company_id);
            } catch (\Throwable $e) {
                dd($e->getMessage(), $e->getLine(), $e->getFile());
            }

            return response()->json([
                'status'          => 'success',
                'type'            => $request->type,
                'TotalDocumentos' => $filteredData->count(),
                'TotalValor'      => $filteredData->sum('ValorTotal'),
                'TotalIva'        => $filteredData->sum('ValorImptos'),
                'data'            => $filteredData,
            ], 200);
        }

        return response()->json(['error' => 'Error al conectar con el servicio externo'], 500);
    }

    public function extraerTabla_detallescompras(Request $request)
    {
        $url = $request->url_token;
        $fechadesde = $request->fechadesde;
        $fechahasta = $request->fechahasta;
        $codigointerno =  Auth::user()->code_n8n;
        //$url = "https://catalogo-vpfe.dian.gov.co/User/AuthToken?pk=10910094|77193886&rk=901148547&token=abe167ee-c7c5-4cba-be6b-0f28ff25679d";
        $compras = PurchasesInvoice::select('id', 'date_issue', 'cufe', 'document_name')
            ->whereBetween('date_issue', [$fechadesde, $fechahasta])->get();

        // return response()->json([
        //     'status'          => 'success',
        //     'fechadesde'      => $fechadesde,
        //     'fechahasta'      => $fechahasta,
        //     'url'            => $url,
        // ], 200);

        $company = Company::find($request->company_id);
        $endpoint = "http://194.163.159.64:8000/descargar-dian";
        $endpoint = preg_replace('/\\s+/', '', $endpoint);

        $urlDian = html_entity_decode($url, ENT_QUOTES, 'UTF-8');

        try {
            $response = Http::withoutVerifying()
                ->timeout(180)
                ->connectTimeout(10)
                ->withHeaders([
                    'X-API-KEY'    => '6ed6d9ae8423598a5287ab60df52442f1d60c3ae5fcf877bcdbc1fedd1d24316',
                    'Content-Type' => 'application/json; charset=UTF-8',
                    'Accept'       => 'application/json',
                ])->post($endpoint, [
                    'nitempresa'             => $company->nit,
                    'nitrepresentantelegal'  => $company->nit_representante_legal,
                    'fechadesde'             => $request->fechadesde,
                    'fechahasta'             => $request->fechahasta,
                    'type'                   => "2",
                    'headless'               => true,
                    'url_dian'               => $urlDian,
                    'datos_cufe'             => $compras,
                    'codigointerno'          => $codigointerno,
                    'companies_id'           => $request->company_id,
                ]);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            return response()->json([
                'message' => 'Error al consumir la API',
                'error' => $e->getMessage()
            ], 408);
        }


        if ($response->successful()) {
            $rawRecords = collect($response->json('data') ?? []);

            $filteredData = $rawRecords
                // 1. Filtrar solo los tipos de documento permitidos
                ->filter(function ($item) {
                    return in_array($item['TipoDocumento'], ['Factura electrónica', 'Nota de crédito electrónica']);
                })
                // 2. Mapear y transformar valores
                ->map(function ($item) {
                    $esNotaCredito = ($item['TipoDocumento'] === 'Nota de crédito electrónica');

                    // Campos a convertir a numérico
                    $camposNumericos = ['ValorTotal', 'ValorImptos', 'ValorRetefuente', 'ValorReteiva', 'ValorReteica'];

                    foreach ($camposNumericos as $campo) {
                        $valor = floatval($item[$campo] ?? 0);
                        // Si es Nota de Crédito, multiplicamos por -1
                        $item[$campo] = $esNotaCredito ? ($valor * -1) : $valor;
                    }

                    return $item;
                })
                // 3. Ordenar por Fecha (de la más antigua a la más reciente)
                ->sortBy('Fecha')
                ->values(); // Resetear índices del array

            try {
                $this->updatePurchasesInvoices($filteredData, $request->company_id);
            } catch (\Throwable $e) {
                dd($e->getMessage(), $e->getLine(), $e->getFile());
            }

            return response()->json([
                'status'          => 'success',
                'type'            => $request->type,
                'TotalDocumentos' => $filteredData->count(),
                'TotalValor'      => $filteredData->sum('ValorTotal'),
                'TotalIva'        => $filteredData->sum('ValorImptos'),
                'data'            => $filteredData,
            ], 200);
        }

        return response()->json(['error' => 'Error al conectar con el servicio externo'], 500);
    }

    public function extraerTabla_detallesventas(Request $request)
    {
        $url = $request->url_token;
        $fechadesde = $request->fechadesde;
        $fechahasta = $request->fechahasta;
        $codigointerno =  Auth::user()->code_n8n;
        //$url = "https://catalogo-vpfe.dian.gov.co/User/AuthToken?pk=10910094|77193886&rk=901148547&token=abe167ee-c7c5-4cba-be6b-0f28ff25679d";
        $ventas = SalesInvoice::select('id', 'date_issue', 'cufe', 'document_name')
            ->whereBetween('date_issue', [$fechadesde, $fechahasta])->get();

        // return response()->json([
        //     'status'          => 'success',
        //     'fechadesde'      => $fechadesde,
        //     'fechahasta'      => $fechahasta,
        //     'url'            => $url,
        // ], 200);

        $company = Company::find($request->company_id);
        $endpoint = "http://194.163.159.64:8000/descargar-dian";
        $endpoint = preg_replace('/\\s+/', '', $endpoint);

        $urlDian = html_entity_decode($url, ENT_QUOTES, 'UTF-8');

        try {
            $response = Http::withoutVerifying()
                ->timeout(180)
                ->connectTimeout(10)
                ->withHeaders([
                    'X-API-KEY'    => '6ed6d9ae8423598a5287ab60df52442f1d60c3ae5fcf877bcdbc1fedd1d24316',
                    'Content-Type' => 'application/json; charset=UTF-8',
                    'Accept'       => 'application/json',
                ])->post($endpoint, [
                    'nitempresa'             => $company->nit,
                    'nitrepresentantelegal'  => $company->nit_representante_legal,
                    'fechadesde'             => $request->fechadesde,
                    'fechahasta'             => $request->fechahasta,
                    'type'                   => "1",
                    'headless'               => true,
                    'url_dian'               => $urlDian,
                    'datos_cufe'             => $ventas,
                    'codigointerno'          => $codigointerno,
                    'companies_id'           => $request->company_id,
                ]);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            return response()->json([
                'message' => 'Error al consumir la API',
                'error' => $e->getMessage()
            ], 408);
        }

        if ($response->successful()) {
            $rawRecords = collect($response->json('data') ?? []);

            $filteredData = $rawRecords
                // 1. Filtrar solo los tipos de documento permitidos
                ->filter(function ($item) {
                    return in_array($item['TipoDocumento'], ['Factura electrónica', 'Nota de crédito electrónica']);
                })
                // 2. Mapear y transformar valores
                ->map(function ($item) {
                    $esNotaCredito = ($item['TipoDocumento'] === 'Nota de crédito electrónica');

                    // Campos a convertir a numérico
                    $camposNumericos = ['ValorTotal', 'ValorImptos', 'ValorRetefuente', 'ValorReteiva', 'ValorReteica'];

                    foreach ($camposNumericos as $campo) {
                        $valor = floatval($item[$campo] ?? 0);
                        // Si es Nota de Crédito, multiplicamos por -1
                        $item[$campo] = $esNotaCredito ? ($valor * -1) : $valor;
                    }

                    return $item;
                })
                // 3. Ordenar por Fecha (de la más antigua a la más reciente)
                ->sortBy('Fecha')
                ->values(); // Resetear índices del array

            try {
                $this->updatePurchasesInvoices($filteredData, $request->company_id);
            } catch (\Throwable $e) {
                dd($e->getMessage(), $e->getLine(), $e->getFile());
            }

            return response()->json([
                'status'          => 'success',
                'type'            => $request->type,
                'TotalDocumentos' => $filteredData->count(),
                'TotalValor'      => $filteredData->sum('ValorTotal'),
                'TotalIva'        => $filteredData->sum('ValorImptos'),
                'data'            => $filteredData,
            ], 200);
        }

        return response()->json(['error' => 'Error al conectar con el servicio externo'], 500);
    }


    public function updateSales($resp, $company_id)
    {

        foreach ($resp as $item) {
            //dd($item);
            $numerofactura = $item['NroDocumento'] ?? 0;
            $prefijo       = $item['Prefijo'] ?? '';
            $nit           = $item['NitReceptor'] ?? '';
            $subtotal      = $item['ValorTotal'] - $item['ValorImptos'];

            try {
                //dd($item);
                $reg_fact       = SalesInvoice::updateOrCreate(
                    ['number' => $numerofactura, 'prefix' => $prefijo, 'customer' => $nit, 'companies_id' => $company_id],
                    [
                        'date_issue'           => $item['Fecha'],
                        'expiration_date'      => $item['Fecha'],
                        'document_name'        => $item['TipoDocumento'],
                        'client_name'          => $item['Receptor'],
                        'subtotal'             => $subtotal,
                        'discounts'            => 0,
                        'vatvalue'             => $item['ValorImptos'],
                        'retefuente'           => 0,
                        'reteiva'              => 0,
                        'reteica'              => 0,
                        'impoconsumo'          => 0,
                        'total_sale'           => $item['ValorTotal'],
                        'cufe'                 => $item['data-id'],
                        'state'                => 'ACTIVO',
                    ]
                );
            } catch (\Exception $ex) {
                return response()->json(

                    [
                        'status'   => '404 OK',
                        'msg'      => 'Error en la actualización de la factura: ' . $numerofactura,
                        'error' => $ex,
                    ],
                    Response::HTTP_BAD_REQUEST
                );
            }
        }
    }

    public function updatePurchasesInvoices($resp, $company_id)
    {

        foreach ($resp as $item) {
            //dd($item);
            $numerofactura = $item['NroDocumento'] ?? 0;
            $prefijo       = $item['Prefijo'] ?? '';
            $nit           = $item['NitEmisor'] ?? '';
            $subtotal      = $item['ValorTotal'] - $item['ValorImptos'];

            try {
                //dd($item);
                $reg_fact       = PurchasesInvoice::updateOrCreate(
                    ['number' => $numerofactura, 'prefix' => $prefijo, 'supplier' => $nit, 'companies_id' => $company_id],
                    [
                        'date_issue'           => $item['Fecha'],
                        'expiration_date'      => $item['Fecha'],
                        'document_name'        => $item['TipoDocumento'],
                        'supplier_name'        => $item['Emisor'],
                        'subtotal'             => $subtotal,
                        'discounts'            => 0,
                        'vatvalue'             => $item['ValorImptos'],
                        'retefuente'           => 0,
                        'reteiva'              => 0,
                        'reteica'              => 0,
                        'impoconsumo'          => 0,
                        'total_purchase'       => $item['ValorTotal'],
                        'cufe'                 => $item['data-id'],
                        'state'                => 'ACTIVO',
                    ]
                );
            } catch (\Exception $ex) {
                return response()->json(
                    // dd([
                    //     'error'         => $ex->getMessage(),
                    //     'linea'         => $ex->getLine(),
                    //     'numerofactura' => $numerofactura,
                    // ]),


                    [
                        'status'   => '404 OK',
                        'msg'      => 'Error en la actualización de la factura: ' . $numerofactura,
                        'error' => $ex,
                    ],
                    Response::HTTP_BAD_REQUEST
                );
            }
        }
    }
}
