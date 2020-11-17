<?php

declare(strict_types=1);

namespace RabbitCMS\Carrot\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Database\Eloquent\{Builder, Collection, Model};
use Illuminate\Foundation\Bus\{Dispatchable, PendingDispatch};
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\LazyCollection;
use RabbitCMS\Carrot\Contracts\QueryHandlerInterface;
use ReflectionClass;

/**
 * Class QueryJob
 * @method static PendingDispatch dispatch(Builder $builder, QueryHandlerInterface $handler, int $limit = 0)
 * @method static mixed dispatchNow(Builder $builder, QueryHandlerInterface $handler, int $limit = 0)
 */
final class QueryJob implements ShouldQueue
{
    use Dispatchable;
    use Queueable;

    private QueryHandlerInterface $handler;

    private string $query;

    private array $bindings;

    private int $limit;

    private string $model;

    public function __construct(Builder $builder, QueryHandlerInterface $handler, int $limit = 0)
    {
        $this->query = $builder->toSql();
        $this->bindings = $builder->getBindings();
        $this->handler = $handler;
        $this->limit = $limit;
        $this->model = get_class($builder->getModel());
        $this->queue = 'import';
    }

    /**
     * @return mixed
     */
    public function handle()
    {
        return $this->handler->handle($this);
    }

    public function each(callable $callable, array $with = [], int $chunkSize = 200): void
    {
        /** @var Model $model */
        $model = (new ReflectionClass($this->model))->newInstance();

        $model->getConnection()->transaction(function () use ($chunkSize, $with, $callable, $model) {
            LazyCollection::make($model->getConnection()->cursor($this->query, $this->bindings))
                ->when($this->limit, static fn(LazyCollection $collection, int $limit) => $collection
                    ->take($limit))
                ->map(fn($record) => $model
                    ->newFromBuilder($record))
                ->when(
                    $with,
                    fn(LazyCollection $collection, array $with) => $collection
                        ->chunk($chunkSize)
                        ->each(static fn(LazyCollection $collection, $key) => Collection::make($collection)
                            ->load($with)
                            ->every(static fn($model, $index) => $callable($model, $index) !== false)),
                    fn(LazyCollection $collection) => $collection
                        ->every(static fn($model, $index) => $callable($model, $index) !== false));
        });
    }

    public function displayName(): string
    {
        return get_class($this->handler);
    }

    public function tags(): array
    {
        return method_exists($this->handler, 'tags')
            ? (array) $this->handler->tags()
            : [];
    }
}
