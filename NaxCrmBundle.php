<?php
/*
 * @file
 * @brief Main Bundle for all Brands
 */
namespace NaxCrmBundle;

use NaxCrmBundle\Entity\Account;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class NaxCrmBundle extends Bundle
{
    public function boot()
    {
        Account::$defaultPlatform = $this->container->getParameter('brand');
    }
}

class Debug
{
    public static $messages = [];

    public static function add()
    {
        $messages[] = func_get_args();
    }
}