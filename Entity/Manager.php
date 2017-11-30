<?php

namespace NaxCrmBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use JMS\Serializer\Annotation\Exclude;
use Gedmo\Mapping\Annotation as Gedmo;

use NaxCrmBundle\Security\PermissionVoter;

/**
 * Manager
 *
 * @ORM\Table(name="managers")
 * @ORM\Entity(repositoryClass="NaxCrmBundle\Repository\ManagerRepository")
 * @UniqueEntity(
 *     fields={"email"},
 *     message="This email is already in use.",
 *     ignoreNull=false
 * )
 */
class Manager extends BaseEntity implements UserInterface
{
    protected $accessibleDepartmentIds = [];

    /**
     * @return array
     */
    public function getAccessibleDepartmentIds()
    {
        return $this->accessibleDepartmentIds;
    }

    /**
     * @param array $accessibleDepartmentIds
     */
    public function setAccessibleDepartmentIds($accessibleDepartmentIds)
    {
        $this->accessibleDepartmentIds = $accessibleDepartmentIds;
    }

    protected $resourcePermissions = [];

    /**
     * @return array
     */
    public function getResourcePermissions()
    {
        return $this->resourcePermissions;
    }

    /**
     * @param array $resourcePermissions
     */
    public function setResourcePermissions($resourcePermissions)
    {
        $this->resourcePermissions = $resourcePermissions;
    }

    /**
     * @var array
     * @Exclude
     */
    public static $requiredFields = [
        'name',
        // 'skype',
        'email',
        'phone',
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
     * @Assert\NotBlank()
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    protected $name;

    /**
     * @var string
     * @Assert\NotBlank()
     * @Assert\Email()
     *
     * @ORM\Column(name="email", type="string", length=255)
     */
    protected $email;

    /**
     * @var string
     * @Assert\NotBlank()
     *
     * @ORM\Column(name="phone", type="string", length=30)
     */
    protected $phone;

    /**
     * @var string
     *
     * @ORM\Column(name="skype", type="string", length=255)
     */
    protected $skype = '';

    /**
     * @var string
     *
     * @ORM\Column(name="password", type="string", length=255)
     * @Exclude
     */
    protected $password;

    /**
     * @var Department
     * @ORM\ManyToOne(targetEntity="NaxCrmBundle\Entity\Department", inversedBy="managers")
     * @ORM\JoinColumn(name="department_id", referencedColumnName="id", nullable=false)
     * @Exclude
     */
    protected $department;
    /**
     * @var integer
     * @ORM\Column(name="department_id", type="integer")
     */
    protected $departmentId;
    /**
     * @var bool
     *
     * @ORM\Column(name="is_head", type="boolean")
     */
    protected $isHead = true;

    /**
     * @var string
     *
     * @ORM\Column(name="secret2fa", type="string", length=16, nullable=true)
     * @Exclude
     */
    protected $secret2fa;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="last_logged_at", type="datetime", nullable=true)
     */
    protected $lastLoggedAt;

    /**
     * @var \DateTime
     * @ORM\Column(type="datetime", nullable=true)
     * @Exclude
     */
    protected $deletedAt;


    /**
     * @var \DateTime
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(name="created", type="datetime")
     */
    protected $created;

    /**
     * @var AclRoles[]
     *
     * @ORM\ManyToMany(targetEntity="NaxCrmBundle\Entity\AclRoles", inversedBy="managers")
     * @ORM\JoinTable(name="acl_roles_manager",
     *      joinColumns={@ORM\JoinColumn(name="manager_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="acl_roles_id", referencedColumnName="id")}
     * )
     */
    protected $aclRoles;

    /**
     * @ORM\OneToMany(targetEntity="NaxCrmBundle\Entity\Event", mappedBy="manager")
     */
    protected $events;

    /**
     * @ORM\OneToMany(targetEntity="NaxCrmBundle\Entity\UserLogItem", mappedBy="manager")
     */
    protected $userLogItem;

    /**
     * @ORM\OneToMany(targetEntity="NaxCrmBundle\Entity\Client", mappedBy="manager")
     */
    protected $clients;

    /**
     * @ORM\OneToMany(targetEntity="NaxCrmBundle\Entity\Comment", mappedBy="manager")
     */
    protected $comments;

    /**
     * @ORM\OneToMany(targetEntity="NaxCrmBundle\Entity\Document", mappedBy="manager")
     */
    protected $documents;

