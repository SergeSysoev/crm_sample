<?php

namespace NaxCrmBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Exclude;

/**
 * Message
 *
 * @ORM\Table(name="messages")
 * @ORM\Entity(repositoryClass="NaxCrmBundle\Repository\MessageRepository")
 */
class Message extends BaseEntity
{
    const STATUS_NEW = 0;
    const STATUS_ERROR = -1;
    const STATUS_SENT = 2;
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
     * @ORM\Column(name="subject", type="string", length=255)
     */
    protected $subject;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=255)
     */
    protected $email;

    /**
     * @var string
     *
     * @ORM\Column(name="attachment", type="string", length=255)
     */
    protected $attachment = '';

    /**
     * @var string
     *
     * @ORM\Column(name="html", type="text")
     */
    protected $html;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created", type="datetime", nullable=false)
     */
    protected $created;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="send_at", type="datetime", nullable=false)
     */
    protected $sendAt;
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="was_sent", type="datetime", nullable=true)
     */
    protected $wasSent;

    /**
     * @var EmailTemplate
     * @ORM\ManyToOne(targetEntity="NaxCrmBundle\Entity\EmailTemplate")
     * @ORM\JoinColumn(name="template_id", referencedColumnName="id", nullable=false)
     * @Exclude
     */
    protected $template;

    /**
     * @var string email
     *
     * @ORM\Column(name="sender", type="string", length=255)
     */
    protected $sender;
    /**
     * @var string email
     *
     * @ORM\Column(name="reply", type="string", length=255)
     */
    protected $replyTo = '';

    /**
     * @var int
     *
     * @ORM\Column(name="status", type="integer")
     */
    protected $status = 0;

    public function __construct()
    {
        $this->created = new \DateTime();
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
     * Set subject
     *
     * @param string $subject
     *
     * @return Message
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * Get subject
     *
     * @return string
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * Set email
     *
     * @param string $email
     *
     * @return Message
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @return string
     */
    public function getAttachment()
    {
        return $this->attachment;
    }

    /**
     * @param string $attachment
     * @return Message
     */
    public function setAttachment(string $attachment)
    {
        $this->attachment = $attachment;
        return $this;
    }

    /**
     * @return string
     */
    public function getReplyTo(): string
    {
        return $this->replyTo ?: $this->sender;
    }

    /**
     * @param string $replyTo
     * @return Message
     */
    public function setReplyTo(string $replyTo): Message
    {
        $this->replyTo = $replyTo;
        return $this;
    }

    /**
     * Set html
     *
     * @param string $html
     *
     * @return Message
     */
    public function setHtml($html)
    {
        $this->html = $html;

        return $this;
    }

    /**
     * Get html
     *
     * @return string
     */
    public function getHtml()
    {
        return $this->html;
    }

    /**
     * Set created
     *
     * @param \DateTime $created
     *
     * @return Message
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
     * Set sendAt
     *
     * @param \DateTime $sendAt
     *
     * @return Message
     */
    public function setSendAt($sendAt)
    {
        $this->sendAt = $sendAt;

        return $this;
    }

    /**
     * Get sendAt
     *
     * @return \DateTime
     */
    public function getSendAt()
    {
        return $this->sendAt;
    }

    /**
     * @return \DateTime
     */
    public function getWasSent(): \DateTime
    {
        return $this->wasSent;
    }

    /**
     * @param \DateTime $wasSent
     * @return Message
     */
    public function setWasSent(\DateTime $wasSent): Message
    {
        $this->wasSent = $wasSent;
        return $this;
    }

    /**
     * Set template
     *
     * @param EmailTemplate $template
     *
     * @return Message
     */
    public function setTemplate($template)
    {
        $this->template = $template;

        return $this;
    }

    /**
     * Get template
     *
     * @return EmailTemplate
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * Set sender
     *
     * @param string $sender
     *
     * @return Message
     */
    public function setSender($sender)
    {
        $this->sender = $sender;

        return $this;
    }

    /**
     * Get sender
     *
     * @return string
     */
    public function getSender()
    {
        return $this->sender;
    }

    /**
     * Set status
     *
     * @param integer $status
     *
     * @return Message
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }
}

