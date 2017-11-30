<?php

namespace NaxCrmBundle\Modules;

use Doctrine\ORM\EntityManager;
use NaxCrmBundle\Entity\Manager;
use NaxCrmBundle\Entity\UserLogItem;

class NaxUserLogger
{
    private $em;


    public function __construct(EntityManager $entityManager)
    {
        $this->em = $entityManager;
    }

    public function createLog($manager, $actionType, $objectType, $jsonPayload=[], $request = null, $userType = 'Unknown')
    {
        if (!empty($manager) && !($manager instanceof Manager)) {
            if (method_exists($manager, 'getManager')){
                $manager = $manager->getManager();
            }
        }
        if (!($manager instanceof Manager)){
            $manager = null;
        }

        $logItem = UserLogItem::createInst();
        $logItem->setManager($manager);
        $logItem->setActionType($actionType);
        $logItem->setObjectType($objectType);
        $logItem->setJsonPayload($jsonPayload);
        $logItem->setUserType($userType);

        if(!empty($request)){
            $logItem->setClientIp($request->getClientIp());
        }
        $_HEADERS = getallheaders();
        if(!empty($_HEADERS['X-Forwarded-Addr'])){
            $logItem->setClientIp($_HEADERS['X-Forwarded-Addr']);
        }

        $this->em->persist($logItem);
        $this->em->flush();
        return true;
    }
}