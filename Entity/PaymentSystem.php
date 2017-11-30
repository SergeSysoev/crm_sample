<?php

namespace NaxCrmBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * PaymentSystem
 *
 * @ORM\Table(name="payment_systems")
 * @ORM\Entity
 */
class PaymentSystem extends BaseEntity
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="string", length=15, options={"fixed" = true})
     * @ORM\Id
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="payment_system_name", type="string", length=255, unique=true)
     */
    protected $paymentSystemName;

    /**
     * @var boolean
     *
     * @ORM\Column(name="status", type="boolean", options={"default" : 0})
     */
    protected $status = 0;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_card", type="boolean", options={"default" : 0})
     */
    protected $isCard = 0;

    /**
     * @var boolean
     *
     * @ORM\Column(name="just_crm", type="boolean", options={"default" : 0})
     */
    protected $justCrm = 0;

    /**
     * @var boolean
     *
     * @ORM\Column(name="card_form", type="boolean", options={"default" : 0})
     */
    protected $cardForm = 0;

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
     * Set paymentSystemName
     *
     * @param string $paymentSystemName
     *
     * @return PaymentSystem
     */
    public function setPaymentSystemName($paymentSystemName)
    {
        $this->paymentSystemName = $paymentSystemName;

        return $this;
    }

    /**
     * Get paymentSystemName
     *
     * @return string
     */
    public function getPaymentSystemName()
    {
        return $this->paymentSystemName;
    }

    /**
     * Set status
     *
     * @param boolean $status
     *
     * @return PaymentSystem
     */
    public function setStatus($status)
    {
        $this->status = boolval($status);

        return $this;
    }

    /**
     * Get status
     *
     * @return bool
     */
    public function getStatus()
    {
        return (bool)$this->status;
    }

    /**
     * Set isCard
     *
     * @param boolean $isCard
     *
     * @return PaymentSystem
     */
    public function setIsCard($isCard)
    {
        $this->isCard = boolval($isCard);

        return $this;
    }

    /**
     * Get isCard
     *
     * @return bool
     */
    public function getIsCard()
    {
        return (bool)$this->isCard;
    }
    /**
     * Set justCrm
     *
     * @param integer $justCrm
     *
     * @return PaymentSystem
     */
    public function setJustCrm($justCrm)
    {
        $this->justCrm = boolval($justCrm);

        return $this;
    }
    /**
     * Get justCrm
     *
     * @return int
     */
    public function getJustCrm()
    {
        return (bool)$this->justCrm;
    }

    /**
     * Set cardForm
     *
     * @param integer $cardForm
     *
     * @return PaymentSystem
     */
    public function setCardForm($cardForm)
    {
        $this->cardForm = boolval($cardForm);

        return $this;
    }

    /**
     * Get cardForm
     *
     * @return int
     */
    public function getCardForm()
    {
        return (bool)$this->cardForm;
    }

    public function export()
    {
        return [
            'id'                    => $this->getId(),
            'paymentSystemName'     => $this->getPaymentSystemName(),
            'status'                => $this->getStatus(),
            'justCrm'               => $this->getJustCrm(),
            'cardForm'              => $this->getCardForm(),
        ];
    }
}

