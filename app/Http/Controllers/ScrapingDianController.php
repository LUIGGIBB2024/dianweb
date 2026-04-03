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
    public function extraerTabla1()
    {
        $url = "https://catalogo-vpfe.dian.gov.co/User/AuthToken?pk=10910094|77023910&rk=77023910&token=cf8b709e-d371-40b5-9568-3c3be2c963d7";


        // $html = Browsershot::url($url)
        //     ->noSandbox()
        //     ->timeout(60)
        //     ->waitUntilNetworkIdle()
        //     ->bodyHtml();

        $html = Browsershot::url($url)
            ->noSandbox()
            ->waitUntilNetworkIdle()
            ->evaluate("
                    async () => {

                        function wait(ms) {
                            return new Promise(resolve => setTimeout(resolve, ms));
                        }

                        await wait(3000);

                        const btn = document.querySelector('#DocumentSent a');
                        if (btn) btn.click();

                        await wait(5000);

                        return document.documentElement.outerHTML;
                    }
                ");

        return response()->json([
            'tiene_login' => str_contains($html, 'Acceder'),
            'tiene_tabla' => str_contains($html, '<table'),
            'preview' => substr($html, 0, 1000),
        ]);
    }

    public function extraerTabla3()
    {
        $url = "https://catalogo-vpfe.dian.gov.co/User/AuthToken?pk=10910094|77023910&rk=77023910&token=cf8b709e-d371-40b5-9568-3c3be2c963d7";

        $browser = Browsershot::url($url)
            ->noSandbox()
            ->waitUntilNetworkIdle();

        // Ejecutar JS (sin esperar retorno complejo)
        #DocumentReceived  - DocumentSent
        $browser->evaluate("document.querySelector('#DocumentReceived a')?.click();");

        // Luego obtener HTML
        $html = $browser
            ->setDelay(7000)
            ->bodyHtml();

        return response()->json([
            'tiene_login' => str_contains($html, 'Acceder'),
            'tiene_tabla' => str_contains($html, '<table'),
            'preview' => substr($html, 0, 1000000000),
        ]);
    }

    public function extraerTabla_f()
    {
        $url = "https://catalogo-vpfe.dian.gov.co/User/AuthToken?pk=10910094|77023910&rk=77023910&token=b72f0ee6-c194-4ee4-82b8-f2daf9fbdfa8";

        $browser = Browsershot::url($url)
            ->noSandbox()
            ->setOption('args', ['--disable-web-security'])
            ->userAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64)')
            ->waitUntilNetworkIdle()
            ->setDelay(8000); // ⬅️ carga inicial real

        // 🔥 FORZAR CLICK DE TODAS LAS FORMAS POSIBLES
        $browser->evaluate("
            (function () {

                function clickButton() {
                    const btn = document.querySelector('#DocumentReceived a');
                    if (!btn) return false;

                    // método 1
                    btn.click();

                    // método 2
                    btn.dispatchEvent(new MouseEvent('click', { bubbles: true }));

                    // método 3 (por si es framework)
                    if (typeof btn.onclick === 'function') {
                        btn.onclick();
                    }

                    return true;
                }

                let attempts = 0;

                const interval = setInterval(() => {
                    const ok = clickButton();

                    attempts++;

                    if (ok || attempts > 10) {
                        clearInterval(interval);
                    }
                }, 1000);

            })();
        ");

        // ⏳ esperar que el DOM cambie
        $html = $browser
            ->setDelay(15000) // ⬅️ MUY IMPORTANTE
            ->bodyHtml();

        return response()->json([
            'tiene_login' => str_contains($html, 'Acceder'),
            'tiene_tabla' => str_contains($html, '<table'),
            'tiene_document_received' => str_contains($html, 'DocumentReceived'),
            'preview' => substr($html, 0, 100000000),
        ]);
    }

    public function extraerTabla_nada()
    {
        $url = "https://catalogo-vpfe.dian.gov.co/User/AuthToken?pk=10910094|77023910&rk=77023910&token=b54429d7-5037-4288-b647-f49f8f4836cc";

        // FASE 1 — Dejar que el AuthToken redirija y la página final cargue completamente
        $htmlInicial = Browsershot::url($url)
            ->addChromiumArguments(['--no-sandbox', '--disable-web-security'])
            ->userAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36')
            ->waitUntilNetworkIdle()  // espera que todas las redirecciones y peticiones terminen
            ->setDelay(8000)          // margen extra para JS de la DIAN
            ->bodyHtml();

        // Diagnóstico: ver dónde quedamos después de la redirección
        if (!str_contains($htmlInicial, 'DocumentReceived')) {
            return response()->json([
                'error'   => 'No se encontró #DocumentReceived tras la redirección',
                'preview' => substr($htmlInicial, 0, 5000),
            ]);
        }

        // FASE 2 — Nueva instancia de Browsershot sobre la URL ya resuelta
        // Usamos script JS que espera el DOM estable antes de hacer clic
        $htmlFinal = Browsershot::url($url)
            ->addChromiumArguments(['--no-sandbox', '--disable-web-security'])
            ->userAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36')
            ->waitUntilNetworkIdle()
            ->setDelay(10000)  // dar tiempo a que la página destino cargue sus componentes
            ->evaluate("
                new Promise((resolve, reject) => {
                    const timeout = setTimeout(() => reject('TIMEOUT'), 25000);

                    // Esperar a que #DocumentReceived esté en el DOM
                    const esperar = setInterval(() => {
                        const btn = document.querySelector('#DocumentReceived a');
                        if (!btn) return;

                        clearInterval(esperar);

                        // Hacer clic
                        btn.dispatchEvent(new MouseEvent('click', {
                            bubbles: true,
                            cancelable: true,
                            view: window
                        }));

                        // Esperar que aparezca la tabla
                        let intentos = 0;
                        const esperarTabla = setInterval(() => {
                            intentos++;
                            const tabla = document.querySelector('table');
                            if (tabla || intentos > 20) {
                                clearTimeout(timeout);
                                clearInterval(esperarTabla);
                                resolve(document.body.innerHTML);
                            }
                        }, 1000);

                    }, 500);
                })
            ");

        return response()->json([
            'tiene_tabla'             => str_contains($htmlFinal, '<table'),
            'tiene_document_received' => str_contains($htmlFinal, 'DocumentReceived'),
            'preview'                 => substr($htmlFinal, 0, 100000000),
        ]);
    }

    public function extraerTabla_2(Request $request)
    {
        //$url = "https://catalogo-vpfe.dian.gov.co/User/AuthToken?pk=10910094|77193886&rk=901148547&token=8d5129ef-cbb1-4d7f-ab15-5af12b651ca7";
        $url = $request->url_token;

        $company = Company::find($request->company_id);

        $endpoint = preg_replace('/\\s+/', '', $company->endpoint3);
        try {
            $response = Http::withoutVerifying()
                ->timeout(180) // ⏳ Aumentar el tiempo de espera a 180 segundos (3 minutos)
                ->connectTimeout(10) // Tiempo máximo para lograr la conexión inicial
                ->withHeaders([
                    'X-API-KEY'     => '6ed6d9ae8423598a5287ab60df52442f1d60c3ae5fcf877bcdbc1fedd1d24316',
                    'Content-Type'  => 'application/json; charset=UTF-8',
                    'Accept'        => 'application/json',
                ])->post($endpoint, [
                    'nitempresa'                => $company->nit,
                    'nitrepresentantelegal'     => $company->nit_representante_legal,
                    'fechadesde'                => $request->fechadesde,
                    'fechahasta'                => $request->fechahasta,
                    'type'                      => '2',
                    'headless'                  => true,
                    'url_dian'                  => $url,
                ]);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            return response()->json([
                'message' => 'El servidor de la DIAN tardó demasiado en responder. Por favor, intenta con un rango de fechas más pequeño.',
                'error' => $e->getMessage()
            ], 408); // 408 Request Timeout
        }

        if ($response->successful()) {
            return response()->json([
                'message'             => '📤 Recepción Exitosa',
                'status '             => $response->status(),
                'TotalDocumentos'     => $response->json('TotalDocumentos') ?? 0,
                'data'                => $response->json('data'),
            ], 200);

            $resultado = json_decode($proceso->getOutput(), true);

            return response()->json($resultado);
        }
    }

    public function extraerTabla_3(Request $request)
    {
        $url = $request->url_token;
        $url = "https://catalogo-vpfe.dian.gov.co/User/AuthToken?pk=10910094|77193886&rk=901148547&token=5aa71452-1afa-4209-8418-e0281973c33a";
        $company = Company::find($request->company_id);
        $endpoint = preg_replace('/\\s+/', '', $company->endpoint3);

        try {
            $response = Http::withoutVerifying()
                ->timeout(180)
                ->connectTimeout(10)
                ->withHeaders([
                    'X-API-KEY'    => '6ed6d9ae8423598a5287ab60df52442f1d60c3ae5fcf877bcdbc1fedd1d24316',
                    'Content-Type' => 'application/json; charset=UTF-8',
                    'Accept'        => 'application/json',
                ])->post($endpoint, [
                    'nitempresa'             => $company->nit,
                    'nitrepresentantelegal'  => $company->nit_representante_legal,
                    'fechadesde'             => $request->fechadesde,
                    'fechahasta'             => $request->fechahasta,
                    'type'                   => '2',
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

            return response()->json([
                'status'          => 'success',
                'TotalDocumentos' => $filteredData->count(),
                'data'            => $filteredData,
            ], 200);
        }

        return response()->json(['error' => 'Error al conectar con el servicio externo'], 500);
    }

    public function extraerTabla(Request $request)
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
                    'type'                   => $request->type,
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

            if ($request->type == "1") {
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
                        'discounts'           => 0,
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
                        'total_purchase'           => $item['ValorTotal'],
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
