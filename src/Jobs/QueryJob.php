<?php
declare(strict_types=1);

namespace RabbitCMS\Carrot\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Database\Eloquent\{Builder, Collection};
use Illuminate\Foundation\Bus\{Dispatchable, PendingDispatch};
use RabbitCMS\Carrot\Contracts\QueryHandlerInterface;
use ReflectionClass;
use ReflectionException;

/**
 * Class QueryJob
 * @method static PendingDispatch dispatch(Builder $builder, QueryHandlerInterface $handler, int $limit = 0)
 * @method static mixed dispatchNow(Builder $builder, QueryHandlerInterface $handler, int $limit = 0)
 */
final class QueryJob
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
        $query = $builder->applyScopes();
        $this->query = $query->toSql();
        $this->bindings = $query->getBindings();
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

    /**
     * @param  callable  $callable
     * @param  array  $with
     * @return mixed
     * @throws ReflectionException
     */
    public function each(callable $callable, array $with = [])
    {
        $model = (new ReflectionClass($this->model))->newInstance();

        return $model->getConnection()->transaction(function () use ($with, $callable, $model) {
            $index = 0;
            $chunk = 0;
            $collection = new Collection();
            foreach ($model->getConnection()->cursor($this->query, $this->bindings) as $record) {
                $collection->push($model->newFromBuilder($record));
                $chunk++;
                $index++;
                if ($chunk > 200) {
                    if (! $this->process($collection, $callable, $with, $index - $collection->count())) {
                        break;
                    }
                    $collection = new Collection();
                    $chunk = 0;
                }

                if ($this->limit && $index >= $this->limit) {
                    break;
                }
            }

            if ($collection->count()) {
                $this->process($collection, $callable, $with, $index - $collection->count());
            }

            return $index;
        });
    }

    protected function process(Collection $collection, callable $callable, array $with, int $shift): bool
    {
        $collection->load($with);

        foreach ($collection as $index => $model) {
            if ($callable($model, $index + $shift + 1) === false) {
                return false;
            }
        }

        return true;
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
