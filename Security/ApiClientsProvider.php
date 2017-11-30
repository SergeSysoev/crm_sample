<?php

namespace NaxCrmBundle\Security;

use NaxCrmBundle\Entity\Client;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Doctrine\ORM\EntityManager;

class ApiClientsProvider implements UserProviderInterface
{
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function loadUserByUsername($username)
    {
        $client = $this->em->getRepository(Client::class())->findOneBy([
            'email' => $username,
            'deletedAt' => null,
        ]);
        return $client;
    }

    public function refreshUser(UserInterface $user)
    {
        //Throwing this exception is proper to make things stateless
        throw new UnsupportedUserException();
    }

    public function supportsClass($class)
    {
        return 'NaxCrmBundle\Entity\Client' === $class;
    }
}