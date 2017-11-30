<?php

namespace NaxCrmBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use NaxCrmBundle\Entity\Manager;
use NaxCrmBundle\Entity\AclPrivileges;

/**
 * NaxCrmBundle\Entity\AclRoles
 *
 * @ORM\Table(name="acl_roles")
 * @ORM\Entity(repositoryClass="NaxCrmBundle\Repository\AclRolesRepository")
 * @UniqueEntity(
 *     fields={"name"},
 *     message="Role name is already in use",
 *     ignoreNull=false
 * )
 */
class AclRoles extends BaseEntity
{
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
     * @ORM\Column(name="description", type="string", length=255)
     */
    protected $description;

    /**
     * @var \DateTime
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(name="created", type="datetime")
     */
    protected $created;

    /**
     * @var Manager[]
     * @ORM\ManyToMany(targetEntity="NaxCrmBundle\Entity\Manager", mappedBy="aclRoles")
     */
    protected $managers;


    /**
     * @var AclPrivileges[]
     * @ORM\OneToMany(targetEntity="NaxCrmBundle\Entity\AclPrivileges", mappedBy="role")
     */
    protected $privileges;

    public function __construct()
    {
        $this->created = new \DateTime();
        $this->managers = new ArrayCollection();
        $this->privileges = new ArrayCollection();
    }
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
     * @return AclRoles
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
     * @return AclRoles
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
     * Set created
     *
     * @param \DateTime $created
     *
     * @return AclRoles
     */
    public function setCreated($created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * Get created
     *
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @return Manager[]
     */
    public function getManagers()
    {
        $managers = [];
        foreach ($this->managers as $m) {
            if(!$m->getBlocked()){
                $managers[] = $m;
            }
        }
        $this->managers = $managers;
        return $this->managers;
    }


    /**
     * Add manager
     *
     * @param Manager $manager
     *
     * @return AclRoles
     */
    public function addManager(Manager $manager)
    {
        $this->managers[] = $manager;

        return $this;
    }

    /**
     * Remove manager
     *
     * @param Manager $manager
     */
    public function removeManager(Manager $manager)
    {
        $this->managers->removeElement($manager);
    }

    /**
     * Add privilege
     *
     * @param AclPrivileges $privilege
     *
     * @return AclRoles
     */
    public function addPrivilege(AclPrivileges $privilege)
    {
        $this->privileges[] = $privilege;

        return $this;
    }

    /**
     * Remove privilege
     *
     * @param AclPrivileges $privilege
     */
    public function removePrivilege(AclPrivileges $privilege)
    {
        $this->privileges->removeElement($privilege);
    }

    /**
     * Get privileges
     *
     * @return AclPrivileges[]
     */
    public function getPrivileges()
    {
        return $this->privileges;
    }

    public function export($include = ['managers' => 1, 'privileges' => 1])
    {
        $result = [
            'id'        => $this->getId(),
            'name'      => $this->getName(),
            'created'   => $this->getCreated()->format('Y-m-d H:i:s'),
            'description' => $this->getDescription(),
        ];
        if (!empty($include['managers'])) {
            $result['managers'] = [];
            foreach ($this->getManagers() as $manager) {
                $result['managers'][] = $manager->export();
            }
        }

        if (!empty($include['privileges'])) {
            $result['privileges'] = [];
            foreach ($this->getPrivileges() as $privilege) {
                $result['privileges'][$privilege->getResource()->getName()] = $privilege->getPrivileges();
            }
        }

        return $result;
    }
}
