<?php

namespace NaxCrmBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Exclude;

/**
 * Deposit
 *
 * @ORM\Table(name="deposits", indexes={
 *     @ORM\Index(name="FK_last_digits", columns={"last_digits"}),
 *     @ORM\Index(name="FK_account", columns={"account_id"}),
 *     @ORM\Index(name="FK_client", columns={"client_id"}),
 *     @ORM\Index(name="FK_payment_systems", columns={"payment_system_id"})
 * })
 * @ORM\Entity(repositoryClass="NaxCrmBundle\Repository\DepositRepository")
 */
class Deposit extends BaseEntity
{

    const STATUS_PENDING    = 1;
    const STATUS_PROCESSED  = 2;
    const STATUS_DECLINED   = 3;
    const STATUS_EXPIRED    = 4;

    const MAX_CONFIRMATION_TIME_IN_MINUTES  = 120;

    /**
     * @Exclude
     */
    public static $Statuses = [
        self::STATUS_PENDING => 'Pending',
        self::STATUS_PROCESSED => 'Processed',
        self::STATUS_DECLINED => 'Declined',
        self::STATUS_EXPIRED => 'Expired',
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
     * @var int
     *
     * @ORM\Column(name="client_id", type="integer")
     * @Exclude
     */
    protected $clientId;

    /**
     * @ORM\ManyToOne(targetEntity="NaxCrmBundle\Entity\Client", inversedBy="deposits")
     * @ORM\JoinColumn(name="client_id", referencedColumnName="id")
     * @Exclude
     */
    protected $client;

    /**
     * @var int
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
     * @ORM\Column(name="comment", type="text")
     */
    protected $comment;

    /**
     * @var string
     *
     * @ORM\Column(name="message", type="text", nullable=true)
     */
    protected $message;

    /**
     * @var string
     *
     * @ORM\Column(name="transaction_id", type="string", length=100, options={"fixed" = true, "default"=""}, nullable=true)
     */
    protected $transactionId = '';

    /**
     * @var string
     *
     * @ORM\Column(name="payment_system_id", type="string", length=15, options={"fixed" = true})
     */
    protected $paymentSystemId;

    /**
     * @ORM\ManyToOne(targetEntity="NaxCrmBundle\Entity\PaymentSystem")
     * @ORM\JoinColumn(name="payment_system_id", referencedColumnName="id")
     * @Exclude
     */
    protected $paymentSystem;

    /**
     * @var Account
     * @ORM\ManyToOne(targetEntity="NaxCrmBundle\Entity\Account")
     * @ORM\JoinColumn(name="account_id", referencedColumnName="id", nullable=false)
     * @Exclude
     */
    protected $account;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created", type="datetime")
     */
    protected $created;

    /**
     * @var string
     *
     * @ORM\Column(name="last_digits", type="string", length=4, nullable=true)
     */
    protected $lastDigits;

    /**
     * @var string
     *
     * @ORM\Column(name="pay_method", type="string", length=20, nullable=false, options={"default"="Unknown"})
     */
    protected $payMethod;

    /**
     * @var string
     *
     * @ORM\Column(name="currency", type="string", length=3)
     */
    protected $currency = Account::DEFAULT_CURRENCY;

    /**
     * Deposit constructor.
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
     * Set clientId
     *
     * @param integer $clientId
     *
     * @return Deposit
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
     * @param integer $amount
     *
     * @return Deposit
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Get amount
     *
     * @return int
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
     * @return Deposit
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
     * @return Deposit
     */
    public function setComment($comment)
    {
        if(is_array($comment)){
            $comment = json_encode($comment);
        }
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
     * Set message
     *
     * @param string $message
     *
     * @return Deposit
     */
    public function setMessage($message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Get message
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Set paymentSystem
     *
     * @param PaymentSystem $paymentSystem
     *
     * @return Deposit
     */
    public function setPaymentSystem(PaymentSystem $paymentSystem)
    {
        $this->paymentSystem = $paymentSystem;
        $this->setPaymentSystemId($paymentSystem->getId());

        return $this;
    }

    /**
     * Get paymentSystem
     *
     * @return PaymentSystem
     */
    public function getPaymentSystem()
    {
        return $this->paymentSystem;
    }

    /**
     * Set accounts
     *
     * @param Account $account
     *
     * @return Deposit
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
     * @return Deposit
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
     * Set paymentSystemId
     *
     * @param string $paymentSystemId
     *
     * @return Deposit
     */
    public function setPaymentSystemId($paymentSystemId)
    {
        $this->paymentSystemId = $paymentSystemId;

        return $this;
    }

    /**
     * Get paymentSystemId
     *
     * @return string
     */
    public function getPaymentSystemId()
    {
        return $this->paymentSystemId;
    }

    /**
     * Set transactionId
     *
     * @param string $transactionId
     *
     * @return Deposit
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
     * Set client
     *
     * @param Client $client
     *
     * @return Deposit
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
     * @return Deposit
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

    /**
     * Set payMethod
     *
     * @param string $payMethod
     *
     * @return Deposit
     */
    public function setPayMethod($payMethod)
    {
        $this->payMethod = $payMethod;

        return $this;
    }
    /**
     * Get payMethod
     *
     * @return string
     */
    public function getPayMethod()
    {
        return $this->payMethod;
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

    public function export()
    {
        $client = $this->getClient();
        $account = $this->getAccount();
        return [
            'id'                => $this->getId(),
            'firstname'         => $client? $client->getFirstname():'',
            'lastname'          => $client? $client->getLastname():'',
            'amount'            => $this->getAmount(),
            'currency'          => $this->getCurrency(),
            'accountId'         => $account? $account->getId():'',
            'accountExtId'      => $account? $account->getExternalId():'',
            'status'            => $this->getStatus(),
            'paymentSystemId'   => $this->getPaymentSystemId(),
            'comment'           => $this->getComment(),
            'message'           => $this->getMessage(),
            'created'           => $this->getCreated()->format('Y-m-d H:i:s'),
            'lastDigits'        => $this->getLastDigits(),
            'payMethod'         => $this->getPayMethod(),
        ];
    }
}
