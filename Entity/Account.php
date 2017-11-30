<?php

namespace NaxCrmBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use JMS\Serializer\Annotation\Exclude;

/**
 * Account
 *
 * @ORM\Table(name="accounts", indexes={
 *     @ORM\Index(name="fk_client", columns={"client_id"})
 *    },
 *    uniqueConstraints={
 *      @ORM\UniqueConstraint(name="fk_unique_accs", columns={"external_id", "platform"})
 *    }
 * )
 * @ORM\Entity(repositoryClass="NaxCrmBundle\Repository\AccountRepository")
 * @UniqueEntity(fields={"externalId", "platform"},message="This externalId already used.")
 */
class Account extends BaseEntity
{
    const TYPE_DEMO = 1;
    const TYPE_LIVE = 2;

    const DEFAULT_CURRENCY = 'USD';

    /**
     * @Exclude
     */
    public static $Types = [
        self::TYPE_DEMO => 'Demo',
        self::TYPE_LIVE => 'Pro',
    ];

    /**
     * @var string
     * @Exclude
     */
    public static $defaultPlatform;
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
     * @ORM\Column(name="currency", type="string", length=3)
     */
    protected $currency = self::DEFAULT_CURRENCY;

    /**
     * @var integer
     *
     * @ORM\Column(name="type", type="integer")
     */
    protected $type = self::TYPE_LIVE;

    /**
     * @var integer
     * @ORM\Column(name="client_id", type="integer")
     * @Exclude
     */
    protected $clientId;

    /**
     * @var Client
     * @ORM\ManyToOne(targetEntity="NaxCrmBundle\Entity\Client", inversedBy="accounts", cascade={"persist"})
     * @ORM\JoinColumn(name="client_id", referencedColumnName="id")
     * @Exclude
     */
    protected $client;

    /**
     * @var integer
     * @ORM\Column(name="external_id", type="integer", nullable=true)
     */
    protected $externalId;


    /**
     * @var string
     * @ORM\Column(name="platform", type="string", length=15, options={"fixed" = true}, nullable=false)
     */
    protected $platform;

    public function __construct()
    {
        $this->platform = self::$defaultPlatform;
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
     * Set currency
     *
     * @param string $currency
     *
     * @return Account
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * Get currency
     *
     * @return string
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    public function isDemo()
    {
        return $this->getType() == self::TYPE_DEMO;
    }

    /**
     * @return integer
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param integer $type
     * @return Account
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * Set clientId
     *
     * @param integer $clientId
     *
     * @return Account
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
     * Set client
     *
     * @param Client $client
     *
     * @return Account
     */
    public function setClient(Client $client = null)
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
     * @return int
     */
    public function getExternalId()
    {
        return $this->externalId;
    }

    /**
     * @param int $externalId
     * @return Account
     */
    public function setExternalId(int $externalId): Account
    {
        $this->externalId = $externalId;
        return $this;
    }

    /**
     * @return string
     */
    public function getPlatform(): string
    {
        return $this->platform;
    }

    /**
     * @param string $platform
     * @return Account
     */
    public function setPlatform(string $platform): Account
    {
        $this->platform = $platform;
        return $this;
    }

    public function export()
    {
        return [
            'id'                    => $this->getId(),
            'platform'              => $this->getPlatform(),
            'externalId'            => $this->getExternalId(),
            'clientId'              => $this->getClientId(),
            // 'client'                => $this->getClient()->export(),
            'type'                  => $this->getType(),
            'currency'              => $this->getCurrency(),
        ];
    }

}
