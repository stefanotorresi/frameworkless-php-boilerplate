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
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\ServerRequest;

class ToDoUpdateUnitTest extends TestCase
{
    /**
     * @var ToDoUpdate
     */
    private $SUT;

    /**
     * @var ToDoDataMapper & MockObject
     */
    private $recipeDataMapper;

    protected function setUp(): void
    {
        $this->recipeDataMapper = $this->createMock(ToDoDataMapper::class);
        $this->SUT = new ToDoUpdate($this->recipeDataMapper);
    }

    public function testSuccess(): void
    {
        $item = new ToDo('foo');
        $data = [
            'name' => 'bar',
            'dueFor' => (new DateTimeImmutable())->format(ToDo::DATE_FORMAT),
            'doneAt' => (new DateTimeImmutable())->format(ToDo::DATE_FORMAT),
        ];
        $request = (new ServerRequest())->withParsedBody($data);
        $args = ['id' => Uuid::NIL];

        $this->recipeDataMapper
            ->method('find')
            ->with($args['id'])
            ->willReturn($item)
        ;

        $this->recipeDataMapper
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

        $this->recipeDataMapper
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

        $this->recipeDataMapper
            ->method('find')
            ->with($args['id'])
            ->willReturn($item)
        ;

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('Invalid request body');

        $this->SUT->__invoke($request, $args);
    }
}
