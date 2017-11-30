<?php

namespace NaxCrmBundle\Modules\Email\Triggers\Client;

class DepositFailed extends Deposit
{
    protected static $alias = 'Deposit failed';

    /*public static function getVariables() {
        return array_merge(parent::$variables, [
            'DEPOSIT_FAILED_REASON',
        ]);
    }

    public function getValues() {
        return array_merge(parent::getValues(), [
            'DEPOSIT_FAILED_REASON' => $this->document->getComment()
        ]);
    }*/
}