<?php
declare(strict_types=1);
namespace RabbitCMS\Carrot\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Class Grid2.
 */
abstract class Grid2
{
    /**
     * @return Eloquent
     */
    abstract public function getModel() :Eloquent;

    /**
     * @param Builder $query
     * @param array   $filters
     *
     * @return Builder
     */
    protected function filters(Builder $query, array $filters) :Builder
    {
        return $query;
    }

    /**
     * @param Eloquent $row
     *
     * @return array
     */
    protected function prepareRow(Eloquent $row) :array
    {
        return $row->attributesToArray();
    }

    /**
     * @param Request|null $request
     *
     * @return JsonResponse
     */
    public function response(Request $request = null) :JsonResponse
    {
        $request = $request ?: request();
        $total = $this->getModel()->newQuery()->count();

        $query = $this->getQuery((array)$request->input('filters', []), $this->getOrders($request));
        $count = $query->count();

        if ($request->input('length') > 0) {
            $query
                ->limit($request->input('length', 25))
                ->offset($request->input('start', 0));
        }

        $data = $query->get()->map(function (Eloquent $row) {
            return $this->prepareRow($row);
        });

        return new JsonResponse([
            'recordsTotal'    => $total,
            'recordsFiltered' => $count,
            'draw'            => $request->input('draw'),
            'data'            => $data,
        ], 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * @param array $filters
     * @param array $orders
     *
     * @return Builder
     */
    public function getQuery(array $filters = [], array $orders = []) :Builder
    {
        $query = $this->filters($this->getModel()->newQuery(), $filters);

        foreach ($orders as $order) {
            $query->orderBy(...$order);
        }

        return $query;
    }

    /**
     * @param Request $request
     *
     * @return array
     */
    public function getOrders(Request $request) :array
    {
        $result = [];

        $columns = $request->input('columns', []);
        foreach ((array)$request->input('order', []) as $order) {
            if (array_key_exists($order['column'], $columns) && !empty($columns[$order['column']]['name'])) {
                $result[] = [
                    $columns[$order['column']]['name'],
                    $order['dir']
                ];
            }
        }

        return $result;
    }
}
