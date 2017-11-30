<?php

namespace NaxCrmBundle\Modules\Email\Triggers\Client;


class ResetPassword extends BaseClient
{
    protected static $alias = 'Reset password';

    public static $variables = ['RESET_PASSWORD_LINK'];

    public function getValues() {
        if (!isset($this->urls['reset_password_url'])) {
            throw new \Exception('Missing reset password url in parameters!');
        }
        $jwt = $this->calculateJwt();
        $this->client->setOneTimeJwt($jwt);
        $this->em->flush();
        return array_merge(parent::getValues(), [
            'RESET_PASSWORD_LINK' => $this->urls['reset_password_url'] . '?token=' . urlencode($jwt),
        ]);
    }
}