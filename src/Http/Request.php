<?php

namespace RabbitCMS\Carrot\Http;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class Request
 */
abstract class Request extends FormRequest
{
    /**
     * Defaults values for request
     *
     * @var array
     */
    protected $defaults = [];

    /**
     * {@inheritdoc}
     */
    public function initialize(
        array $query = [],
        array $request = [],
        array $attributes = [],
        array $cookies = [],
        array $files = [],
        array $server = [],
        $content = null
    ) {
        parent::initialize(array_merge($this->defaults, $query), $request, $attributes, $cookies, $files, $server, $content);
    }

}