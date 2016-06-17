<?php

namespace RabbitCMS\Carrot\Repository;

use Illuminate\Contracts\Container\Container;

/**
 * Class BackendAcl.
 */
class BackendAcl
{
    /**
     * ACL resolvers.
     *
     * @var (string|callable)[]
     */
    protected $aclResolvers = [];

    /**
     * @var Container
     */
    protected $container;

    /**
     * Acl list.
     *
     * @var array
     */
    protected $acl = null;

    /**
     * Group list.
     *
     * @var array
     */
    protected $groups = [];

    /**
     * BackendAcl constructor.
     *
     * @param \Illuminate\Contracts\Container\Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Add acl resolver.
     *
     * @param callable|string $callback
     */
    public function addAclResolver($callback)
    {
        $this->aclResolvers[] = $callback;
    }

    /**
     * Add new group to ACL.
     *
     * @param string $group
     * @param string $label
     */
    public function addGroup($group, $label)
    {
        if (array_key_exists($group, $this->groups)) {
            throw  new \RuntimeException(sprintf('Acl group with name "%s" already exist.', $group));
        }
        $this->groups[$group] = $label;
    }

    /**
     * Add new ACL item to group.
     *
     * @param string $group
     * @param string $name
     * @param string $label
     */
    public function addAcl($group, $name, $label)
    {
        if (!array_key_exists($group, $this->groups)) {
            throw  new \RuntimeException(sprintf('Acl group with name "%s" not found.', $group));
        }
        $this->acl[$group . '.' . $name] = $label;
    }

    /**
     * Get acl groups.
     *
     * @return array
     */
    public function getGroups()
    {
        $this->getAll();

        return $this->groups;
    }

    /**
     * Get all acl lists.
     *
     * @return array
     */
    public function getAll()
    {
        if ($this->acl === null) {
            $this->acl = [];
            array_walk(
                $this->aclResolvers,
                function ($callback) {
                    $this->container->call($callback, [$this]);
                }
            );
        }

        return $this->acl;
    }

    /**
     * Get ACL rules for module or its section.
     *
     * @param string $module
     * @param string $section [optional]
     *
     * @return string[]
     * @deprecated since 0.1.8. Use getGroupPermissions.
     */
    public function getModulePermissions($module, $section = null)
    {
        return array_keys($this->getGroupPermissions($module, $section));
    }

    /**
     * Get ACL rules for module or its section.
     *
     * @param string $group
     * @param string $acl [optional]
     *
     * @return array
     */
    public function getGroupPermissions($group, $acl = null)
    {
        $rule = '/^' . preg_quote($group . '.' . ($acl ? $acl . '.' : '')) . '/';

        $result = [];

        foreach ($this->getAll() as $key => $value) {
            if (preg_match($rule, $key)) {
                $result[$key] = $value;
            }
        }

        return $result;
    }
}