<?php

namespace NaxCrmBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\VirtualProperty;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\Exclude;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * EmailTemplate
 *
 * @ORM\Table(name="email_templates")
 * @ORM\Entity
 * @Gedmo\SoftDeleteable(fieldName="deletedAt", timeAware=false)
 */
class EmailTemplate extends BaseEntity
{
    const DEFAULT_LANGCODE  = 'en';

    /**
     * @var array
     * @Exclude
     */
    public static $supportedLangcodes = ['en', 'ru'];

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
     * @ORM\Column(name="subject", type="string", length=255, nullable=true)
     */
    protected $subject;

    /**
     * @var string
     *
     * @ORM\Column(name="langcode", type="string", length=2)
     */
    protected $langcode = self::DEFAULT_LANGCODE;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(name="trigger_name", type="string", length=255)
     */
    protected $triggerName = '';

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="string", length=2047)
     */
    protected $description;

    /**
     * @var string
     *
     * @ORM\Column(name="html", type="text", nullable=true)
     */
    protected $html;

    /**
     * @var string
     *
     * @ORM\Column(name="json", type="text", nullable=true)
     */
    protected $json;

    /**
     * @var string
     *
     * @ORM\Column(name="delay", type="string", length=50)
     */
    protected $delay = '';


    /**
     * @var boolean
     *
     * @ORM\Column(name="enabled", type="boolean")
     */
    protected $enabled = 0;

    /**
     * @var \DateTime
     * @ORM\Column(type="datetime", nullable=true)
     * @Exclude
     */
    protected $deletedAt;

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
     * @return EmailTemplate
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
     * Set langcode
     *
     * @param string $langcode
     *
     * @return EmailTemplate
     */
    public function setLangcode($langcode)
    {
        $this->langcode = $langcode;

        return $this;
    }

    /**
     * Get langcode
     *
     * @return string
     */
    public function getLangcode()
    {
        return $this->langcode;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return EmailTemplate
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
     * @return EmailTemplate
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
     * Set html
     *
     * @param string $html
     *
     * @return EmailTemplate
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
     * Set json
     *
     * @param string $json
     *
     * @return EmailTemplate
     */
    public function setJson($json)
    {
        $this->json = $json;

        return $this;
    }

    /**
     * Get json
     *
     * @return string
     */
    public function getJson()
    {
        return $this->json;
    }

    /**
     * Set triggerName
     *
     * @param string $triggerName
     *
     * @return EmailTemplate
     */
    public function setTriggerName($triggerName)
    {
        $this->triggerName = $triggerName;

        return $this;
    }

    /**
     * Get triggerName
     *
     * @return string
     */
    public function getTriggerName()
    {
        return $this->triggerName;
    }

    /**
     * @return string
     */
    public function getDelay(): string
    {
        return $this->delay;
    }
    /**
     * @VirtualProperty
     * @SerializedName("minutes")
     */
    public function getMinutes(): int
    {
        preg_match("#P.*T.*(\d+)M#", strtoupper($this->delay), $result);
        return empty($result[1]) ? 0 :  $result[1];
    }
    /**
     * @VirtualProperty
     * @SerializedName("hours")
     */
    public function getHours(): int
    {
        preg_match("#P.*T.*(\d+)H#", strtoupper($this->delay), $result);
        return empty($result[1]) ? 0 :  $result[1];
    }
    /**
     * @param string $delay
     * @return EmailTemplate
     */
    public function setDelay(string $delay): EmailTemplate
    {
        $this->delay = $delay;
        return $this;
    }

    /**
     * @return boolean
     */
    public function isEnabled()
    {
        return (bool)$this->enabled;
    }

    /**
     * @param boolean $enabled
     * @return EmailTemplate
     */
    public function setEnabled($enabled): EmailTemplate
    {
        $this->enabled = boolval($enabled);
        return $this;
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

    public function export(){
        $res = [
            'id'            => $this->getId(),
            'name'          => $this->getName(),
            'description'   => $this->getDescription(),
            'langcode'      => $this->getLangcode(),
            'subject'       => $this->getSubject(),
            'html'          => $this->getHtml(),
            'json'          => $this->getJson(),
            'triggerName'   => $this->getTriggerName(),
            'delay'         => $this->getDelay(),
            'minutes'       => $this->getMinutes(),
            'hours'         => $this->getHours(),
            'enabled'       => $this->isEnabled(),
        ];
        return $res;
    }
}
