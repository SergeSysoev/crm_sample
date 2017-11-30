<?php

namespace NaxCrmBundle\Modules\Deposits;

class PlatformDepositHandler extends BaseDepositHandler
{
    public function makeDeposit($amount)
    {
        // $this->validateCardNumber();
        $this->createDeposit($amount);
        //save to get deposit id
        $this->deposit->setStatus($this->status);
        $this->em->persist($this->deposit);
        $this->em->flush();

        return [
            'status' => $this->deposit->getStatus(),
            'depositId' => $this->deposit->getId(),
        ];
    }
}