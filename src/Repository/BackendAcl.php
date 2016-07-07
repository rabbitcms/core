<?php

namespace RabbitCMS\Carrot\Repository;

use RabbitCMS\Backend\Support\Backend;

/**
 * Class BackendAcl.
 * @deprecated
 */
class BackendAcl
{
    /**
     * @var Backend
     */
    protected $backend;

    /**
     * BackendAcl constructor.
     *
     * @param Backend $backend
     */
    public function __construct(Backend $backend)
    {
        $this->backend = $backend;
    }

    /**
     * Add acl resolver.
     *
     * @param callable|string $callback
     */
    public function addAclResolver($callback)
    {
        $this->backend->addAclResolver($callback);
    }

    /**
     * Add new group to ACL.
     *
     * @param string $group
     * @param string $label
     */
    public function addGroup($group, $label)
    {
        $this->backend->addAclGroup($group, $label);
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
        $this->backend->addAcl($group, $name, $label);
    }

    /**
     * Get acl groups.
     *
     * @return array
     */
    public function getGroups()
    {
        return $this->backend->getAclGroups();
    }

    /**
     * Get all acl lists.
     *
     * @return array
     */
    public function getAll()
    {
        return $this->backend->getAllAcl();
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
        return $this->backend->getGroupPermissions($group, $acl);
    }
}