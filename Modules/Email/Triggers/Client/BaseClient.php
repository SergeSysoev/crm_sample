<?php

namespace NaxCrmBundle\Modules\Email\Triggers\Client;

use Firebase\JWT\JWT;
use NaxCrmBundle\Modules\Email\Triggers\AbstractTrigger;
use NaxCrmBundle\Entity\Client;
use NaxCrmBundle\Entity\Lead;
use NaxCrmBundle\NaxCrmBundle;
use Negotiation\Exception\InvalidArgument;

abstract class BaseClient extends AbstractTrigger
{
    /**
     * @var Client
     */
    protected $client;

    public static $variables = [
        'CLIENT_ID',
        'CLIENT_FIRSTNAME',
        'CLIENT_LASTNAME',
        'CLIENT_EMAIL',
        'BALANCE_AMOUNT',
        'BALANCE_CURRENCY',
        'ACCOUNT',
        'MANAGER_ID',
        'MANAGER_NAME',
        'MANAGER_EMAIL',
        'MANAGER_PHONE',
    ];

    /**
     * BaseClient constructor.
     * @param Client|array $client
     */
    public function __construct($client)
    {
        $this->client = $this->findObject('client', $client);
        if (!($this->client instanceof Client) && !($this->client instanceof Lead)) {
            throw new InvalidArgument('Client should be ClientEntity or LeadEntity: '. get_class($client));
        }
    }

    public function getValues() {
        $manager = $this->client->getManager();
        $values = array_merge(parent::getValues(), [
            'CLIENT_ID' => $this->client->getId(),
            'CLIENT_FIRSTNAME' => $this->client->getFirstname(),
            'CLIENT_LASTNAME' => $this->client->getLastname(),
            'CLIENT_EMAIL' => $this->client->getEmail(),
            'MANAGER_ID'    => $manager? $manager->getId():'',
            'MANAGER_NAME'  => $manager? $manager->getName():'',
            'MANAGER_EMAIL' => $manager? $manager->getEmail():'',
            'MANAGER_PHONE' => $manager? $manager->getPhone():'',
        ]);
        if (method_exists($this->client, 'getAccounts') && !$this->client->getAccounts()->isEmpty()) {
            $account = $this->client->getAccounts()->first();
            $values = array_merge($values, [
                'BALANCE_AMOUNT' => '',
                'BALANCE_CURRENCY' => $account->getCurrency(),
                'ACCOUNT' => $account->getId(),
            ]);
        }
        return $values;
    }

    public function getEmail() {
        return $this->client->getEmail();
    }

    protected function calculateJwt() {
        $tokenId    = base64_encode(mcrypt_create_iv(32));
        $issuedAt   = time();
        $notBefore  = $issuedAt + 8;
        $expire     = $notBefore + 86400;

        /*
         * Create the token as an array
         */
        $data = [
            'iat'  => $issuedAt,         // Issued at: time when the token was generated
            'jti'  => $tokenId,          // Json Token Id: an unique identifier for the token
            'nbf'  => $notBefore,        // Not before
            'exp'  => $expire,           // Expire
            'data' => [
                'clientId'  => $this->client->getId(),
                'email'     => $this->client->getEmail(),
            ]
        ];

        $jwtSalt = $this->jwtSalt;
        $secretKey = base64_decode($jwtSalt);
        $jwtToken = JWT::encode(
            $data,      //Data to be encoded in the JWT
            $secretKey, // The signing key
            'HS256'     // Algorithm used to sign the token, see https://tools.ietf.org/html/draft-ietf-jose-json-web-algorithms-40#section-3
        );

        return $jwtToken;
    }
}