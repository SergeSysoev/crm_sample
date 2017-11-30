<?php

namespace NaxCrmBundle\Modules\Deposits;

use NaxCrmBundle\Entity\Deposit;

class FakeDepositHandler extends BaseDepositHandler
{
    public function makeDeposit($amount) {
        $this->createDeposit($amount);
        $this->success();
        return [
            'status' => $this->deposit->getStatus(),
            'depositId' => $this->deposit->getId()
        ];
    }
}