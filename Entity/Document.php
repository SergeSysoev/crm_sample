<?php

namespace NaxCrmBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation\Exclude;

/**
 * Document
 *
 * @ORM\Table(name="documents")
 * @ORM\Entity(repositoryClass="NaxCrmBundle\Repository\DocumentRepository")
 */
class Document extends BaseEntity
{
    const STATUS_PENDING    = 1;
    const STATUS_DECLINED   = 2;
    const STATUS_APPROVED   = 3;

    /**
     * @Exclude
     */
    public static $Statuses = [
        self::STATUS_PENDING => 'Pending',
        self::STATUS_DECLINED => 'Declined',
        self::STATUS_APPROVED => 'Approved',
    ];

    /**
     * @Exclude
     */
    public static $statusLabels = [
        self::STATUS_PENDING => 'default',
        self::STATUS_DECLINED => 'danger',
        self::STATUS_APPROVED => 'success'
    ];

    public static function getStatuses()
    {
        $statuses = self::$Statuses;
        $labels = self::$statusLabels;

        $res = [];
        foreach ($statuses as $k => $v)
        {
            $res[] = [
                'id' => $k,
                'alias' => $v,
                'name' => $v,
                'label' => $labels[$k]
            ];
        }

        return $res;
    }

    /**
     * @var array
     * @Exclude
     */
    public static $allowedMimeTypes = [
        'application/pdf',
        'application/octet-stream',
        'image/jpeg',
        'image/jpg',
        'image/bmp',
        'image/png',
    ];

    /**
     * @var array
     * @Exclude
     */
    public static $allowedExtensions = [
        'pdf',
        'jpeg',
        'jpg',
        'bmp',
        'png',
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
     * @var \DateTime
     *
     * @ORM\Column(name="time", type="datetime")
     */
    protected $time;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime", nullable=true)
     */
    protected $updated;

    /**
     * @var string
     *
     * @ORM\Column(name="path", type="string", length=1023)
     */
    protected $path;

    /**
     * @var string
     *
     * @ORM\Column(name="mime_type", type="string", length=255)
     */
    protected $mimeType;

    /**
     * @ORM\ManyToOne(targetEntity="NaxCrmBundle\Entity\DocumentType")
     * @ORM\JoinColumn(name="document_type_id", referencedColumnName="id", nullable=false)
     */
    protected $documentType;

    /**
     * @ORM\ManyToOne(targetEntity="NaxCrmBundle\Entity\Manager")
     * @ORM\JoinColumn(name="manager_id", referencedColumnName="id", nullable=true)
     */
    protected $manager;

    /**
     * @ORM\ManyToOne(targetEntity="NaxCrmBundle\Entity\Client", inversedBy="documents")
     * @ORM\JoinColumn(name="client_id", referencedColumnName="id", nullable=false)
     * @Exclude
     */
    protected $client;


    /**
     * @var int
     *
     * @ORM\Column(name="client_id", type="integer", nullable=true)
     */
    protected $clientId;

    /**
     * @var int
     *
     * @ORM\Column(name="document_type_id", type="integer", nullable=true)
     */
    protected $documentTypeId;

    /**
     * @var string
     *
     * @ORM\Column(name="comment", type="text", nullable=true)
     */
    protected $comment;

    /**
     * @var int
     *
     * @ORM\Column(name="status", type="integer")
     * @Assert\Range(
     *      min = 1,
     *      max = 3,
     * )
     */
    protected $status = self::STATUS_PENDING;

    /**
     * Document constructor.
     */
    public function __construct()
    {
        $this->time     = new \DateTime('now');
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
     * Set time
     *
     * @param \DateTime $time
     * @return Document
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
     * Set updated
     *
     * @param \DateTime $updated
     *
     * @return Withdrawal
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;

        return $this;
    }
    /**
     * Get updated
     *
     * @return \DateTime
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * Set path
     *
     * @param string $path
     * @return Document
     */
    public function setPath($path)
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Get path
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Set documentType
     *
     * @param DocumentType $documentType
     * @return Document
     */
    public function setDocumentType(DocumentType $documentType)
    {
        $this->documentType = $documentType;

        return $this;
    }

    /**
     * Get documentType
     *
     * @return DocumentType
     */
    public function getDocumentType()
    {
        return $this->documentType;
    }

    /**
     * Set client
     *
     * @param Client $client
     *
     * @return Document
     */
    public function setClient(Client $client)
    {
        $this->client = $client;

        return $this;
    }

    /**
     * Get client
     *
     * @return Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * Set status
     *
     * @param integer $status
     *
     * @return Document
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return integer
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set mimeType
     *
     * @param string $mimeType
     *
     * @return Document
     */
    public function setMimeType($mimeType)
    {
        $this->mimeType = $mimeType;

        return $this;
    }

    /**
     * Get mimeType
     *
     * @return string
     */
    public function getMimeType()
    {
        return $this->mimeType;
    }

    /**
     * Set manager
     *
     * @param Manager|null $manager
     *
     * @return Document
     */
    public function setManager($manager)
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
     * Set comment
     *
     * @param string $comment
     *
     * @return Document
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
     * Set clientId
     *
     * @param integer $clientId
     *
     * @return Document
     */
    public function setClientId($clientId)
    {
        $this->clientId = $clientId;

        return $this;
    }

    /**
     * Get clientId
     *
     * @return integer
     */
    public function getClientId()
    {
        return $this->clientId;
    }

    /**
     * Set documentTypeId
     *
     * @param integer $documentTypeId
     *
     * @return Document
     */
    public function setDocumentTypeId($documentTypeId)
    {
        $this->documentTypeId = $documentTypeId;

        return $this;
    }

    /**
     * Get documentTypeId
     *
     * @return integer
     */
    public function getDocumentTypeId()
    {
        return $this->documentTypeId;
    }

    public function export()
    {
        $res = [
            'id'                    => $this->getId(),
            'manager'               => $this->getManager()? $this->getManager()->getName() : '',
            'time'                  => $this->getTime()? $this->getTime()->format('Y-m-d H:i:s') : '',
            'updated'               => $this->getUpdated()? $this->getUpdated()->format('Y-m-d H:i:s') : '',
            'status'                => $this->getStatus(),
            'path'                  => $this->getPath(),
            'mimeType'              => $this->getMimeType(),
        ];
        if(!empty($doc_type = $this->getDocumentType())){
            $res['documentTypeId'] = $doc_type->getId();
            $res['documentTypeName'] = $doc_type->getType();
        }
        return $res;
    }
}
