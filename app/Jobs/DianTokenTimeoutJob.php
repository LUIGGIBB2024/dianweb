<?php

namespace App\Jobs;

use App\Models\DianTokenQueue;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DianTokenTimeoutJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        // Busca solicitudes en processing que llevan más de 5 minutos
        $expiradas = DianTokenQueue::where('status', 'processing')
            ->where('processing_at', '<=', now()->subMinutes(2))
            ->get();

        foreach ($expiradas as $solicitud) {
            $solicitud->update(['status' => 'timeout']);

            // Procesa el siguiente en la cola
            $this->procesarSiguiente();
        }
    }

    private function procesarSiguiente(): void
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
}
