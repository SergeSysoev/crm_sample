<?php

namespace NaxCrmBundle\Modules;

use NaxCrmBundle\Entity\Account;
use NaxCrmBundle\Entity\Platform;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PlatformService
{
    /**
     * @var PlatformHandler[]
     */
    protected $handlers = [];

    protected $container = [];
    // protected $config = [];
    protected $defaultPlatform = 'NaxBinary';

    public function __construct($container)
    {
        $this->container = $container;
        // $this->config = $container->getParameterBag()->all();
        // \NaxCrmBundle\Debug::$messages[] = $this->config;
    }

    /**
     * @param string|Platform $platform
     * @return PlatformHandler
     * @throws \Exception
     */
    public function getHandler($platform_name = false, $exception = true)
    {
        $brand = ucfirst(Account::$defaultPlatform);
        if(empty($platform_name)){
            $platform_name = $this->defaultPlatform;
        }
        else{
            $platform_name = ucfirst($platform_name);
            if($platform_name == $brand){
                $platform_name = $this->defaultPlatform;
            }
        }

        if (empty($this->handlers[$platform_name])) {
            // $config = !empty($this->config) ? $this->config : [];
            $bundle_name = $brand . 'CrmBundle';
            $platform_class = $bundle_name . '\\Modules\\PlatformHandlers\\'.$platform_name;

            while (!class_exists($platform_class) && $bundle_name != 'NaxCrmBundle') {
                $bundle_name = $bundle_name . '\\' . $bundle_name;
                $bundle = new $bundle_name();
                $bundle_name = $bundle->getParent();
                $platform_class = $bundle_name . '\\Modules\\PlatformHandlers\\'.$platform_name;
            };
            if(!class_exists($platform_class)){
                if($exception){
                    throw new HttpException(500, 'It`s not my platform '.$platform_name);
                }
                else{
                    return false;
                }
            }

            $this->handlers[$platform_name] = new $platform_class($this->container);
        }
        return $this->handlers[$platform_name];
    }
}