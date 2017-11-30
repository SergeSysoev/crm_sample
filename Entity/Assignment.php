<?php

namespace NaxCrmBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Assignment
 *
 * @ORM\Table(name="assignment")
 * @ORM\Entity
 */
class Assignment extends BaseEntity
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
     * @ORM\ManyToOne(targetEntity="NaxCrmBundle\Entity\Manager", inversedBy="leadUploads")
     * @ORM\JoinColumn(name="manager_id", referencedColumnName="id", nullable=true)
     */
    protected $manager;
    /**
     * @ORM\ManyToOne(targetEntity="NaxCrmBundle\Entity\Department")
     * @ORM\JoinColumn(name="department_id", referencedColumnName="id", nullable=true)
     */
    protected $department;
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created", type="datetime")
     */
    protected $created;

    /**
     * @ORM\OneToMany(targetEntity="NaxCrmBundle\Entity\Lead", mappedBy="assignment")
     */
    protected $leads;

    /**
     * @ORM\OneToMany(targetEntity="NaxCrmBundle\Entity\Client", mappedBy="assignment")
     */
    protected $clients;

    /**
     * LeadUpload constructor.
     */
    public function __construct()
    {
        $this->created  = new \DateTime('now');
        $this->leads    = new ArrayCollection();
        $this->clients  = new ArrayCollection();
    }

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
     * Set created
     *
     * @param \DateTime $created
     *
     * @return $this
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
     * Set manager
     *
     * @param Manager $manager
     *
     * @return $this
     */
    public function setManager(Manager $manager = null)
    {
        $this->manager = $manager;

        return $this;
    }

    /**
     * Get manager
     *
     * @return Manager
     */
    public function getManager()
    {
        return $this->manager;
    }

    /**
     * @return mixed
     */
    public function getDepartment()
    {
        return $this->department;
    }

    /**
     * @param mixed $department
     * @return Assignment
     */
    public function setDepartment($department)
    {
        $this->department = $department;
        return $this;
    }


    /**
     * Add lead
     *
     * @param Lead $lead
     *
     * @return Assignment
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

    /**
     * Add client
     *
     * @param Client $client
     *
     * @return Assignment
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
}
