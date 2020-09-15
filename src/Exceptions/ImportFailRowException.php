<?php
declare(strict_types=1);

namespace RabbitCMS\Carrot\Exceptions;

use Exception;
use Throwable;

class ImportFailRowException extends Exception
{
    private array $context;

    public function __construct(string $message, array $context, Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
        $this->context = $context;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
