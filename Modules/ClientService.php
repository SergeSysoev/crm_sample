<?php

namespace NaxCrmBundle\Modules;

use Doctrine\ORM\EntityManager;
use NaxCrmBundle\Entity\Account;
use NaxCrmBundle\Entity\Client;
use NaxCrmBundle\Entity\Document;

class ClientService
{
    protected $em;
    /** @var Client */
    protected $client;

    public function __construct(EntityManager $entityManager) {
        $this->em = $entityManager;
    }

    /**
     * @param Client $client
     * @return $this
     */
    public function setClient($client)
    {
        $this->client = $client;
        return $this;
    }

    /**
     * @param string $currency
     * @param int $type
     * @param string $platform
     * @return Account
     */
    public function getAccountByCurrencyAndType($currency, $type = Account::TYPE_LIVE, $platform = null)
    {
        $filter = [
            'client' => $this->client,
            'platform' =>  $platform ?: Account::$defaultPlatform,
            'type'  => $type,
            'currency' => strtoupper($currency)
        ];
        return $this->em->getRepository(Account::class())->findOneBy($filter);
    }

    /**
     * @param string $platform
     * @param int $externalId
     * @return Account
     */
    public function getAccount($externalId, $platform = null)
    {
        $filter = [
            'client' => $this->client,
            'platform' =>  $platform ?: Account::$defaultPlatform,
            'externalId' => $externalId
        ];
        return $this->em->getRepository(Account::class())->findOneBy($filter);
    }

}