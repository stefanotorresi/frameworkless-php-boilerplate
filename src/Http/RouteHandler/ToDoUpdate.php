<?php declare(strict_types=1);

namespace Acme\ToDo\Http\RouteHandler;

use Acme\ToDo\Http\RouteHandler;
use Acme\ToDo\Model\InvalidDataException;
use Acme\ToDo\Model\ToDoDataMapper;
use League\Route\Http\Exception\BadRequestException;
use League\Route\Http\Exception\NotFoundException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Ramsey\Uuid\Uuid;
use Zend\Diactoros\Response\JsonResponse;

class ToDoUpdate implements RouteHandler
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
     * @throws BadRequestException
     * @throws InvalidDataException
     * @throws NotFoundException
     */
    public function __invoke(Request $request, array $args): Response
    {
        if (! Uuid::isValid($args['id'])) {
            throw new BadRequestException('Invalid UUID');
        }

        $item = $this->recipeDataMapper->find($args['id']);

        if ($item === null) {
            throw new NotFoundException('Resource not found');
        }

        $requestBody = $request->getParsedBody();

        if (! is_array($requestBody)) {
            throw new BadRequestException('Invalid request body');
        }

        $item->updateFromArray($requestBody);

        $this->recipeDataMapper->update($item);

        return new JsonResponse($item, 200);
    }
}
