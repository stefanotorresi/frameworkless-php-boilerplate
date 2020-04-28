<?php declare(strict_types=1);

namespace Acme\ToDo;

use Acme\ToDo\Model\InvalidDataException;
use League\Route\Http\Exception as HttpException;
use League\Route\Router;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;
use Laminas\Diactoros\Response\JsonResponse;

class App implements ContainerInterface, RequestHandlerInterface
{
    public const CACHE_DIR = 'var/cache';

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var Router
     */
    private $router;

    public static function bootstrap(): self
    {
        $container = (new Config\DependencyInjection())();

        $app = $container->get(__CLASS__);
        $app->container = $container;

        return $app;
    }

    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    public function handle(Request $request): Response
    {
        try {
            $response = $this->router->dispatch($request);
        } catch (InvalidDataException $e) {
            return new JsonResponse([ 'error' => exception_to_array($e) ], 400);
        } catch (HttpException $e) {
            return new JsonResponse([ 'error' => exception_to_array($e) ], $e->getStatusCode());
        }

        return $response;
    }

    public function get($id)
    {
        return $this->container->get($id);
    }

    public function has($id)
    {
        return $this->container->has($id);
    }
}
