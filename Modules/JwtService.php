<?php

namespace NaxCrmBundle\Modules;

use Doctrine\ORM\EntityManager;
use Firebase\JWT\JWT;
use NaxCrmBundle\Entity\Account;
use NaxCrmBundle\Entity\Client;
use NaxCrmBundle\Entity\Department;
use NaxCrmBundle\Entity\Manager;
use NaxCrmBundle\Repository\DepartmentRepository;

class JwtService
{
    private $em;

    private $jwtSalt;

    public function __construct(EntityManager $entityManager, $jwtSalt)
    {
        $this->em       = $entityManager;
        $this->jwtSalt  = $jwtSalt;
    }

    public function generateManagersJWT(Manager $manager)
    {
        if ($manager->getIsHead()) {
            /* @var $departmentRepo DepartmentRepository */
            $departmentRepo = $this->em->getRepository(Department::class());
            $departmentIds = $departmentRepo->getChildsIdList($manager->getDepartment());
            $departmentIds[] = $manager->getDepartmentId();
        } else {
            $departmentIds = [$manager->getDepartmentId()];
        }
        $data = [
            'brand'                 => Account::$defaultPlatform,
            'type'                  => 'manager',
            'id'                    => $manager->getId(),
            'email'                 => $manager->getEmail(),
            'departmentIds'         => $departmentIds,
            'resourcePermissions'   => $manager->getManagerPermissions(),
        ];

        // return $this->generateJwt(30758400, $data); // one year
        return $this->generateJwt(86400, $data); // one day
    }

    public function decodeJwt($jwt)
    {
        $secretKey = base64_decode($this->jwtSalt);
        JWT::decode($jwt, $secretKey, array('HS256'));
    }

    public function generateClientsJWT(Client $client, $lifeTimeSeconds = null)
    {
        if (is_null($lifeTimeSeconds)) {
            $lifeTimeSeconds = 86400;
        }
        $data = [
            'brand'                 => Account::$defaultPlatform,
            'type'                  => 'client',
            'id'                    => $client->getId(),
            'email'                 => $client->getEmail(),
        ];

        return $this->generateJwt($lifeTimeSeconds, $data);
    }

    private function generateJwt($expireSeconds, $data)
    {
        $tokenId    = base64_encode(mcrypt_create_iv(32));
        $issuedAt   = time();
        $notBefore  = $issuedAt;
        $expire     = $notBefore + $expireSeconds;


        $data = [
            'iat'  => $issuedAt,         // Issued at: time when the token was generated
            'jti'  => $tokenId,          // Json Token Id: an unique identifier for the token
            'nbf'  => $notBefore,        // Not before
            'exp'  => $expire,           // Expire
            'data' => $data
        ];

        $secretKey = base64_decode($this->jwtSalt);
        return JWT::encode(
            $data,      //Data to be encoded in the JWT
            $secretKey, // The signing key
            'HS256'     // Algorithm used to sign the token, see https://tools.ietf.org/html/draft-ietf-jose-json-web-algorithms-40#section-3
        );
    }
}