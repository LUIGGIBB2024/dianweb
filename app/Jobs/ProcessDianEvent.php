<?php

namespace App\Jobs;

use App\Models\PurchasesInvoice;
use App\Services\DianApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessDianEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // El Job puede durar hasta 10 minutos (600 segundos) antes de fallar
    public $timeout = 600;

    // Si la DIAN falla, reintentar 2 veces más en momentos diferentes
    public $tries = 3;

    // Esperar 30 segundos antes de reintentar si falla
    public $backoff = 30;

    protected $invoiceId, $eventCode, $field, $token;

    public function __construct($invoiceId, $eventCode, $field, $token)
    {
        $this->invoiceId = $invoiceId;
        $this->eventCode = $eventCode;
        $this->field = $field; // 'evento1', 'evento2', 'evento3'
        $this->token = $token;
    }

    public function handle(DianApiService $api)
    {

        info("El token recibido es: " . $this->token);
        $invoice = PurchasesInvoice::find($this->invoiceId);
        if (!$invoice) return;

        // Actualizamos qué evento estamos procesando
        $invoice->update(['evento' => 'evento_' . substr($this->invoiceId, -1)]);

        $response = $api->sendEvent($invoice, $this->eventCode, $this->token);

        if ($response->successful()) {
            $data = $response->json();
            $invoice->update([
                $this->field => 'EXITOSO', // O el ID/Mensaje que devuelva tu API
                'state_evento' => 'pendiente' // Sigue pendiente para el próximo en la cadena
            ]);

            $invoice->update(['evento' => 'evento_' . substr($this->field, -1)]);
        } else {
            $invoice->update(['state_evento' => 'fallido']);
            throw new \Exception("Error DIAN en evento {$this->eventCode}: " . $response->body());
        }
    }
}
