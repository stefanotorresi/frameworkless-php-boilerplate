<?php declare(strict_types=1);

namespace Acme\ToDo\Model;

use Assert\Assert;
use Closure;
use PDO;
use PDOStatement;
use RuntimeException;

class ToDoDataMapper
{
    public const DEFAULT_PAGE_SIZE = 20;

    /**
     * @var PDO
     */
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function insert(ToDo $item): void
    {
        $stmt = $this->pdo->prepare(
            <<<SQL
            INSERT INTO todos 
                ("id", "name", "createdAt", "dueFor", "doneAt", "searchVector") 
            VALUES (
                :id, 
                :name, 
                :createdAt, 
                :dueFor, 
                :doneAt, 
                to_tsvector(:searchVector)
            )
            SQL
        );

        $this->bindParams($item, $stmt);

        $result = $stmt->execute();

        if ($result === false) {
            throw new RuntimeException('PDO failed to execute a statement');
        }
    }

    public function update(ToDo $item): void
    {
        $stmt = $this->pdo->prepare(
            <<<SQL
            UPDATE "todos" 
            SET 
                "name"          = :name, 
                "createdAt"     = :createdAt, 
                "dueFor"        = :dueFor,
                "doneAt"        = :doneAt,
                "searchVector"  = to_tsvector(:searchVector)
            WHERE "id" = :id
            SQL
        );

        $this->bindParams($item, $stmt);

        $result = $stmt->execute();

        if ($result === false) {
            throw new RuntimeException('PDO failed to execute a statement');
        }
    }

    /**
     * @return ToDo[]
     */
    public function getAll(string $search = '', int $page = 1, int $pageSize = self::DEFAULT_PAGE_SIZE): array
    {
        Assert::that($page)->greaterThan(0);
        Assert::that($pageSize)->greaterThan(0);
        $isSearch = $search !== '';
        $where = $isSearch ? 'WHERE "searchVector" @@ plainto_tsquery(:search_query)' : '';

        $offset = ($page - 1) * $pageSize;
        $limit = $pageSize;

        $stmt = $this->pdo->prepare(
            <<<SQL
            SELECT 
                "id", 
                "name", 
                to_char("createdAt", 'YYYY-MM-DD"T"HH24:MI:SS.USOF') as "createdAt", 
                to_char("dueFor", 'YYYY-MM-DD"T"HH24:MI:SS.USOF') as "dueFor", 
                to_char("doneAt", 'YYYY-MM-DD"T"HH24:MI:SS.USOF') as "doneAt"
            FROM "todos" 
            {$where}
            ORDER BY "createdAt" LIMIT ${limit} OFFSET ${offset};
            SQL
        );

        if ($isSearch) {
            $stmt->bindValue('search_query', $search);
        }

        $result = $stmt->execute();

        if ($result === false) {
            throw new RuntimeException('PDO failed to execute a statement');
        }

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($rows === false) {
            throw new RuntimeException('PDO failed to fetch rows');
        }

        $toDoFactory = Closure::fromCallable([ $this, 'createToDoFromRow' ]);

        return array_map($toDoFactory, $rows);
    }

    public function countPages(string $search = '', int $pageSize = self::DEFAULT_PAGE_SIZE): int
    {
        Assert::that($pageSize)->greaterThan(0);

        $isSearch = $search !== '';
        $where = $isSearch ? 'WHERE "searchVector" @@ plainto_tsquery(:search_query)' : '';

        $stmt = $this->pdo->prepare(
            <<<SQL
            SELECT COUNT(*) FROM "todos" {$where}
            SQL
        );

        if ($isSearch) {
            $stmt->bindValue('search_query', $search);
        }

        $result = $stmt->execute();

        if ($result === false) {
            throw new RuntimeException('PDO failed to execute a statement');
        }

        $count = $stmt->fetchColumn();

        if ($count === false) {
            throw new RuntimeException('PDO failed to fetch a row');
        }

        if ($count <= $pageSize) {
            return 1;
        }

        return (int) ceil($count / $pageSize);
    }

    public function find(string $id): ?ToDo
    {
        $stmt = $this->pdo->prepare(
            <<<SQL
            SELECT 
                "id", 
                "name", 
                to_char("createdAt", 'YYYY-MM-DD"T"HH24:MI:SS.USOF') as "createdAt", 
                to_char("dueFor", 'YYYY-MM-DD"T"HH24:MI:SS.USOF') as "dueFor", 
                to_char("doneAt", 'YYYY-MM-DD"T"HH24:MI:SS.USOF') as "doneAt"
            FROM "todos" 
            WHERE "id" = :id;
            SQL
        );
        $result = $stmt->execute(compact('id'));

        if ($result === false) {
            throw new RuntimeException('PDO failed to execute a statement');
        }

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        return $this->createToDoFromRow($row);
    }

    public function delete(string $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM "todos" WHERE "id" = :id;');
        $result = $stmt->execute(compact('id'));

        if ($result === false) {
            throw new RuntimeException('PDO failed to execute a statement');
        }
    }

    public function initSchema(): void
    {
        $this->pdo->exec(static::getSchema());
    }

    public function dropSchema(): void
    {
        $this->pdo->exec(sprintf('DROP TABLE IF EXISTS "%s";', 'todos'));
    }

    /**
     * @param ToDo $item
     * @param PDOStatement<mixed> $stmt
     */
    private function bindParams(ToDo $item, PDOStatement $stmt): void
    {
        $stmt->bindValue('id', $item->getId());
        $stmt->bindValue('name', $item->getName());
        $stmt->bindValue('createdAt', $item->getCreatedAtAsString());
        $stmt->bindValue('dueFor', $item->getDoneAtAsString() ?: null);
        $stmt->bindValue('doneAt', $item->getDoneAtAsString() ?: null);
        $stmt->bindValue('searchVector', $item->getName());
    }

    private static function getSchema(): string
    {
        return <<<SQL
            CREATE TABLE IF NOT EXISTS "todos" (
                "id" UUID NOT NULL,
                "name" TEXT NOT NULL,
                "createdAt" TIMESTAMP WITH TIME ZONE NOT NULL,
                "dueFor" TIMESTAMP WITH TIME ZONE NULL,
                "doneAt" TIMESTAMP WITH TIME ZONE NULL,
                "searchVector" TSVECTOR NOT NULL,
                PRIMARY KEY (id)
            );
            CREATE INDEX "searchIdx" ON "todos" USING gin("searchVector");
            SQL
        ;
    }

    /**
     * @param string[] $row
     */
    private function createToDoFromRow(array $row): ToDo
    {
        return ToDo::createFromArray($row)->withId($row['id'])->withCreatedAt($row['createdAt']);
    }
}
