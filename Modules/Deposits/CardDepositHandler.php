<?php

namespace NaxCrmBundle\Modules\Deposits;

use NaxCrmBundle\Entity\Deposit;

class CardDepositHandler extends BaseDepositHandler
{
    public function makeDeposit($amount)
    {
        // $this->validateCardNumber();
        $this->method = 'CreditCard';
        $this->createDeposit($amount);
        $this->deposit->setStatus(Deposit::STATUS_PENDING);

        //save to get deposit id
        $this->em->persist($this->deposit);
        $this->em->flush();

        $wallet_data = [
            'card_number'=> $this->cardNumber,
        ];
        $wallet_flds = [
            'card_name',
            'card_expiry_month',
            'card_expiry_year',
            'card_cvv',
        ];

        foreach ($wallet_flds as $k) {
            if(empty($this->dep_data[$k])){
                // $this->deposit->hardRemove($this->em);
                throw $this->Exception("Missing required parameter: '{$k}'");
            }
            $wallet_data[$k]=$this->dep_data[$k];
        }

        $this->deposit->setWalletData($wallet_data);

        return [
            'status' => $this->deposit->getStatus(),
            'depositId' => $this->deposit->getId(),
        ];
    }
}