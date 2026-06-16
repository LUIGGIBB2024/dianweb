<?php

namespace App\Http\Controllers;

use App\Models\PurchasesInvoice;
use App\Jobs\ProcessDianEvent;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;

class DianEventController extends Controller
{
    public function process(Request $request)
    {
        $registros = $request->input('lista'); // Array de IDs seleccionados
        $token = $request->input('company_token');
        //$company = Company::find($request->company_id);
        //return response()->json(['message' => $request->all()]);
        //$token = $company->token ?? '';

        foreach ($registros as $item) {
            // Marcamos la factura como 'procesando' antes de entrar a la cola
            //return response()->json(['message - Token:' => $token]);

            PurchasesInvoice::where('id', $item['id'])->update(['state_evento' => 'procesando']);

            // Encadenamos los 3 eventos secuencialmente
            $id = $item['id'];
            Bus::chain([
                new ProcessDianEvent($id, '1', 'evento1', $token),
                new ProcessDianEvent($id, '3', 'evento2', $token),
                new ProcessDianEvent($id, '4', 'evento3', $token),
                // Al finalizar con éxito, marcar como completado
                fn() => PurchasesInvoice::where('id', $id)->update(['state_evento' => 'completado'])
            ])->dispatch();
        }

        return response()->json(['message' => 'Proceso iniciado en segundo plano.']);
    }

    // 👇 Nuevo endpoint de estado
    public function estado(Request $request)
    {
        $ids = collect($request->input('lista'))->pluck('id');

        $facturas = PurchasesInvoice::whereIn('id', $ids)
            ->select('id', 'number', 'prefix', 'evento', 'state_evento', 'evento1', 'evento2', 'evento3')
            ->get();

        return response()->json([
            'success'  => true,
            'facturas' => $facturas,
        ]);
    }
}
