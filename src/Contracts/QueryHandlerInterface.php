<?php
declare(strict_types=1);

namespace RabbitCMS\Carrot\Contracts;

use RabbitCMS\Carrot\Jobs\QueryJob;

interface QueryHandlerInterface
{
    /**
     * @param  QueryJob  $job
     * @return mixed
     */
    public function handle(QueryJob $job);
}
