<?php

namespace App\Http\Controllers;

use App\Jobs\DIAN\Evento1DIAN;
use App\Models\PurchasesInvoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DianEventoController extends Controller
{
    public function procesarEventos(Request $request): JsonResponse
    {
        $request->validate([
            'url_token'  => 'required|string',
            'company_id' => 'required|integer',
            'lista'      => 'required|array|min:1',
            'lista.*'    => 'exists:purchases_invoices,id',
        ]);

        $facturas = PurchasesInvoice::whereIn('id', $request->lista)
            ->where('companies_id', $request->company_id)
            ->where('state_evento', '!=', 'completado') // No reprocesar completadas
            ->get();

        foreach ($facturas as $factura) {
            $factura->update(['state_evento' => 'pendiente']);

            // 🚀 Lanza el primer evento, los siguientes se encadenan automáticamente
            Evento1DIAN::dispatch($factura, $request->url_token);
        }

        return response()->json([
            'success' => true,
            'mensaje' => "Se enviaron {$facturas->count()} facturas a procesar",
            'ids'     => $facturas->pluck('id'),
        ]);
    }

    public function estadoEventos(Request $request): JsonResponse
    {
        $facturas = PurchasesInvoice::whereIn('id', $request->lista)
            ->select('id', 'number', 'prefix', 'evento', 'state_evento', 'evento1', 'evento2', 'evento3')
            ->get();

        return response()->json([
            'success'  => true,
            'facturas' => $facturas,
        ]);
    }
}
