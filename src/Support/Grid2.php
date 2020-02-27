<?php

declare(strict_types=1);

namespace RabbitCMS\Carrot\Support;

use Closure;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\{Builder, Collection, Model as Eloquent};

/**
 * Class Grid2.
 */
abstract class Grid2 implements Responsable
{
    /**
     * @var array
     */
    public static $handlers = [];

    /**
     * @var Builder
     */
    protected $query;

    /**
     * @var array[]
     */
    protected static $filters = [null => []];

    /**
     * @var array
     */
    protected $orderMap = [];

    /**
     * @param  callable  $handler
     */
    public static function addHandler(callable $handler)
    {
        self::$handlers[] = $handler;
    }

    /**
     * @param  string|null  $filter
     * @param  \Closure  $callback
     * @param  array  $params  [only,except,terminate]
     */
    public static function addFilter(
        ?string $filter,
        Closure $callback = null,
        array $params = []
    ) {
        if ($callback === null) {
            $callback = function (Builder $builder, $filter, $self, $name) {
                $builder->where($name, $filter);
            };
        }
        $params['uses'] = $callback;
        static::$filters[$filter][] = $params + ['except' => [''], 'terminate' => true];
    }

    /**
     * @param  Builder  $query
     *
     * @return Grid2
     */
    public function setQuery(Builder $query): Grid2
    {
        $this->query = $query;

        return $this;
    }

    /**
     * @return Eloquent
     */
    abstract public function getModel(): Eloquent;

    /**
     * @param  Builder  $query
     * @param  array  $filters
     *
     * @return Builder
     */
    protected function filters(Builder $query, array $filters): Builder
    {
        foreach (static::$filters[null] as $value) {
            call_user_func($value['uses'], $query, $filters, $this);
        }

        foreach (static::$filters as $name => $values) {
            if ($name === '' || ! array_key_exists($name, $filters)) {
                continue;
            }
            $filter = $filters[$name];
            foreach ($values as $value) {
                if ((is_array($value['only'] ?? null) && ! in_array($filter, $value['only'], true))
                    || (is_array($value['except'] ?? null) && in_array($filter, $value['except'], true))) {
                    continue;
                }

                call_user_func($value['uses'], $query, $filter, $this, $name, $filters);

                if ($value['terminate'] ?? null !== false) {
                    //terminate the filter
                    break;
                }
            }
        }

        return $query;
    }

    /**
     * @param  Eloquent  $row
     *
     * @return array
     */
    protected function prepareRow(Eloquent $row): array
    {
        return $row->attributesToArray();
    }

    /**
     * @return Builder
     */
    protected function createQuery(): Builder
    {
        $this->relations = [true => [], false => []];

        return $this->getModel()->newQuery();
    }

    protected function additional(Request $request, Builder $builder): array
    {
        return [];
    }

    /**
     * Add an "order by" clause to the query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder  $query
     * @param  string  $column
     * @param  string  $direction
     *
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder
     */
    protected function orderBy($query, $column, $direction = 'asc')
    {
        return $query->orderBy($column, $direction);
    }

    /**
     * @param  \Illuminate\Http\Request|null  $request
     * @return \Illuminate\Http\JsonResponse
     * @deprecated
     */
    public function response(Request $request = null): JsonResponse
    {
        return $this->toResponse($request ?: request());
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     * @return Builder
     */
    protected function getTotalQuery(Request $request): Builder
    {
        return $this->filters($this->createQuery(), (array) $request->input('prefilters', []));
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     * @return int
     */
    protected function getTotal(Request $request): int
    {
        return $this->getTotalQuery($request)->count();
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     * @return Builder
     */
    protected function getCountQuery(Request $request): Builder
    {
        return $this->getQuery($request);
    }
    /**
     * @param  \Illuminate\Http\Request  $request
     * @return int
     */
    protected function getCount(Request $request): int
    {
        return $this->getCountQuery($request)->count();
    }

    /**
     * @param  Request  $request
     *
     * @return JsonResponse
     */
    public function toResponse($request)
    {
        DB::enableQueryLog();
        $request = $request ?: request();

        $total = $this->getTotal($request);

        $count = $this->getCount($request);

        $query = $this->getQuery($request);

        $additional = $this->additional($request, clone $query);

        foreach ($this->getOrders($request) as $order) {
            $this->orderBy($query, ...$order);
        }

        if ($request->input('length') > 0) {
            $query
                ->limit($request->input('length', 25))
                ->offset($request->input('start', 0));
        }


        $data = $this->getResult($query)->map(function (Eloquent $row) {
            return $this->prepareRow($row);
        });
        DB::disableQueryLog();

        return new JsonResponse(array_merge($additional, [
            'recordsTotal' => $total,
            'recordsFiltered' => $count,
            'draw' => $request->input('draw'),
            'data' => $data,
            'query' => DB::getQueryLog(),
        ]), 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * @param  Builder  $builder
     *
     * @return Collection
     */
    protected function getResult(Builder $builder): Collection
    {
        return $builder->get();
    }

    /**
     * @param  Request  $request
     *
     * @return Builder
     */
    public function getQuery(Request $request): Builder
    {
        $query = $this->filters($this->query ?: $this->createQuery(), (array) $request->input('prefilters', []));
        $query = $this->filters($query, (array) $request->input('filters', []));

        $query->where(function (Builder $query) use ($request) {
            \array_map(function (callable $handler) use ($query, $request) {
                $handler($query, $request);
            }, self::$handlers);
        });

        return $query;
    }

    /**
     * @param  Request  $request
     * @param  array  $orders
     *
     * @return array
     */
    public function getOrders(Request $request, array $orders = []): array
    {
        $result = [];

        $columns = $request->input('columns', []);
        foreach ((array) $request->input('order', []) as $order) {
            if (array_key_exists($order['column'], $columns)) {
                $name = $columns[$order['column']]['name'] ?? $columns[$order['column']]['data'] ?? '';
                if (array_key_exists($name, $this->orderMap)) {
                    $name = $this->orderMap[$name];
                }
                if ($name) {
                    $result[] = [
                        $name,
                        $order['dir'],
                    ];
                }
            }
        }

        foreach ($orders as $order) {
            $result[] = $order;
        }

        return $result;
    }
}
