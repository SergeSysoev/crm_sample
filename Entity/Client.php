<?php

namespace NaxCrmBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use JMS\Serializer\Annotation\Exclude;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Client
 * @ORM\Table(name="clients",
 *    indexes={
 *      @ORM\Index(name="fk_manager", columns={"manager_id", "deleted_at"}),
 *      @ORM\Index(name="fk_department", columns={"department_id", "deleted_at"}),
 *      @ORM\Index(name="fk_country", columns={"country_id", "deleted_at"}),
 *      @ORM\Index(name="fk_status", columns={"status_id", "deleted_at"}),
 *      @ORM\Index(name="fk_sale_status", columns={"sale_status_id", "deleted_at"}),
 *      @ORM\Index(name="fk_assignment", columns={"assignment_id", "deleted_at"})
 *    },
 *    uniqueConstraints={
 *      @ORM\UniqueConstraint(name="uni_email", columns={"email", "deleted_at"}),
 *      @ORM\UniqueConstraint(name="uni_phone", columns={"phone", "deleted_at"})
 *    }
 * )
 * @ORM\Entity(repositoryClass="NaxCrmBundle\Repository\ClientRepository")
 * @Gedmo\SoftDeleteable(fieldName="deletedAt", timeAware=false)
 */
class Client extends BaseEntity implements UserInterface
{
    const GENDER_MALE       = 1;
    const GENDER_FEMALE     = 2;
    const GENDER_UNKNOWN    = 3;

    const STATUS_UNBLOCKED          = 1;
    const STATUS_BLOCKED            = 2;
    const STATUS_TRADING_BLOCKED    = 3;

    /**
     * @Exclude
     */
    public static $Genders = [
        self::GENDER_MALE => 'Male',
        self::GENDER_FEMALE => 'Female',
        self::GENDER_UNKNOWN => 'Unknown',
    ];

    /**
     * @Exclude
     */
    public static $Statuses = [
        self::STATUS_UNBLOCKED => 'Unblocked',
        self::STATUS_BLOCKED => 'Blocked',
        self::STATUS_TRADING_BLOCKED => 'Trading blocked',
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
     * @Groups({"group1"})
     *
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
     * @Assert\NotBlank
     */
    protected $email;

    /**
     * @var string
     *
     * @ORM\Column(name="phone", type="string", length=255, nullable=true)
     */
    protected $phone = null;

    /**
     * @var string
     *
     * @ORM\Column(name="password", type="string", length=255)
     * @Exclude
     */
    protected $password;

    /**
     * @ORM\ManyToOne(targetEntity="NaxCrmBundle\Entity\Department", inversedBy="clients")
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
     * @ORM\ManyToOne(targetEntity="NaxCrmBundle\Entity\Manager", inversedBy="clients")
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
     * @var \DateTime
     *
     * @ORM\Column(name="birthday", type="date", nullable=true)
     */
    protected $birthday;

    /**
     * @var int
     *
     * @ORM\Column(name="gender", type="integer")
     */
    protected $gender;

    /**
     * @var string
     *
     * @ORM\Column(name="state", type="string", length=255, nullable=true)
     */
    protected $state;

    /**
     * @var string
     *
     * @ORM\Column(name="city", type="string", length=255, nullable=true)
     */
    protected $city;

    /**
     * @var string
     *
     * @ORM\Column(name="address", type="string", length=255, nullable=true)
     */
    protected $address;

    /**
     * @var string
     *
     * @ORM\Column(name="zip", type="string", length=63, nullable=true)
     */
    protected $zip;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="last_login", type="datetime", nullable=true)
     */
    protected $lastLogin;

    /**
     * @ORM\ManyToOne(targetEntity="NaxCrmBundle\Entity\Country")
     * @ORM\JoinColumn(name="country_id", referencedColumnName="id", nullable=true)
     */
    protected $country;

    /**
     * @ORM\ManyToOne(targetEntity="NaxCrmBundle\Entity\SaleStatus", inversedBy="clients")
     * @ORM\JoinColumn(name="sale_status_id", referencedColumnName="id", nullable=true)
     */
    protected $saleStatus;
    /**
     * @var int
     *
     * @ORM\Column(name="sale_status_id", type="integer", nullable=true)
     */
    protected $saleStatusId;

