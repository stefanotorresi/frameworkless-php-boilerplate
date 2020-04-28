<?php declare(strict_types=1);

namespace Acme\ToDo\Http;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

interface RouteHandler
{
    /**
     * @param Request $request
     * @param string[] $args
     * @return Response
     */
    public function __invoke(Request $request, array $args): Response;
}
