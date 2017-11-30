<?php

namespace NaxCrmBundle\Modules\Email\Triggers\Client;

use NaxCrmBundle\Entity\Withdrawal;
use Negotiation\Exception\InvalidArgument;

class WithdrawalNew extends BaseClient
{
    protected static $alias = 'Make withdrawal';

    /**
     * @var Withdrawal
     */
    protected $withdrawal;

    public static $variables = [
        'WITHDRAWAL_AMOUNT',
        'WITHDRAWAL_COMMENT',
        'WITHDRAWAL_CURRENCY',
        'LAST_CARD_NUMBERS',
    ];

    public function getValues() {
        $card_num = $this->withdrawal->getLastDigits();
        return array_merge(parent::getValues(), [
            'WITHDRAWAL_AMOUNT' => $this->withdrawal->getAmount(),
            'WITHDRAWAL_COMMENT' => $this->withdrawal->getComment(),
            'WITHDRAWAL_CURRENCY' => $this->withdrawal->getAccount()->getCurrency(),
            'LAST_CARD_NUMBERS' => $card_num,
        ]);
    }

    /**
     * CreateWithdrawal constructor.
     * @param array $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
        $this->withdrawal= $this->findObject('withdrawal', $data);
        if (!($this->withdrawal instanceof Withdrawal)) {
            throw new InvalidArgument('Withdrawal wrong type');
        }
    }
}