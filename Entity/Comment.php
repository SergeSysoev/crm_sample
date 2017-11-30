<?php

namespace NaxCrmBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Exclude;

/**
 * Comment
 *
 * @ORM\Table(name="comments")
 * @ORM\Entity(repositoryClass="NaxCrmBundle\Repository\CommentRepository")
 */
class Comment extends BaseEntity
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
     * @ORM\ManyToOne(targetEntity="NaxCrmBundle\Entity\Manager", inversedBy="comments")
     * @ORM\JoinColumn(name="manager_id", referencedColumnName="id", nullable=false)
     */
    protected $manager;

    /**
     * @var string
     *
     * @ORM\Column(name="text", type="string", length=2047)
     */
    protected $text;

    /**
     * @ORM\ManyToOne(targetEntity="NaxCrmBundle\Entity\Lead", inversedBy="comments")
     * @ORM\JoinColumn(name="lead_id", referencedColumnName="id", nullable=true)
     * @Exclude
     */
    protected $lead;

    /**
     * @ORM\ManyToOne(targetEntity="NaxCrmBundle\Entity\Client", inversedBy="comments")
     * @ORM\JoinColumn(name="client_id", referencedColumnName="id", nullable=true)
     * @Exclude
     */
    protected $client;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created", type="datetime")
     */
    protected $created;

    /**
     * Comment constructor.
     */
    public function __construct()
    {
        $this->created = new \DateTime('now');
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
     * Set text
     *
     * @param string $text
     *
     * @return Comment
     */
    public function setText($text)
    {
        if($text){
            $text = mb_substr($text, 0, 2000);
        }
        $this->text = $text;

        return $this;
    }

    /**
     * Get text
     *
     * @return string
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * Set lead
     *
     * @param Lead $lead
     *
     * @return Comment
     */
    public function setLead(Lead $lead = null)
    {
        $this->lead = $lead;

        return $this;
    }

    /**
     * Get lead
     *
     * @return Lead
     */
    public function getLead()
    {
        return $this->lead;
    }

    /**
     * Set client
     *
     * @param Client $client
     *
     * @return Comment
     */
    public function setClient(Client $client = null)
    {
        $this->client = $client;

        return $this;
    }

    /**
     * Get client
     *
     * @return Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * Set created
     *
     * @param \DateTime $created
     *
     * @return Comment
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
     * @return Comment
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
     * @return array
     */
    public function export()
    {
        $data = [
            'id' => $this->getId(),
            'managerId' => $this->getManager()->getId(),
            'managerName' => $this->getManager()->getName(),
            'text'	     => $this->getText(),
            'created'    => $this->getCreated()->format('Y-m-d H:i:s'),
        ];
        if(!empty($this->getLead())){
            $data['lead_id']=$this->getLead()->getId();
        }
        if(!empty($this->getClient())){
            $data['client_id']=$this->getClient()->getId();
        }
        return $data;
    }
}
