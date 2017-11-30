<?php

namespace NaxCrmBundle\Controller\v1\integration;

use Symfony\Component\HttpFoundation\Request;
use NaxCrmBundle\Entity\Account;
use NaxCrmBundle\Entity\Client;
use NaxCrmBundle\Entity\Country;
use NaxCrmBundle\Entity\Manager;
use NaxCrmBundle\Entity\Department;
use NaxCrmBundle\Entity\Assignment;
use NaxCrmBundle\Entity\UserLogItem;
use NaxCrmBundle\Modules\SlackBot;
use NaxCrmBundle\Modules\Email\Triggers\Client\Registration;
use NaxCrmBundle\Debug;
use FOS\RestBundle\Controller\Annotations\Post;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use NaxCrmBundle\Controller\v1\BaseController;

class ClientController extends BaseController
{
    /**
     * @POST("/client/check", name="v1_integrate_client")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "integration",
     *    resource = "integration/client",
     *    authentication = true,
     *    description = "Return client info. Create it if not exists",
     *    statusCodes={
     *        200="Returned when successful",
     *        400={
     *          "Returned when incorrect data",
     *          "Returned when start group ignored",
     *        },
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *          "Returned when the user have not proper acl rights",
     *        },
     *        404={
     *          "Returned when can`t find account email",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     *    parameters = {
     *        {"name"="login",        "dataType"="string",   "required"=false, "format"=".+",                       "description" = "set client login"},
     *        {"name"="group",        "dataType"="string",   "required"=false, "format"="demo|live|blocked|start",  "description" = "set client group"},
     *        {"name"="is_demo",      "dataType"="boolean",  "required"=false, "format"="0|1",                      "description" = "set client account is_demo. If group=demo set is_demo=1"},
     *        {"name"="email",        "dataType"="string",   "required"=false, "format"="email format",             "description" = "set client email"},
     *        {"name"="birthday",     "dataType"="date",     "required"=false, "format"="Y-m-d",                    "description" = "set client birthday"},
     *        {"name"="registered",   "dataType"="datetime", "required"=false, "format"=".+",                       "description" = "set client registered"},
     *        {"name"="departmentId", "dataType"="integer",  "required"=false, "format"="\d+",                      "description" = "set client departmentId"},
     *        {"name"="managerId",    "dataType"="integer",  "required"=false, "format"="\d+",                      "description" = "set client managerId"},
     *        {"name"="currency",     "dataType"="string",   "required"=false, "format"=".+",                       "description" = "set client account currency"},
     *    },
     * )
     * @Security("has_role('ROLE_MANAGER') and ( is_granted('U', 'client') || is_granted('C', 'client'))")
     */
    public function integrationClientAction(Request $request)
    {
        $params = $request->request->all();
        if (empty($params['login'])) {
            return $this->getFailedJsonResponse(null, 'Incorrect data');
        }
        if (isset($params['group']) && $params['group'] == 'start') {
            return $this->getFailedJsonResponse(null, 'Start group ignored');
        }

        $def_values = [
            'group' => 'demo',
            'is_demo' => true,
            'name' => '',
            'firstname' => '',
            'lastname' => '',
            'country' => '',
            'phone' => '',
            'address' => '',
            'city' => '',
            'state' => '',
            'zip' => '',
            'langcode' => '',
            'departmentId' => 0,
            'managerId' => 0,
            'birthday' => 0,
            'registered' => 0,
            'currency' => Account::DEFAULT_CURRENCY,
        ];
        $params = array_merge($def_values, $params);

        if ($params['group'] == 'demo' && !$params['is_demo']) {//FIX for demo without is_demo
            $params['is_demo'] = true;
            Debug::$messages[] = 'WARN: incorrect is_demo for demo acc';
        }

        $new_client = $new_acc = false;
        $repo = $this->getRepository(Client::class());

        $platform = $this->getPlatformName();

        if (empty($params['email'])) {
            $platform_h = $this->getPlatformHandler();
            $rows = $platform_h->getAccountFromDB([
                'filters' => [
                    'login' => $params['login'],
                ],
                'limit' => 1,
            ]);

            if (empty($rows[0]) || empty($rows[0]['email'])) {
                return $this->getFailedJsonResponse(null, 'Can`t find account email', 404);
            }
            Debug::$messages[] = 'INFO: get account data from db';
            // Debug::$messages[] = $row;

            $params = array_merge($params, $rows[0]);
        }

        $client = $repo->findOneBy([
            'email' => $params['email'],
        ]);

        if (is_null($client)) {
            Debug::$messages[] = 'INFO: new client';
            $new_client = true;
            $client = Client::createInst();
        }

        $this->setClientFields($client, $params);

        $isDemo = $this->getIsDemo($client, $params);

        $repo = $this->getRepository(Account::class());

        if ($new_client) {
            $new_acc = true;
            $account = $repo->findOneBy([
                'externalId'  => $params['login'],
                'platform'    => $platform,
            ]);
            if(!empty($account)){
                $err = 'Account externalId already in use at client #'.$account->getClientId();
                if(((int)$params['login']) === 1  && $params['email'] == 'dev@naxtrader.com'){
                    SlackBot::send('Admin login or change data', $params, SlackBot::INFO);
                }
                else{
                    SlackBot::send($err, $params, SlackBot::CRIT);
                }
                return $this->getFailedJsonResponse($params, $err, 409);
            }

            $password = $this->getParameter('client_default_password');
            $hashedPassword = $this->get('security.password_encoder')->encodePassword($client, $password);
            $client->setPassword($hashedPassword);

            $birthday = $params['birthday'] ?: '1981-05-05';
            $client->setBirthday(\DateTime::createFromFormat('Y-m-d', $birthday));

            if ($params['registered'] > 0){
                $client->setCreated(\DateTime::createFromFormat('U', $params['registered']));
            }

            $this->setManager($client, $params);
        }
        else {
            $account = $repo->findOneBy([
                'externalId'  => $params['login'],
                // 'clientId'    => $client->getId(),
                'platform'    => $platform,
            ]);
            $new_acc = empty($account);
        }

        $type = $isDemo ? Account::TYPE_DEMO : Account::TYPE_LIVE;
        if ($new_acc) {
            Debug::$messages[] = 'INFO: new account';
            $account = Account::createInst();
            $account->setClient($client);
            $account->setPlatform($platform);
            $account->setType($type);
            $account->setCurrency($params['currency']);
            $account->setExternalId($params['login']);
            // if(validate($account)){
                $this->getEm()->persist($account);
            // }
        }
        else {
            Debug::$messages[] = 'INFO: account exist';
            if(empty($account->getClientId())){
                $err = 'Account without client';
                SlackBot::send($err, $params, SlackBot::CRIT);
                return $this->getFailedJsonResponse($params, $err, 409);
            }
            if(!$new_client && $account->getClientId()!=$client->getId()){
                $err = 'Account externalId already in use at another client #'.$account->getClientId();
                SlackBot::send($err, $params, SlackBot::CRIT);
                return $this->getFailedJsonResponse($params, $err, 409);
            }

            if ($params['group'] != 'blocked' && $type != $account->getType()) {
                $account->setType($type);
                $this->getEm()->persist($account);
                Debug::$messages[] = 'WARN: update acc type';
            }
        }

        $resp = $this->preFlushClient($client, $account, $params, $new_client, $new_acc, $isDemo);
        if($resp){
            return $resp;
        }

        $this->getEm()->persist($client);
        $this->getEm()->flush();

        $client_data = $client->export();
        $client_data['account'] = $account->export();

        if ($new_client) {
            $this->getTriggerService()->sendEmails(Registration::class(), compact('client', 'password'), $client->getLangcode());

            $this->createLog($client, UserLogItem::ACTION_TYPE_IMPORT, UserLogItem::OBJ_TYPE_CLIENTS, $client_data, 'Platform');
        }

        return $this->getJsonResponse($client_data, 'success');
    }


