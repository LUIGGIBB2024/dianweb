<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Js;

use App\Models\Company;
use App\Models\User;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use Symfony\Component\DomCrawler\Crawler;


class ScrapingController extends Controller
{
    public function scraping_dian(Request $request)
    {
        // Lógica para iniciar el proceso de scraping DIAN
        // Puedes utilizar RoachPHP o cualquier otra herramienta de scraping aquí

        // Ejemplo de respuesta
        // return response()->json([
        //      'message' => 'Scraping DIAN iniciado correctamente',
        //      'id_company' => $request->bearerToken(),
        //      // 'data' => $scrapedData, // Puedes devolver los datos scrapeados si es necesario
        //  ]);

        // if (Auth::check()) {
        //     // ✅ Hay sesión activa
        //     $user = Auth::user();
        //     echo "Usuario autenticado 000 : {$user->name} (ID: {$user->id})";
        // } else {
        //     // ❌ No hay sesión
        //     echo "No hay usuario autenticado. 000 {$request->bearerToken()}";
        //     return response()->json([
        //         'message' => 'No hay usuario autenticado',

        //     ]);
        // }

        $loginrequest = $request;

        return $this->login($loginrequest);
    }


    public function login(Request $loginrequest)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'message' => 'No hay usuario autenticado',
            ], 401);
        }

        $id_company     = $user->company_id;
        $info_control   = Company::find($id_company);
        $nit            = $info_control->nit;
        $cedula         = $info_control->representativeid;

        if (!$info_control) {
            return response()->json(['message' => 'Empresa no encontrada'], 404);
        }

        $nitempresa = $info_control->nit;
        $certificateName = trim($info_control->certificatename);
        $certificatePath = public_path("certificates" . DIRECTORY_SEPARATOR . "{$nitempresa}" . DIRECTORY_SEPARATOR . "{$certificateName}");
        $certificatePassword = trim($info_control->certificatekey);
        //$certificatePassword = 'clave2026JKMK.pem';
        //$certificatePassword = 'kXGnaDJSqbxjyOBg';

        if (!file_exists($certificatePath)) {
            dd("❌ El archivo no existe en: {$certificatePath}");
        }

        $size = filesize($certificatePath);
        if ($size === 0) {
            dd("❌ El archivo .p12 está vacío o dañado.");
        }

        echo "Datos Importantes : {$certificatePath} - {} -  - {$certificatePassword} \n";

        $certPem = public_path("certificates/{$nitempresa}/certificado.pem");
        $keyPem  = public_path("certificates/{$nitempresa}/clave.pem");

        if (!file_exists($certPem) || !file_exists($keyPem)) {
            return response()->json([
                'message' => '❌ No se encuentran los archivos PEM generados. Verifica certificado.pem y clave.pem',
                'path_cert' => $certPem,
                'path_key'  => $keyPem,
            ], 404);
        }

        $endpoint = 'https:' . DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR . 'catalogo-vpfe.dian.gov.co';
        $endpoint = 'https://catalogo-vpfe.dian.gov.co';
        $endpoint_main = preg_replace('/\\s+/', '', $endpoint);

        //$endpoint_main = preg_replace('/\\s+/', '', $endpoint);

        // ✅ Crear cliente Guzzle usando los .pem
        //$jar = new CookieJar();
        // $client = new Client([
        //     'base_uri' => 'https://catalogo-vpfe.dian.gov.co',
        //     'cert'     => $certPem,
        //     'ssl_key'  => $keyPem,
        //     'verify'   => false, // ⚠️ solo para pruebas locales
        //     'cookies'  => $jar,
        //     'headers'  => ['User-Agent' => 'Mozilla/5.0'],
        // ]);
        // echo "Voy aqui 2000 client2 \n";
        // $client2 = new Client([
        //     'base_uri' => 'https://catalogo-vpfe.dian.gov.co',
        //     'cert'     => $certPem,
        //     'ssl_key'  => $keyPem,
        //     'verify'   => true, // solo para pruebas
        //     'cookies'  => $jar,
        //     'headers'  => ['User-Agent' => 'Mozilla/5.0'],
        //     'timeout'  => 10, // evita bloqueos largos
        // ]);

        echo "Voy aqui 00 \n";

        // try {
        //     $response = $client2->get('/User/Login');
        //     //  return response()->json([
        //     //     'message' => '✅ Cliente Guzzle inicializado correctamente',
        //     //     'status_code' => $response->getStatusCode(),
        //     // ]);

        // } catch (\GuzzleHttp\Exception\ClientException $e) {
        //     dd('404 o 401: ' . $e->getResponse()->getStatusCode());
        // }

        $jar = new \GuzzleHttp\Cookie\CookieJar();

        echo "Voy aqui 01 \n";

        $client = new \GuzzleHttp\Client([
            'base_uri' => $endpoint_main,
            'verify'   => false,
            'cookies'  => $jar,
            'headers'  => ['User-Agent' => 'Mozilla/5.0'],
        ]);

        echo "Voy aqui 02 \n";

        // 1️⃣ Paso: entrar a la página principal (sin certificado aún)


        $endpoint =  DIRECTORY_SEPARATOR . 'User' . DIRECTORY_SEPARATOR . 'Login';
        $endpoint =  '/User/Login';
        $endpoint_login = preg_replace('/\\s+/', '', $endpoint);

        $response = $client->get($endpoint_login);


        echo "Voy aqui 03 \n";


        try {
            // 2️⃣ Paso: ahora validar con certificado
            echo "Voy aqui 04 \n";
            $clientCert = new \GuzzleHttp\Client([
                'base_uri' => $endpoint_main,
                'cert'     => public_path('certificates' . "/" . $nit . "/" . 'certificado.pem'),
                'ssl_key'  => public_path('certificates' . "/" . $nit . "/" . 'clave.pem'),
                'verify'   => false,
                'cookies'  => $jar,
                'headers'  => [
                    'User-Agent' => 'Mozilla/5.0',
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
            ]);
            echo "Voy aqui 05 \n";
            //$responseCert = $clientCert->get('/User/CertificateLogin');
            /////////////////////////////////////////////////////////////////////////////


            $endpoint_page = '/User/CertificateLogin';
            $url = rtrim($clientCert->getConfig('base_uri'), '/') . $endpoint_page;
            logger()->info("Consultando URL: {$url}");

            // $endpoint_page =  "/" . "User" . "/" . "CertificateLogin";
            // //$endpoint_page = preg_replace('/\\s+/', '', $endpoint_page);

            // $response = $clientCert->get($endpoint_page);

            $html = (string) $response->getBody();

            $crawler = new \Symfony\Component\DomCrawler\Crawler($html);

            $csrf = $crawler->filter('input[name="__RequestVerificationToken"]')->attr('value');

            echo "Voy aqui 051 {$csrf}\n";

            $response = $client->post('/User/CertificateLogin', [
                'form_params' =>
                [
                    'UserCode'    => $cedula,
                    '_token'      => $csrf,
                ],
                'cookies' => $jar,
            ]);

            echo "Voy aqui 052 \n";


            if (str_contains($html, 'Checking your browser before accessing')) {
                $estado = '⚠️ Cloudflare JS Challenge detectado';
            } elseif (str_contains($html, 'cf-challenge') || str_contains($html, 'cf-browser-verification')) {
                $estado = '⚠️ Cloudflare bloqueó el acceso (JS challenge o captcha)';
            } else {
                $estado = '✅ Página cargada normalmente (sin bloqueo)';
            }

            return response()->json([
                'status' => $estado,
                'http_code' => $response->getStatusCode(),
                'html_snippet' => substr($html, 0, 300),
                // 'html_snippet2' => $html,
            ]);



            /////////////////////////////////////////////////////////////////////////////



            echo "Voy aqui 06 \n";

            echo "Voy aqui 2000 - https://catalogo-vpfe.dian.gov.co/User/CertificateLogin\n";

            // Intentar una petición de prueba
            // $response = $client->get('/User/Login');

            // Si llega aquí, el cliente y el certificado están correctos
            // return response()->json([
            //     'message' => '✅ Cliente Guzzle inicializado correctamente',
            //     'status_code' => $response->getStatusCode(),
            // ]);

        } catch (\GuzzleHttp\Exception\RequestException $e) {
            // Errores de conexión o SSL
            return response()->json([
                'message' => '❌ Error en la conexión - verifica el certificado y la conexión --',
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ], 500);
        } catch (\Throwable $e) {
            // Cualquier otro error (ruta errónea, etc.)
            return response()->json([
                'message' => '❌ Error inesperado al crear o usar el cliente Guzzle',
                'error'   => $e->getMessage(),
            ], 500);
        }

        echo "Voy aqui 2000 \n";
        // $p12Content = file_get_contents($certificatePath);
        // if (openssl_pkcs12_read($p12Content, $certs, $certificatePassword)) {
        //     // Guardar certificado público
        //     file_put_contents(public_path('certificates/901148547/certificado.pem'), $certs['cert']);
        //     // Guardar clave privada
        //     file_put_contents(public_path('certificates/901148547/clave.pem'), $certs['pkey']);
        //     echo "Certificado exportado correctamente.";
        // } else {
        //     echo "Error al leer el archivo .p12. Verifica la contraseña.";
        // }

        // === Leer y convertir el .p12 a .pem ===
        //     $p12Content = file_get_contents($certificatePath);
        //     $certs = [];

        //     if (!openssl_pkcs12_read($p12Content, $certs, $certificatePassword)) {
        //         return response()->json([
        //             'message' => '❌ Error al leer el archivo .p12. Verifica la contraseña o el formato del certificado.',
        //             'openssl_version' => OPENSSL_VERSION_TEXT,
        //         ], 400);
        //     }


        //     // ✅ Crear CookieJar correctamente
        //     $jar = new CookieJar();

        //    $client = new \GuzzleHttp\Client([
        //         'base_uri' => 'https://catalogo-vpfe.dian.gov.co',
        //         'cert'     => public_path("certificates/901148547/certificado.pem"),
        //         'ssl_key'  => public_path("certificates/901148547/clave.pem"),
        //         'verify'   => false, // solo para pruebas locales
        //         'headers'  => ['User-Agent' => 'Mozilla/5.0'],
        //     ]);

        // Paso 2: Obtener login
        $response = $client->get('/User/CertificateLogin');
        echo "Voy aqui 010 :  \n";
        $html = (string) $response->getBody();
        echo "Voy aqui 020 : \n";

        $crawler = new Crawler($html);
        echo "Voy aqui 030 : \n";
        //$csrfToken = $crawler->filter('input[name="_token"]')->attr('value');

        $crawler = new Crawler($html);

        $linkNode = $crawler->filterXPath('/html/body/div[3]/div[1]/div/a[5]');

        if ($linkNode->count() > 0) {
            $crsfValue = $linkNode->attr('data-crsf');
            echo "Valor de crsf encontrado: {$crsfValue} \n";
        } else {
            echo "No se encontró el elemento con el XPath especificado.  \n";
        }

        echo "Voy aqui 100 : {$loginrequest->crsf} \n";

        // Paso 3: Hacer login
        $loginResponse = $client->post('/User/Login', [
            'form_params' => [
                'email'    => 'usuario@example.com',
                'password' => 'secreto123',
                // '_token'   => $csrfToken,
            ],
            'cookies' => $jar,
            'allow_redirects' => ['referer' => true],
        ]);

        echo "Voy aqui 200 :  \n";

        // Paso 4: Acceder a dashboard
        $dashResponse = $client->get('/dashboard', ['cookies' => $jar]);
        $dashboardHtml = (string) $dashResponse->getBody();

        return response()->json([
            'message' => 'Scraping DIAN completado correctamente',
            'html' => $dashboardHtml,
        ]);
    }

    public function login1()
    {
        // Paso 1: Configurar cliente Guzzle con certificado y CookieJar

        $id_company   =   Auth::User()->company_id;
        $info_control =   Company::find($id_company);

        $nitempresa         = $info_control->nit;
        $certificateName    = trim($info_control->certificatename);
        $certificatePath    = public_path("certificates" . DIRECTORY_SEPARATOR . "{$nitempresa}" . DIRECTORY_SEPARATOR . "{$certificateName}");
        $certificatePassword = env('CERTIFICATE_P12_PASSWORD', trim($info_control->certificatekey));


        $info_control = Company::find($id_company);
        echo "Parsing page...\n " .  $info_control->certificatekey . "-" . $certificatePath . "\n";

        // ✅ Crear CookieJar correctamente
        $jar = new CookieJar();

        $client = new Client([
            'base_uri' => 'https://appweb.barfcar.com',
            'cert'     => [$certificatePath, $certificatePassword],
            'curl'     => [CURLOPT_SSLCERTTYPE => 'P12'],
            'cookies'  => $jar,
            'headers'  => ['User-Agent' => 'Mozilla/5.0'],
        ]);

        // Paso 2: Obtener página de login y extraer CSRF
        $response = $client->get('/login');
        $html = (string) $response->getBody();

        return response()->json([
            'message' => 'Scraping DIAN iniciado correctamente html',
            'html' => $html,
            // 'data' => $scrapedData, // Puedes devolver los datos scrapeados si es necesario
        ]);

        $crawler = new Crawler($html);
        $csrfToken = $crawler->filter('input[name="_token"]')->attr('value');

        // Paso 3: Enviar formulario de login
        $loginResponse = $client->post('/login', [
            'form_params' => [
                'email'    => 'usuario@example.com',
                'password' => 'secreto123',
                '_token'   => $csrfToken,
            ],
            'cookies' => $jar,
            'allow_redirects' => ['referer' => true],
        ]);

        // Paso 4: Acceder a página interna después del login (p.ej. /dashboard)
        $dashResponse = $client->get('/dashboard', ['cookies' => $jar]);
        $dashboardHtml = (string) $dashResponse->getBody();

        // Aquí podríamos usar DomCrawler de nuevo para extraer datos de $dashboardHtml
        // ...
        return response()->json(['html' => $dashboardHtml]);
    }
}
