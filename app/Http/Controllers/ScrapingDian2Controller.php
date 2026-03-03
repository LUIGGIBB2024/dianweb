<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Control;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\Panther\Client;
use Symfony\Component\DomCrawler\Crawler;

class ScrapingDianController extends Controller
{
    public function scraping_dian(Request $request)
    {
        echo "Iniciando scraping DIAN...\n";

        $user = Auth::user();
        $control = Control::find(1);
        $urldian = trim($control->urldian ?? '');

        if (!$urldian) {
            return response()->json(['message' => '❌ La URL de la DIAN no está configurada.'], 400);
        }

        if (!$user) {
            return response()->json(['message' => '❌ No hay usuario autenticado.'], 401);
        }

        $info_control = Company::find($user->company_id);
        if (!$info_control) {
            return response()->json(['message' => '❌ Empresa no encontrada.'], 404);
        }

        $nitempresa = $info_control->nit;
        $cedula = $info_control->representativeid;

        $certPem = public_path("certificates/{$nitempresa}/certificado.pem");
        $keyPem  = public_path("certificates/{$nitempresa}/clave.pem");

        // ------------------------------------------------------------------
        // 🔧 CONFIGURACIÓN DE CHROMEDRIVER Y CHROME EN WINDOWS
        // ------------------------------------------------------------------
        // Rutas absolutas sin espacios (o entre comillas dobles si los hay)
        // Recomendado: colocar chromedriver.exe en C:\tools\chromedriver.exe

        putenv('PANTHER_CHROME_DRIVER_BINARY=C:\\tools\\chromedriver.exe');
        putenv('PANTHER_CHROME_BINARY=C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe');
        putenv('PANTHER_NO_SANDBOX=1');

        echo "Rutas configuradas correctamente. Iniciando Chrome...\n";

        try {
            // Inicializa el cliente de Chrome en modo headless
            $client = Client::createChromeClient(null, [
                '--headless', // puedes comentar esto para depurar
                '--no-sandbox',
                '--disable-gpu',
                '--disable-dev-shm-usage',
                '--disable-software-rasterizer',
                '--disable-extensions',
                '--disable-background-networking',
                '--enable-features=NetworkService,NetworkServiceInProcess',
                '--ignore-certificate-errors',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => '❌ Error al iniciar Panther: ' . $e->getMessage(),
            ], 500);
        }

        $url = $urldian . '/User/CertificateLogin';
        echo "Accediendo a: {$url}\n";

        try {
            echo "Accediendo 200 Ok {$url} \n";
            $crawler = $client->request('GET', $url);
        } catch (\Exception $e) {
            return response()->json([
                'message' => '❌ Error al cargar la página: ' . $e->getMessage(),
            ], 500);
        }

        echo "Página cargada correctamente.\n";

        $client->waitFor('input[name="UserCode"]', 10);
        $crawler->filter('input[name="UserCode"]')->sendKeys($cedula);

        $token = $crawler->filter('input[name="__RequestVerificationToken"]')->count()
            ? $crawler->filter('input[name="__RequestVerificationToken"]')->attr('value')
            : null;

        if ($crawler->filter('button[type="submit"], input[type="submit"]')->count()) {
            $crawler->filter('button[type="submit"], input[type="submit"]')->first()->click();
        } else {
            return response()->json(['message' => '❌ No se encontró el botón de envío.'], 400);
        }

        $client->waitFor('#tableDocuments, body, html', 10);
        $html = $client->getPageSource();

        file_put_contents(storage_path('app/panther_last.html'), $html);

        return response()->json([
            'status' => '✅ Página cargada correctamente',
            'token' => $token,
            'current_url' => $client->getCurrentURL(),
            'html_snippet' => substr($html, 0, 500),
        ]);
    }
}
