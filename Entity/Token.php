<?php

namespace NaxCrmBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Gedmo\Mapping\Annotation as Gedmo;
use JMS\Serializer\Annotation\MaxDepth;
use JMS\Serializer\Annotation\Exclude;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * Token
 *
 * @ORM\Table(name="token", uniqueConstraints={@UniqueConstraint(name="uni_name", columns={"name", "group_id", "deleted_at"})})
 *
 * @ORM\Entity(repositoryClass="NaxCrmBundle\Repository\TokenRepository")
 * @Gedmo\SoftDeleteable(fieldName="deletedAt", timeAware=false)
 * @UniqueEntity(
 *     fields={"name", "groupId", "deletedAt"},
 *     message="This token already exists.",
 *     ignoreNull=true
 * )
 */
class Token extends BaseEntity
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
     */
    protected $name;

    /**
     * @var int
     *
     * @ORM\Column(name="group_id", type="integer")
     */
    protected $groupId;

    /**
     * @ORM\ManyToOne(targetEntity="NaxCrmBundle\Entity\TokenGroup", inversedBy="tokens")
     * @ORM\JoinColumn(name="group_id", referencedColumnName="id", nullable=true)
     * @MaxDepth(2)
     */
    protected $group;

    /**
     * @var TokenValue[]|ArrayCollection
     * @ORM\OneToMany(targetEntity="NaxCrmBundle\Entity\TokenValue", mappedBy="token", cascade={"remove"}, orphanRemoval=true)
     * @MaxDepth(2)
     */
    protected $values;

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
     * Set name
     *
     * @param string $name
     *
     * @return Token
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
     * Set groupId
     *
     * @param integer $groupId
     *
     * @return Token
     */
    public function setGroupId($groupId)
    {
        $this->groupId = $groupId;

        return $this;
    }

    /**
     * Get groupId
     *
     * @return int
     */
    public function getGroupId()
    {
        return $this->groupId;
    }
    /**
     * Set group
     *
     * @param TokenGroup $group
     *
     * @return Token
     */
    public function setGroup($group)
    {
        $this->group = $group;

        $this->setGroupId($group->getId());
        return $this;
    }

    /**
     * Get group
     *
     * @return TokenGroup
     */
    public function getGroup()
    {
        return $this->group;
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
            'id'        => $this->getId(),
            'name'      => $this->getName(),
            'groupId'   => $this->getGroupId(),
            'groupName' => $this->getGroup()->getName()
        ];
    }
}

