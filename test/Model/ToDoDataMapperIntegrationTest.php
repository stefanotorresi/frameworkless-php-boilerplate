<?php declare(strict_types=1);

namespace Acme\ToDo\Model;

use Acme\ToDo\App;
use PHPUnit\Framework\TestCase;

class ToDoDataMapperIntegrationTest extends TestCase
{
    /**
     * @var ToDoDataMapper
     */
    private $SUT;

    protected function setUp(): void
    {
        $app = App::bootstrap();
        $this->SUT = $app->get(ToDoDataMapper::class);
        $this->SUT->dropSchema();
        $this->SUT->initSchema();
    }

    public function testAddAndFind(): void
    {
        $item = new ToDo('foo');

        assertNull($this->SUT->find($item->getId()));

        $this->SUT->insert($item);

        assertEquals($item, $this->SUT->find($item->getId()));
    }

    public function testAddAndGetall(): void
    {
        $item1 = new ToDo('foo');
        $item2 = new ToDo('bar');

        $this->SUT->insert($item1);
        $this->SUT->insert($item2);

        $items = $this->SUT->getAll();
        assertEquals([ $item1, $item2 ], $items);
    }

    public function testAddUpdateAndFind(): void
    {
        $item = new ToDo('foo');

        $this->SUT->insert($item);

        $item->updateFromArray([
            'name' => 'bar',
            'prep_time_mins' => 6,
            'difficulty' => 2,
            'vegetarian' => true
        ]);

        $this->SUT->update($item);

        assertEquals($item, $this->SUT->find($item->getId()));
    }

    public function testAddAndDelete(): void
    {
        $item = new ToDo('foo');

        $this->SUT->insert($item);

        assertEquals($item, $this->SUT->find($item->getId()));

        $this->SUT->delete($item->getId());

        assertNull($this->SUT->find($item->getId()));
    }

    public function testFullTextSearch(): void
    {
        $item1 = new ToDo('foo');
        $item2 = new ToDo('bar');
        $item3 = new ToDo('bar baz');

        $this->SUT->insert($item1);
        $this->SUT->insert($item2);
        $this->SUT->insert($item3);

        assertEquals([$item1], $this->SUT->getAll('foo'));
        assertEquals([$item2, $item3], $this->SUT->getAll('bar'));
        assertEquals([$item3], $this->SUT->getAll('baz'));
    }

    public function testPagination(): void
    {
        $item1 = new ToDo('foo');
        $item2 = new ToDo('bar');

        $this->SUT->insert($item1);
        $this->SUT->insert($item2);

        assertEquals([$item1, $item2], $this->SUT->getAll());
        assertEquals([$item2], $this->SUT->getAll('', $page=2, $pageSize=1));
        assertEquals([], $this->SUT->getAll('', $page=2));
    }

    public function testCountPages(): void
    {
        $item1 = new ToDo('foo');
        $item2 = new ToDo('bar');
        $item3 = new ToDo('bar baz');
        $item4 = new ToDo('bat');

        $this->SUT->insert($item1);
        $this->SUT->insert($item2);
        $this->SUT->insert($item3);
        $this->SUT->insert($item4);

        assertSame(1, $this->SUT->countPages());
        assertSame(2, $this->SUT->countPages('bar', $pageSize=1));
        assertSame(2, $this->SUT->countPages('', $pageSize=2));
    }
}
