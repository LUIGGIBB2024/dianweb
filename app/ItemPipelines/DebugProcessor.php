<?php

namespace App\ItemPipelines;

use Closure;
use Psr\Log\LoggerInterface;

class DebugProcessor
{
    public function __construct(protected LoggerInterface $logger) {}

    public function processItem(array $item, Closure $next): mixed
    {
        echo "Scraped item: " . json_encode($item) . PHP_EOL;
        $this->logger->info('Scraped item: ' . json_encode($item));

        return $next($item);
    }
}
