<?php

namespace NaxCrmBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * User log item
 *
 * @ORM\Table(name="user_logs", indexes={
 *     @ORM\Index(name="logType", columns={"objectType", "actionType"})
 * })
 * @ORM\Entity(repositoryClass="NaxCrmBundle\Repository\UserLogItemRepository")
 */
class UserLogItem extends BaseEntity
{

    const OBJ_TYPE_LEADS             = 'Leads';
    const OBJ_TYPE_CLIENTS           = 'Clients';
    const OBJ_TYPE_MANAGERS          = 'Managers';
    const OBJ_TYPE_NOTES             = 'Notes';
    const OBJ_TYPE_DEPOSITS          = 'Deposits';
    const OBJ_TYPE_WITHDRAWALS       = 'Withdrawals';
    const OBJ_TYPE_CLIENT_DOCUMENTS  = 'Client documents';
    const OBJ_TYPE_ROLES             = 'Roles';
    const OBJ_TYPE_DEPARTMENTS       = 'Departments';
    const OBJ_TYPE_ORDERS            = 'Orders';
    const OBJ_TYPE_CLIENT_STATUSES   = 'Client statuses';
    const OBJ_TYPE_COMMENTS          = 'Comments';
    const OBJ_TYPE_SALE_STATUSES     = 'Sale statuses';
    const OBJ_TYPE_RISK_MANAGEMENT   = 'Risk management';
    const OBJ_TYPE_SUPPORT           = 'Support';
    const OBJ_TYPE_FOLLOW_UPS        = 'Follow-ups';
    const OBJ_TYPE_CHARGE_BACKS      = 'Charge backs';
    const OBJ_TYPE_LOGS              = 'Logs';
    const OBJ_TYPE_EVENTS            = 'Events';
    const OBJ_TYPE_EMAIL_TEMPLATE    = 'Email template';

    const ACTION_TYPE_LOGIN     = 'Login';
    const ACTION_TYPE_MNG_LOGIN = 'Login as client';
    const ACTION_TYPE_CREATE    = 'Create';
    const ACTION_TYPE_UPDATE    = 'Update';
    const ACTION_TYPE_DELETE    = 'Delete';
    const ACTION_TYPE_IMPORT    = 'Import';
    const ACTION_TYPE_EXPORT    = 'Export';
    const ACTION_TYPE_OTHER     = 'Other';

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var int
     *
     * @ORM\ManyToOne(targetEntity="NaxCrmBundle\Entity\Department")
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
     * @ORM\ManyToOne(targetEntity="NaxCrmBundle\Entity\Manager", inversedBy="userLogItem")
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
     * @ORM\Column(name="time", type="datetime")
     */
    protected $time = null;

    /**
     * @var string
     *
     * @ORM\Column(name="json_payload", type="text", nullable=true)
     */
    protected $jsonPayload;

    /**
     * @var string
     *
     * @ORM\Column(name="objectType", type="string", length=255, nullable=false)
     */
    protected $objectType;

    /**
     * @var int
     *
     * @ORM\Column(name="objectId", type="integer", nullable=true)
     */
    protected $objectId;

    /**
     * @var string
     *
     * @ORM\Column(name="actionType", type="string", length=255, nullable=false)
     */
    protected $actionType;

    /**
     * @var string
     *
     * @ORM\Column(name="clientIp", type="string", length=50, nullable=true)
     */
    protected $clientIp;

    /**
     * @var string
     *
     * @ORM\Column(name="userType", type="string", length=20, nullable=true)
     */
    protected $userType = 'Unknown';

    /**
     * LogItem constructor.
     */
    public function __construct()
    {
        $this->time = new \DateTime();
        $this->actionType = self::ACTION_TYPE_OTHER;
        $this->clientIp = $_SERVER['REMOTE_ADDR'];
        $this->userType = 'Unknown';
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
     * Set departmentId
     *
     * @param integer $departmentId
     *
     * @return UserLogItem
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
     * @return UserLogItem
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
     * Set time
     *
     * @param \DateTime $time
     *
     * @return UserLogItem
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
     * @return UserLogItem
     */
    public function setJsonPayload($jsonPayload)
    {
        if(is_array($jsonPayload)){
            if(!empty($jsonPayload['id'])){
                $this->setObjectId($jsonPayload['id']);
            }
            if(!empty($jsonPayload['ids'])){
                $ids = $jsonPayload['ids'];
                if(is_array($jsonPayload['ids'])){
                    $ids = implode(',',$jsonPayload['ids']);
                }
                if(!substr_count($ids, ',')){
                    $this->setObjectId($ids);
                }
            }
            $jsonPayload = json_encode($jsonPayload);
        }
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
     * Set objectId
     *
     * @param int $objectId
     *
     * @return UserLogItem
     */
    public function setObjectId($objectId)
    {
        $this->objectId = $objectId;

        return $this;
    }
    /**
     * Get objectId
     *
     * @return int
     */
    public function getObjectId()
    {
        return $this->objectId;
    }

    /**
     * Set objectType
     *
     * @param string $objectType
     *
     * @return UserLogItem
     */
    public function setObjectType($objectType)
    {
        $this->objectType = $objectType;

        return $this;
    }

    /**
     * Get objectType
     *
     * @return string
     */
    public function getObjectType()
    {
        return $this->objectType;
    }

    /**
     * Set actionType
     *
     * @param string $actionType
     *
     * @return UserLogItem
     */
    public function setActionType($actionType)
    {
        $this->actionType = $actionType;

        return $this;
    }

    /**
     * Get actionType
     *
     * @return string
     */
    public function getActionType()
    {
        return $this->actionType;
    }

    /**
     * Set clientIp
     *
     * @param string $clientIp
     *
     * @return UserLogItem
     */
    public function setClientIp($clientIp)
    {
        $this->clientIp = $clientIp;

        return $this;
    }
    /**
     * Get clientIp
     *
     * @return string
     */
    public function getClientIp()
    {
        return $this->clientIp;
    }

    /**
     * Set userType
     *
     * @param string $userType
     *
     * @return UserLogItem
     */
    public function setUserType($userType)
    {
        $this->userType = $userType;

        return $this;
    }
    /**
     * Get userType
     *
     * @return string
     */
    public function getUserType()
    {
        return $this->userType;
    }

    /**
     * Set department
     *
     * @param \NaxCrmBundle\Entity\Department $department
     *
     * @return UserLogItem
     */
    public function setDepartment(\NaxCrmBundle\Entity\Department $department = null)
    {
        $this->department = $department;

        return $this;
    }

    /**
     * Set manager
     *
     * @param \NaxCrmBundle\Entity\Manager $manager
     *
     * @return UserLogItem
     */
    public function setManager(\NaxCrmBundle\Entity\Manager $manager = null)
    {
        $this->manager = $manager;
        if(!empty($manager)){
            $this->setDepartment($manager->getDepartment());
        }

        return $this;
    }

    /**
     * Get manager
     *
     * @return \NaxCrmBundle\Entity\Manager
     */
    public function getManager()
    {
        return $this->manager;
    }
}
