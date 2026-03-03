<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Control;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\Panther\Client;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\DesiredCapabilities;

class ScrapingDianController extends Controller
{
    public function scraping_dian(Request $request)
    {
        echo "Iniciando scraping DIAN...\n";

        // ────────────────────────────────────────────────
        // 1️⃣ Validaciones iniciales
        // ────────────────────────────────────────────────
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

        // ────────────────────────────────────────────────
        // 2️⃣ Configuración de ChromeDriver
        // ────────────────────────────────────────────────
        putenv('PANTHER_CHROME_DRIVER_BINARY=C:\\tools\\chromedriver.exe');
        putenv('PANTHER_CHROME_BINARY=C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe');
        putenv('PANTHER_NO_SANDBOX=1');

        $chromeOptions = [
            '--headless', // puedes comentar esta línea para depurar visualmente
            '--no-sandbox',
            '--disable-gpu',
            '--disable-dev-shm-usage',
            '--disable-software-rasterizer',
            '--disable-extensions',
            '--ignore-certificate-errors',
            '--disable-ipv6', // evita errores Winsock
        ];

        echo "Configuración establecida. Intentando iniciar ChromeDriver...\n";

        // ────────────────────────────────────────────────
        // 3️⃣ Intentar iniciar Chrome directamente con Panther
        // ────────────────────────────────────────────────
        $client = null;
        try {
            $client = Client::createChromeClient(null, $chromeOptions);
            echo "✅ ChromeDriver iniciado correctamente.\n";
        } catch (\Exception $e) {
            echo "⚠️ No se pudo iniciar ChromeDriver directamente: {$e->getMessage()}\n";

            // ────────────────────────────────────────────────
            // 4️⃣ Intentar conectar a ChromeDriver remoto (9515)
            // ────────────────────────────────────────────────
            echo "Intentando conectar a ChromeDriver remoto en puerto 9515...\n";

            try {
                // Verificamos si ChromeDriver está disponible
                $fp = @fsockopen('localhost', 9515, $errno, $errstr, 2);
                if (!$fp) {
                    echo "❌ ChromeDriver no está en ejecución. Debes iniciar manualmente:\n";
                    echo "   C:\\tools\\chromedriver.exe --port=9515\n";
                    return response()->json([
                        'message' => 'ChromeDriver no está activo ni pudo iniciarse automáticamente.',
                    ], 500);
                }
                fclose($fp);

                $webDriver = RemoteWebDriver::create(
                    'http://localhost:9515',
                    DesiredCapabilities::chrome()
                );

                // ✅ Método válido en Panther 2.2
                $client = Client::createFromWebDriver($webDriver);
                echo "✅ Conectado a ChromeDriver existente.\n";
            } catch (\Exception $ex) {
                return response()->json([
                    'message' => '❌ Error al conectar con ChromeDriver remoto: ' . $ex->getMessage(),
                ], 500);
            }
        }

        // ────────────────────────────────────────────────
        // 5️⃣ Scraping en la DIAN
        // ────────────────────────────────────────────────
        $url = rtrim($urldian, '/') . '/User/CertificateLogin';
        echo "Accediendo a: {$url}\n";

        try {
            echo "✅ Página cargada correctamente 0000.\n";
            $crawler = $client->request('GET', $url);
            echo "✅ Página cargada correctamente.\n";
        } catch (\Exception $e) {
            return response()->json([
                'message' => '❌ Error al cargar la página 000: ' . $e->getMessage(),
            ], 500);
        }

        try {
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
        } catch (\Exception $e) {
            return response()->json([
                'message' => '⚠️ Error durante la interacción: ' . $e->getMessage(),
            ], 500);
        }
    }
}
