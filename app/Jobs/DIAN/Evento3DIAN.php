<?php

namespace App\Jobs\DIAN;

use App\Models\PurchasesInvoice;
use App\Services\DianEventoService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class Evento3DIAN implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 60;

    public function __construct(
        public PurchasesInvoice $factura,
        public string           $urlToken
    ) {}

    public function handle(DianEventoService $dian): void
    {
        $this->factura->update(['evento' => 'evento_3']);

        $response = $dian->enviarEvento($this->urlToken, 3, $this->factura->cufe);

        if ($dian->fueExitoso($response)) {
            // ✅ Todos los eventos completados
            $this->factura->update([
                'evento3'      => $response['ResponseDian']['Envelope']['Body']['SendEventUpdateStatusResponse']['SendEventUpdateStatusResult']['StatusCode'],
                'state_evento' => 'completado',
            ]);
        } else {
            $this->factura->update(['state_evento' => 'fallido']);
            Log::error("Evento3DIAN fallido - Factura {$this->factura->id}", $response);
        }
    }

    public function failed(\Throwable $e): void
    {
        $this->factura->update(['state_evento' => 'fallido']);
        Log::error("Evento3DIAN excepción - Factura {$this->factura->id}: " . $e->getMessage());
    }
}
