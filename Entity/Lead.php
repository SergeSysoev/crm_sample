<?php

namespace NaxCrmBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation\Exclude;
use NaxCrmBundle\Entity\Assignment;
use NaxCrmBundle\Entity\Country;
use NaxCrmBundle\Entity\Comment;
use NaxCrmBundle\Entity\Department;
use NaxCrmBundle\Entity\SaleStatus;


/**
 * Lead
 *
 * @ORM\Table(name="leads",
 *    uniqueConstraints={
 *      @ORM\UniqueConstraint(name="fk_unique_email", columns={"email"}),
 *      @ORM\UniqueConstraint(name="fk_unique_phone", columns={"phone"})
 *  })
 * @ORM\Entity(repositoryClass="NaxCrmBundle\Repository\LeadRepository")
 * @Gedmo\SoftDeleteable(fieldName="deletedAt", timeAware=false)
 * @UniqueEntity(fields={"email"}, message="This email is already in use.")
 * @UniqueEntity(fields={"phone"}, message="This phone is already in use.")
 */
class Lead extends BaseEntity
{

    /**
     * @var array
     * @Exclude
     */
    public static $requiredFields = [
        'firstname',
        'lastname',
        'email',
        'phone',
        'country',
//        'langcode',
//        'birthday'
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
     * @ORM\Column(name="firstname", type="string", length=255)
     */
    protected $firstname;

    /**
     * @var string
     *
     * @ORM\Column(name="lastname", type="string", length=255)
     */
    protected $lastname;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=255)
     * @Assert\Email
     */
    protected $email;

    /**
     * @var string
     *
     * @ORM\Column(name="phone", type="string", length=63)
     */
    protected $phone;

    /**
     * @var string
     *
     * @ORM\Column(name="city", type="string", length=63, nullable=true)
     */
    protected $city;

