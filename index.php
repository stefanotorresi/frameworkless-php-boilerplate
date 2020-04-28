<?php declare(strict_types=1);

namespace Acme\ToDo;

use Throwable;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\ServerRequestFactory;
use Laminas\HttpHandlerRunner\Emitter\SapiStreamEmitter;

(static function (): void {
    require __DIR__ . '/vendor/autoload.php';

    set_error_handler('\Acme\ToDo\php_error_handler');

    try {
        $app = App::bootstrap();

        $request = ServerRequestFactory::fromGlobals();
        $response = $app->handle($request);
    } catch (Throwable $e) {
        logger()->error($e, ['exception' => $e, 'request' => $request ?? null]);
        $response = new JsonResponse([ 'error' => exception_to_array($e) ], 500);
    }

    (new SapiStreamEmitter())->emit($response);
})();