    /*
    *   Helpers
    */

    public function setClientFields(&$client, $params){
        /* @var Client $client */
        if (empty($params['name'])) {
            $firstname = $params['firstname'];
            $lastname = $params['lastname'];
        } else {
            $name_sep = (stripos($params['name'], 'sup') !== false) ? 'sup' : ' ';
            $name = explode($name_sep, $params['name']);
            $firstname = array_shift($name);
            $lastname = implode(' ', $name);
        }
        $client->setFirstname(trim($firstname));
        $client->setLastname(trim($lastname));

        $country = $this->getCountry($params);
        if($country){
            $client->setCountry($country);
        }

        $client->setEmail($params['email']);
        $client->setPhone($params['phone']);
        $client->setAddress($params['address']);
        $client->setState($params['state']);
        $client->setZip($params['zip']);
        $client->setCity($params['city']);
        $client->setLangcode($params['langcode']);
    }

    public function setManager(&$client, $params){
        $managerId = $params['managerId'] ?: 1;
        $departmentId = $params['departmentId'] ?: 1;
        $manager = $this->getReference(Manager::class(), $managerId);
        if(!empty($manager)){
            $client->setManager($manager);
            $departmentId = $manager->getDepartment()->getId();

            // $assignment = Assignment::createInst();
            // $assignment->setManager($manager);
            // $this->getEm()->persist($assignment);
            // $client->setAssignment($assignment);
        }
        $department = $this->getReference(Department::class(), $departmentId);
        if(!empty($department)){
            $client->setDepartment($department);
        }
    }

    public function getIsDemo($client, $params){
        return $params['is_demo'];
    }

    public function preFlushClient(&$client, &$account, $params, $new_client, $new_acc, $isDemo){
        if (!$this->validate($client)) {
            return $this->response;
        }
        return false;
    }

    public function getCountry($params){
        $repo = $this->getRepository(Country::class());
        $country = null;

        if (empty($country) && !empty($params['country'])){
//            $params['country'] = $params['country'];
            $country = $repo->findOneBy(['code' => $params['country']]);
            if(empty($country)){
                Debug::$messages[] = 'WARN: unknown country by code: ' . $params['country'];
            }
        }

        if (empty($country) && !empty($params['country'])){
            $country = $repo->findOneBy(['name' => $params['country']]);
            if(empty($country)){
                Debug::$messages[] = 'WARN: unknown country by name: ' . $params['country'];
            }
        }

        if (empty($country) && !empty($params['countryCode'])){
//            $params['countryCode'] = $params['countryCode'];
            $country = $repo->findOneBy(['code' => $params['countryCode']]);
            if(empty($country)){
                Debug::$messages[] = 'WARN: Unknown country code: ' . $params['countryCode'];
            }
        }

        if (!empty($country)){
            return $country;
        }
        else{
            Debug::$messages[] = 'WARN: empty country';
        }
        return false;
    }
}