    /**
     * @ORM\OneToMany(targetEntity="NaxCrmBundle\Entity\Comment", mappedBy="client")
     * @ORM\OrderBy({"created" = "DESC"})
     * @Exclude
     */
    protected $comments;

    /**
     * @ORM\OneToMany(targetEntity="NaxCrmBundle\Entity\Document", mappedBy="client")
     * @Exclude
     */
    protected $documents;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @Exclude
     */
    protected $deletedAt;

    /**
     * @var string
     *
     * @ORM\Column(name="langcode", type="string", length=2)
     */
    protected $langcode = 'en';

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created", type="datetime")
     */
    protected $created;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_email_confirmed", type="boolean")
     */
    protected $isEmailConfirmed;

    /**
     * @ORM\OneToMany(targetEntity="NaxCrmBundle\Entity\Account", mappedBy="client")
     */
    protected $accounts;

    /**
     * @ORM\OneToMany(targetEntity="NaxCrmBundle\Entity\Deposit", mappedBy="client")
     */
    protected $deposits;

    /**
     * @ORM\OneToMany(targetEntity="NaxCrmBundle\Entity\Withdrawal", mappedBy="client")
     */
    protected $withdrawals;

    /**
     * @ORM\OneToMany(targetEntity="NaxCrmBundle\Entity\Order", mappedBy="client")
     */
    protected $orders;

    /**
     * @ORM\ManyToOne(targetEntity="NaxCrmBundle\Entity\Assignment", inversedBy="clients")
     * @ORM\JoinColumn(name="assignment_id", referencedColumnName="id", nullable=true)
     */
    protected $assignment;

    /**
     * @var int
     *
     * @ORM\Column(name="status_id", type="integer", nullable=false)
     */
    protected $statusId = 1;

    /**
     * @var string
     *
     * @ORM\Column(name="one_time_jwt", type="string", length=1023, nullable=true)
     */
    protected $oneTimeJwt;

    /**
     * @var string
     *
     * @ORM\Column(name="source", type="string", length=255, nullable=true)
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
     * @var \DateTime
     *
     * @ORM\Column(name="sale_status_update", type="datetime", nullable=true)
     */
    protected $saleStatusUpdate;

