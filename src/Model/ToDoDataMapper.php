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

    private const TABLE_NAME = 'todos';

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
        $tableName = static::TABLE_NAME;

        $stmt = $this->pdo->prepare(
            <<<SQL
            INSERT INTO "$tableName" 
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
        $tableName = static::TABLE_NAME;

        $stmt = $this->pdo->prepare(
            <<<SQL
            UPDATE "$tableName" 
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
        $tableName = static::TABLE_NAME;

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
            FROM "$tableName" 
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

        $tableName = static::TABLE_NAME;

        $isSearch = $search !== '';
        $where = $isSearch ? 'WHERE "searchVector" @@ plainto_tsquery(:search_query)' : '';

        $stmt = $this->pdo->prepare(
            <<<SQL
            SELECT COUNT(*) FROM "$tableName" {$where}
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
        $tableName = static::TABLE_NAME;

        $stmt = $this->pdo->prepare(
            <<<SQL
            SELECT 
                "id", 
                "name", 
                to_char("createdAt", 'YYYY-MM-DD"T"HH24:MI:SS.USOF') as "createdAt", 
                to_char("dueFor", 'YYYY-MM-DD"T"HH24:MI:SS.USOF') as "dueFor", 
                to_char("doneAt", 'YYYY-MM-DD"T"HH24:MI:SS.USOF') as "doneAt"
            FROM "$tableName" 
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
        $stmt = $this->pdo->prepare(sprintf('DELETE FROM "%s" WHERE "id" = :id;', static::TABLE_NAME));
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
        $this->pdo->exec(sprintf('DROP TABLE IF EXISTS "%s";', static::TABLE_NAME));
    }

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
        $tableName = static::TABLE_NAME;

        return <<<SQL
            CREATE TABLE IF NOT EXISTS "$tableName" (
                "id" UUID NOT NULL,
                "name" TEXT NOT NULL,
                "createdAt" TIMESTAMP WITH TIME ZONE NOT NULL,
                "dueFor" TIMESTAMP WITH TIME ZONE NULL,
                "doneAt" TIMESTAMP WITH TIME ZONE NULL,
                "searchVector" TSVECTOR NOT NULL,
                PRIMARY KEY (id)
            );
            CREATE INDEX "searchIdx" ON "$tableName" USING gin("searchVector");
            SQL
        ;
    }

    private function createToDoFromRow(array $row): ToDo
    {
        return ToDo::createFromArray($row)->withId($row['id'])->withCreatedAt($row['createdAt']);
    }
}
