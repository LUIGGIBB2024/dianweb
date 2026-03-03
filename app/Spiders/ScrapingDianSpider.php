<?php

namespace App\Spiders;

use App\Models\Company;
use RoachPHP\Http\Request;
use RoachPHP\Http\Response;
use RoachPHP\Spider\BasicSpider;
use RoachPHP\Extensions\LoggerExtension;
use RoachPHP\Extensions\StatsCollectorExtension;
use Throwable;

class ScrapingDianSpider extends BasicSpider
{
    public array $extensions = [
        LoggerExtension::class,
        StatsCollectorExtension::class,
    ];

    public array $startUrls = [
        'https://www.appweb.barfcar.com/login',
    ];

    protected function initialRequests(): array
    {
        echo "[DEBUG] Iniciando initialRequests...\n";

        try {
            // --- Configuración básica (sin certificados) ---
            $guzzleOptions = [
                'verify' => false,
                'timeout' => 30,
            ];

            $request = new Request(
                'GET',
                $this->startUrls[0],
                [$this, 'parse'],
                [], // contexto vacío
                $guzzleOptions
            );

            echo "[DEBUG] Request creado exitosamente. Enviando al motor...\n";
            return [$request];
        } catch (Throwable $e) {
            echo "[FATAL ERROR] initialRequests: " . $e->getMessage() . "\n";
            return [];
        }
    }

    public function parse(Response $response): \Generator
    {
        echo "[DEBUG] Estoy en Parse...\n";
        yield from $this->parseLogin($response);
    }

    public function parseLogin(Response $response): \Generator
    {
        echo "[DEBUG] parseLogin alcanzado! Status: " . $response->getStatus() . "\n";

        // ✅ Imprimimos directamente en consola
        echo "[PAGE BODY]\n";
        echo substr($response->getBody(), 0, 500) . "\n...\n";

        // No usamos pipelines; solo mostramos los datos.
        yield $this->item(['status' => $response->getStatus()]);
    }
}


// namespace App\Spiders;

// use App\Models\Company;
// use Illuminate\Support\Facades\Auth;
// use RoachPHP\Http\Request;
// use RoachPHP\Http\Response;
// use RoachPHP\Spider\BasicSpider;
// use Throwable;
// use RoachPHP\Extensions\LoggerExtension;
// use RoachPHP\Extensions\StatsCollectorExtension;
// use RoachPHP\ItemPipeline\Processors\PrintItemProcessor;


// class ScrapingDianSpider extends BasicSpider
// {
//     public array $itemProcessors = [
//         PrintItemProcessor::class,
//     ];

//     public array $extensions = [
//         LoggerExtension::class,
//         StatsCollectorExtension::class,
//     ];

//     public array $startUrls = [
//         'https://www.appweb.barfcar.com/login',
//     ]; 

//     protected function initialRequests(): array
//     {
//         echo "[DEBUG] Iniciando initialRequests...\n";

//         try {
//             $id_company   = 1;
//             $info_control = Company::find($id_company);

//             if (!$info_control) {
//                 echo "[ERROR] No se encontró la compañía con ID: {$id_company}.\n";
//                 return [];
//             }

//             $certificateName = trim($info_control->certificatename);
//             // Aseguramos que la ruta sea absoluta y usamos DIRECTORY_SEPARATOR para compatibilidad con Windows
//             $certificatePath = public_path("certificates" . DIRECTORY_SEPARATOR . "901148547" . DIRECTORY_SEPARATOR . $certificateName);
//             //$certificatePassword = env("CERTIFICATE_P12_PASSWORD", trim($info_control->certificatekey));
//             $certificatePassword =  trim($info_control->certificatekey);

//             echo "[DEBUG] Verificando archivo en: {$certificatePath} - {$certificatePassword}\n";

//             if (!file_exists($certificatePath)) {
//                 echo "[ERROR] EL ARCHIVO NO EXISTE FISICAMENTE EN LA RUTA.\n";
//                 return [];
//             }

//             echo "[DEBUG] Archivo encontrado. Tamaño: " . filesize($certificatePath) . " bytes.\n";

        
//             echo "[DEBUG] Creando objeto Request...\n";

//             // 🔍 Agrega este try separado para detectar error de Guzzle
//             try {
//                 $guzzleOptions = [
//                     // 'cert' => [$certificatePath, $certificatePassword],
//                     // 'curl' => [
//                     //     CURLOPT_SSLCERTTYPE => 'P12',
//                     // ],
//                     'verify' => false,
//                     'timeout' => 30,
//                 ];
//                 echo "[DEBUG] Guzzle options creadas correctamente.\n";
//             } catch (Throwable $e) {
//                 echo "[ERROR] Fallo creando opciones Guzzle: " . $e->getMessage() . "\n";
//                 return [];
//             }

//            $request = new Request(
//                 'GET',
//                 $this->startUrls[0],
//                 [$this, 'parse'],
//                 [], // 👈 contexto vacío
//                 [
//                     'verify' => false,
//                     'timeout' => 30,
//                 ]
//             );

//             echo "[DEBUG] Request creado exitosamente. Enviando al motor de Roach...\n";

