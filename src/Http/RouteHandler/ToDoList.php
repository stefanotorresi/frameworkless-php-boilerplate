<?php declare(strict_types=1);

namespace Acme\ToDo\Http\RouteHandler;

use Acme\ToDo\Http\RouteHandler;
use Acme\ToDo\Model\ToDoDataMapper;
use League\Route\Http\Exception\BadRequestException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Laminas\Diactoros\Response\JsonResponse;

class ToDoList implements RouteHandler
{
    private const MAX_PAGE_SIZE = 100;

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
     */
    public function __invoke(Request $request, array $args): Response
    {
        $query  = $request->getQueryParams();
        $search = $query['search'] ?? '';

        $page = (int) ($query['page'] ?? 1);
        if ($page < 1) {
            throw new BadRequestException('Page must be greater than 0');
        }

        $pageSize = (int) ($query['pageSize'] ?? ToDoDataMapper::DEFAULT_PAGE_SIZE);

        if ($pageSize < 1 || $pageSize > self::MAX_PAGE_SIZE) {
            throw new BadRequestException(sprintf('Page size must be between 1 and %s', self::MAX_PAGE_SIZE));
        }

        $totalPages = $this->todoDataMapper->countPages($search, $pageSize);
        $items      = $this->todoDataMapper->getAll($search, $page, $pageSize);

        $prev = $page > 1 ? min($page - 1, $totalPages) : null;
        $next = $page < $totalPages ? $page + 1 : null;

        return new JsonResponse(compact('items', 'totalPages', 'prev', 'next'));
    }
}
