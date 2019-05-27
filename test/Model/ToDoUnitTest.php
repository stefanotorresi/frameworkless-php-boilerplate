<?php declare(strict_types=1);

namespace Acme\ToDo\Model;

use function Acme\ToDo\now;
use DateTimeImmutable;
use Lcobucci\Clock\FrozenClock;
use Lcobucci\Clock\SystemClock;
use PHPUnit\Framework\TestCase;

class ToDoUnitTest extends TestCase
{
    protected function tearDown(): void
    {
        now(new SystemClock());
    }

    public function testCreatedAt(): void
    {
        $createdAt = new DateTimeImmutable('@0');
        now(new FrozenClock($createdAt));
        $item = new ToDo('foo');

        assertEquals($createdAt, $item->getCreatedAt());
    }

    /**
     * @dataProvider typeSafeInvalidDataProvider
     */
    public function testValidation($name, $dueFor, $doneAt, array $invalidProperties): void
    {
        try {
            new ToDo($name, $dueFor, $doneAt);
        } catch (InvalidDataException $e) {
        }

        assertTrue(isset($e));
        assertEquals($invalidProperties, array_keys($e->getDetails()), 'Expected invalid properties don\'t match');
    }

    /**
     * @dataProvider invalidDataProvider
     */
    public function testValidationFromArray($name, $dueFor, $doneAt, array $invalidProperties): void
    {
        try {
            ToDo::createFromArray(compact('name', 'dueFor', 'doneAt'));
        } catch (InvalidDataException $e) {
        }

        assertTrue(isset($e));
        assertEquals($invalidProperties, array_keys($e->getDetails()), 'Expected invalid properties don\'t match');
    }

    /**
     * @dataProvider invalidDataProvider
     */
    public function testValidationOnUpdate($name, $dueFor, $doneAt, array $invalidProperties): void
    {
        $item = new ToDo('foo');

        try {
            $item->updateFromArray(compact('name', 'dueFor', 'doneAt'));
        } catch (InvalidDataException $e) {
        }

        assertTrue(isset($e));
        assertEquals($invalidProperties, array_keys($e->getDetails()), 'Expected invalid properties don\'t match');
    }

    public function invalidDataProvider(): array
    {
        return [
            [ '', '', '', [ 'name', 'dueFor', 'doneAt' ] ],
            [ '', $this->createDateTimeString(), $this->createDateTimeString(), [ 'name' ] ],
            [ 'foo', '', $this->createDateTimeString(), [ 'dueFor' ] ],
            [ 'foo', '1970-01-01', $this->createDateTimeString(), [ 'dueFor' ] ],
            [ 'foo', $this->createDateTimeString(), '', [ 'doneAt' ] ],
            [ 'foo', $this->createDateTimeString(), '1970-01-01', [ 'doneAt' ] ],
        ];
    }

    public function typeSafeInvalidDataProvider(): array
    {
        return [
            [ '', new DateTimeImmutable('@0'), new DateTimeImmutable('@0'), [ 'name'] ],
            [ ' ', new DateTimeImmutable('@0'), new DateTimeImmutable('@0'), [ 'name'] ],
        ];
    }

    public function testMarkDone(): void
    {
        $createdAt = new DateTimeImmutable('@0');
        now(new FrozenClock($createdAt));
        $item = new ToDo('foo');

        $doneAt = new DateTimeImmutable('@1');
        now(new FrozenClock($doneAt));
        $item->markDone();
        assertEquals($doneAt, $item->getDoneAt());
    }

    private function createDateTimeString(int $timestamp = 0): string
    {
        return (new DateTimeImmutable("@$timestamp"))->format(ToDo::DATE_FORMAT);
    }
}
