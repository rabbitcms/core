<?php

declare(strict_types=1);

namespace RabbitCMS\Carrot\Contracts;

use Closure;
use Psr\Log\LoggerInterface;
use Throwable;

interface ImporterInterface extends LoggerInterface
{
    public function head(bool $parse = false): array;

    public function next(): ?array;

    public function probe(Closure $condition): bool;

    public function setTranslator(Closure $closure): ImporterInterface;

    /**
     * @param  string  $message
     * @param  array  $context
     * @param  Throwable|null  $previous
     */
    public function error($message, array $context = [], Throwable $previous = null): void;
}
