<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DianTokenQueue;
use Illuminate\Support\Facades\Auth;

class DianController extends Controller
{
    // DianController.php
    public function solicitarToken()
    {
        // Evita duplicados del mismo usuario
        $yaEnCola = DianTokenQueue::where('user_id', auth()->id())
            ->whereIn('status', ['waiting', 'processing'])
            ->exists();

        if ($yaEnCola) {
            return response()->json([
                'error' => 'Ya tienes una solicitud activa'
            ], 409);
        }

        // Evita que dos usuarios procesen al mismo tiempo
        $hayProcesando = DianTokenQueue::where('status', 'processing')->exists();

        $solicitud = DianTokenQueue::create([
            'user_id'        => auth()->id(),
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

    public function verificarToken()
    {
        $solicitud = DianTokenQueue::where('user_id', auth()->id())
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
        $request->validate([
            'token' => 'required|string',
            'url_completa' => 'required|string'
        ]);

        $solicitud = DianTokenQueue::where('user_id', auth()->id())
            ->where('status', 'processing')
            ->first();

        if (!$solicitud) {
            return response()->json(['error' => 'No hay solicitud en procesamiento'], 404);
        }

        $solicitud->update([
            'status' => 'received',
            'token' => $request->token,
            'url_completa' => $request->url_completa,
            'received_at' => now()
        ]);

        $this->procesarSiguiente();

        return response()->json(['ok' => true]);
    }
}