    /**
     * @var string
     *
     * @ORM\Column(name="langcode", type="string", length=2)
     * @Exclude
     */
    protected $langcode = 'en';

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="birthday", type="date", nullable=true)
     * @Exclude
     */
    protected $birthday;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="last_note_time", type="datetime", nullable=true)
     */
    protected $lastNoteTime;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created", type="datetime")
     */
    protected $created;

    /**
     * @var string
     *
     * @ORM\Column(name="comment", type="string", length=255, nullable=true)
     */
    protected $comment;

    /**
     * @var string
     *
     * @ORM\Column(name="note1", type="string", length=255, nullable=true)
     */
    protected $note1;

    /**
     * @var string
     *
     * @ORM\Column(name="note2", type="string", length=255, nullable=true)
     */
    protected $note2;

    /**
     * @ORM\ManyToOne(targetEntity="NaxCrmBundle\Entity\Department", inversedBy="leads")
     * @ORM\JoinColumn(name="department_id", referencedColumnName="id", nullable=true)
     */
    protected $department;

    /**
     * @var int
     *
     * @ORM\Column(name="department_id", type="integer", nullable=true)
     */
    protected $departmentId;

    /**
     * @ORM\ManyToOne(targetEntity="NaxCrmBundle\Entity\Manager", inversedBy="clients", inversedBy="leads")
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
     * @ORM\ManyToOne(targetEntity="NaxCrmBundle\Entity\SaleStatus", inversedBy="leads")
     * @ORM\JoinColumn(name="sale_status_id", referencedColumnName="id", nullable=true)
     * @Exclude
     */
    protected $saleStatus;

    /**
     * @var int
     *
     * @ORM\Column(name="sale_status_id", type="integer", nullable=true)
     */
    protected $saleStatusId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="sale_status_update", type="datetime", nullable=true)
     */
    protected $saleStatusUpdate;

    /**
     * @ORM\ManyToOne(targetEntity="NaxCrmBundle\Entity\Country")
     * @ORM\JoinColumn(name="country_id", referencedColumnName="id", nullable=false)
     */
    protected $country;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @Exclude
     */
    protected $deletedAt;

    /**
     * @ORM\OneToMany(targetEntity="NaxCrmBundle\Entity\Comment", mappedBy="lead")
     * @ORM\OrderBy({"created" = "DESC"})
     * @Exclude
     */
    protected $comments;

    /**
     * @ORM\ManyToOne(targetEntity="NaxCrmBundle\Entity\Assignment", inversedBy="leads")
     * @ORM\JoinColumn(name="assignment_id", referencedColumnName="id", nullable=true)
     */
    protected $assignment;

    /**
     * @ORM\ManyToOne(targetEntity="NaxCrmBundle\Entity\LeadUpload")
     * @ORM\JoinColumn(name="lead_upload_id", referencedColumnName="id", nullable=true)
     */
    protected $upload;


    /**
     * @var string
     *
     * @ORM\Column(name="source", type="string", length=50, nullable=true)
     */
    protected $source;

    /**
     * @var string
     * @ORM\Column(name="platform", type="string", length=20, nullable=true)
     */
    protected $platform;
    /**
     * @var integer
     * @ORM\Column(name="external_id", type="integer", nullable=true)
     */
    protected $externalId;


    /**
     * Lead constructor.
     */
    public function __construct()
    {
        $this->created      = new \DateTime('now');
        $this->comments     = new ArrayCollection();
        $this->saleStatusUpdate   = null;
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
     * Set firstname
     *
     * @param string $firstname
     * @return Lead
     */
    public function setFirstname($firstname)
    {
        $this->firstname = $firstname;

        return $this;
    }

    /**
     * Get firstname
     *
     * @return string
     */
    public function getFirstname()
    {
        return $this->firstname;
    }

    /**
     * Set lastname
     *
     * @param string $lastname
     * @return Lead
     */
    public function setLastname($lastname)
    {
        $this->lastname = $lastname;

        return $this;
    }

    /**
     * Get lastname
     *
     * @return string
     */
    public function getLastname()
    {
        return $this->lastname;
    }

    /**
     * Set email
     *
     * @param string $email
     * @return Lead
     */
    public function setEmail($email)
    {
        $email = trim($email);
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
     * Set phone
     *
     * @param string $phone
     * @return Lead
     */
    public function setPhone($phone)
    {
        $phone = preg_replace("/[^0-9]/", '', $phone);
        $this->phone = $phone;

        return $this;
    }

    /**
     * Get phone
     *
     * @return string
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * Set city
     *
     * @param string $city
     * @return Lead
     */
    public function setCity($city)
    {
        $this->city = $city;

        return $this;
    }

    /**
     * Get city
     *
     * @return string
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * Set langcode
     *
     * @param string $langcode
     * @return Lead
     */
    public function setLangcode($langcode)
    {
        $this->langcode = strtolower($langcode);

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
     * Set birthday
     *
     * @param \DateTime $birthday
     * @return Lead
     */
    public function setBirthday($birthday)
    {
        if(!($birthday instanceof \DateTime)){
            $birthday = \DateTime::createFromFormat('Y-m-d', $birthday);
        }
        if(empty($birthday)){
            $birthday = null;
        }
        $this->birthday = $birthday;

        return $this;
    }

    /**
     * Get birthday
     *
     * @return \DateTime
     */
    public function getBirthday()
    {
        return $this->birthday;
    }

    /**
     * Set lastNoteTime
     *
     * @param \DateTime $lastNoteTime
     * @return Lead
     */
    public function setLastNoteTime($lastNoteTime)
    {
        $this->lastNoteTime = $lastNoteTime;

        return $this;
    }

    /**
     * Get lastNoteTime
     *
     * @return \DateTime
     */
    public function getLastNoteTime()
    {
        return $this->lastNoteTime;
    }

    /**
     * Set created
     *
     * @param \DateTime $created
     * @return Lead
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
     * Set comment
     *
     * @param string $comment
     * @return Lead
     */
    public function setComment($comment)
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * Get comment
     *
     * @return string
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * Set note1
     *
     * @param string $note1
     * @return Lead
     */
    public function setNote1($note1)
    {
        $this->note1 = $note1;

        return $this;
    }
    /**
     * Get note1
     *
     * @return string
     */
    public function getNote1()
    {
        return $this->note1;
    }

    /**
     * Set note2
     *
     * @param string $note2
     * @return Lead
     */
    public function setNote2($note2)
    {
        $this->note2 = $note2;

        return $this;
    }

    /**
     * Get note2
     *
     * @return string
     */
    public function getNote2()
    {
        return $this->note2;
    }

    /**
     * Get saleStatus
     *
     * @return SaleStatus
     */
    public function getSaleStatus()
    {
        return $this->saleStatus;
    }
    /**
     * Set saleStatus
     *
     * @param SaleStatus $saleStatus
     *
     * @return Lead
     */
    public function setSaleStatus($saleStatus = null)
    {
        if(!($saleStatus instanceof SaleStatus)){
            $saleStatus = null;
        }

        if($saleStatus != $this->saleStatus){
            $this->saleStatusUpdate = new \DateTime('now');
        }

        $this->saleStatus = $saleStatus;

        return $this;
    }

    /**
     * Get saleStatusUpdate
     *
     * @return \DateTime
     */
    public function getSaleStatusUpdate()
    {
        return $this->saleStatusUpdate;
    }

    /**
     * Set country
     *
     * @param Country $country
     *
     * @return Lead
     */
    public function setCountry(Country $country)
    {
        $this->country = $country;

        return $this;
    }

    /**
     * Get country
     *
     * @return Country
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * Set deletedAt
     *
     * @param \DateTime $deletedAt
     *
     * @return Lead
     */
    public function setDeletedAt($deletedAt)
    {
        $this->deletedAt = $deletedAt;

        return $this;
    }

    /**
     * Get deletedAt
     *
     * @return \DateTime
     */
    public function getDeletedAt()
    {
        return $this->deletedAt;
    }

    /**
     * Add comment
     *
     * @param Comment $comment
     *
     * @return Lead
     */
    public function addComment(Comment $comment)
    {
        $this->comments[] = $comment;

        return $this;
    }

    /**
     * Remove comment
     *
     * @param Comment $comment
     */
    public function removeComment(Comment $comment)
    {
        $this->comments->removeElement($comment);
    }

    /**
     * Get comments
     *
     * @return ArrayCollection
     */
    public function getComments()
    {
        return $this->comments;
    }

    /**
     * Set manager
     *
     * @param Manager $manager
     *
     * @return Lead
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
     * Set department
     *
     * @param Department $department
     *
     * @return Lead
     */
    public function setDepartment(Department $department = null)
    {
        $this->department = $department;

        return $this;
    }

    /**
     * Get department
     *
     * @return Department
     */
    public function getDepartment()
    {
        return $this->department;
    }

    /**
     * Set departmentId
     *
     * @param integer $departmentId
     *
     * @return Lead
     */
    public function setDepartmentId($departmentId)
    {
        $this->departmentId = $departmentId;

        return $this;
    }

    /**
     * Get departmentId
     *
     * @return integer
     */
    public function getDepartmentId()
    {
        return $this->departmentId;
    }

    /**
     * Set managerId
     *
     * @param integer $managerId
     *
     * @return Lead
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
     * Set saleStatusId
     *
     * @param integer $saleStatusId
     *
     * @return Lead
     */
    public function setSaleStatusId($saleStatusId)
    {
        $this->saleStatusId = $saleStatusId;

        return $this;
    }
    /**
     * Get saleStatusId
     *
     * @return integer
     */
    public function getSaleStatusId()
    {
        return $this->saleStatusId;
    }

    /**
     * Set assignment
     *
     * @param Assignment $assignment
     *
     * @return Lead
     */
    public function setAssignment(Assignment $assignment = null)
    {
        $this->assignment = $assignment;

        return $this;
    }

    /**
     * Get assignment
     *
     * @return Assignment
     */
    public function getAssignment()
    {
        return $this->assignment;
    }

    /**
     * Set upload
     *
     * @param LeadUpload $upload
     *
     * @return Lead
     */
    public function setUpload(LeadUpload $upload = null)
    {
        $this->upload = $upload;

        return $this;
    }

    /**
     * Get upload
     *
     * @return LeadUpload
     */
    public function getUpload()
    {
        return $this->upload;
    }

    /**
     * @param string $source
     */
    public function setSource($source)
    {
        if($source){
            $source = mb_substr($source, 0, 50);
        }
        $this->source = $source;
    }
    /**
     * @return string
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @return string
     */
    public function getPlatform()
    {
        return $this->platform;
    }
    /**
     * @param string $platform
     * @return Lead
     */
    public function setPlatform($platform)
    {
        $this->platform = $platform;
        return $this;
    }

    /**
     * @return int
     */
    public function getExternalId()
    {
        return $this->externalId;
    }
    /**
     * @param int $externalId
     * @return Lead
     */
    public function setExternalId($externalId)
    {
        $this->externalId = $externalId;
        return $this;
    }

    public function export()
    {
        $manager = $this->getManager();
        return [
            'id'                => $this->getId(),
            'firstname'         => $this->getFirstname(),
            'lastname'          => $this->getLastname(),
            'email'             => $this->getEmail(),
            'phone'             => $this->getPhone(),
            'langcode'          => $this->getLangcode(),
            'departmentId'      => $this->getDepartment() ? $this->getDepartment()->getId() : '',
            'managerId'         => $manager ? $manager->getId() : '',
            'manager'           => $manager ? $manager->getName() : '',
            'birthday'          => $this->getBirthday() ? $this->getBirthday()->format('Y-m-d') : '',
            'city'              => $this->getCity() ? $this->getCity() : '',
            'country'           => $this->getCountry()->getName(),
            'countryCode'       => $this->getCountry()->getCode(),
            'saleStatus'        => $this->getSaleStatus() ? $this->getSaleStatus()->getId() : '',
            'saleStatusUpdate'  => $this->getSaleStatusUpdate() ? $this->getSaleStatusUpdate()->format('Y-m-d H:i:s') : '',
            'created'           => $this->getCreated()->format('Y-m-d H:i:s'),
            'note1'             => $this->getNote1(),
            'note2'             => $this->getNote2(),
            'source'            => $this->getSource(),
            'platform'          => $this->getPlatform(),
            'externalId'        => $this->getExternalId(),
        ];
    }
}
