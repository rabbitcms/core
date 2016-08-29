<?php

namespace RabbitCMS\Carrot\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

abstract class Grid2
{
    abstract public function getModel() :Eloquent;

    protected function filters(Builder $query, array $filters) :Builder
    {
        return $query;
    }

    protected function prepareRow(Eloquent $row) :array
    {
        return $row->attributesToArray();
    }

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

        return new JsonResponse(
            [
                'recordsTotal'    => $total,
                'recordsFiltered' => $count,
                'draw'            => $request->input('draw'),
                'data'            => $data,
            ],
            200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
    }

    public function getQuery(array $filters = [], array $orders = []) :Builder
    {
        $query = $this->filters($this->getModel()->newQuery(), $filters);

        foreach ($orders as $order) {
            $query->orderBy(...$order);
        }

        return $query;
    }

    public function getOrders(Request $request) :array
    {
        $result = [];

        $columns = $request->input('columns', []);
        foreach ((array)$request->input('order', []) as $order) {
            if (array_key_exists($order['column'], $columns)) {
                $result[] = [
                    $columns[$order['column']]['name'],
                    $order['dir']
                ];
            }
        }

        return $result;
    }
}