//             return [$request];
//             //yield $request;
//         } catch (Throwable $e) {
//             echo "[FATAL ERROR] Ocurrió una excepción en initialRequests: " . $e->getMessage() . "\n";
//             echo "[TRACE] " . $e->getTraceAsString() . "\n";
//             echo "[DEBUG] No se devolvió ningún request por excepción previa.\n";
//             return [];
//         }
//     }

//     public function parse(Response $response): \Generator
//     {
//         echo "[DEBUG] Estoy en Parse...\n";
//         //yield $this->item(['title' => 'Default parse']);
//         yield from $this->parseLogin($response);
//     }

//     public function parseLogin(Response $response): \Generator
//     {
//         echo "[DEBUG] parseLogin alcanzado! Status: " . $response->getStatus() . "\n";
//         yield $this->item(['status' => $response->getStatus()]);
//     }
// } 



// namespace App\Spiders;

// use App\Models\Company;
// use Illuminate\Support\Facades\Auth;
// use RoachPHP\Http\Request;
// use RoachPHP\Http\Response;
// use RoachPHP\Spider\BasicSpider;

// class ScrapingDianSpider extends BasicSpider
// {
//     public array $startUrls = [
//         'https://www.dian.gov.co/',
//     ];

//     /**
//      * En Roach v3.2.1, el constructor de Request es:
//      * public function __construct(string $method, string $uri, callable $parseMethod, array $options = [])
//      */
//     protected function initialRequests(): array
//     {
//         $id_company   = 1;
//         $info_control = Company::find($id_company);

//         $certificateName = trim($info_control->certificatename);
//         $certificatePath = public_path("certificates/901148547/{$certificateName}");
//         $certificatePassword = env('CERTIFICATE_P12_PASSWORD', trim($info_control->certificatekey));

//         $guzzleOptions = [
//             'cert' => [$certificatePath, $certificatePassword],
//             'curl' => [
//                 CURLOPT_SSLCERTTYPE => 'P12',
//             ],
//         ];

//         return [
//             // Para el constructor manual de Request, usamos el array callable.
//             // Si el error persiste aquí, la alternativa en v3 es usar \Closure::fromCallable([$this, 'parseLogin'])
//             new Request(
//                 'GET',
//                 $this->startUrls[0],
//                 [$this, 'parseLogin'],
//                 $guzzleOptions
//             ),
//         ];
//     }

//     public function parseLogin(Response $response): \Generator
//     {
//         echo "Página de login cargada. Enviando formulario...\n";

//         $id_company   = 1;
//         $info_control = Company::find($id_company);

//         $certificateName = trim($info_control->certificatename);
//         $certificatePath = public_path("certificates/901148547/{$certificateName}");
//         $certificatePassword = env('CERTIFICATE_P12_PASSWORD', trim($info_control->certificatekey));

//         $cedulaNumber = '901148547';

//         $guzzleOptions = [
//             'form_params' => [
//                 'cedula' => $cedulaNumber,
//             ],
//             'cert' => [$certificatePath, $certificatePassword],
//             'curl' => [
//                 CURLOPT_SSLCERTTYPE => 'P12',
//             ],
//         ];

//         /**
//          * En Roach v3.x, el método $this->request() es un proxy que espera 
//          * el nombre del método como STRING.
//          */
//         yield $this->request(
//             'POST',
//             $response->getUri(),
//             'parseAuthenticatedPage',
//             $guzzleOptions
//         );
//     }

//     public function parse(Response $response): \Generator
//     {
//         echo "Parsing page...\n";
//         $title = $response->filter('title')->text();
//         yield $this->item([
//             'title' => $title,
//         ]);
//     }

//     public function parseAuthenticatedPage(Response $response): \Generator
//     {
//         echo "Página autenticada cargada. Extrayendo datos...\n";
//         $title = $response->filter('title')->text();
//         yield $this->item([
//             'title' => $title,
//         ]);
//     }
// }



// // class ScrapingDianSpider extends BasicSpider
// // {
// //     public array $startUrls = [
// //         'https://www.transportar.com.co/',
// //     ];

// //     protected function initialRequests(): array
// //     {
// //         $id_company   = 1;
// //         $info_control = Company::find($id_company);
// //         // --- Configuración del certificado digital (.p12) ---

// //         // Usamos public_path() para apuntar a la carpeta public/certificate/901148547/
// //         // Asegúrate de incluir el nombre exacto del archivo .p12
// //         //$certificateName = 'tu_certificado.p12'; // CAMBIA ESTO por el nombre real de tu archivo
// //         $certificateName = trim($info_control->certificatename);
// //         $certificatePath = public_path("/certificates/901148547/{$certificateName}");

// //         // La contraseña del certificado (se recomienda usar env() para seguridad)
// //         $certificatePassword = env('CERTIFICATE_P12_PASSWORD', trim($info_control->certificatekey));

// //         // Opciones de Guzzle para el certificado SSL
// //         $guzzleOptions = [
// //             'cert' => [$certificatePath, $certificatePassword],
// //             'curl' => [
// //                 // A veces es necesario especificar el tipo de certificado para cURL
// //                 CURLOPT_SSLCERTTYPE => 'P12',
// //             ],
// //         ];

