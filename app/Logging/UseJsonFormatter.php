<?php

namespace App\Logging;

use Monolog\Formatter\JsonFormatter;
use Illuminate\Log\Logger;

class UseJsonFormatter
{
    public function __invoke(Logger $logger): void
    {
        foreach ($logger->getLogger()->getHandlers() as $handler) {
            $handler->setFormatter(new JsonFormatter());
        }
    }
}
