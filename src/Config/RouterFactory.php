<?php declare(strict_types=1);

namespace Acme\ToDo\Config;

use Assert\Assert;
use Acme\ToDo\Http\RouteHandler;
use League\Route\RouteGroup;
use League\Route\Router as LeagueRouter;
use League\Route\Strategy\ApplicationStrategy;
use Middlewares\BasicAuthentication;
use Middlewares\ContentType;
use Middlewares\JsonPayload;
use Psr\Container\ContainerInterface as Container;
use Psr\Http\Server\MiddlewareInterface as Middleware;

class RouterFactory
{
    public function __invoke(Container $container): LeagueRouter
    {
        $strategy = new ApplicationStrategy();
        $strategy->setContainer($container);
        $router   = new LeagueRouter();
        $router->setStrategy($strategy);

        $authMiddleware = $container->get(BasicAuthentication::class);
        $contentNegotiationMiddleware = $container->get(ContentType::class);

        Assert::thatAll([ $authMiddleware, $contentNegotiationMiddleware ])->isInstanceOf(Middleware::class);

        $router->middleware($contentNegotiationMiddleware);
        $router->middleware(new JsonPayload());

        $router->map('GET', '/', RouteHandler\Home::class);

        $router->group('/todos', function (RouteGroup $route) use ($authMiddleware): void {
            $route->map('GET', '/', RouteHandler\ToDoList::class);
            $route->map('POST', '/', RouteHandler\ToDoCreate::class)->middleware($authMiddleware);
            $route->map('GET', '/{id}', RouteHandler\ToDoRead::class);
            $route->map('PUT', '/{id}', RouteHandler\ToDoUpdate::class)->middleware($authMiddleware);
            $route->map('PATCH', '/{id}', RouteHandler\ToDoUpdate::class)->middleware($authMiddleware);
            $route->map('DELETE', '/{id}', RouteHandler\ToDoDelete::class)->middleware($authMiddleware);
        });

        return $router;
    }
}
