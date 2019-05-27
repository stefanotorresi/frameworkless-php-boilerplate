<?php declare(strict_types=1);

namespace Acme\ToDo\Config;

use DI;
use Acme\ToDo\App;
use Acme\ToDo\Model\ToDoDataMapper;
use Middlewares\BasicAuthentication;
use Middlewares\ContentType;
use PDO;
use League\Route\Router;
use Psr\Container\ContainerInterface as Container;
use Psr\Log\LoggerInterface;

class DependencyInjection
{
    public function __invoke(): Container
    {
        $builder = new DI\ContainerBuilder();

        $builder->addDefinitions(
            [
                LoggerInterface::class => DI\factory('\Acme\ToDo\logger'),
                Router::class => DI\factory(RouterFactory::class),
                PDO::class => DI\factory(PdoFactory::class),
                ToDoDataMapper::class => DI\autowire()->lazy(),
                BasicAuthentication::class => DI\create()->constructor(
                    json_decode(env('AUTH_USERS'), true, 512, JSON_THROW_ON_ERROR)
                ),
                ContentType::class => DI\create()
                    ->constructor(
                        array_filter(
                            ContentType::getDefaultFormats(),
                            function ($key) {
                                return $key === 'json';
                            },
                            ARRAY_FILTER_USE_KEY
                        )
                    )
                    ->method('useDefault', false),
            ]
        );

        if (env('CACHE')) {
            $builder->enableCompilation(App::CACHE_DIR);
        }

        return $builder->build();
    }
}
