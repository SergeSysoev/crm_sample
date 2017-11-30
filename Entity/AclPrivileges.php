<?php

namespace NaxCrmBundle\Entity;

use Doctrine\ORM\Mapping as ORM;


/**
 * AclPrivileges
 *
 * @ORM\Table(name="acl_privileges")
 * @ORM\Entity
 */
class AclPrivileges extends BaseEntity
{
    /**
     * @var AclResources
     *
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="NaxCrmBundle\Entity\AclResources")
     * @ORM\JoinColumn(name="resource_id", referencedColumnName="id")
     */
    protected $resource;

    /**
     * @var AclRoles
     *
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="NaxCrmBundle\Entity\AclRoles", inversedBy="privileges")
     * @ORM\JoinColumn(name="role_id", referencedColumnName="id")
     */
    protected $role;

    /**
     * @var string
     *
     * @ORM\Column(name="privileges", type="string", length=6, options={"fixed" = true})
     */
    protected $privileges = '';

    /**
     * @return AclResources
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * @param int $resource
     * @return AclPrivileges
     */
    public function setResource($resource)
    {
        $this->resource = $resource;
        return $this;
    }

    /**
     * @return AclRoles
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * @param AclRoles $role
     * @return AclPrivileges
     */
    public function setRole(AclRoles $role)
    {
        $this->role = $role;
        return $this;
    }

    /**
     * Set privileges
     *
     * @param string $privileges
     *
     * @return AclPrivileges
     */
    public function setPrivileges($privileges)
    {
        $this->privileges = $privileges;

        return $this;
    }

    /**
     * Get privileges
     *
     * @return string
     */
    public function getPrivileges()
    {
        return $this->privileges;
    }

    public function export()
    {
        return [
            'role' => $this->getRole()->getName(),
            'resource' => $this->getResource()->getName(),
            'privileges' => $this->getPrivileges(),
        ];
    }
}

