<?php
namespace NaxCrmBundle\Security;

use NaxCrmBundle\Entity\AclResources;
use NaxCrmBundle\Entity\Manager;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class PermissionVoter extends Voter
{

    protected function supports($attribute, $subject)
    {
        // if the attribute isn't one we support, return false
        if (!in_array($attribute, ['C', 'R', 'U', 'D', 'I'])) {
            return false;
        }

        if (!in_array($subject, array_keys(AclResources::get('resourceNames')))) {
            return false;
        }

        return true;
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        $user = $token->getUser();
        /* @var $user Manager */
        return self::voteOnUserAttribute($attribute, $subject, $user);
    }

    static public function voteOnUserAttribute($attribute, $subject, Manager $user){
        $privileges = $user->getResourcePermissions();

        if (isset($privileges[$subject]) && in_array($attribute, $privileges[$subject])) {
            return true;
        }
        return false;
    }
}