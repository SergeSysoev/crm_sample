<?php

namespace NaxCrmBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Exclude;

/**
 * Withdrawal
 *
 * @ORM\Table(name="withdrawals", indexes={
 *     @ORM\Index(name="FK_account", columns={"account_id"}),
 *     @ORM\Index(name="FK_client", columns={"client_id"})
 * })
 * @ORM\Entity(repositoryClass="NaxCrmBundle\Repository\WithdrawalRepository")
 */
class Withdrawal extends BaseEntity
{

    const STATUS_PENDING    = 0;
    const STATUS_PROCESSED  = 1;
    const STATUS_APPROVED   = 2;
    const STATUS_DECLINED   = 3;

    /**
     * @Exclude
     */
    public static $Statuses = [
        self::STATUS_PENDING => 'Pending',
        self::STATUS_PROCESSED => 'Processed',
        self::STATUS_APPROVED => 'Approved',
        self::STATUS_DECLINED => 'Declined',
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
     * @ORM\Column(name="transaction_id", type="string", length=100, options={"fixed" = true, "default"=""}, nullable=true)
     */
    protected $transactionId = '';

    /**
     * @var int
     *
     * @ORM\Column(name="client_id", type="integer")
     * @Exclude
     */
    protected $clientId;

    /**
     * @ORM\ManyToOne(targetEntity="NaxCrmBundle\Entity\Client", inversedBy="withdrawals")
     * @ORM\JoinColumn(name="client_id", referencedColumnName="id")
     * @Exclude
     */
    protected $client;

    /**
     * @var float
     *
     * @ORM\Column(name="amount", type="float")
     */
    protected $amount;

    /**
     * @var int
     *
     * @ORM\Column(name="status", type="integer")
     */
    protected $status;

    /**
     * @var string
     *
     * @ORM\Column(name="comment", type="string", length=255, nullable=true)
     */
    protected $comment;

    /**
     * @ORM\ManyToOne(targetEntity="NaxCrmBundle\Entity\Account")
     * @ORM\JoinColumn(name="account_id", referencedColumnName="id", nullable=false)
     * @Exclude
     */
    protected $account;

   /**
     * @var string
     *
     * @ORM\Column(name="last_digits", type="string", length=4, nullable=true)
     */
    protected $lastDigits;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created", type="datetime")
     */
    protected $created;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime", nullable=true)
     */
    protected $updated;

    /**
     * Withdrawal constructor.
     */
    public function __construct()
    {
        $this->created = new \DateTime('now');
        $this->status  = self::STATUS_PENDING;
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
     * Set transactionId
     *
     * @param string $transactionId
     *
     * @return Withdrawal
     */
    public function setTransactionId($transactionId)
    {
        $this->transactionId = $transactionId;

        return $this;
    }

    /**
     * Get transactionId
     *
     * @return string
     */
    public function getTransactionId()
    {
        return $this->transactionId;
    }

    /**
     * Set clientId
     *
     * @param integer $clientId
     *
     * @return Withdrawal
     */
    public function setClientId($clientId)
    {
        $this->clientId = $clientId;

        return $this;
    }

    /**
     * Get clientId
     *
     * @return int
     */
    public function getClientId()
    {
        return $this->clientId;
    }

    /**
     * Set amount
     *
     * @param float $amount
     *
     * @return Withdrawal
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Get amount
     *
     * @return float
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * Set status
     *
     * @param integer $status
     *
     * @return Withdrawal
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set comment
     *
     * @param string $comment
     *
     * @return Withdrawal
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
     * Set accounts
     *
     * @param Account $account
     *
     * @return Withdrawal
     */
    public function setAccount(Account $account)
    {
        $this->account = $account;

        return $this;
    }

    /**
     * Get accounts
     *
     * @return Account
     */
    public function getAccount()
    {
        return $this->account;
    }

    /**
     * Set created
     *
     * @param \DateTime $created
     *
     * @return Withdrawal
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
     * Set client
     *
     * @param Client $client
     *
     * @return Withdrawal
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
     * Set lastDigits
     *
     * @param string $lastDigits
     *
     * @return Withdrawal
     */
    public function setLastDigits($lastDigits)
    {
        $lastDigits = substr($lastDigits, -4);
        $this->lastDigits = $lastDigits;

        return $this;
    }

    /**
     * Get lastDigits
     *
     * @return string
     */
    public function getLastDigits()
    {
        return $this->lastDigits;
    }

    public function export()
    {
        $client = $this->getClient();
        $account = $this->getAccount();
        return [
            'id'                => $this->getId(),
            'firstname'         => $client? $client->getFirstname() : '',
            'lastname'          => $client? $client->getLastname() : '',
            'amount'            => $this->getAmount(),
            'accountId'         => $account? $account->getId() : '',
            'status'            => $this->getStatus(),
            'comment'           => $this->getComment(),
            'lastDigits'        => $this->getLastDigits(),
            'created'           => $this->getCreated()? $this->getCreated()->format('Y-m-d H:i:s') : '',
            'updated'           => $this->getUpdated()? $this->getUpdated()->format('Y-m-d H:i:s') : '',
        ];
    }
}
