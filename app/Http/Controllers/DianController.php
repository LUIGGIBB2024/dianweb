<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\Request;
use App\Models\DianTokenQueue;
use App\Models\PurchasesInvoice;
use App\Models\SalesInvoice;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class DianController extends Controller
{
    // DianController.php
    public function solicitarToken(Request $request)
    {
        // Evita duplicados del mismo usuario    

        $token          = $request->input('token');
        $urlCompleta    = $request->input('url_completa');
        $user_id        = $request->input('user_id');
        $company_id     = $request->input('company_id');
        $yaEnCola = DianTokenQueue::where('user_id', $user_id)
            ->whereIn('status', ['waiting', 'processing'])
            ->exists();

        if ($yaEnCola) {
            return response()->json([
                'error' => 'Ya tienes una solicitud activa'
            ], 409);
        }

        // Evita que dos usuarios procesen al mismo tiempo
        $hayProcesando = DianTokenQueue::where('status', 'processing')->exists();

        // DianController@solicitarToken
        $solicitud = DianTokenQueue::create([
            'token'          => $token, // se llenará cuando n8n envíe el token real
            'url_completa'   => $urlCompleta,   // se llenará cuando n8n envíe la URL completa
            'user_id'        => $user_id,
            'company_id'     => $company_id,  // ← agrega esto
            'status'         => $hayProcesando ? 'waiting' : 'processing',
            'processing_at'  => $hayProcesando ? null : now(),
            'queued_at'      => now()
        ]);

        return response()->json([
            'ok'     => true,
            'status' => $solicitud->status,
            'pos'    => $hayProcesando
                ? DianTokenQueue::where('status', 'waiting')->count()
                : 1
        ]);
    }

    public function verificarToken(Request $request)
    {
        $token          = $request->input('token');
        $user_id        = $request->input('user_id');
        $company_id     = $request->input('company_id');

        $solicitud = DianTokenQueue::where('user_id', $user_id)
            ->whereIn('status', ['waiting', 'processing', 'received', 'timeout'])
            ->orderBy('queued_at', 'desc')
            ->first();

        if (!$solicitud) {
            return response()->json(['status' => 'not_found']);
        }

        return response()->json([
            'status'      => $solicitud->status,
            'token'       => $solicitud->token,
            'url_completa' => $solicitud->url_completa,
            'user_id'        => $user_id,
            'company_id'     => $company_id,  // ← agrega esto
            'pos'         => DianTokenQueue::where('status', 'waiting')
                ->where('queued_at', '<', $solicitud->queued_at)
                ->count() + 1
        ]);
    }

    public function timeout()
    {
        DianTokenQueue::where('user_id', auth()->id())
            ->where('status', 'processing')
            ->update(['status' => 'timeout']);

        $this->procesarSiguiente();

        return response()->json(['ok' => true]);
    }

    private function procesarSiguiente()
    {
        $hayProcesando = DianTokenQueue::where('status', 'processing')->exists();
        if ($hayProcesando) return;

        $siguiente = DianTokenQueue::where('status', 'waiting')
            ->orderBy('queued_at', 'asc')
            ->first();

        if (!$siguiente) return;

        $siguiente->update([
            'status'        => 'processing',
            'processing_at' => now()
        ]);
    }

    public function recibirToken(Request $request)
    {
        $secretRecibido = $request->header('X-N8N-SECRET');
        $secretEsperado = config('app.n8n_secret');
        $user_id        = $request->input('user_id');
        $company_id     = $request->input('company_id');

        // // Debug completo
        // return response()->json([
        //     'recibido'      => $secretRecibido,
        //     'esperado'      => $secretEsperado,
        //     'son_iguales'   => $secretRecibido === $secretEsperado,
        //     'metodo'        => $request->method(),
        //     'url'           => $request->url(),
        // ]);

        if ($secretRecibido !== $secretEsperado) {
            return response()->json(['error' => 'No autorizado'], 401);
        }

        $token       = $request->input('token');
        $urlCompleta = $request->input('url_dian');
        $fecha       = $request->input('fecha');

        if (!$token) {
            return response()->json(['error' => 'Token no recibido'], 422);
        }

        $enProceso = \App\Models\DianTokenQueue::where('status', 'processing')
            ->orderBy('processing_at', 'desc')
            ->first();

        if (!$enProceso) {
            return response()->json(['error' => 'No hay solicitud en proceso'], 404);
        }

        $enProceso->update([
            'token'       => $token,
            'url_completa' => $urlCompleta,
            'received_at' => now(),
            'status'      => 'received'
        ]);

        // DianTokenQueue::create([
        //     'token'          => $token, // se llenará cuando n8n envíe el token real
        //     'url_completa'   => $urlCompleta,   // se llenará cuando n8n envíe la URL completa
        //     'user_id'        => $user_id,
        //     'company_id'     => $company_id,  // ← agrega esto
        //     'status'         => 'processing',
        //     'processing_at'  => now(),
        //     'queued_at'      => now()
        // ]);

        return response()->json([
            'token_recibido'       => $request->input('token'),
            'url_completa_recibida' => $request->input('url_completa'),
            'nit_dian_recibido'     => $request->input('nit_dian'),
            'fecha_recibida'       => $request->input('fecha'),
            'body_completo'        => $request->all()
        ]);
    }


    public function webHook(Request $request)
    {
        // Tomar el usuario autenticado EN ESTA PETICIÓN específica
        $user    = User::find($request->user_id); // equivalente a Auth::user() pero más explícito
        $company = Company::find($request->company_id);

        $endpoint = preg_replace('/\\s+/', '', $company->endpoint2);

        $response = Http::withoutVerifying()
            ->withHeaders([
                'Content-Type' => 'application/json; charset=UTF-8',
                'Accept'       => 'application/json',
            ])->post($endpoint, [
                'token'         => $request->token,
                'email'         => $user->email,
                'nit_dian'      => $company->nit,
                'user_id'       => $user->id,
                'codigointerno' => $company->codigointerno ?? '',
                'fecha'         => now('America/Bogota')->format('Y-m-d H:i:s'),
                'code_n8n'      => $user->code_n8n,
            ]);

        if ($response->successful()) {
            return response()->json([
                'message'             => '📤 Envío exitoso',
                'user_id'             => $user->id,
                'company_id'          => $company->id,
                'nit_empresa'         => $company->nit,
                'url_n8n'             => $company->endpoint2,
                'representante_legal' => $company->representativeid,
                'code_n8n'            => $user->code_n8n,

            ], 200);
        }
    }

    public function documentosEnviados(Request $request): JsonResponse
    {
        $desdefecha = $request->input('fechadesde');
        $hastafecha = $request->input('fechahasta');

        $ventas = SalesInvoice::select(
            'id',
            'date_issue',
            'expiration_date',
            'number',
            'prefix',
            'document_name',
            'customer',
            'client_name',
            'subtotal',
            'discounts',
            'total_sale',
            'vatvalue',
            'retefuente',
            'reteiva',
            'reteica',
            'impoconsumo',
            'cufe',
            'state'
        )
            ->whereBetween('date_issue', [$desdefecha, $hastafecha])
            ->where('companies_id', $request->input('company_id'))
            ->orderBy('date_issue')
            ->get();

        return response()->json(
            [
                'status'           => '200',
                'message'          => 'Mostrando documentos enviados a la DIAN entre ' . $desdefecha . ' y ' . $hastafecha,
                'TotalDocumentos' => $ventas->count(),
                'TotalValor'      => $ventas->sum('total_sale'),
                'TotalIva'        => $ventas->sum('vatvalue'),
                'data'            => $ventas,
            ],
            Response::HTTP_ACCEPTED
        );
    }

    public function documentosRecibidos(Request $request): JsonResponse
    {
        $desdefecha = $request->input('fechadesde');
        $hastafecha = $request->input('fechahasta');

        $compras = PurchasesInvoice::select(
            'id',
            'date_issue',
            'expiration_date',
            'number',
            'prefix',
            'document_name',
            'supplier',
            'supplier_name',
            'subtotal',
            'discounts',
            'total_purchase',
            'vatvalue',
            'retefuente',
            'reteiva',
            'reteica',
            'cufe',
            'state'
        )
            ->whereBetween('date_issue', [$desdefecha, $hastafecha])
            ->where('companies_id', $request->input('company_id'))
            ->orderBy('date_issue')
            ->get();

        return response()->json(
            [
                'status'           => '200',
                'message'          => 'Mostrando documentos recibidos de la DIAN entre ' . $desdefecha . ' y ' . $hastafecha,
                'TotalDocumentos' => $compras->count(),
                'TotalValor'      => $compras->sum('total_purchase'),
                'TotalIva'        => $compras->sum('vatvalue'),
                'data'            => $compras,
            ],
            Response::HTTP_ACCEPTED
        );
    }

    public function procesarIva(Request $request): JsonResponse
    {
        $desdefecha = $request->input('fechadesde');
        $hastafecha = $request->input('fechahasta');

        $ventas = SalesInvoice::select(
            'id',
            'date_issue',
            'expiration_date',
            'number',
            'prefix',
            'document_name',
            'customer',
            'client_name',
            'subtotal',
            'discounts',
            'total_sale',
            'vatvalue',
            'retefuente',
            'reteiva',
            'reteica',
            'impoconsumo',
            'cufe',
            'state'
        )
            ->whereBetween('date_issue', [$desdefecha, $hastafecha])
            ->where('companies_id', $request->input('company_id'))
            ->orderBy('date_issue')
            ->get();

        $compras = PurchasesInvoice::select(
            'id',
            'date_issue',
            'expiration_date',
            'number',
            'prefix',
            'document_name',
            'supplier',
            'supplier_name',
            'subtotal',
            'discounts',
            'total_purchase',
            'vatvalue',
            'retefuente',
            'reteiva',
            'reteica',
            'cufe',
            'state'
        )
            ->whereBetween('date_issue', [$desdefecha, $hastafecha])
            ->where('companies_id', $request->input('company_id'))
            ->orderBy('date_issue')
            ->get();

        return response()->json(
            [
                'status'                 => '200',
                'message'                => 'Mostrando Información de Ivas - Fechas entre ' . $desdefecha . ' y ' . $hastafecha,
                'NumRegistroVentas'      => $ventas->count(),
                'NumRegistroCompras'     => $compras->count(),
                'TotalIvaVentas'         => $ventas->sum('vatvalue'),
                'TotalIvaCompras'        => $compras->sum('vatvalue'),
            ],
            Response::HTTP_ACCEPTED
        );
    }

    public function estadisticaAnual(Request $request): JsonResponse
    {
        $desdefecha = $request->input('fechadesde');
        $hastafecha = $request->input('fechahasta');

        $nombresMeses = [
            1  => 'Enero',
            2  => 'Febrero',
            3  => 'Marzo',
            4  => 'Abril',
            5  => 'Mayo',
            6  => 'Junio',
            7  => 'Julio',
            8  => 'Agosto',
            9  => 'Septiembre',
            10 => 'Octubre',
            11 => 'Noviembre',
            12 => 'Diciembre'
        ];

        if ($request->input('toggle') === 'ventas') {
            $datos = SalesInvoice::select(
                DB::raw('MONTH(date_issue) as month'),
                DB::raw('COUNT(*) as total_documentos'),
                DB::raw('SUM(subtotal) as total_subtotal'),
                DB::raw('SUM(vatvalue) as total_iva'),
                DB::raw('SUM(total_sale) as gran_total'),
            )
                ->whereBetween('date_issue', [$desdefecha, $hastafecha])
                ->groupBy(DB::raw('MONTH(date_issue)'))
                ->orderBy(DB::raw('MONTH(date_issue)'))
                ->get()
                ->map(function ($item) use ($nombresMeses) {
                    $item->nombre_mes = $nombresMeses[$item->month];
                    return $item;
                });
        }

        if ($request->input('toggle') === 'compras') {
            $datos = PurchasesInvoice::select(
                DB::raw('MONTH(date_issue) as month'),
                DB::raw('COUNT(*) as total_documentos'),
                DB::raw('SUM(subtotal) as total_subtotal'),
                DB::raw('SUM(vatvalue) as total_iva'),
                DB::raw('SUM(total_purchase) as gran_total'),
            )
                ->whereBetween('date_issue', [$desdefecha, $hastafecha])
                ->groupBy(DB::raw('MONTH(date_issue)'))
                ->orderBy(DB::raw('MONTH(date_issue)'))
                ->get()
                ->map(function ($item) use ($nombresMeses) {
                    $item->nombre_mes = $nombresMeses[$item->month];
                    return $item;
                });
        }

        return response()->json(
            [
                'status'          => '200',
                'message'         => 'Mostrando Información de Ventas - Fechas entre ' . $desdefecha . ' y ' . $hastafecha,
                'NumeroMeses'     => $datos->count(),
                'AcumuladoTotal'  => $datos->sum('gran_total'),
                'AcumuladoIva'    => $datos->sum('total_iva'),
                'TotalDocumentos' => $datos->sum('total_documentos'),
                'data'            => $datos,
            ],
            Response::HTTP_ACCEPTED
        );
    }
}