    /**
     * @ORM\OneToMany(targetEntity="NaxCrmBundle\Entity\Bookmark", mappedBy="manager")
     */
    protected $bookmarks;

    /**
     * @ORM\OneToMany(targetEntity="NaxCrmBundle\Entity\Lead", mappedBy="manager")
     */
    protected $leads;

    /**
     * @ORM\OneToMany(targetEntity="NaxCrmBundle\Entity\Assignment", mappedBy="manager")
     */
    protected $assignments;

    /**
     * @ORM\OneToMany(targetEntity="NaxCrmBundle\Entity\LeadUpload", mappedBy="manager")
     */
    protected $leadUploads;

    /**
     * @var bool
     *
     * @ORM\Column(name="re_login", type="boolean", options={"default" : 0})
     */
    protected $recalculateJwt = false;

    /**
     * @var bool
     *
     * @ORM\Column(name="can_reset_pswd", type="boolean", options={"default" : 0})
     */
    protected $canResetPswd = false;

    /**
     * @var bool
     *
     * @ORM\Column(name="blocked", type="boolean", options={"default" : 0})
     */
    protected $blocked = false;

    /**
     * @var bool
     *
     * @ORM\Column(name="client_area_access", type="boolean", options={"default" : 0})
     */
    protected $clientAreaAccess = false;

    /**
     * @var string
     *
     * @ORM\Column(name="platform", type="string", options={"default" : ""})
     */
    protected $platform = false;

    public function __construct()
    {
        $this->created          = new \DateTime();
        $this->aclRoles         = new ArrayCollection();
        $this->clients          = new ArrayCollection();
        $this->leads            = new ArrayCollection();
        $this->assignments      = new ArrayCollection();
        $this->leadUploads      = new ArrayCollection();
        $this->comments         = new ArrayCollection();
        $this->events           = new ArrayCollection();
        $this->documents        = new ArrayCollection();
        $this->recalculateJwt   = false;
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
        return $this->aclRoles;
    }

    /**
     * @return AclRoles[]
     */
    public function getAclRoles()
    {
        return $this->aclRoles;
    }

    /**
     * @param AclRoles[] $aclRoles
     * @return $this
     */
    public function setAclRoles($aclRoles)
    {
        if (!is_array($aclRoles)) $aclRoles = [$aclRoles];
        $this->aclRoles = $aclRoles;
        return $this;
    }
    /**
     * Add role
     *
     * @param AclRoles $role
     * @return $this
     */
    public function addAclRole(AclRoles $role)
    {
        //@TODO Symfony is fucking shit so it loads whole collection and can`t check it by id!!!!!!
        if (!$this->aclRoles->contains($role)){
            $this->aclRoles[] = $role;
        }

        return $this;
    }

    /**
     * Remove role
     *
     * @param AclRoles $role
     */
    public function removeAclRole(AclRoles $role)
    {
        $this->aclRoles->removeElement($role);
    }

