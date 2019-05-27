<?php declare(strict_types=1);

namespace Acme\ToDo\Config;

use PDO;

class PdoFactory
{
    private const DSN_SCHEMA = 'pgsql';

    public function __invoke(): PDO
    {
        $dsn = sprintf(
            '%s:host=%s;port=%d;dbname=%s',
            self::DSN_SCHEMA,
            env('DB_HOST'),
            env('DB_PORT'),
            env('DB_NAME')
        );

        $pdo = new PDO($dsn, env('DB_USER'), env('DB_PASSWORD'));
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        return $pdo;
    }
}
