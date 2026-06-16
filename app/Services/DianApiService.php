<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class DianApiService
{
    protected $baseUrl = 'http://89.116.31.33:81/api/ubl2.1/';

    public function sendEvent($invoice, $eventCode, $token_autentication)
    {
        $payload = [
            "event_id" => (string)$eventCode,
            "document_reference" => [
                "cufe" => $invoice->cufe
            ],
            "issuer_party" => [
                "identification_number" => "77023910",
                "first_name" => "LUIS",
                "last_name" => "BERNAL",
                "organization_department" => "CONTABILIDAD",
                "job_title" => "AUXILIAR CONTABLE"
            ]
        ];


        return Http::withToken($token_autentication)
            ->withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])
            ->post($this->baseUrl . 'send-event-data', $payload);
    }
}
