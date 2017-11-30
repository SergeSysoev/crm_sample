<?php

namespace NaxCrmBundle\Security;

use NaxCrmBundle\Entity\Manager;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Doctrine\ORM\EntityManager;

class ApiManagersProvider implements UserProviderInterface
{
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function loadUserByUsername($username)
    {
        $manager = $this->em->getRepository(Manager::class())->findOneBy([
            'email' => $username,
            'deletedAt' => null
        ]);
        return $manager;
    }

    public function refreshUser(UserInterface $user)
    {
        //Throwing this exception is proper to make things stateless
        throw new UnsupportedUserException();
    }

    public function supportsClass($class)
    {
        return 'NaxCrmBundle\Entity\Manager' === $class;
    }
}