    public function eraseCredentials()
    {
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
     * @return Manager
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
     * Set email
     *
     * @param string $email
     * @return Manager
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
     * Set phone
     *
     * @param string $phone
     * @return Manager
     */
    public function setPhone($phone)
    {
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
     * Set skype
     *
     * @param string $skype
     * @return Manager
     */
    public function setSkype($skype)
    {
        $this->skype = $skype;

        return $this;
    }

    /**
     * Get skype
     *
     * @return string
     */
    public function getSkype()
    {
        return $this->skype;
    }

    /**
     * Set password
     *
     * @param string $password
     * @return Manager
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
     * @return Department
     */
    public function getDepartment()
    {
        return $this->department;
    }

    /**
     * @return int
     */
    public function getDepartmentId()
    {
        return $this->departmentId;
    }

    /**
     * @param int $departmentId
     * @return Manager
     */
    public function setDepartmentId($departmentId)
    {
        $this->departmentId = $departmentId;
        return $this;
    }

    /**
     * @param Department $department
     * @return $this
     */
    public function setDepartment($department)
    {
        $this->department = $department;
        $this->setDepartmentId($department->getId());
        return $this;
    }

    /**
     * Set head
     *
     * @param boolean $isHead
     * @return Manager
     */
    public function setIsHead($isHead)
    {
        $this->isHead = boolval($isHead);

        return $this;
    }
    /**
     * Get head
     *
     * @return boolean
     */
    public function getIsHead()
    {
        return (bool)$this->isHead;
    }

    /**
     * @param \DateTime $lastLoggedAt
     * @return Manager
     */
    public function setLastLoggedAt($lastLoggedAt)
    {
        $this->lastLoggedAt = $lastLoggedAt;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getLastLoggedAt()
    {
        return $this->lastLoggedAt;
    }

    /**
     * Set recalculateJwt
     *
     * @param boolean $recalculateJwt
     *
     * @return Manager
     */
    public function setRecalculateJwt($recalculateJwt)
    {
        $this->recalculateJwt = boolval($recalculateJwt);

        return $this;
    }
    /**
     * Get recalculateJwt
     *
     * @return boolean
     */
    public function getRecalculateJwt()
    {
        return (bool)$this->recalculateJwt;
    }

    /**
     * Set secret2fa
     *
     * @param string $secret2fa
     *
     * @return Manager
     */
    public function setSecret2fa($secret2fa = '')
    {
        $this->secret2fa = $secret2fa;

        return $this;
    }

    /**
     * Get secret2fa
     *
     * @return string
     */
    public function getSecret2fa()
    {
        return $this->secret2fa;
    }

    /**
     * Set created
     *
     * @param \DateTime $created
     *
     * @return Manager
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
     * Add client
     *
     * @param Client $client
     *
     * @return Manager
     */
    public function addClient(Client $client)
    {
        $this->clients[] = $client;

        return $this;
    }

    /**
     * Remove client
     *
     * @param Client $client
     */
    public function removeClient(Client $client)
    {
        $this->clients->removeElement($client);
    }

    /**
     * Get clients
     *
     * @return ArrayCollection
     */
    public function getClients()
    {
        return $this->clients;
    }

    /**
     * Add document
     *
     * @param Document $document
     *
     * @return Manager
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
     * Add event
     *
     * @param Event $event
     *
     * @return Manager
     */
    public function addEvent(Event $event)
    {
        $this->events[] = $event;

        return $this;
    }

    /**
     * Remove event
     *
     * @param Event $event
     */
    public function removeEvent(Event $event)
    {
        $this->events->removeElement($event);
    }

    /**
     * Get events
     *
     * @return ArrayCollection
     */
    public function getEvents()
    {
        return $this->events;
    }

    /**
     * Add comment
     *
     * @param Comment $comment
     *
     * @return Manager
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
     * Add lead
     *
     * @param Lead $lead
     *
     * @return Manager
     */
    public function addLead(Lead $lead)
    {
        $this->leads[] = $lead;

        return $this;
    }

    /**
     * Remove lead
     *
     * @param Lead $lead
     */
    public function removeLead(Lead $lead)
    {
        $this->leads->removeElement($lead);
    }

    /**
     * Get leads
     *
     * @return ArrayCollection
     */
    public function getLeads()
    {
        return $this->leads;
    }

    /**
     * Add assignment
     *
     * @param Assignment $assignment
     *
     * @return Manager
     */
    public function addAssignment(Assignment $assignment)
    {
        $this->assignments[] = $assignment;

        return $this;
    }

    /**
     * Remove assignment
     *
     * @param Assignment $assignment
     */
    public function removeAssignment(Assignment $assignment)
    {
        $this->assignments->removeElement($assignment);
    }

    /**
     * Get assignments
     *
     * @return ArrayCollection
     */
    public function getAssignments()
    {
        return $this->assignments;
    }

    /**
     * Add leadUpload
     *
     * @param LeadUpload $leadUpload
     *
     * @return Manager
     */
    public function addLeadUpload(LeadUpload $leadUpload)
    {
        $this->leadUploads[] = $leadUpload;

        return $this;
    }

    /**
     * Remove leadUpload
     *
     * @param LeadUpload $leadUpload
     */
    public function removeLeadUpload(LeadUpload $leadUpload)
    {
        $this->leadUploads->removeElement($leadUpload);
    }

    /**
     * Get leadUploads
     *
     * @return ArrayCollection
     */
    public function getLeadUploads()
    {
        return $this->leadUploads;
    }

    /**
     * Get manager permissions
     */
    public function getManagerPermissions()
    {
        $permissions = [];
        foreach ($this->getAclRoles() as $role) {
            foreach ($role->getPrivileges() as $privilege) {
                $resourceName = $privilege->getResource()->getName();
                if (isset($permissions[$resourceName])) {
                    $permissions[$resourceName] = array_merge($permissions[$resourceName], str_split($privilege->getPrivileges()));
                } else {
                    $permissions[$resourceName] = str_split($privilege->getPrivileges());
                }
            }
        }
        return $permissions;
    }

    protected function getChildDepartmentIds(Department $department, $ids = []) {
        /* @var $childDepartment Department */
        foreach ($department->getChildren() as $childDepartment) {
            $ids[] = $childDepartment->getId();
            if (!$childDepartment->getChildren()->isEmpty()) {
                $ids = $this->getChildDepartmentIds($childDepartment, $ids);
            }
        }
        return $ids;
    }

    public function hasAccessToDepartment(Department $department) {
        return in_array($department->getId(), $this->getAccessibleDepartmentIds());
    }

    /**
     * @param Client $client
     * @return bool
     */
    public function hasAccessToClient($client) {
        return empty($client) || $this->getId() == $client->getManagerId() || in_array($client->getDepartmentId(), $this->getAccessibleDepartmentIds());
    }

    public function hasAccessToDocument(Document $document)
    {
        return $this->hasAccessToClient($document->getClient());
    }

    public function hasAccessToManager(Manager $manager) {
        return in_array($manager->getDepartmentId(), $this->getAccessibleDepartmentIds());
    }

    public function isGranted($attribute, $subject)
    {
        return PermissionVoter::voteOnUserAttribute($attribute, $subject, $this);
    }


    /**
     * @param Lead $lead
     * @return bool
     */
    public function hasAccessToLead(Lead $lead)
    {
        return $this->getId() == $lead->getManagerId() || in_array($lead->getDepartmentId(), $this->getAccessibleDepartmentIds()) || is_null($lead->getDepartmentId());
    }

    /**
     * Set can reset password
     *
     * @param boolean $canResetPswd
     * @return Manager
     */
    public function setCanResetPswd($canResetPswd)
    {
        $this->canResetPswd = boolval($canResetPswd);

        return $this;
    }
    /**
     * Get is can reset password
     *
     * @return boolean
     */
    public function getCanResetPswd()
    {
        return (bool)$this->canResetPswd;
    }

    /**
     * Set blocked
     *
     * @param boolean $blocked
     * @return Manager
     */
    public function setBlocked($blocked)
    {
        $this->blocked = boolval($blocked);

        return $this;
    }
    /**
     * Get blocked
     *
     * @return boolean
     */
    public function getBlocked()
    {
        return (bool)$this->blocked;
    }

    /**
     * Set access to client area
     *
     * @param boolean $clientAreaAccess
     * @return Manager
     */
    public function setClientAreaAccess($clientAreaAccess)
    {
        $this->clientAreaAccess = boolval($clientAreaAccess);

        return $this;
    }
    /**
     * Get access to client area
     *
     * @return boolean
     */
    public function getClientAreaAccess()
    {
        return (bool)$this->clientAreaAccess;
    }

    /**
     * Get manager's platform
     *
     * @return string
     */
    public function getPlatform()
    {
        return $this->platform;
    }

    /**
     * Set manager's platform
     *
     * @param string $platform
     * @return string
     */
    public function setPlatform($platform)
    {
        $this->platform = $platform;
        return $this;
    }

    public function export()
    {
        $dep = $this->getDepartment();
        return [
            'id'                => $this->getId(),
            'name'              => $this->getName(),
            'email'             => $this->getEmail(),
            'phone'             => $this->getPhone(),
            'skype'             => $this->getSkype(),
            'isHead'            => $this->getIsHead(),
            'blocked'           => $this->getBlocked(),
            'canResetPswd'      => $this->getCanResetPswd(),
            'clientAreaAccess'  => $this->getClientAreaAccess(),
            'created'           => $this->getCreated()? $this->getCreated()->format('Y-m-d H:i:s') : '',
            'departmentId'      => $this->getDepartmentId(),
            'departmentName'    => !empty($dep)? $dep->getName() : '',
            'aclRoles'          => array_map(function($role) { return $role->getId(); }, $this->getAclRoles()->toArray()),
            '2fa'               => (bool)$this->getSecret2fa(),
            'platform'          => $this->getPlatform(),
        ];
    }
}
