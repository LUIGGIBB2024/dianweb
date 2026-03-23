<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Control;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
#use Symfony\Component\Panther\Client;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;

class ScrapingDianController extends Controller
{


    public function extraerTabla()
    {
        print("Entre Aquí a extraer tabla...\n");
        $url = "https://catalogo-vpfe.dian.gov.co/User/AuthToken?pk=10910094|77023910&rk=77023910&token=b5636d69-ab87-4f63-8cad-2eadef96b75a";

        $client = new Client([
            'verify' => false, // evitar problemas SSL (opcional)
            'timeout' => 30,
        ]);

        try {
            $response = $client->request('GET', $url);

            $html = $response->getBody()->getContents();

            print("HTML recibido:\n" . substr($html, 0, 500) . "\n");

            $crawler = new Crawler($html);

            $data = [];

            // Selecciona la tabla (ajusta el selector según la página)
            $crawler->filter('table tr')->each(function ($tr, $i) use (&$data) {

                $row = [];

                $tr->filter('td')->each(function ($td) use (&$row) {
                    $row[] = trim($td->text());
                });

                if (!empty($row)) {
                    $data[] = $row;
                }
            });

            return $data;
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
