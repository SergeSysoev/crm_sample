<?php

namespace NaxCrmBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Exclude;

/**
 * Orders
 *
 * @ORM\Table(name="orders")
 * @ORM\Entity(repositoryClass="NaxCrmBundle\Repository\OrderRepository")
 */
class Order extends BaseEntity
{
    const STATUS_CREATED    = 1;
    const STATUS_CLOSED     = 2;
    const STATUS_DECLINED   = 3;

    const CURRENCY_USD      = 'USD';

    const TYPE_CALL         = 1;
    const TYPE_PUT          = 2;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="amount", type="float", nullable=false)
     */
    protected $amount;

    /**
     * @var integer
     *
     * @ORM\Column(name="profit", type="float", nullable=true)
     */
    protected $profit;

    /**
     * @var string
     *
     * @ORM\Column(name="symbol", type="string", length=15, nullable=false)
     */
    protected $symbol = self::CURRENCY_USD;

    /**
     * @var integer
     *
     * @ORM\Column(name="type", type="integer", nullable=false)
     */
    protected $type = self::TYPE_CALL;

    /**
     * @var float
     *
     * @ORM\Column(name="price_strike", type="float", nullable=false)
     */
    protected $priceStrike;

    /**
     * Used only for "InRange" orders
     * @var float
     *
     * @ORM\Column(name="price_strike2", type="float", nullable=false)
     */
    protected $priceStrike2 = 0;


    /**
     * Used only for fibiz orders
     * @var integer
     *
     * @ORM\Column(name="flags", type="integer", nullable=false)
     */
    protected $flags = 0;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="time_open", type="datetime", nullable=false)
     */
    protected $timeOpen;

    /**
     * @var integer
     *
     * @ORM\Column(name="time_open_millisec", type="integer", nullable=true)
     */
    protected $timeOpenMillisec;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="time_close", type="datetime", nullable=true)
     */
    protected $timeClose;

    /**
     * @var integer
     *
     * @ORM\Column(name="time_close_millisec", type="integer", nullable=true)
     */
    protected $timeCloseMillisec;

    /**
     * @var integer
     *
     * @ORM\Column(name="client_id", type="integer", nullable=false)
     * @Exclude
     */
    protected $clientId;

    /**
     * @ORM\ManyToOne(targetEntity="NaxCrmBundle\Entity\Client", inversedBy="orders")
     * @ORM\JoinColumn(name="client_id", referencedColumnName="id")
     * @Exclude
     */
    protected $client;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="time_expiration", type="datetime", nullable=false)
     */
    protected $timeExpiration;

    /**
     * @var integer
     *
     * @ORM\Column(name="time_expiration_millisec", type="integer", nullable=true)
     */
    protected $timeExpirationMillisec;

    /**
     * @var string
     *
     * @ORM\Column(name="ext_id", type="string", nullable=false, length=64, options={"fixed"=true})
     */
    protected $extId;

    /**
     * @var float
     *
     * @ORM\Column(name="payout", type="float", nullable=false)
     */
    protected $payout;

    /**
     * @var float
     *
     * @ORM\Column(name="payout_rate", type="float", nullable=false)
     */
    protected $payoutRate;

    /**
     * @var integer
     *
     * @ORM\Column(name="state", type="integer", nullable=false)
     */
    protected $state;

    /**
     * @var float
     *
     * @ORM\Column(name="price_open", type="float", nullable=false)
     */
    protected $priceOpen;

    /**
     * @var float
     *
     * @ORM\Column(name="price_close", type="float", nullable=false)
     */
    protected $priceClose;

    /**
     * @var integer
     *
     * @ORM\Column(name="status", type="integer", length=2, nullable=true)
     */
    protected $status = self::STATUS_CREATED;

    /**
     * @var integer
     *
     * @ORM\Column(name="account_id", type="integer", nullable=false)
     * @Exclude
     */
    protected $accountId;

    /**
     * @var Account
     * @ORM\ManyToOne(targetEntity="NaxCrmBundle\Entity\Account")
     * @ORM\JoinColumn(name="account_id", referencedColumnName="id")
     * @Exclude
     */
    protected $account;

    /**
     * @var string
     * @ORM\Column(name="info", type="string", length=2047, nullable=true)
     */
    protected $info;

    /**
     * Order constructor.
     */
    public function __construct()
    {
        $this->priceClose   = 0;
    }

    /**
     * Check order
     */
    public function check()
    {
        $this->status = self::STATUS_CLOSED;
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
     * Set amount
     *
     * @param integer $amount
     *
     * @return Order
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Get amount
     *
     * @return integer
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * Set profit
     *
     * @param integer $profit
     *
     * @return Order
     */
    public function setProfit($profit)
    {
        $this->profit = $profit;

        return $this;
    }

    /**
     * Get profit
     *
     * @return integer
     */
    public function getProfit()
    {
        return $this->profit;
    }

    /**
     * Set status
     *
     * @param integer $status
     *
     * @return Order
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
     * Set clientId
     *
     * @param integer $clientId
     *
     * @return Order
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
     * Set accountId
     *
     * @param integer $accountId
     *
     * @return Order
     */
    public function setAccountId($accountId)
    {
        $this->accountId = $accountId;

        return $this;
    }

    /**
     * Get accountId
     *
     * @return integer
     */
    public function getAccountId()
    {
        return $this->accountId;
    }

    /**
     * Set symbol
     *
     * @param string $symbol
     *
     * @return Order
     */
    public function setSymbol($symbol)
    {
        $this->symbol = $symbol;

        return $this;
    }

    /**
     * Get symbol
     *
     * @return string
     */
    public function getSymbol()
    {
        return $this->symbol;
    }

    /**
     * Set type
     *
     * @param integer $type
     *
     * @return Order
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return integer
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set priceStrike
     *
     * @param float $priceStrike
     *
     * @return Order
     */
    public function setPriceStrike($priceStrike)
    {
        $this->priceStrike = $priceStrike;

        return $this;
    }

    /**
     * Get priceStrike
     *
     * @return float
     */
    public function getPriceStrike()
    {
        return $this->priceStrike;
    }

    /**
     * Set priceStrike2
     *
     * @param float $priceStrike
     *
     * @return Order
     */
    public function setPriceStrike2($priceStrike2)
    {
        $this->priceStrike2 = $priceStrike2;

        return $this;
    }

    /**
     * Get priceStrike2
     *
     * @return float
     */
    public function getPriceStrike2()
    {
        return $this->priceStrike2;
    }

    /**
     * @return int
     */
    public function getFlags(): int
    {
        return $this->flags;
    }

    /**
     * @param int $flags
     * @return Order
     */
    public function setFlags(int $flags): Order
    {
        $this->flags = $flags;
        return $this;
    }

    /**
     * Set extId
     *
     * @param string $extId
     *
     * @return Order
     */
    public function setExtId($extId)
    {
        $this->extId = $extId;

        return $this;
    }

    /**
     * Get extId
     *
     * @return string
     */
    public function getExtId()
    {
        return $this->extId;
    }

    /**
     * Set payout
     *
     * @param float $payout
     *
     * @return Order
     */
    public function setPayout($payout)
    {
        $this->payout = $payout;

        return $this;
    }

    /**
     * Get payout
     *
     * @return float
     */
    public function getPayout()
    {
        return $this->payout;
    }

    /**
     * Set payoutRate
     *
     * @param float $payoutRate
     *
     * @return Order
     */
    public function setPayoutRate($payoutRate)
    {
        $this->payoutRate = $payoutRate;

        return $this;
    }

    /**
     * Get payoutRate
     *
     * @return float
     */
    public function getPayoutRate()
    {
        return $this->payoutRate;
    }

    /**
     * Set state
     *
     * @param integer $state
     *
     * @return Order
     */
    public function setState($state)
    {
        $this->state = $state;

        return $this;
    }

    /**
     * Get state
     *
     * @return integer
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * Set priceOpen
     *
     * @param float $priceOpen
     *
     * @return Order
     */
    public function setPriceOpen($priceOpen)
    {
        $this->priceOpen = $priceOpen;

        return $this;
    }

    /**
     * Get priceOpen
     *
     * @return float
     */
    public function getPriceOpen()
    {
        return $this->priceOpen;
    }

    /**
     * Set priceClose
     *
     * @param float $priceClose
     *
     * @return Order
     */
    public function setPriceClose($priceClose)
    {
        $this->priceClose = $priceClose;

        return $this;
    }

    /**
     * Get priceClose
     *
     * @return float
     */
    public function getPriceClose()
    {
        return $this->priceClose;
    }

    /**
     * Set client
     *
     * @param Client $client
     *
     * @return Order
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
     * @return Account
     */
    public function getAccount(): Account
    {
        return $this->account;
    }

    /**
     * @param Account $account
     * @return Order
     */
    public function setAccount(Account $account): Order
    {
        $this->account = $account;
        return $this;
    }


    /**
     * Set timeOpen
     *
     * @param \DateTime $timeOpen
     *
     * @return Order
     */
    public function setTimeOpen($timeOpen)
    {
        $this->timeOpen = $timeOpen;

        return $this;
    }

    /**
     * Get timeOpen
     *
     * @return \DateTime
     */
    public function getTimeOpen()
    {
        return $this->timeOpen;
    }

    /**
     * Set timeOpenMillisec
     *
     * @param integer $timeOpenMillisec
     *
     * @return Order
     */
    public function setTimeOpenMillisec($timeOpenMillisec)
    {
        $this->timeOpenMillisec = $timeOpenMillisec;

        return $this;
    }

    /**
     * Get timeOpenMillisec
     *
     * @return integer
     */
    public function getTimeOpenMillisec()
    {
        return $this->timeOpenMillisec;
    }

    /**
     * Set timeClose
     *
     * @param \DateTime $timeClose
     *
     * @return Order
     */
    public function setTimeClose($timeClose)
    {
        $this->timeClose = $timeClose;

        return $this;
    }

    /**
     * Get timeClose
     *
     * @return \DateTime
     */
    public function getTimeClose()
    {
        return $this->timeClose;
    }

    /**
     * Set timeCloseMillisec
     *
     * @param integer $timeCloseMillisec
     *
     * @return Order
     */
    public function setTimeCloseMillisec($timeCloseMillisec)
    {
        $this->timeCloseMillisec = $timeCloseMillisec;

        return $this;
    }

    /**
     * Get timeCloseMillisec
     *
     * @return integer
     */
    public function getTimeCloseMillisec()
    {
        return $this->timeCloseMillisec;
    }

    /**
     * Set timeExpiration
     *
     * @param \DateTime $timeExpiration
     *
     * @return Order
     */
    public function setTimeExpiration($timeExpiration)
    {
        $this->timeExpiration = $timeExpiration;

        return $this;
    }

    /**
     * Get timeExpiration
     *
     * @return \DateTime
     */
    public function getTimeExpiration()
    {
        return $this->timeExpiration;
    }

    /**
     * Set timeExpirationMillisec
     *
     * @param integer $timeExpirationMillisec
     *
     * @return Order
     */
    public function setTimeExpirationMillisec($timeExpirationMillisec)
    {
        $this->timeExpirationMillisec = $timeExpirationMillisec;

        return $this;
    }

    /**
     * Get timeExpirationMillisec
     *
     * @return integer
     */
    public function getTimeExpirationMillisec()
    {
        return $this->timeExpirationMillisec;
    }

    public function getFullTimeClose() {
        return $this->getTimeClose()->format('Y-m-d H:i:s') . '.' . $this->getTimeCloseMillisec();
    }

    /**
     * @return string
     */
    public function getInfo(): string
    {
        return $this->info;
    }

    /**
     * @param string $info
     * @return Order
     */
    public function setInfo(string $info): Order
    {
        $this->info = $info;
        return $this;
    }



    public function export()
    {
        return [
            'id'                => $this->getId(),
            'firstname'         => $this->getClient()->getFirstname(),
            'lastname'          => $this->getClient()->getLastname(),
            'amount'            => $this->getAmount(),
            'profit'            => $this->getProfit(),
            'symbol'            => $this->getSymbol(),
            'type'              => $this->getType(),
            'priceStrike'       => $this->getPriceStrike(),
            'timeOpen'          => $this->getTimeOpen()->format('Y-m-d H:i:s') . '.' . $this->getTimeOpenMillisec(),
            'timeClose'         => $this->getTimeClose() ? $this->getFullTimeClose() : '',
            'timeExpiration'    => $this->getTimeExpiration()->format('Y-m-d H:i:s') . '.' . $this->getTimeExpirationMillisec(),
            'extId'             => $this->getExtId(),
            'payout'            => $this->getPayout(),
            'payoutRate'        => $this->getPayoutRate(),
            'state'             => $this->getState(),
            'priceOpen'         => $this->getPriceOpen(),
            'priceClose'        => $this->getPriceClose(),
            'status'            => $this->getStatus(),
        ];
    }
}
