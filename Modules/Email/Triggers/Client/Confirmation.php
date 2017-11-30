<?php

namespace NaxCrmBundle\Modules\Email\Triggers\Client;

class Confirmation extends BaseClient
{
    protected static $alias = 'Confirmation';

    public static $variables = ['CONFIRM_LINK'];

    public function getValues() {
        if (!isset($this->urls['confirm_url'])) {
            throw new \Exception('Missing confirm url in parameters!');
        }
        return array_merge(parent::getValues(), [
            'CONFIRM_LINK' => $this->urls['confirm_url'] . '?token=' . urlencode($this->calculateJwt()),
        ]);
    }
}