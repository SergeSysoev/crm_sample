<?php

namespace NaxCrmBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use JMS\Serializer\Annotation\Exclude;
/**
 * Event
 *
 * @ORM\Table(name="events")
 * @ORM\Entity(repositoryClass="NaxCrmBundle\Repository\EventRepository")
 * @Gedmo\SoftDeleteable(fieldName="deletedAt", timeAware=false)
 */
class Event extends BaseEntity
{
    const TYPE_EVENT         = 'event';
    const TYPE_FOLLOW_UP     = 'follow_up';

    const OBJ_TYPE_CLIENT                = 'Client';
    const OBJ_TYPE_CLIENT_ASSIGNMENT     = 'Client_Assignment';
    const OBJ_TYPE_LEAD                  = 'Lead';
    const OBJ_TYPE_LEAD_UPLOAD           = 'LeadUpload';
    const OBJ_TYPE_LEAD_ASSIGNMENT       = 'Lead_Assignment';

    const STATUS_UNREAD = 0;
    const STATUS_READ   = 1;

    /*
     * @Exclude
     */
    public static $statuses = [
        self::STATUS_UNREAD => 'Uncompleted',
        self::STATUS_READ   => 'Completed',
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
     * @ORM\Column(name="message", type="string", length=2047)
     */
    protected $message;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255)
     */
    protected $title;

    /**
     * @var \DateTime
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(name="created", type="datetime")
     */
    protected $created;

    /**
     * @var \DateTime
     * @ORM\Column(type="datetime", nullable=true)
     * @Exclude
     */
    protected $deletedAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="time_followup", type="datetime", nullable=true)
     */
    protected $timeFollowup;

    /**
     * @var Manager
     *
     * @ORM\ManyToOne(targetEntity="NaxCrmBundle\Entity\Manager", inversedBy="events")
     * @ORM\JoinColumn(name="manager_id", referencedColumnName="id", nullable=false)
     */
    protected $manager;

    /**
     * @var int
     *
     * @ORM\Column(name="manager_id", type="integer", nullable=false)
     */
    protected $managerId;

    /**
     * @var string
     *
     * @ORM\Column(name="participants", type="string", length=255, nullable=true)
     */
    protected $participants;

    /**
     * @var int
     *
     * @ORM\Column(name="object_id", type="integer", nullable=true)
     */
    protected $objectId;

    /**
     * @var string
     *
     * @ORM\Column(name="object_type", type="string", length=255, nullable=false)
     */
    protected $objectType = '';

    /**
     * @var bool
     *
     * @ORM\Column(name="status", type="boolean", options={"default" : 0})
     */
    protected $status = false;

    public function __construct()
    {
        $this->setCreated(new \DateTime());
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
     * Set message
     *
     * @param string $message
     * @return Event
     */
    public function setMessage($message)
    {
        if($message){
            $message = mb_substr($message, 0, 2000);
        }
        $this->message = $message;

        return $this;
    }

    /**
     * Get message
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Set title
     *
     * @param string $title
     * @return Event
     */
    public function setTitle($title)
    {
        if($title){
            $title = mb_substr($title, 0, 255);
        }
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set created
     *
     * @param \DateTime $created
     * @return Event
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
     * @return \DateTime
     */
    public function getDeletedAt()
    {
        return $this->deletedAt;
    }

    /**
     * @param \DateTime $deletedAt
     * @return $this
     */
    public function setDeletedAt($deletedAt)
    {
        $this->deletedAt = $deletedAt;
        return $this;
    }

    /**
     * Set timeFollowup
     *
     * @param \DateTime $timeFollowup
     * @return Event
     */
    public function setTimeFollowup($timeFollowup)
    {
        if(!($timeFollowup instanceof \DateTime)){
            if(is_numeric($timeFollowup)){
                $timeFollowup = (new \DateTime()) ->setTimestamp($timeFollowup);
            }
            else{
                $timeFollowup = ($timeFollowup ? new \DateTime($timeFollowup) : null);
            }
        }
        $this->timeFollowup = $timeFollowup;

        return $this;
    }

    /**
     * Get timeFollowup
     *
     * @return \DateTime
     */
    public function getTimeFollowup()
    {
        return $this->timeFollowup;
    }

    /**
     * Set manager
     *
     * @param Manager $manager
     * @return Event
     */
    public function setManager($manager)
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
     * Get manager
     *
     * @return int $managerId
     */
    public function getManagerId()
    {
        return $this->managerId;
    }

    /**
     * @param int $objectId
     */
    public function setObjectId($objectId)
    {
        if(!$objectId) return;

        $this->objectId = $objectId;
    }
    /**
     * @return int
     */
    public function getObjectId()
    {
        return $this->objectId;
    }

    /**
     * @param string $objectType
     */
    public function setObjectType($objectType)
    {
        if(empty($objectType)){
            $objectType = 'custom';
        }
        $this->objectType = $objectType;
    }
    /**
     * @return string
     */
    public function getObjectType()
    {
        return $this->objectType;
    }

    /**
     * @param bool $status
     */
    public function setStatus($status)
    {
        $this->status = boolval($status);
    }
    /**
     * @return bool
     */
    public function getStatus()
    {
        return (bool)$this->status;
    }

    /**
     * @param string $participants
     */
    public function setParticipants($participants)
    {
        if(!empty($participants)){
            if(is_array($participants)){
                $participants = implode('|',$participants);
            }
            $participants = mb_substr($participants, 0, 250);
            $participants = '|'.trim($participants, '|').'|';
        }
        else{
            $participants = null;
        }
        $this->participants = $participants;
    }
    /**
     * @return string
     */
    public function getParticipants()
    {
        $participants = explode('|', trim($this->participants, '|'));

        return $participants;
    }

    /**
     * @return bool
     */
    public function getType()
    {
        return $this->getCreated()? self::TYPE_FOLLOW_UP : self::TYPE_EVENT;
    }

    public function export()
    {
        $manager = $this->getManager();
        return [
            'id'            => $this->getId(),
            'message'       => $this->getMessage(),
            'title'         => $this->getTitle(),
            'type'          => $this->getType(),
            'objectType'    => $this->getObjectType(),
            'objectId'      => $this->getObjectId(),
            'status'        => $this->getStatus(),
            'created'       => $this->getCreated()? $this->getCreated()->format('Y-m-d H:i:s') : '',
            'timeFollowup'  => $this->getTimeFollowup()? $this->getTimeFollowup()->format('Y-m-d H:i:s') : '',
            'managerId'     => $this->getManagerId(),
            'participants'  => $this->getParticipants(),
        ];
    }
}
