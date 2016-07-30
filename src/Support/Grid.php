<?php

namespace RabbitCMS\Carrot\Support;

use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class Grid
{
    /**
     * @var array
     */
    protected $filters;

    /**
     * @var callable
     */
    protected $prepare;

    /**
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $model;

    /**
     * Grid constructor.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param callable|null                       $prepare
     * @param callable|null                       $filters
     */
    public function __construct(Eloquent $model, callable $prepare = null, callable $filters = null)
    {
        $this->model = $model;
        $this->filters = $filters;
        $this->prepare = $prepare;
    }

    /**
     * @param \Illuminate\Http\Request|null $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function response(Request $request = null)
    {
        $query = $this->model->newQuery();
        $request = $request ?: request();
        $total = $query->count();

        if ($this->filters !== null) {
            app()->call($this->filters, ['query' => $query, 'filters' => (array)$request->input('filters', [])]);
        }
        $count = $query->count();
        $columns = $request->input('columns', []);

        foreach ($request->input('order', []) as $order) {
            if (array_key_exists($order['column'], $columns)) {
                $query->orderBy($columns[$order['column']]['name'], $order['dir']);
            }
        }

        if ($request->input('length') > 0) {
            $query
                ->limit($request->input('length', 25))
                ->offset($request->input('start', 0));
        }

        $data = $query->get()->map(
            $this->prepare
                ?: function (Eloquent $row) {
                    return $row->attributesToArray();
                }
        );

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
}
