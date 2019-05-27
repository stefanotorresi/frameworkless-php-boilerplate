<?php declare(strict_types=1);

namespace Acme\ToDo\Http\RouteHandler;

use League\Route\Http;
use Acme\ToDo\Http\RouteHandler;
use Acme\ToDo\Model\ToDoDataMapper;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Ramsey\Uuid\Uuid;
use Zend\Diactoros\Response\JsonResponse;

class ToDoRead implements RouteHandler
{
    /**
     * @var ToDoDataMapper
     */
    private $recipeDataMapper;

    public function __construct(ToDoDataMapper $recipeDataMapper)
    {
        $this->recipeDataMapper = $recipeDataMapper;
    }

    /**
     * @throws Http\Exception\BadRequestException
     * @throws Http\Exception\NotFoundException
     */
    public function __invoke(Request $request, array $args): Response
    {
        if (! Uuid::isValid($args['id'])) {
            throw new Http\Exception\BadRequestException('Invalid UUID');
        }

        $item = $this->recipeDataMapper->find($args['id']);

        if ($item === null) {
            throw new Http\Exception\NotFoundException('Resource not found');
        }

        return new JsonResponse($item);
    }
}
