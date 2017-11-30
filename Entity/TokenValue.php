<?php

namespace NaxCrmBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use JMS\Serializer\Annotation\MaxDepth;
use JMS\Serializer\Annotation\Exclude;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
/**
 * TokenValue
 *
 * @ORM\Table(name="token_value")
 * @ORM\Entity(repositoryClass="NaxCrmBundle\Repository\TokenValueRepository")
 * @Gedmo\SoftDeleteable(fieldName="deletedAt", timeAware=false)
 * @UniqueEntity(
 *     fields={"tokenId", "languageId"},
 *     message="This token already exists.",
 *     ignoreNull=false
 * )
 */
class TokenValue extends BaseEntity
{
    /**
     * @var string
     *
     * @ORM\Column(name="translate", type="string", length=255)
     */
    protected $translate;

    /**
     * @var int
     * @ORM\Id
     * @ORM\Column(name="token_id", type="integer")
     */
    protected $tokenId;

    /**
     * @var int
     * @ORM\Id
     * @ORM\Column(name="language_id", type="integer")
     */
    protected $languageId;

    /**
     * @var \DateTime
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(name="created", type="datetime")
     */
    protected $created;

    /**
     * @var int
     *
     * @ORM\Column(name="manager_id", type="integer")
     */
    protected $managerId;

    /**
     * @ORM\ManyToOne(targetEntity="NaxCrmBundle\Entity\Manager")
     * @ORM\JoinColumn(name="manager_id", referencedColumnName="id", nullable=true)
     */
    protected $manager;

    /**
     * @ORM\ManyToOne(targetEntity="NaxCrmBundle\Entity\Language")
     * @ORM\JoinColumn(name="language_id", referencedColumnName="id", nullable=false)
     */
    protected $language;

    /**
     * @ORM\ManyToOne(targetEntity="NaxCrmBundle\Entity\Token", inversedBy="values")
     * @ORM\JoinColumn(name="token_id", referencedColumnName="id", nullable=false)
     */
    protected $token;

    /**
     * @var \DateTime
     * @ORM\Column(type="datetime", nullable=true)
     * @Exclude
     */
    protected $deletedAt;

    public function __construct()
    {
        $this->created = new \DateTime();
    }

    /**
     * Set translate
     *
     * @param string $translate
     *
     * @return TokenValue
     */
    public function setTranslate($translate)
    {
        $this->translate = $translate;

        return $this;
    }

    /**
     * Get translate
     *
     * @return string
     */
    public function getTranslate()
    {
        return $this->translate;
    }

    /**
     * Set tokenId
     *
     * @param integer $tokenId
     *
     * @return TokenValue
     */
    public function setTokenId($tokenId)
    {
        $this->tokenId = $tokenId;

        return $this;
    }

    /**
     * Get tokenId
     *
     * @return int
     */
    public function getTokenId()
    {
        return $this->tokenId;
    }

    /**
     * Set languageId
     *
     * @param integer $languageId
     *
     * @return TokenValue
     */
    public function setLanguageId($languageId)
    {
        $this->languageId = $languageId;

        return $this;
    }

    /**
     * Get languageId
     *
     * @return int
     */
    public function getLanguageId()
    {
        return $this->languageId;
    }

    /**
     * Set created
     *
     * @param \DateTime $created
     *
     * @return TokenValue
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
     * Set managerId
     *
     * @param integer $managerId
     *
     * @return TokenValue
     */
    public function setManagerId($managerId)
    {
        $this->managerId = $managerId;

        return $this;
    }

    /**
     * Get managerId
     *
     * @return int
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
     * @return TokenValue
     */
    public function setManager($manager)
    {
        $this->manager = $manager;

        $this->setManagerId($manager->getId());

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
     * Set language
     *
     * @param Language $language
     *
     * @return TokenValue
     */
    public function setLanguage($language)
    {
        $this->language = $language;

        return $this;
    }

    /**
     * Get language
     *
     * @return Language
     */
    public function getLanguage()
    {
        return $this->language;
    }
    /**
     * Set token
     *
     * @param Token $token
     *
     * @return TokenValue
     */
    public function setToken($token)
    {
        $this->token = $token;

        return $this;
    }

    /**
     * Get token
     *
     * @return Token
     */
    public function getToken()
    {
        return $this->token;
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
     * @return \DateTime
     */
    public function getDeletedAt()
    {
        return $this->deletedAt;
    }

    public function export()
    {
        return [
            'tokenId'   => $this->getTokenId(),
            'languageId'=> $this->getLanguageId(),
            'translate' => $this->getTranslate(),
            'managerId' => $this->getManagerId(),
            'created'   => $this->getCreated()->format('Y-m-d H:i:s')
        ];

    }
}

