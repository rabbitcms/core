<?php

if (!function_exists('asset_module')) {
    /**
     * Get assett for module
     *
     * @param string $asset
     * @param string $module [optional]
     *
     * @return string
     */
    function asset_module($asset, $module = '')
    {
        if ($module !== '') {
            $asset = "$module:$asset";
        }

        return \Module::asset($asset);
    }
}

if (! function_exists('relative_route')) {
    /**
     * Generate a relative URL to a named route.
     *
     * @param  string  $name
     * @param  array   $parameters
     * @param  \Illuminate\Routing\Route  $route
     * @return string
     */
    function relative_route($name, $parameters = [], $route = null)
    {
        return app('url')->route($name, $parameters, false, $route);
    }
}

if (! function_exists('html_link')) {
    /**
     * Generate a HTML link.
     *
     * @param string $url
     * @param string $title
     * @param array  $attributes
     *
     * @return \Illuminate\Support\HtmlString
     */
    function html_link($url, $title = null, $attributes = [])
    {
        if (is_null($title) || $title === false) {
            $title = $url;
        }

        $_attributes = "";
        foreach ($attributes as $key => $value) {
            $_attributes .= $key . '="' . e($value) . '"';
        }

        $html = '<a href="' . $url . '" ' . $_attributes . '>' . $title . '</a>';

        $result = new \Illuminate\Support\HtmlString($html);

        return $result->toHtml();
    }
}