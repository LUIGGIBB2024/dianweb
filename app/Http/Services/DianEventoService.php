<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class DianEventoService
{
    private string $baseUrl = 'http://89.116.31.33:81/api/ubl2.1';

    public function enviarEvento(
        string $urlToken,
        int    $eventId,
        string $cufe
    ): array {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $urlToken,
            'Content-Type'  => 'application/json',
        ])->post("{$this->baseUrl}/send-event-data", [
            'event_id' => (string) $eventId,
            'document_reference' => [
                'cufe' => $cufe,
            ],
            'issuer_party' => [
                'identification_number'   => '89000001',
                'first_name'              => 'PEPITO',
                'last_name'               => 'PEREZ',
                'organization_department' => 'CONTABILIDAD',
                'job_title'               => 'AUXILIAR CONTABLE',
            ],
        ]);

        return $response->json();
    }

    public function fueExitoso(array $response): bool
    {
        return isset($response['ResponseDian']['Envelope']['Body']
                ['SendEventUpdateStatusResponse']
                ['SendEventUpdateStatusResult']['IsValid'])
            && $response['ResponseDian']['Envelope']['Body']
                ['SendEventUpdateStatusResponse']
                ['SendEventUpdateStatusResult']['IsValid'] === 'true';
    }
}
