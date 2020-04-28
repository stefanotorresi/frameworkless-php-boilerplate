<?php declare(strict_types = 1);

namespace Acme\ToDo\Http\RouteHandler;

use Acme\ToDo\Model\ToDo;
use Acme\ToDo\Model\ToDoDataMapper;
use DateTimeImmutable;
use League\Route\Http\Exception\BadRequestException;
use League\Route\Http\Exception\NotFoundException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\ServerRequest;
use const Acme\ToDo\DATE_FORMAT;

class ToDoUpdateUnitTest extends TestCase
{
    /**
     * @var ToDoUpdate
     */
    private $SUT;

    /**
     * @var ToDoDataMapper & MockObject
     */
    private $todoDataMapper;

    protected function setUp(): void
    {
        $this->todoDataMapper = $this->createMock(ToDoDataMapper::class);
        $this->SUT = new ToDoUpdate($this->todoDataMapper);
    }

    public function testSuccess(): void
    {
        $item = new ToDo('foo');
        $data = [
            'name' => 'bar',
            'dueFor' => (new DateTimeImmutable())->format(DATE_FORMAT),
            'doneAt' => (new DateTimeImmutable())->format(DATE_FORMAT),
        ];
        $request = (new ServerRequest())->withParsedBody($data);
        $args = ['id' => Uuid::NIL];

        $this->todoDataMapper
            ->method('find')
            ->with($args['id'])
            ->willReturn($item)
        ;

        $this->todoDataMapper
            ->expects(once())
            ->method('update')
            ->with($item)
        ;

        $response = $this->SUT->__invoke($request, $args);

        assertSame(200, $response->getStatusCode());
        assertInstanceOf(JsonResponse::class, $response); /** @var JsonResponse $response */
        assertEquals($item, $response->getPayload());
        assertSame($data['name'], $item->getName());
        assertSame($data['dueFor'], $item->getDueForAsString());
        assertSame($data['doneAt'], $item->getDoneAtAsString());
    }

    public function testInvalidUUID(): void
    {
        $request = new ServerRequest();
        $args = ['id' => 'foo'];

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('Invalid UUID');

        $this->SUT->__invoke($request, $args);
    }

    public function testNotFound(): void
    {
        $request = new ServerRequest();
        $args = ['id' => Uuid::NIL];

        $this->todoDataMapper
            ->method('find')
            ->with($args['id'])
            ->willReturn(null)
        ;

        $this->expectException(NotFoundException::class);

        $this->SUT->__invoke($request, $args);
    }

    public function testInvalidRequestBody(): void
    {
        $item = new ToDo('foo');
        $request = new ServerRequest();
        $args = ['id' => Uuid::NIL];

        $this->todoDataMapper
            ->method('find')
            ->with($args['id'])
            ->willReturn($item)
        ;

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('Invalid request body');

        $this->SUT->__invoke($request, $args);
    }
}