    /**
     * Client constructor.
     */
    public function __construct()
    {
        $this->created          = new \DateTime('now');
        $this->comments         = new ArrayCollection();
        $this->documents        = new ArrayCollection();
        $this->deposits         = new ArrayCollection();
        $this->accounts         = new ArrayCollection();
        $this->withdrawals      = new ArrayCollection();
        $this->orders           = new ArrayCollection();
        $this->gender           = self::GENDER_UNKNOWN;
        $this->statusId         = self::STATUS_UNBLOCKED;
        $this->isEmailConfirmed = false;
        $this->saleStatusUpdate   = null;
    }

    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $class = self::class();
        $data = $class::getUniqueData();
        foreach ($data as $key => $data) {
            $metadata->addConstraint(new UniqueEntity($data));
        }
    }
    protected static function getUniqueData()
    {
        $data = [];
        $data['email']=[
            'fields'    => ['email', 'deletedAt'],
            'message'   => 'This email is already in use.',
            'ignoreNull' => false,
        ];
        $data['phone']=[
            'fields'    => ['phone', 'deletedAt'],
            'message'   => 'This phone is already in use.',
            'ignoreNull' => false,
        ];
        return $data;
    }

    public function getUsername()
    {
        return $this->email;
    }

    public function getSalt()
    {
        return null;
    }

    public function getRoles()
    {
        return [
            'ROLE_CLIENT'
        ];
    }

    public function eraseCredentials()
    {
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
     * Set firstname
     *
     * @param string $firstname
     *
     * @return Client
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
     *
     * @return Client
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

    public function getFullName()
    {
        return trim("$this->firstname $this->lastname");
    }

    /**
     * Set email
     *
     * @param string $email
     *
     * @return Client
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
     *
     * @return Client
     */
    public function setPhone($phone)
    {
        $phone = trim($phone);
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
     * Set password
     *
     * @param string $password
     *
     * @return Client
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Get password
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Set birthday
     *
     * @param \DateTime $birthday
     *
     * @return Client
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
     * Set gender
     *
     * @param integer $gender
     *
     * @return Client
     */
    public function setGender($gender)
    {
        $Genders = array_keys(self::$Genders);
        $this->gender = in_array($gender, $Genders)?
            $gender:
            $Genders[0];

        return $this;
    }

    /**
     * Get gender
     *
     * @return int
     */
    public function getGender()
    {
        return $this->gender;
    }

    /**
     * Set state
     *
     * @param string $state
     *
     * @return Client
     */
    public function setState($state)
    {
        $this->state = $state;

        return $this;
    }

    /**
     * Get state
     *
     * @return string
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * Set city
     *
     * @param string $city
     *
     * @return Client
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
     * Set address
     *
     * @param string $address
     *
     * @return Client
     */
    public function setAddress($address)
    {
        $this->address = $address;

        return $this;
    }

    /**
     * Get address
     *
     * @return string
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * Set zip
     *
     * @param string $zip
     *
     * @return Client
     */
    public function setZip($zip)
    {
        $this->zip = $zip;

        return $this;
    }

    /**
     * Get zip
     *
     * @return string
     */
    public function getZip()
    {
        return $this->zip;
    }

    /**
     * Set lastLogin
     *
     * @param \DateTime $lastLogin
     *
     * @return Client
     */
    public function setLastLogin($lastLogin)
    {
        $this->lastLogin = $lastLogin;

        return $this;
    }

    /**
     * Get lastLogin
     *
     * @return \DateTime
     */
    public function getLastLogin()
    {
        return $this->lastLogin;
    }

    /**
     * Set country
     *
     * @param Country $country
     *
     * @return Client
     */
    public function setCountry($country = null)
    {
        if(!($country instanceof Country)){
            $country = null;
        }
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
     * @return Client
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
     * Add comment
     *
     * @param Comment $comment
     *
     * @return Client
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
     * Set deletedAt
     *
     * @param \DateTime $deletedAt
     *
     * @return Client
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
     * Add document
     *
     * @param Document $document
     *
     * @return Client
     */
    public function addDocument(Document $document)
    {
        $this->documents[] = $document;

        return $this;
    }

    /**
     * Remove document
     *
     * @param Document $document
     */
    public function removeDocument(Document $document)
    {
        $this->documents->removeElement($document);
    }

    /**
     * Get documents
     *
     * @return ArrayCollection
     */
    public function getDocuments()
    {
        return $this->documents;
    }

    /**
     * Set langcode
     *
     * @param string $langcode
     *
     * @return Client
     */
    public function setLangcode($langcode)
    {
        if (!$langcode) {
            $langcode = empty($this->country) ? 'en' : $this->country->getLangCode();
        }
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
     * Set created
     *
     * @param \DateTime $created
     *
     * @return Client
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
     * Set isEmailConfirmed
     *
     * @param boolean $isEmailConfirmed
     *
     * @return Client
     */
    public function setIsEmailConfirmed($isEmailConfirmed)
    {
        $this->isEmailConfirmed = boolval($isEmailConfirmed);

        return $this;
    }

    /**
     * Get isEmailConfirmed
     *
     * @return boolean
     */
    public function getIsEmailConfirmed()
    {
        return (bool)$this->isEmailConfirmed;
    }

    /**
     * Set manager
     *
     * @param Manager $manager
     *
     * @return Client
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
     * @return Client
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
     * @return Client
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
     * @return Client
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
     * @return Client
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
     * Add account
     *
     * @param Account $account
     *
     * @return Client
     */
    public function addAccount(Account $account)
    {
        $this->accounts[] = $account;

        return $this;
    }

    /**
     * Remove account
     *
     * @param Account $account
     */
    public function removeAccount(Account $account)
    {
        $this->accounts->removeElement($account);
    }

    /**
     * Get accounts
     *
     * @return ArrayCollection|Account[]
     */
    public function getAccounts()
    {
        return $this->accounts;
    }

    /**
     * Add deposit
     *
     * @param Deposit $deposit
     *
     * @return Client
     */
    public function addDeposit(Deposit $deposit)
    {
        $this->deposits[] = $deposit;

        return $this;
    }

    /**
     * Remove deposit
     *
     * @param Deposit $deposit
     */
    public function removeDeposit(Deposit $deposit)
    {
        $this->deposits->removeElement($deposit);
    }

    /**
     * Get deposits
     *
     * @return ArrayCollection|Deposit[]
     */
    public function getDeposits()
    {
        return $this->deposits;
    }

    /**
     * Add order
     *
     * @param Order $order
     *
     * @return Client
     */
    public function addOrder(Order $order)
    {
        $this->orders[] = $order;

        return $this;
    }

    /**
     * Remove order
     *
     * @param Order $order
     */
    public function removeOrder(Order $order)
    {
        $this->orders->removeElement($order);
    }

    /**
     * Get orders
     *
     * @return ArrayCollection
     */
    public function getOrders()
    {
        return $this->orders;
    }

    /**
     * Set assignment
     *
     * @param Assignment $assignment
     *
     * @return Client
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
     * Set status
     *
     * @param int $statusId
     *
     * @return Client
     */
    public function setStatus(int $statusId)
    {
        $Statuses = array_keys(self::$Statuses);
        $this->statusId = in_array($statusId, $Statuses)?
            $statusId:
            $Statuses[0];

        return $this;
    }

    /**
     * Get status
     *
     * @return int
     */
    public function getStatus()
    {
        return $this->statusId;
    }

    public function isBlocked()
    {
        return $this->getStatus() == Client::STATUS_BLOCKED;
    }

    public function isTradingBlocked()
    {
        return $this->getStatus() == Client::STATUS_TRADING_BLOCKED;
    }

    /**
     * Add withdrawal
     *
     * @param Withdrawal $withdrawal
     *
     * @return Client
     */
    public function addWithdrawal(Withdrawal $withdrawal)
    {
        $this->withdrawals[] = $withdrawal;

        return $this;
    }

    /**
     * Remove withdrawal
     *
     * @param Withdrawal $withdrawal
     */
    public function removeWithdrawal(Withdrawal $withdrawal)
    {
        $this->withdrawals->removeElement($withdrawal);
    }

    /**
     * Get withdrawals
     *
     * @return ArrayCollection
     */
    public function getWithdrawals()
    {
        return $this->withdrawals;
    }

    /**
     * @param string $oneTimeJwt
     */
    public function setOneTimeJwt($oneTimeJwt)
    {
        $this->oneTimeJwt = $oneTimeJwt;
    }
    /**
     * @return string
     */
    public function getOneTimeJwt()
    {
        return $this->oneTimeJwt;
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

    public function export()
    {
        return [
            'id'                    => $this->getId(),
            'firstname'             => $this->getFirstname(),
            'lastname'              => $this->getLastname(),
            'email'                 => $this->getEmail(),
            'phone'                 => $this->getPhone(),
            'langcode'              => $this->getLangcode(),
            'departmentId'          => $this->getDepartment() ? $this->getDepartment()->getId() : '',
            'managerId'             => $this->getManager() ? $this->getManager()->getId() : '',
            'manager'               => $this->getManager() ? $this->getManager()->getName() : '',
            'birthday'              => $this->getBirthday() ? $this->getBirthday()->format('Y-m-d') : '',
            'status'                => $this->getStatus(),
            'gender'                => $this->getGender(),
            'city'                  => $this->getCity() ? $this->getCity() : '',
            'state'                 => $this->getState() ? $this->getState() : '',
            'address'               => $this->getAddress() ? $this->getAddress() : '',
            'zip'                   => $this->getZip() ? $this->getZip() : '',
            'country'               => $this->getCountry() ? $this->getCountry()->getName() : '',
            'countryCode'           => $this->getCountry() ? $this->getCountry()->getCode() : '',
            'saleStatus'            => $this->getSaleStatus() ? $this->getSaleStatus()->getId() : '',
            'saleStatusUpdate'      => $this->getSaleStatusUpdate() ? $this->getSaleStatusUpdate()->format('Y-m-d H:i:s') : '',
            'lastLogin'             => $this->getLastLogin() ? $this->getLastLogin()->format('Y-m-d H:i:s') : '',
            'created'               => $this->getCreated() ? $this->getCreated()->format('Y-m-d H:i:s') : '',
            'isEmailConfirmed'      => $this->getIsEmailConfirmed() ? $this->getIsEmailConfirmed() : false,
            'source'                => $this->getSource(),
            'platform'              => $this->getPlatform(),
            'externalId'            => $this->getExternalId(),
        ];
    }
}
