<?php

namespace NaxCrmBundle\Modules\Deposits;

use Doctrine\ORM\EntityManager;
use NaxCrmBundle\Entity\Account;
use NaxCrmBundle\Entity\Client;
use NaxCrmBundle\Entity\Deposit;
use NaxCrmBundle\Entity\PaymentSystem;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\HttpKernel\Exception\HttpException;

class DepositService
{
    private $em;

    private $router;

    /**
     * @var BaseDepositHandler
     */
    private $handler;

    protected $container;

    protected $config = [];

    /**
     * DepositService constructor.
     * @param EntityManager $entityManager
     */
    public function __construct($container, EntityManager $entityManager, Router $router)
    {
        $this->container = $container;
        $this->config = $this->container->getParameter('payment_systems');
        $this->em = $entityManager;
        $this->router = $router;
    }

    /**
     * @param Client $client
     * @param $paymentSystemId
     * @param $accountId
     * @param $amount
     * @param $data
     * @return Deposit
     * @throws \Exception
     */
    public function createDeposit($paymentSystemId, $accountId, $amount, $data = [])
    {
        if (empty($data)) {
            $data = [
                'cardNumber' => '',
                'method' => 'Unknown',
                'transactionId' => 0,
            ];
        }
        $paymentSystem = $this->em->getRepository(PaymentSystem::class())->find($paymentSystemId);
        if (!$paymentSystem) {
            throw new HttpException(400, 'Payment system not found');
        }
        $account = $this->em->getRepository(Account::class())->find($accountId);
        if (!$account) {
            throw new HttpException(400, 'Account not found');
        }

        $this->handler = DepositHandlerFactory::createHandler($paymentSystem, $account, $this->em, $this->router, $data);
        $this->handler->config = $this->config;
        $this->handler->container = $this->container;
        $data = $this->handler->makeDeposit($amount);
        return $data;
    }

    public function getDeposit()
    {
        if ($this->handler) {
            return $this->handler->getDeposit();
        }
        return 0;
    }

    public function getAccount()
    {
        if ($this->handler) {
            return $this->handler->getDeposit()->getAccount();
        }
        return 0;
    }
}