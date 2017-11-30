<?php

namespace NaxCrmBundle\Modules\Email\Triggers\Client;


use NaxCrmBundle\Entity\Deposit as Entity;
use Negotiation\Exception\InvalidArgument;

class Deposit extends BaseClient
{
    protected static $alias = 'Deposit approved';
    /** @var Entity */
    protected $deposit;

    public static $variables = [
        //номер счета, сумма депозита, платежная система, валюта депозита, последние цифры карты, статус, дата депозита
        'AMOUNT',
        'PAYMENT_SYSTEM',
        'CURRENCY',
        'LAST_CARD_NUMBERS',
        'DATE',
        'STATUS'
    ];

    /**
     * CreateDepositTrigger
     * @param array $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
        $this->deposit= $this->findObject('deposit', $data);
        if (!($this->deposit instanceof Entity)) {
            throw new InvalidArgument('Deposit wrong type');
        }
    }

    public function getValues() {
        return array_merge(parent::getValues(), [
            'AMOUNT' => $this->deposit->getAmount(),
            'PAYMENT_SYSTEM' =>$this->deposit->getPaymentSystem()->getPaymentSystemName(),
            'CURRENCY' => $this->deposit->getCurrency(),
            'LAST_CARD_NUMBERS' => $this->deposit->getLastDigits(),
            'DATE' => $this->deposit->getCreated()->format('Y-m-d H:i:s'),
            'STATUS' => Entity::get('Statuses')[$this->deposit->getStatus()],
        ]);
    }
}