<?php declare(strict_types=1);

namespace Acme\ToDo\Http\RouteHandler;

use Acme\ToDo\Http\RouteHandler;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Zend\Diactoros\Response\JsonResponse;

class Home implements RouteHandler
{
    public function __invoke(Request $request, array $args): Response
    {
        return new JsonResponse([
            'links' => [
                'todos' => $request->getUri() . 'todos',
                'docs' => $request->getUri() . 'docs',
            ],
        ]);
    }
}
