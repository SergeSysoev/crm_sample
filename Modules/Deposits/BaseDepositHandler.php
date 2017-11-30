<?php

namespace NaxCrmBundle\Modules\Deposits;

use Curl\Curl;
use Doctrine\ORM\EntityManager;
use NaxCrmBundle\Entity\Account;
use NaxCrmBundle\Entity\Deposit;
use NaxCrmBundle\Entity\PaymentSystem;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\HttpKernel\Exception\HttpException;
use NaxCrmBundle\Modules\Email\Triggers\Client\DepositFailed;
// use NaxCrmBundle\Modules\Email\Triggers\Client\DepositSuccess;

abstract class BaseDepositHandler
{
    protected $client;
    protected $paymentSystem;
    protected $account;
    protected $cardNumber;
    protected $last4Digits;
    protected $currency;
    protected $method;
    protected $transactionId;
    protected $status;
    protected $router;
    protected $dep_data;
    protected $em;
    public $container = null;

    /** @var  Curl */
    protected $curl;
    public $config = [];
    /**
     * @var Deposit
     */
    protected $deposit;

    public function __construct(PaymentSystem $paymentSystem, Account $account, EntityManager $em, Router $router, $data = [])
    {
        $this->client = $account->getClient();
        $this->paymentSystem = $paymentSystem;
        $this->account = $account;
        $this->status = !empty($data['status']) ? $data['status'] : Deposit::STATUS_PENDING;
        $this->method = !empty($data['method']) ? $data['method'] : '';
        $this->transactionId = !empty($data['transactionId']) ? $data['transactionId'] : '';
        $this->em = $em;
        $this->router = $router;
        $this->dep_data = $data;
        $this->currency = empty($this->dep_data['currency']) ?
            $this->account->getCurrency():
            $this->currency = $this->dep_data['currency'];

        if(!empty($data['cardNumber'])){
            $this->cardNumber = $data['cardNumber'];
            $this->last4Digits = substr($this->cardNumber, -4);
        }
    }

    /**
     * @return Curl
     */
    protected function getCurl(): Curl
    {
        if (!$this->curl) {
            $this->curl = new Curl();
        }
        return $this->curl;
    }


    /**
     * @param $amount
     * @return Deposit
     * @throws \Doctrine\ORM\ORMException
     */
    protected function createDeposit($amount) {
        $this->deposit = Deposit::createInst();
        $this->deposit->setAmount($amount);
        $this->deposit->setAccount($this->account);
        $this->deposit->setClient($this->client);
        $this->deposit->setCurrency($this->currency);
        $this->deposit->setPaymentSystem($this->paymentSystem);
        $this->deposit->setTransactionId($this->transactionId);
        $this->deposit->setStatus($this->status);
        $this->deposit->setPayMethod($this->method);
        $this->deposit->setLastDigits($this->last4Digits);
        $this->deposit->setComment('');
    }

    /**
     * @param $cardNumber
     * @throws \Exception
     */
    protected function validateCardNumber() {
        if (strlen($this->cardNumber) > 16 || strlen($this->cardNumber) < 4) {
            if(!empty($this->deposit)){
                $this->deposit->hardRemove($this->em);
            }
            throw $this->Exception('Invalid card number');
        }
    }
    protected function Exception($text, $code = 400){
        if(!empty($this->deposit)){
            $comment = empty($this->deposit->getComment())? $text : '';
            $this->decline($comment);
        }

        return new HttpException($code, $text);
    }

    function success($test = false){
        $this->deposit->setStatus(Deposit::STATUS_PROCESSED);
        $this->em->persist($this->deposit);
        $this->em->flush();

        // $this->container->get('nax_crm.trigger_service')->sendEmails(DepositSuccess::class(), [
        //         'deposit' => $this->deposit,
        //         'client' => $this->client,
        //     ], $this->client->getLangcode());

    }
    function decline($comment = '', $test = false){
        $this->deposit->setStatus(Deposit::STATUS_DECLINED);
        if(!empty($comment)){
            $this->deposit->setComment($text);
        }
        $this->em->persist($this->deposit);
        $this->em->flush();

        $this->container->get('nax_crm.trigger_service')->sendEmails(DepositFailed::class(), [
                'deposit' => $this->deposit,
                'client' => $this->client,
            ], $this->client->getLangcode());
    }

    /**
     * @param $amount
     * @return Deposit
     */
    abstract public function makeDeposit($amount);

    public function getDeposit() {
        return $this->deposit;
    }

}