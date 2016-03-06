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