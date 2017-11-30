<?php

namespace NaxCrmBundle\Modules\Email\Triggers\Client;


class Message extends BaseClient
{
    protected static $alias = 'Custom message';
    protected $message;
    public static $variables = ['MESSAGE'];

    /**
     * CreateDepositTrigger
     * @param array $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
        $this->message = $data['message'];
    }

    public function getValues() {
        return array_merge(parent::getValues(), [
            'MESSAGE' => $this->message,
        ]);
    }
}