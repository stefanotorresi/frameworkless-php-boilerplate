<?php declare(strict_types = 1);

namespace Acme\ToDo\Http\RouteHandler;

use Acme\ToDo\Model\ToDo;
use Acme\ToDo\Model\ToDoDataMapper;
use League\Route\Http\Exception\BadRequestException;
use League\Route\Http\Exception\NotFoundException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\ServerRequest;

class ToDoReadUnitTest extends TestCase
{
    /**
     * @var ToDoRead
     */
    private $SUT;

    /**
     * @var ToDoDataMapper & MockObject
     */
    private $todoDataMapper;

    protected function setUp(): void
    {
        $this->todoDataMapper = $this->createMock(ToDoDataMapper::class);
        $this->SUT = new ToDoRead($this->todoDataMapper);
    }

    public function testSuccess(): void
    {
        $item = new ToDo('foo');
        $request = new ServerRequest();
        $args = ['id' => Uuid::NIL];

        $this->todoDataMapper
            ->method('find')
            ->with($args['id'])
            ->willReturn($item)
        ;

        $response = $this->SUT->__invoke($request, $args);

        assertSame(200, $response->getStatusCode());
        assertInstanceOf(JsonResponse::class, $response); /** @var JsonResponse $response */
        assertEquals($item, $response->getPayload());
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
}
