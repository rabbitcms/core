<?php
declare(strict_types=1);

namespace RabbitCMS\Carrot\Contracts;

use Exception;

interface ImportInterface
{
    public function init(ImporterInterface $importer): void;

    public function row(array $row, ImporterInterface $importer): void;

    public function end(ImporterInterface $importer): void;

    public function catch(Exception $exception, ImporterInterface $importer): void;
}
