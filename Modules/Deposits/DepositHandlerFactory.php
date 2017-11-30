<?php

namespace NaxCrmBundle\Modules\Deposits;

use Symfony\Component\HttpKernel\Exception\HttpException;
use NaxCrmBundle\Modules\SlackBot;

class DepositHandlerFactory
{

    /**
     * @param Client $client
     * @param PaymentSystem $paymentSystem
     * @param Account $account
     * @param $cardNumber
     * @return BaseDepositHandler
     * @throws \Exception
     */
    public static function createHandler($paymentSystem, $account, $em, $router, $data = [])
    {
        // $psName = $paymentSystem->getPaymentSystemName();
        $psName = ucfirst(strtolower($paymentSystem->getId()));
        $fullClassName = 'NaxCrmBundle\Modules\Deposits\\' . $psName . 'DepositHandler';
        if (!class_exists($fullClassName)) {
            SlackBot::send('Missing hanlder for payment system', $psName);
            throw new HttpException(500, "Missing hanlder for {$psName} payment system", SlackBot::ERR);
        }
        /* @var $handler BaseDepositHandler */
        $handler = new $fullClassName($paymentSystem, $account, $em, $router, $data);
        return $handler;
    }
}