// //         return [
// //             new Request(
// //                 'GET',
// //                 $this->startUrls[0],
// //                 [$this, 'parseLogin'],
// //                 [],
// //                 $guzzleOptions
// //             ),
// //         ];
// //     }


// //     public function parse(Response $response): \Generator
// //     {
// //         $id_company   = 1;
// //         $info_control = Company::find($id_company);
// //         echo "Parsing page...\n " .  $info_control->certificatekey . "\n";


// //         $title = $response->filter('title')->text();

// //         // 🔥 Imprimir directamente sin pipeline
// //         echo "Scraped item: " . json_encode(['title' => $title]) . PHP_EOL;

// //         yield $this->item([
// //             'title' => $title,
// //         ]);
// //     }

// //     public function parseAuthenticatedPage(Response $response): \Generator
// //     {
// //         echo "Página autenticada cargada. Extrayendo datos...\n";

// //         // Aquí puedes empezar a extraer los datos de la página ya autenticada.
// //         $title = $response->filter('title')->text();

// //         echo "Scraped item: " . json_encode(['title' => $title]) . PHP_EOL;

// //         yield $this->item([
// //             'title' => $title,
// //             // Otros datos que quieras extraer
// //         ]);
// //     }
// }

// class ScrapingDianSpider extends BasicSpider
// {
//     public array $startUrls = [
//         'https://midominio.com/User/Login',
//     ];

//     /**
//      * @return Request[]
//      */
//     protected function initialRequests(): array
//     {
//         // --- Configuración del certificado digital (.p12) ---
//         // Es crucial que el archivo .p12 y su contraseña se manejen de forma segura.
//         // Se recomienda usar variables de entorno o un sistema de gestión de secretos.
//         $certificatePath = env('CERTIFICATE_P12_PATH', '/path/to/your/certificate.p12');
//         $certificatePassword = env('CERTIFICATE_P12_PASSWORD', 'your_certificate_password');

//         // Opciones de Guzzle para el certificado SSL
//         // Guzzle puede manejar archivos .p12 directamente si se proporciona la ruta y la contraseña como un array.
//         $guzzleOptions = [
//             'cert' => [$certificatePath, $certificatePassword],
//             // Puedes añadir otras opciones de Guzzle si son necesarias, por ejemplo, para ignorar la verificación SSL (NO RECOMENDADO EN PRODUCCIÓN)
//             // 'verify' => false,
//         ];

//         return [
//             new Request(
//                 'GET',
//                 $this->startUrls[0],
//                 [$this, 'parseLogin'], // Usaremos un método de parseo específico para la página de login
//                 [], // No hay meta data adicional para esta solicitud inicial
//                 $guzzleOptions // Pasamos las opciones de Guzzle aquí
//             ),
//         ];
//     }

//     public function parseLogin(Response $response): \Generator
//     {
//         echo "Página de login cargada. Intentando autenticar con certificado y cédula...\n";

//         // Aquí deberías inspeccionar la página de login para encontrar los campos del formulario.
//         // Por ejemplo, si hay un campo oculto para un token CSRF o cualquier otro campo necesario.
//         // Para este ejemplo, asumiremos que el campo de la cédula es 'cedula_number' y que no hay CSRF.
//         // DEBES ADAPTAR ESTO A LA ESTRUCTURA REAL DEL FORMULARIO DE LA PÁGINA.

//         $cedulaNumber = '123456789'; // Reemplaza con el número de cédula real

//         // Si hay un token CSRF, deberías extraerlo de la respuesta:
//         // $csrfToken = $response->filter('input[name="_token"]')->attr('value');

//         // Opciones de Guzzle para la solicitud POST del formulario
//         $guzzleOptions = [
//             'form_params' => [
//                 'cedula_number' => $cedulaNumber,
//                 // '_token' => $csrfToken, // Descomentar si hay token CSRF
//                 // Otros campos del formulario si los hay
//             ],
//             // Reutilizamos las opciones del certificado para la solicitud POST si es necesario
//             'cert' => [env('CERTIFICATE_P12_PATH', '/path/to/your/certificate.p12'), env('CERTIFICATE_P12_PASSWORD', 'your_certificate_password')],
//         ];

//         // Realizamos la solicitud POST para enviar el formulario de login
//         yield $this->request(
//             'POST',
//             $response->getUri(), // Enviamos al mismo URI de la página de login
//             [$this, 'parseAuthenticatedPage'], // Método para parsear la página después del login
//             [],
//             $guzzleOptions
//         );
//     }

//     public function parseAuthenticatedPage(Response $response): \Generator
//     {
//         echo "Página autenticada cargada. Extrayendo datos...\n";

//         // Aquí puedes empezar a extraer los datos de la página ya autenticada.
//         $title = $response->filter('title')->text();

//         echo "Scraped item: " . json_encode(['title' => $title]) . PHP_EOL;

//         yield $this->item([
//             'title' => $title,
//             // Otros datos que quieras extraer
//         ]);
//     }
// }
