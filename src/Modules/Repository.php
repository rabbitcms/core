<?php

namespace RabbitCMS\Carrot\Modules;

use ArrayIterator;
use Countable;
use Illuminate\Contracts\Support\Arrayable;
use IteratorAggregate;
use JsonSerializable;
use RuntimeException;

class Repository implements IteratorAggregate, Countable, Arrayable, JsonSerializable
{
    /**
     * @var Module[]
     */
    protected $modules = [];

    /**
     * Get modules count.
     *
     * @return int
     */
    public function count()
    {
        return count($this->modules);
    }

    /**
     * Get modules iterator.
     *
     * @return ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->modules);
    }

    /**
     * Checks if module exists.
     *
     * @param string $name
     *
     * @return bool
     */
    public function has($name)
    {
        return array_key_exists($name, $this->modules);
    }

    /**
     * Get module by name.
     *
     * @param string $name
     *
     * @return Module
     * @throws RuntimeException
     */
    public function get($name)
    {
        if (array_key_exists($name, $this->modules)) {
            return $this->modules[$name];
        }
        throw new RuntimeException("Module '$name' not found.");
    }

    /**
     * Run function for each module.
     *
     * @param callable $callback
     */
    public function each(callable $callback)
    {
        foreach ($this->modules as $name => $module) {
            $callback($module, $name);
        }
    }

    /**
     * Filter modules.
     *
     * @param callable $callback
     *
     * @return Repository
     */
    public function filter(callable $callback)
    {
        $result = new Repository();
        foreach ($this->modules as $name => $module) {
            if ($callback($module, $name)) {
                $result->add($module);
            }
        }

        return $result;
    }

    /**
     * Add module to repository.
     *
     * @param Module[] ...$modules
     */
    public function add(Module ...$modules)
    {
        foreach ($modules as $module) {
            $this->modules[$module->getName()] = $module;
        }
    }

    /**
     * @inheritdoc
     */
    function toArray()
    {
        return $this->map(
            function (Module $module) {
                return $module->toArray();
            }
        );
    }

    /**
     * Map modules.
     *
     * @param callable $callback
     *
     * @return array
     */
    public function map(callable $callback):array
    {
        $result = [];
        foreach ($this->modules as $name => $module) {
            $result[$name] = $callback($module, $name);
        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function jsonSerialize()
    {
        return $this->all();
    }

    /**
     * Get all module.
     *
     * @return Module[]
     */
    public function all()
    {
        return $this->modules;
    }
}