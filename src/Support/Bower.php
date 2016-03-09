<?php
/**
 * Created by PhpStorm.
 * User: lnkvisitor
 * Date: 09.03.16
 * Time: 01:21
 */

namespace RabbitCMS\Carrot\Support;


class Bower
{
    protected $packages = [];

    public function define($package, array $deps = [], $main, array $css = [])
    {
        $this->packages[$package] = [
            'deps' => $deps,
            'main' => $main,
            'css'  => $css,
        ];
    }
}