<?php

namespace NaxCrmBundle\Modules\Email;

use Doctrine\ORM\EntityManager;
use NaxCrmBundle\Modules\Email\Triggers\AbstractTrigger;

class TriggerFactory
{
    public static function getClass($triggerName) {
        /**@todo $triggerName can be duplicated for client and manager */
        if (class_exists($triggerName)) {
            return $triggerName;
        }
        $triggerName = ucfirst($triggerName);
        if (class_exists('NaxCrmBundle\Modules\Email\Triggers\Client\\' . $triggerName)) {
            $className = 'NaxCrmBundle\Modules\Email\Triggers\Client\\' . $triggerName;
        } elseif (class_exists('NaxCrmBundle\Modules\Email\Triggers\Manager\\' . $triggerName)) {
            $className = 'NaxCrmBundle\Modules\Email\Triggers\Manager\\' . $triggerName;
        }elseif (class_exists('NaxCrmBundle\Modules\Email\Triggers\Custom\\' . $triggerName)) {
            $className = 'NaxCrmBundle\Modules\Email\Triggers\Custom\\' . $triggerName;
        } else {
            throw new \Exception('Invalid trigger name');
        }
        return $className;
    }
    /**
     * @param $triggerName
     * @param $args
     * @return AbstractTrigger
     * @throws \Exception
     */
    public static function createTrigger($triggerName, $user, $jwtSalt, $urls, EntityManager $em, $args, $mailer) {
        $className = self::getClass($triggerName);
        /** @var AbstractTrigger $trigger */
        $trigger = new $className($args);
        $trigger->setEm($em);
        $trigger->setUser($user);
        $trigger->setJwtSalt($jwtSalt);
        $trigger->setUrls($urls);
        if(!empty($mailer['user'])){
            $trigger->from = $mailer['user'];
        }
        if(!empty($mailer['reply_to'])){
            $trigger->setReplyTo($mailer['reply_to']);
        }
        if(!empty($args['attachment'])){
            $trigger->setAttachment($args['attachment']);
        }
        return $trigger;
    }
}
