<?php

namespace NaxCrmBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\MaxDepth;
use JMS\Serializer\Annotation\Exclude;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
/**
 * Department
 *
 * @Gedmo\Tree(type="nested")
 * @Gedmo\SoftDeleteable(fieldName="deletedAt", timeAware=false)
 * @ORM\Table(name="departments")
 * @ORM\Entity(repositoryClass="NaxCrmBundle\Repository\DepartmentRepository")
 * @UniqueEntity(
 *     fields={"name", "deletedAt"},
 *     message="This name is already in use. ",
 *     ignoreNull=false
 * )
 */
class Department extends BaseEntity
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
     * @ORM\Column(name="name", type="string", length=255)
     * @Assert\NotBlank
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="string", length=255)
     */
    protected $description;

    /**
     * @var int
     * @Gedmo\TreeLeft
     * @ORM\Column(name="lft", type="integer")
     */
    protected $lft;

    /**
     * @var int
     * @Gedmo\TreeRight
     * @ORM\Column(name="rgt", type="integer")
     */
    protected $rgt;

    /**
     * @var int
     * @Gedmo\TreeLevel
     * @ORM\Column(name="lvl", type="integer")
     */
    protected $lvl;
    /**
     * @Gedmo\TreeRoot
     * @ORM\Column(name="root", type="integer", nullable=true)
     */
    protected $root;

    /**
     * @var Department
     * @Gedmo\TreeParent
     * @ORM\ManyToOne(targetEntity="NaxCrmBundle\Entity\Department", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id")
     * @MaxDepth(1)
     */
    protected $parent;
    /**
     * @var integer
     * @ORM\Column(name="parent_id", nullable=true, type="integer")
     */
    public $parentId;
    /**
     * @var Department
     * @ORM\OneToMany(targetEntity="NaxCrmBundle\Entity\Department", mappedBy="parent")
     * @ORM\OrderBy({"lft" = "ASC"})
     * @MaxDepth(1)
     */
    protected $children;
    /**
     * @var \DateTime
     * @ORM\Column(type="datetime", nullable=true)
     * @Exclude
     */
    protected $deletedAt;

    /**
     * @var Manager[]|ArrayCollection
     * @ORM\OneToMany(targetEntity="NaxCrmBundle\Entity\Manager", mappedBy="department")
     * @MaxDepth(1)
     */
    protected $managers;

    /**
     * @ORM\OneToMany(targetEntity="NaxCrmBundle\Entity\Client", mappedBy="department")
     */
    protected $clients;

    /**
     * @ORM\OneToMany(targetEntity="NaxCrmBundle\Entity\Lead", mappedBy="department")
     */
    protected $leads;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return Department
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
     * @return Department
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
     * Set lft
     *
     * @param integer $lft
     * @return Department
     */
    public function setLft($lft)
    {
        $this->lft = $lft;

        return $this;
    }

    /**
     * Get lft
     *
     * @return integer
     */
    public function getLft()
    {
        return $this->lft;
    }

    /**
     * Set rgt
     *
     * @param integer $rgt
     * @return Department
     */
    public function setRgt($rgt)
    {
        $this->rgt = $rgt;

        return $this;
    }

    /**
     * Get rgt
     *
     * @return integer
     */
    public function getRgt()
    {
        return $this->rgt;
    }

    /**
     * Set lvl
     *
     * @param integer $lvl
     * @return Department
     */
    public function setLvl($lvl)
    {
        $this->lvl = $lvl;

        return $this;
    }

    /**
     * Get lvl
     *
     * @return integer
     */
    public function getLvl()
    {
        return $this->lvl;
    }

    /**
     * @return mixed
     */
    public function getRoot()
    {
        return $this->root;
    }

    /**
     * @param mixed $root
     * @return Department
     */
    public function setRoot($root)
    {
        $this->root = $root;
        return $this;
    }

    /**
     * Set parent
     *
     * @param Department $parent
     * @return Department
     */
    public function setParent($parent)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get parent
     *
     * @return Department
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @return \DateTime
     */
    public function getDeletedAt()
    {
        return $this->deletedAt;
    }

    /**
     * @param \DateTime $deletedAt
     * @return Department
     */
    public function setDeletedAt($deletedAt)
    {
        $this->deletedAt = $deletedAt;
        return $this;
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->children = new ArrayCollection();
        $this->clients  = new ArrayCollection();
        $this->leads    = new ArrayCollection();
        $this->managers = new ArrayCollection();
    }

    /**
     * Set parentId
     *
     * @param integer $parentId
     *
     * @return Department
     */
    public function setParentId($parentId)
    {
        $this->parentId = $parentId;

        return $this;
    }

    /**
     * Get parentId
     *
     * @return integer
     */
    public function getParentId()
    {
        return $this->parentId;
    }

    /**
     * Add child
     *
     * @param Department $child
     *
     * @return Department
     */
    public function addChild(Department $child)
    {
        $this->children[] = $child;

        return $this;
    }

    /**
     * Remove child
     *
     * @param Department $child
     */
    public function removeChild(Department $child)
    {
        $this->children->removeElement($child);
    }

    /**
     * Get children
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * Add manager
     *
     * @param Manager $manager
     *
     * @return Department
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
     * Get manager
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getManagers($show_blocked = false)
    {
        $managers = new ArrayCollection();
        foreach ($this->managers as $m) {
            if($show_blocked || !$m->getBlocked()){
                $managers[] = $m;
            }
        }
        $this->managers = $managers;
        return $this->managers;
    }

    /**
     * Add client
     *
     * @param Client $client
     *
     * @return Department
     */
    public function addClient(Client $client)
    {
        $this->clients[] = $client;

        return $this;
    }

    /**
     * Remove client
     *
     * @param Client $client
     */
    public function removeClient(Client $client)
    {
        $this->clients->removeElement($client);
    }

    /**
     * Get clients
     *
     * @return ArrayCollection
     */
    public function getClients()
    {
        return $this->clients;
    }

    /**
     * Add lead
     *
     * @param Lead $lead
     *
     * @return Department
     */
    public function addLead(Lead $lead)
    {
        $this->leads[] = $lead;

        return $this;
    }

    /**
     * Remove lead
     *
     * @param Lead $lead
     */
    public function removeLead(Lead $lead)
    {
        $this->leads->removeElement($lead);
    }

    /**
     * Get leads
     *
     * @return ArrayCollection
     */
    public function getLeads()
    {
        return $this->leads;
    }

    public function export()
    {
        return [
            'id'            => $this->getId(),
            'name'          => $this->getName(),
            'description'   => $this->getDescription(),
            'lft'           => $this->getLft(),
            'rgt'           => $this->getRgt(),
            'lvl'           => $this->getLvl(),
            'root'          => $this->getRoot(),
            'parentId'      => $this->getParent() ? $this->getParent()->getId() : '',
        ];
    }
}
