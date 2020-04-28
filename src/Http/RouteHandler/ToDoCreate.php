<?php declare(strict_types=1);

namespace Acme\ToDo\Http\RouteHandler;

use Acme\ToDo\Http\RouteHandler;
use Acme\ToDo\Model\InvalidDataException;
use Acme\ToDo\Model\ToDo;
use Acme\ToDo\Model\ToDoDataMapper;
use League\Route\Http\Exception\BadRequestException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Laminas\Diactoros\Response\JsonResponse;

class ToDoCreate implements RouteHandler
{
    /**
     * @var ToDoDataMapper
     */
    private $todoDataMapper;

    public function __construct(ToDoDataMapper $todoDataMapper)
    {
        $this->todoDataMapper = $todoDataMapper;
    }

    /**
     * @param Request $request
     * @param string[] $args
     *
     * @return Response
     *
     * @throws BadRequestException
     * @throws InvalidDataException
     */
    public function __invoke(Request $request, array $args): Response
    {
        $requestBody = $request->getParsedBody();

        if (! is_array($requestBody)) {
            throw new BadRequestException('Invalid request body');
        }

        $item = ToDo::createFromArray($requestBody);

        $this->todoDataMapper->insert($item);

        return new JsonResponse($item, 201);
    }
}
