<?php

namespace NaxCrmBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * LeadUpload
 *
 * @ORM\Table(name="lead_uploads")
 * @ORM\Entity
 */
class LeadUpload extends BaseEntity
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
     * @ORM\JoinColumn(name="manager_id", referencedColumnName="id", nullable=false)
     */
    protected $manager;

    /**
     * @var int
     *
     * @ORM\Column(name="number_of_uploaded_leads", type="integer")
     */
    protected $numberOfUploadedLeads;

    /**
     * @var string
     *
     * @ORM\Column(name="file_with_failed_leads", type="string", length=255, nullable=true)
     */
    protected $fileWithFailedLeads;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created", type="datetime")
     */
    protected $created;

    /**
     * LeadUpload constructor.
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
     * Set numberOfUploadedLeads
     *
     * @param integer $numberOfUploadedLeads
     *
     * @return LeadUpload
     */
    public function setNumberOfUploadedLeads($numberOfUploadedLeads)
    {
        $this->numberOfUploadedLeads = $numberOfUploadedLeads;

        return $this;
    }

    /**
     * Get numberOfUploadedLeads
     *
     * @return int
     */
    public function getNumberOfUploadedLeads()
    {
        return $this->numberOfUploadedLeads;
    }

    /**
     * Set fileWithFailedLeads
     *
     * @param string $fileWithFailedLeads
     *
     * @return LeadUpload
     */
    public function setFileWithFailedLeads($fileWithFailedLeads)
    {
        $this->fileWithFailedLeads = $fileWithFailedLeads;

        return $this;
    }

    /**
     * Get fileWithFailedLeads
     *
     * @return string
     */
    public function getFileWithFailedLeads()
    {
        return $this->fileWithFailedLeads;
    }

    /**
     * Set created
     *
     * @param \DateTime $created
     *
     * @return LeadUpload
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
     * @return LeadUpload
     */
    public function setManager(Manager $manager)
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
}
