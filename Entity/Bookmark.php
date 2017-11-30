<?php

namespace NaxCrmBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use JMS\Serializer\Annotation\Exclude;
use NaxCrmBundle\Entity\Manager;

/**
 * Bookmark
 *
 * @ORM\Table(name="bookmarks")
 * @ORM\Entity(repositoryClass="NaxCrmBundle\Repository\BookmarkRepository")
 * @Gedmo\SoftDeleteable(fieldName="deletedAt", timeAware=false)
 */
class Bookmark extends BaseEntity
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
     * @ORM\Column(name="name", type="string", length=100)
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(name="bookmark", type="text")
     */
    protected $bookmark;

    /**
     * @var string
     *
     * @ORM\Column(name="bookmark_group", type="string", length=100)
     */
    protected $bookmarkGroup;

    /**
     * @var string
     *
     * @ORM\Column(name="tableName", type="text")
     */
    protected $table;

    /**
     * @var string
     *
     * @ORM\Column(name="url", type="text")
     */
    protected $url = '';

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
     * @ORM\ManyToOne(targetEntity="NaxCrmBundle\Entity\Manager", inversedBy="bookmarks")
     * @ORM\JoinColumn(name="manager_id", referencedColumnName="id", nullable=false)
     */
    protected $manager;

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
     * Set name
     *
     * @param string $name
     * @return Bookmark
     */
    public function setName($name)
    {
        if($name){
            $name = mb_substr($name, 0, 100);
        }
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
     * Set bookmark
     *
     * @param string $bookmark
     * @return Bookmark
     */
    public function setBookmark($bookmark)
    {
        $this->bookmark = $bookmark;

        return $this;
    }
    /**
     * Get bookmark
     *
     * @return string
     */
    public function getBookmark()
    {
        return $this->bookmark;
    }

    /**
     * Set bookmarkGroup
     *
     * @param string $bookmarkGroup
     * @return Bookmark
     */
    public function setBookmarkGroup($bookmarkGroup)
    {
        $this->bookmarkGroup = $bookmarkGroup;

        return $this;
    }
    /**
     * Get bookmarkGroup
     *
     * @return string
     */
    public function getBookmarkGroup()
    {
        return $this->bookmarkGroup;
    }

    /**
     * Set table
     *
     * @param string $table
     * @return Bookmark
     */
    public function setTable($table)
    {
        $this->table = $table;

        return $this;
    }
    /**
     * Get table
     *
     * @return string
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * Set url
     *
     * @param string $url
     * @return Bookmark
     */
    public function setUrl($url)
    {
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
     * Set created
     *
     * @param \DateTime $created
     * @return Bookmark
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
     * @return mixed
     */
    public function getDeletedAt()
    {
        return $this->deletedAt;
    }

    /**
     * @param mixed $deletedAt
     * @return Bookmark
     */
    public function setDeletedAt($deletedAt)
    {
        $this->deletedAt = $deletedAt;
        return $this;
    }

    /**
     * * Get manager
     *
     * @return Manager
     */
    public function getManager()
    {
        return $this->manager;
    }

    /**
     * @param Manager $manager
     * @return $this
     */
    public function setManager($manager)
    {
        $this->manager = $manager;
        return $this;
    }

    /**
     * @return integer
     */
    public function getManagerId()
    {
        return $this->getManager()->getId();
    }

    public function export() {
        return [
            'id'            => $this->getId(),
            'name'          => $this->getName(),
            'bookmarkGroup' => $this->getBookmarkGroup(),
            'bookmark'      => $this->getBookmark(),
            'table'         => $this->getTable(),
            'url'           => $this->getUrl(),
            'managerId'     => $this->getManagerId(),
            'managerName'   => $this->getManager()->getName(),
            'create'        => $this->getCreated()->format('Y-m-d H:i:s')
        ];
    }
}
