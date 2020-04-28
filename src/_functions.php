<?php declare(strict_types=1);

namespace Acme\ToDo;

use Assert\Assert;
use DateTimeImmutable;
use ErrorException;
use Acme\ToDo\Model\InvalidDataException;
use Lcobucci\Clock\Clock;
use Lcobucci\Clock\SystemClock;
use Psr\Log\LoggerInterface as Logger;
use Throwable;

/**
 * @return mixed[]
 */
function exception_to_array(Throwable $exception): array
{
    $singleToArray = function (Throwable $exception) {
        $output = [
            'message' => $exception->getMessage(),
            'code' => $exception->getCode(),
        ];

        if ($exception instanceof InvalidDataException) {
            $output['details'] = $exception->getDetails();
        }

        if (env('DEBUG') === true) {
            $output = array_merge($output, [
                'type' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => explode("\n", $exception->getTraceAsString()),
                'previous' => [],
            ]);
        }

        return $output;
    };

    $result = $singleToArray($exception);
    $last = $exception;

    while ($last = $last->getPrevious()) {
        $result['previous'][] = $singleToArray($last);
    }

    return $result;
}

function php_error_handler(int $errno, string $errstr, string $errfile, int $errline): void
{
    if (! (error_reporting() & $errno)) {
        return; // error_reporting does not include this error
    }

    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
}

function logger(Logger $newLogger = null): Logger
{
    static $logger;

    if ($newLogger) {
        $logger = $newLogger;
    }

    if (! $logger) {
        $logger = (new Config\LoggerFactory())();
    }

    return $logger;
}

function now(Clock $newClock = null): DateTimeImmutable
{
    static $clock;

    if ($newClock) {
        $clock = $newClock;
    }

    if (! $clock) {
        $clock = new SystemClock();
    }

    return $clock->now();
}

function datetime_from_string(string $dateTime): DateTimeImmutable
{
    $dateTime = DateTimeImmutable::createFromFormat(DATE_FORMAT, $dateTime);

    Assert::that($dateTime)->notSame(false);

    return $dateTime;
}

const DATE_FORMAT = "Y-m-d\TH:i:s.uO"; // ISO8601 with milliseconds