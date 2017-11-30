<?php

namespace NaxCrmBundle\Modules\Deposits;

use NaxCrmBundle\Entity\Deposit;

class PlDepositHandler extends BaseDepositHandler
{
    public function makeDeposit($amount)
    {
        // $this->validateCardNumber();
        $this->createDeposit($amount);
        //save to get deposit id
        $this->deposit->setStatus(Deposit::STATUS_PENDING);
        $this->em->persist($this->deposit);
        $this->em->flush();
        $id = $this->deposit->getId();
        $amount = $amount*100;
        $email = $this->client->getEmail();
        $name = $this->client->getFirstname().' '.$this->client->getLastname();
        return [
            'url' => "https://www.myfxuni.com/paylike/paylike.php?amount={$amount}&hash={$id}&x={$amount}&email={$email}&name={$name}",
            // 'status' => $this->deposit->getStatus(),
            'depositId' => $this->deposit->getId(),
        ];
    }
}