<?php

namespace NaxCrmBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Log item
 *
 * @ORM\Table(name="logs")
 * @ORM\Entity(repositoryClass="NaxCrmBundle\Repository\LogItemRepository")
 */
class LogItem extends BaseEntity
{

    const CHANNEL_SYSTEM = 'system';
    const CHANNEL_USER = 'user';

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
     * @ORM\Column(name="channel", type="string", length=255)
     */
    protected $channel;

    /**
     * @var integer
     *
     * @ORM\Column(name="level", type="integer")
     */
    protected $level;

    /**
     * @var string
     *
     * @ORM\Column(name="message", type="text")
     */
    protected $message;

    /**
     * @var string
     *
     * @ORM\Column(name="url", type="string", length=255, nullable=true)
     */
    protected $url;

    /**
     * @ORM\ManyToOne(targetEntity="NaxCrmBundle\Entity\Manager",cascade={"persist"})
     * @ORM\JoinColumn(name="manager_id", referencedColumnName="id", nullable=true)
     */
    protected $manager;

    /**
     * @var int
     *
     * @ORM\Column(name="manager_id", type="integer", nullable=true)
     */
    protected $managerId;

    /**
     * @ORM\ManyToOne(targetEntity="NaxCrmBundle\Entity\Client")
     * @ORM\JoinColumn(name="client_id", referencedColumnName="id", nullable=true)
     */
    protected $client;

    /**
     * @var int
     *
     * @ORM\Column(name="client_id", type="integer", nullable=true)
     */
    protected $clientId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="time", type="datetime")
     */
    protected $time;

    /**
     * @var string
     *
     * @ORM\Column(name="json_payload", type="text", nullable=true)
     */
    protected $jsonPayload;

    /**
     * @var string
     *
     * @ORM\Column(name="referer", type="string", length=255, nullable=true)
     */
    protected $referer;

    /**
     * LogItem constructor.
     */
    public function __construct()
    {
        $this->time = new \DateTime();
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
     * Set level
     *
     * @param integer $level
     *
     * @return LogItem
     */
    public function setLevel($level)
    {
        $this->level = $level;

        return $this;
    }

    /**
     * Get level
     *
     * @return integer
     */
    public function getLevel()
    {
        return $this->level;
    }

    /**
     * Set message
     *
     * @param string $message
     *
     * @return LogItem
     */
    public function setMessage($message)
    {
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
     * Set time
     *
     * @param \DateTime $time
     *
     * @return LogItem
     */
    public function setTime($time)
    {
        $this->time = $time;

        return $this;
    }

    /**
     * Get time
     *
     * @return \DateTime
     */
    public function getTime()
    {
        return $this->time;
    }

    /**
     * Set jsonPayload
     *
     * @param string $jsonPayload
     *
     * @return LogItem
     */
    public function setJsonPayload($jsonPayload)
    {
        $this->jsonPayload = $jsonPayload;

        return $this;
    }

    /**
     * Get jsonPayload
     *
     * @return string
     */
    public function getJsonPayload()
    {
        return $this->jsonPayload;
    }

    /**
     * Set channel
     *
     * @param string $channel
     *
     * @return LogItem
     */
    public function setChannel($channel)
    {
        $this->channel = $channel;

        return $this;
    }

    /**
     * Get channel
     *
     * @return string
     */
    public function getChannel()
    {
        return $this->channel;
    }

    /**
     * Set managerId
     *
     * @param integer $managerId
     *
     * @return LogItem
     */
    public function setManagerId($managerId)
    {
        $this->managerId = $managerId;

        return $this;
    }

    /**
     * Get managerId
     *
     * @return integer
     */
    public function getManagerId()
    {
        return $this->managerId;
    }

    /**
     * Set manager
     *
     * @param Manager $manager
     *
     * @return LogItem
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
     * Set clientId
     *
     * @param integer $clientId
     *
     * @return LogItem
     */
    public function setClientId($clientId)
    {
        $this->clientId = $clientId;

        return $this;
    }

    /**
     * Get clientId
     *
     * @return integer
     */
    public function getClientId()
    {
        return $this->clientId;
    }

    /**
     * Set client
     *
     * @param Client $client
     *
     * @return LogItem
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
     * Set url
     *
     * @param string $url
     *
     * @return LogItem
     */
    public function setUrl( $url)
    {
        if($url){
            $url = mb_substr($url, 0, 255);
        }
        $this->url = $url;

        return $this;
    }

    /**
     * Get url
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set referer
     *
     * @param string $referer
     *
     * @return LogItem
     */
    public function setReferer($referer = null)
    {
        if(!$referer){
            if(!empty($_SERVER['HTTP_REFERER'])){
                $referer = $_SERVER['HTTP_REFERER'];
            }
            $_HEADERS = getallheaders();
            if(!empty($_HEADERS['X-Referer'])){
                $referer = "{$_HEADERS['X-Referer']}";
            }
        }
        if($referer){
            $referer = substr($referer, 0, 255);
        }
        $this->referer = $referer;

        return $this;
    }

    /**
     * Get referer
     *
     * @return string
     */
    public function getReferer()
    {
        return $this->referer;
    }
}
