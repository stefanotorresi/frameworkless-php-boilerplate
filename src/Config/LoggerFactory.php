<?php declare(strict_types = 1);

namespace Acme\ToDo\Config;

use Monolog\Formatter\LineFormatter as MonologLineFormatter;
use Monolog\Handler\StreamHandler as MonologStreamHandler;
use Monolog\Logger as Monolog;
use Psr\Log\LoggerInterface as Logger;

class LoggerFactory
{
    public function __invoke(): Logger
    {
        $logger    = new Monolog('');
        $formatter = new MonologLineFormatter("[%datetime%] %level_name%: %message%\n", 'd-M-Y H:i:s');
        $formatter->includeStacktraces();

        $streamHandler = new MonologStreamHandler('php://stderr', env('DEBUG') ? Monolog::DEBUG : Monolog::INFO);
        $streamHandler->setFormatter($formatter);
        $logger->pushHandler($streamHandler);

        return $logger;
    }
}
