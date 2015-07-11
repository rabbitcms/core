<?php namespace RabbitCMS\Carrot\Support;

/**
 * Trait Permissions
 *
 * @package RabbitCMS\Support
 * @Annotation
 * @Target({"METHOD","CLASS"})
 * @Attributes({
 *   @Attribute("permissions", type = "array", required=true),
 * })
 */
final class Permissions
{

    /**
     * @var array
     */
    public $permissions;
    /**
     * @var bool
     */
    public $all = true;
}