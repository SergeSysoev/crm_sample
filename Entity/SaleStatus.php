<?php

namespace NaxCrmBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use JMS\Serializer\Annotation\Exclude;
use JMS\Serializer\Annotation\Type;

/**
 * SalesStatus
 *
 * @ORM\Table(name="sale_statuses")
 * @ORM\Entity
 * @UniqueEntity(
 *     fields={"statusName"},
 *     message="This status name is already in use."
 * )
 */
class SaleStatus extends BaseEntity
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
     * @ORM\Column(name="status_name", type="string", length=95, unique=true)
     */
    protected $statusName;

    /**
     * @ORM\ManyToOne(targetEntity="NaxCrmBundle\Entity\Manager")
     * @ORM\JoinColumn(name="manager_id", referencedColumnName="id", nullable=true)
     * @Exclude
     */
    protected $manager;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created", type="datetime")
     * @Type("DateTime<'Y-m-d H:i:s'>")
     */
    protected $created;

    /**
     * @ORM\OneToMany(targetEntity="NaxCrmBundle\Entity\Client", mappedBy="saleStatus")
     * @Exclude
     */
    protected $clients;

    /**
     * @ORM\OneToMany(targetEntity="NaxCrmBundle\Entity\Lead", mappedBy="saleStatus")
     * @Exclude
     */
    protected $leads;

    public function __construct()
    {
        $this->created = new \DateTime('now');
        $this->clients = new ArrayCollection();
        $this->leads   = new ArrayCollection();
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
     * Set statusName
     *
     * @param string $statusName
     * @return SaleStatus
     */
    public function setStatusName($statusName)
    {
        $this->statusName = $statusName;

        return $this;
    }

    /**
     * Get statusName
     *
     * @return string
     */
    public function getStatusName()
    {
        return $this->statusName;
    }

    /**
     * Set created
     *
     * @param \DateTime $created
     *
     * @return SaleStatus
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
     * @return SaleStatus
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
     * Add client
     *
     * @param Client $client
     *
     * @return SaleStatus
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
     * @return SaleStatus
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

    public function export(){
        return [
            'id' => $this->getId(),
            'statusName' => $this->getStatusName(),
        ];
    }
}
