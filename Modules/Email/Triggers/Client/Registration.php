<?php

namespace NaxCrmBundle\Modules\Email\Triggers\Client;

use NaxCrmBundle\Entity\Client;

class Registration extends BaseClient
{
    protected static $alias = 'Registration';

    public static $variables = ['CONFIRM_LINK', 'LOGIN', 'PASSWORD'];

    protected $password = '';

    /**
     * @var Client
     */
    protected $client;

    public function __construct($data)
    {
        parent::__construct($data);
        $this->client = $data['client'];
        $this->password = $data['password'];
    }

    public function getValues() {
        if (!isset($this->urls['confirm_url'])) {
            throw new \Exception('Missing confirm url in parameters!');
        }
        return array_merge(parent::getValues(), [
            'CONFIRM_LINK' => $this->urls['confirm_url'] . '?token=' . urlencode($this->calculateJwt()),
            'LOGIN' => $this->client->getEmail(),
            'PASSWORD' => $this->password,
        ]);
    }
}