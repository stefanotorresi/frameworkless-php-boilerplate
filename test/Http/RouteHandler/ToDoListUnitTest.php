<?php declare(strict_types = 1);

namespace Acme\ToDo\Http\RouteHandler;

use Acme\ToDo\Model\ToDo;
use Acme\ToDo\Model\ToDoDataMapper;
use League\Route\Http\Exception\BadRequestException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\ServerRequest;

class ToDoListUnitTest extends TestCase
{
    /**
     * @var ToDoList
     */
    private $SUT;

    /**
     * @var ToDoDataMapper & MockObject
     */
    private $todoDataMapper;

    protected function setUp(): void
    {
        $this->todoDataMapper = $this->createMock(ToDoDataMapper::class);
        $this->SUT = new ToDoList($this->todoDataMapper);
    }

    public function testSuccess(): void
    {
        $item1 = new ToDo('foo');
        $item2 = new ToDo('bar');
        $request = new ServerRequest();

        $records = [ $item1, $item2 ];

        $this->todoDataMapper
            ->expects(once())
            ->method('getAll')
            ->willReturn($records)
        ;

        $response = $this->SUT->__invoke($request, []);

        assertSame(200, $response->getStatusCode());
        assertInstanceOf(JsonResponse::class, $response); /** @var JsonResponse $response */
        $payload = $response->getPayload();
        assertArrayHasKey('items', $payload);
        assertEquals($records, $payload['items']);
    }

    public function testPagination(): void
    {
        $query   = [
            'search' => 'foo',
            'page' => 2,
            'pageSize' => 10,
        ];
        $request = (new ServerRequest())->withQueryParams($query);

        $this->todoDataMapper
            ->expects(once())
            ->method('getAll')
            ->with($query['search'], $query['page'], $query['pageSize'])
        ;

        $this->todoDataMapper
            ->expects(once())
            ->method('countPages')
            ->with($query['search'], $query['pageSize'])
            ->willReturn(3)
        ;

        $response = $this->SUT->__invoke($request, []);
        assertSame(200, $response->getStatusCode());
        assertInstanceOf(JsonResponse::class, $response); /** @var JsonResponse $response */
        $payload = $response->getPayload();
        assertArrayHasKey('prev', $payload);
        assertArrayHasKey('next', $payload);
        assertArrayHasKey('totalPages', $payload);
        assertSame(1, $payload['prev']);
        assertSame(3, $payload['next']);
        assertSame(3, $payload['totalPages']);
    }

    public function testInvalidPageNumber(): void
    {
        $request = (new ServerRequest())->withQueryParams([ 'page' => 0 ]);

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('Page must be greater than 0');

        $this->SUT->__invoke($request, []);
    }

    public function testInvalidPageSize(): void
    {
        $request = (new ServerRequest())->withQueryParams([ 'pageSize' => 0 ]);

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('Page size must be between 1 and 100');

        $this->SUT->__invoke($request, []);
    }
}
