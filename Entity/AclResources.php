<?php

namespace NaxCrmBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * AclResources
 *
 * @ORM\Table(name="acl_resources")
 * @ORM\Entity
 */
class AclResources extends BaseEntity
{
    public static $permissionTitles = [
        'C' => 'Create',
        'R' => 'Read',
        'U' => 'Update',
        'D' => 'Delete',
        'I' => 'Import',
        'E' => 'Export',
    ];
    public static $resourceNames = [
        'manager' => 'Managers',
        'department' => 'Departments',
        'deposit' => 'Deposits',
        'dashboard' => 'Analytics dashboard',
        'lead' => 'Leads',
        'client' => 'Clients',
        'document' => 'Documents',
        'compliance' => 'Compliance report',
        'role' => 'Roles',
        'setting' => 'Setting',
        'withdrawal' => 'Withdrawals',
        'syslog' => 'Syslog',
        'userlog' => 'Logs',
        'lead_deleted' => 'Deleted Leads',
        'email_template' => 'Email template',
        'follow_up' => 'Follow-ups',
    ];

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, unique=true)
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="string", length=255, options={"default" : ""})
     */
    protected $description;

    /**
     * @var string
     *
     * @ORM\Column(name="default_privileges", type="string", length=6)
     */
    protected $defaultPrivileges;

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return AclResources
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set description
     *
     * @param string $description
     *
     * @return AclResources
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set defaultPrivileges
     *
     * @param string $defaultPrivileges
     *
     * @return AclResources
     */
    public function setDefaultPrivileges($defaultPrivileges)
    {
        $this->defaultPrivileges = $defaultPrivileges;

        return $this;
    }

    /**
     * Get defaultPrivileges
     *
     * @return string
     */
    public function getDefaultPrivileges()
    {
        return $this->defaultPrivileges;
    }
}

