<?php

namespace NaxCrmBundle\Security;

use Doctrine\ORM\EntityManager;
use NaxCrmBundle\Entity\Account;
use NaxCrmBundle\Entity\Client;
use NaxCrmBundle\Entity\Manager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Authentication\SimplePreAuthenticatorInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Firebase\JWT\JWT;

class JwtAuthenticator implements SimplePreAuthenticatorInterface, AuthenticationFailureHandlerInterface
{
    private $jwtSalt;

    private $em;

    public function __construct($jwtSalt, EntityManager $em)
    {
        $this->jwtSalt  = $jwtSalt;
        $this->em       = $em;
    }

    public function createToken(Request $request, $providerKey)
    {
        $authHeader = $request->headers->get('authorization');
        if ($authHeader) {
            list($jwt) = sscanf($authHeader, 'Bearer %s');
        } else if (in_array($request->get('_route'), [
            'v1_view_document',
        ])) {
            $jwt = $request->query->get('Authorization');
        } else if (
            !empty($request->get('exportCsv')) &&
            in_array($request->get('_route'), [
                'v1_get_department_documents',
                'v1_get_compliance_report',
                'v1_get_manager_support',
                'v1_own_withdrawals',
                'v1_own_deposits',
                'v1_get_user_logs',
                'v1_managers',
                'v1_get_clients',
                'v1_get_leads',
                'v1_get_leads_deleted',
                'v1_get_notes_own',
                'v1_get_notes_department',

                'v1_demo_charge_backs',
                'v1_pro_charge_backs',

                'v1_get_client_orders',
                'v1_get_total_orders',
                'v1_get_demo_orders',
                'v1_get_pro_orders',

                'v1_demo_deposits',
                'v1_pro_deposits',

                'v1_get_cpi',
                'v1_get_cpa',
            ])
        ) {
            $jwt = $request->query->get('Authorization');
        } else if (in_array($request->get('_route'), [
            'v1_send_confirmation',
            'v1_client_self',
        ])) {
            $jwt = $request->query->get('token');
        } else {
            throw new BadCredentialsException('No JWT found');
        }

        return new PreAuthenticatedToken(
            'anon.',
            $jwt,
            $providerKey
        );
    }

    public function authenticateToken(TokenInterface $token, UserProviderInterface $userProvider, $providerKey)
    {
        if (!$userProvider instanceof ApiManagersProvider) {
            throw new \InvalidArgumentException(
                sprintf(
                    'The user provider must be an instance of ApiManagersProvider (%s was given).',
                    get_class($userProvider)
                )
            );
        }

        $jwt = $token->getCredentials();
        $secretKey = base64_decode($this->jwtSalt);

        $token = JWT::decode($jwt, $secretKey, array('HS256'));

        if ($token->data->brand != Account::$defaultPlatform) {
            throw new AccessDeniedHttpException('Wrong brand in JWT');
        }

        if ($token->data->type == 'manager') {
            /* @var $manager Manager */
            $manager = $this->em->getRepository(Manager::class())->find($token->data->id);
            if (!($manager instanceof Manager)) {
                throw new AccessDeniedHttpException('Invalid JWT');
            }
            if ($manager->getBlocked()) {
                throw new HttpException(401, 'Your account is blocked');
            }

            $manager->setAccessibleDepartmentIds($token->data->departmentIds);
            $manager->setResourcePermissions((array)$token->data->resourcePermissions);

            return new PreAuthenticatedToken(
                $manager,
                [
                    'email'          => $manager->getEmail(),
                    'password_hash'  => $manager->getPassword()
                ],
                $providerKey,
                ['ROLE_MANAGER']
            );
        } elseif ($token->data->type == 'client') {
            /* @var $client Client */
            $client = $this->em->getRepository(Client::class())->find($token->data->id);
            if (!($client instanceof Client)) {
                throw new AccessDeniedHttpException('Invalid JWT');
            }

            return new PreAuthenticatedToken(
                $client,
                [
                    'email'          => $client->getEmail(),
                    'password_hash'  => $client->getPassword()
                ],
                $providerKey,
                ['ROLE_CLIENT']
            );
        }
        throw new AccessDeniedHttpException('Invalid JWT');
    }

    public function supportsToken(TokenInterface $token, $providerKey)
    {
        return $token instanceof PreAuthenticatedToken && $token->getProviderKey() === $providerKey;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        return new JsonResponse(
            [
                'status'    => 'error',
                'message'   => $exception->getMessage()
            ],
            403
        );
    }
}