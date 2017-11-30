<?php

namespace NaxCrmBundle\Controller\v1\crm;

use NaxCrmBundle\Entity\Account;
use NaxCrmBundle\Entity\Assignment;
use NaxCrmBundle\Entity\Client;
use NaxCrmBundle\Entity\Lead;
use NaxCrmBundle\Entity\Country;
use NaxCrmBundle\Entity\Department;
use NaxCrmBundle\Entity\Event;
use NaxCrmBundle\Entity\Manager;
use NaxCrmBundle\Entity\SaleStatus;
use NaxCrmBundle\Entity\UserLogItem;
use NaxCrmBundle\Modules\Email\Triggers\Client\Message;
use NaxCrmBundle\Modules\JwtService;
use NaxCrmBundle\Repository\ClientRepository;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Put;
use FOS\RestBundle\Controller\Annotations\Delete;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpKernel\Exception\HttpException;
use NaxCrmBundle\Modules\SlackBot;

use NaxCrmBundle\Controller\v1\BaseController;

class ClientController extends BaseController
{
    protected $flds_update = [
        'firstname',
        'lastname',
        'email',
        'phone',
        'country',
        'langcode',
        'city',
        'birthday',
        'address',
        'saleStatus',
        'department',

        'state',
        'zip',
    ];

    protected $csvTitles = [
        'firstname',
        'lastname',
        'email',
        'phone',
        'created',
        'country',
        'isEmailConfirmed',
        'saleStatus',
        'langcode',
        'status',
        'managerName',
        'totalDepositsAmount',
        'isOnline',
        'note1',
        'note2',
    ];

    protected function setExportDefaults()
    {
        $this->csvAliases = [
            'isEmailConfirmed' => [
                0 => 'not confirmed',
                1 => 'confirmed'
            ],
            'saleStatus' => $this->getIdNamesArray(SaleStatus::class(), 'StatusName'),
            'status' => Client::get('Statuses'),
            'managerId' => $this->getIdNamesArray(Manager::class()),
            'departmentId' => $this->getIdNamesArray(Department::class()),
        ];
        $this->csvAliases['saleStatusId'] = $this->csvAliases['saleStatus'];
        $this->csvAliases['statusId'] = $this->csvAliases['status'];
    }

    /**
     * @Get("/clients/self", name="v1_client_self")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "crm",
     *    resource = "crm/client",
     *    authentication = true,
     *    description = "Get logged in client information",
     *    statusCodes={
     *        200="Returned when successful",
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *          "Returned when the user have not proper acl rights",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     * )
     * @Security("has_role('ROLE_CLIENT')")
     */
    public function getSelf(Request $request)
    {
        $response = $this->getJsonResponse($this->getUser()->export());
        if ($jwt = $request->query->get('token')) {
            /** @var JwtService $jwtService */
            $jwtService = $this->get('nax.jwt_service');
            $jwtService->decodeJwt($jwt);
            $token = $jwtService->generateClientsJWT($this->getUser());
            $response->headers->set('Authorization', $token);
        }
        return $response;
    }

    /**
     * @Get("/clients/{id}/auth", name="v1_client_auth", requirements={"id" = "\d+"})
     * @ApiDoc(
     *    views = {"default"},
     *    section = "crm",
     *    resource = "crm/client",
     *    authentication = true,
     *    description = "Authorization in client area by manager",
     *    statusCodes={
     *        200="Returned when successful",
     *        403={
     *          "Returned when the manager hasn`t access to client area",
     *          "Returned when the user have not proper acl rights",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     * )
     * @ParamConverter("client", class="NaxCrmBundle\Entity\Client")
     * @Security("has_role('ROLE_MANAGER') && is_granted('R', 'client')")
     */
    public function authByManager($client = null)
    {
        if (empty($client)) {
            return $this->getFailedJsonResponse(null, 'Client was not found', 404);
        }
        if (!$this->getUser()->getClientAreaAccess()) {
            return $this->getFailedJsonResponse(null, 'You hasn`t access to client area', 403);
        }
        if (!$this->getUser()->hasAccessToClient($client)) {
            return $this->getFailedJsonResponse(null, 'You can`t access this client', 403);
        }
        $client_host = $this->getParameter('client_host');
        if(empty($client_host) || $client_host=='http://test.com/'){
            throw new HttpException(500, 'Client host not defined');
        }
        $jwtService = $this->get('nax.jwt_service');
        $token = $jwtService->generateClientsJWT($client);
        $url = $client_host.'auth?token='.$token;

        $this->createUserLog(UserLogItem::ACTION_TYPE_MNG_LOGIN, UserLogItem::OBJ_TYPE_CLIENTS, $client->export());
        return $this->getJsonResponse($url);
    }

    /**
     * @Get("/clients/{id}", name="v1_get_client", requirements={"id" = "\d+"})
     * @ApiDoc(
     *    views = {"default"},
     *    section = "crm",
     *    resource = "crm/client",
     *    authentication = true,
     *    description = "Get selected client information",
     *    statusCodes={
     *        200="Returned when successful",
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *          "Returned when the user have not proper acl rights",
     *          "Returned when manager has no access to the client",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     *    requirements = {
     *         {"name"="id", "dataType"="integer", "description"="select client by id", "requirement"="\d+"},
     *    },
     * )
     * @ParamConverter("client", class="NaxCrmBundle\Entity\Client")
     * @Security("(has_role('ROLE_MANAGER') && is_granted('R', 'client')) || (has_role('ROLE_CLIENT') && user == client)")
     */
    public function getClientAction($client = null)
    {
        if (empty($client)) {
            return $this->getFailedJsonResponse(null, 'Client was not found', 404);
        }
        $user = $this->getUser();
        if (($user instanceof Manager) && !$user->hasAccessToClient($client)) {
            return $this->getFailedJsonResponse(null, 'Manager has no access to the client', 403);
        }
        return $this->getJsonResponse($client->export());
    }

    /**
     * @Get("/clients/list", name="v1_get_clients_list")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "crm",
     *    resource = "crm/client",
     *    authentication = true,
     *    description = "Get manager clients list",
     *    statusCodes={
     *        200="Returned when successful",
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *          "Returned when the user have not proper acl rights",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     *    parameters = {
     *        {"name"="filters",   "dataType"="collection", "required"=false, "format"="filters[columnName]=value",                "description" = "set array of filters. You can get list of filters in response"},
     *        {"name"="order",     "dataType"="collection", "required"=false, "format"="order[column]=columnName&order[dir]=DESC", "description" = "add order column and direction for output."},
     *        {"name"="limit",     "dataType"="integer",    "required"=false, "format"="\d+",                                      "description" = "add limit for output."},
     *        {"name"="offset",    "dataType"="integer",    "required"=false, "format"="\d+",                                      "description" = "add offset shifting for output."},
     *    },
     * )
     * @Security("has_role('ROLE_MANAGER') && is_granted('R', 'client')")
     */
    public function getClientsListAction(Request $request)
    {
        $params = $request->query->all();
        $preFilters = $this->setDepAccessFilters();
        $res = $this->getList($params, $preFilters, 'getClientList');
        return $this->getJsonResponse($res);
    }

    /**
     * @Get("/clients", name="v1_get_clients")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "crm",
     *    resource = "crm/client",
     *    authentication = true,
     *    description = "Get manager clients",
     *    statusCodes={
     *        200="Returned when successful",
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *          "Returned when the user have not proper acl rights",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     *    parameters = {
     *        {"name"="filters",   "dataType"="collection", "required"=false, "format"="filters[columnName]=value",                "description" = "set array of filters. You can get list of filters in response"},
     *        {"name"="order",     "dataType"="collection", "required"=false, "format"="order[column]=columnName&order[dir]=DESC", "description" = "add order column and direction for output."},
     *        {"name"="limit",     "dataType"="integer",    "required"=false, "format"="\d+",                                      "description" = "add limit for output."},
     *        {"name"="offset",    "dataType"="integer",    "required"=false, "format"="\d+",                                      "description" = "add offset shifting for output."},
     *        {"name"="exportCsv", "dataType"="string",     "required"=false, "format"="lzw encoded array",                        "description" = "used for export result in CSV file"},
     *    },
     * )
     * @Security("has_role('ROLE_MANAGER') && is_granted('R', 'client')")
     */
    public function getClientsAction(Request $request)
    {
        $params = $request->query->all();
        $preFilters = $this->setDepAccessFilters();
        $res = $this->getList($params, $preFilters);
        return $this->getJsonResponse($res);
    }

    /**
     * @Put("/clients/{id}", name="v1_update_client", requirements={"id" = "\d+"})
     * @ApiDoc(
     *    views = {"default"},
     *    section = "crm",
     *    resource = "crm/client",
     *    authentication = true,
     *    description = "Update client",
     *    statusCodes={
     *        200="Returned when successful",
     *        400={
     *          "Unknown country code",
     *          "Unknown country",
     *          "Please provide birthday in Y-m-d format",
     *        },
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *          "Returned when the user have not proper acl rights",
     *          "Returned when you can't edit another client",
     *        },
     *        404={
     *          "Returned when client was not found",
     *          "Returned when saleStatus not found",
     *          "Returned when manager not found",
     *          "Returned when department not found",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     *    parameters = {
     *        {"name"="firstname",    "dataType"="string",  "required"=false, "format"=".+",           "description" = "set client firstname"},
     *        {"name"="lastname",     "dataType"="string",  "required"=false, "format"=".+",           "description" = "set client lastname"},
     *        {"name"="email",        "dataType"="string",  "required"=false, "format"=".+",           "description" = "set client email"},
     *        {"name"="phone",        "dataType"="string",  "required"=false, "format"=".+",           "description" = "set client phone"},
     *        {"name"="countryCode",  "dataType"="string",  "required"=false, "format"=".+",           "description" = "set client countryCode"},
     *        {"name"="country",      "dataType"="string",  "required"=false, "format"=".+",           "description" = "set client country"},
     *        {"name"="langcode",     "dataType"="string",  "required"=false, "format"=".+",           "description" = "set client langcode"},
     *        {"name"="city",         "dataType"="string",  "required"=false, "format"=".+",           "description" = "set client city"},
     *        {"name"="birthday",     "dataType"="date",    "required"=false, "format"="Y-m-d format", "description" = "set client birthday"},
     *        {"name"="address",      "dataType"="string",  "required"=false, "format"=".+",           "description" = "set client address"},
     *        {"name"="saleStatus",   "dataType"="integer", "required"=false, "format"="\d+",          "description" = "set saleStatus"},
     *        {"name"="departmentId", "dataType"="integer", "required"=false, "format"="\d+",          "description" = "set client owner departmentId"},
     *        {"name"="externalId",   "dataType"="integer", "required"=false, "format"="\d+",          "description" = "set client externalId"},
     *    },
     * )
     * @ParamConverter("client", class="NaxCrmBundle:Client")
     * @Security("(has_role('ROLE_MANAGER')) || (has_role('ROLE_CLIENT') && user == client)")
     */
    public function updateClientAction(Request $request, $client = null)
    {
        if (empty($client)) {
            return $this->getFailedJsonResponse(null, 'Client was not found', 404);
        }
        $user = $this->getUser();

        if ($user instanceof Client) {
            if ($user != $client) {
                return $this->getFailedJsonResponse(null, 'You can`t edit another client', 403);
            }
        }

        $old_data = $client->export();
        $params = $request->request->all();

        $params = $this->updatePrepare($params, $client);
        $this->fillEntity($client, $this->flds_update, $params);

        if ($user instanceof Manager) {
            $this->updateManager($client, $params);
        }

        if (!$this->validateClient($client)) {
            return $this->response;
        }

        $this->getEm()->flush();
        // $this->getEm()->refresh($client);

        $data = $client->export();
        foreach ($client->getAccounts() as $account) {
            if ($account->getExternalId()) {
                $this->getPlatformHandler($account->getPlatform())->setAccount($account);
            }
        }

        $diff = $this->arrayDiff($data, $old_data);
        if(!empty($diff)){
            $this->addEvent($client, 'updated', $diff);
            $diff['id'] = $client->getId();
            $this->createUserLog(UserLogItem::ACTION_TYPE_UPDATE, UserLogItem::OBJ_TYPE_CLIENTS, $diff);
        }
        return $this->getJsonResponse($data);
    }

    /**
     * @Put("/clients/batch/update", name="v1_update_batch_client")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "crm",
     *    resource = "crm/client",
     *    authentication = true,
     *    description = "Batch update of clients",
     *    statusCodes={
     *        200="Returned when successful",
     *        400="Invalid request",
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *          "Returned when the user have not proper acl rights",
     *          "Returned when you can't edit another client",
     *        },
     *        404="Returned when saleStatus not found",
     *        500="Returned when Something went wrong",
     *    },
     *    parameters = {
     *        {"name"="ids",    "dataType"="array", "required"=true, "format"=".+",  "description" = "ids of clients"},
     *        {"name"="fields", "dataType"="array", "required"=true, "format"="\d+", "description" = "fields for change"},
     *    },
     * )
     * @Security("has_role('ROLE_MANAGER') && is_granted('U', 'client')")
     */
    public function batchUpdateClientAction(Request $request)
    {
        $params = $request->request->all();
        if(empty($params['ids'])){
            return $this->getFailedJsonResponse(null, 'You must provide client ids');
        }
        if(is_array($params['ids'])){
            $params['ids'] = implode(',',$params['ids']);
        }

        $fields = $this->batchFieldsPrepare($params);
        $repo = $this->getRepository(Client::class());
        $res = $repo->batchUpdate($params['ids'], $fields);

        $diff=[
            'ids' => $params['ids'],
            'fields' => $fields,
            'result' => $res? 'success':'failed',
        ];
        $this->createUserLog(UserLogItem::ACTION_TYPE_UPDATE, UserLogItem::OBJ_TYPE_CLIENTS, $diff);
        return $res?
            $this->getJsonResponse('Success'):
            $this->getFailedJsonResponse(null, 'Update operation failed', 500);
    }

    /**
     * @Post("/clients/{id}/confirmation", name="v1_send_confirmation")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "crm",
     *    resource = "crm/client",
     *    authentication = true,
     *    description = "Send client email confirmation",
     *    statusCodes={
     *        200="Returned when successful",
     *        404={
     *          "Returned when client was not found",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     *    requirements = {
     *         {"name"="id", "dataType"="integer", "description"="select client by id", "requirement"="\d+"},
     *    },
     * )
     * @ParamConverter("client", class="NaxCrmBundle:Client")
     * @Security("(has_role('ROLE_MANAGER') && is_granted('U', 'client')) || (has_role('ROLE_CLIENT') && user == client)")
     */
    public function sendClientConfirmationEmailAction($client = null)
    {
        /* @var Client $client */
        if (empty($client)) {
            return $this->getFailedJsonResponse(null, 'Client was not found', 404);
        }
        $this->getTriggerService()->sendEmails(Confirmation::class(), compact('client'), $client->getLangcode());

        return $this->getJsonResponse(null, 'You will receive an email soon');
    }

    /**
     * @Put("/clients/{id}/password", name="v1_update_client_password", requirements={"id" = "\d+"})
     * @ApiDoc(
     *    views = {"default"},
     *    section = "crm",
     *    resource = "crm/client",
     *    authentication = true,
     *    description = "Get managers and their activity",
     *    statusCodes={
     *        200="Returned when successful",
     *        400={
     *          "Returned when missing required parameter",
     *          "Returned when password and confirmation doesn`t match",
     *          "Returned when wrong password",
     *        },
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *        },
     *        404={
     *          "Returned when client was not found",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     *    parameters = {
     *        {"name"="password",     "dataType"="string", "required"=true, "format"=".+", "description"="set new password"},
     *        {"name"="confirmation", "dataType"="string", "required"=true, "format"=".+", "description"="set new password again"},
     *        {"name"="old_password", "dataType"="string", "required"=true, "format"=".+", "description"="set old password"},
     *    },
     *    requirements = {
     *         {"name"="id", "dataType"="integer", "description"="select client by id", "requirement"="\d+"},
     *    },
     * )
     * @ParamConverter("client", class="NaxCrmBundle:Client")
     */
    // * @Security("has_role('ROLE_MANAGER') && is_granted('U', 'client')")
    public function updatePasswordAction($client = null)
    {
        /* @var Client $client */
        if (empty($client)) {
            return $this->getFailedJsonResponse(null, 'Client was not found', 404);
        }
        if ($this->getUser() != $client && !$this->getUser()->hasAccessToClient($client)) {
            return $this->getFailedJsonResponse(null, 'You can\'t access this client', 403);
        }
        $password = $this->getRequiredParam('password');
        $confirmation = $this->getRequiredParam('confirmation');
        $oldPassword = $this->getRequiredParam('old_password');
        if ($password != $confirmation) {
            return $this->getFailedJsonResponse([], 'Password and confirmation doesn`t match');
        }
        if (!$this->get('security.password_encoder')->isPasswordValid($client, $oldPassword)) {
            return $this->getFailedJsonResponse([], 'Wrong password');
        }

        $hashedPassword = $this->get('security.password_encoder')->encodePassword($client, $password);
        $client->setPassword($hashedPassword);
        $this->getEm()->flush();
        $this->getEm()->refresh($client);

        $manager = $client->getManager();
        if(is_null($manager)){
            $manager = $this->getRepository(Manager::class())->find(1);
        }

        $this->createUserLog(UserLogItem::ACTION_TYPE_UPDATE, UserLogItem::OBJ_TYPE_CLIENTS, [
            'id' => $client->getId(),
            'password' => 'changed',
        ]);
        $this->addEvent($client, 'password was changed');
        return $this->getJsonResponse($client->export());
    }

    /**
     * @Post("/clients/block", name="v1_batch_block_clients")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "crm",
     *    resource = "crm/client",
     *    authentication = true,
     *    description = "Batch block clients",
     *    statusCodes={
     *        200="Returned when successful",
     *        400={
     *          "Returned when missing required parameter",
     *        },
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *          "Returned when the user have not proper acl rights",
     *          "Returned when you can`t access this client",
     *        },
     *        404={
     *          "Returned when no one clients were found for given ids",
     *        },
     *        500="Returned when Something went wrong",
     *        503="Platform rejection",
     *    },
     *    parameters = {
     *        {"name"="clientIds", "dataType"="collection", "required"=true, "format"="\d+", "description" = "set array of client ids"},
     *    },
     * )
     * @Security("has_role('ROLE_MANAGER') && is_granted('U', 'client')")
     */
    public function batchBlockClientAction(Request $request)
    {
        $managers = [];
        $errors = $ids = [];
        $params = $request->request->all();
        $clientIds = $this->getRequiredParam('clientIds');
        if(!is_array($clientIds)){
            $clientIds = json_decode($clientIds);
        }
        // return $this->getJsonResponse($clientIds);
        $clients = $this->getRepository(Client::class())->findBy(['id' => $clientIds]);
        if (count($clients) == 0) {
            return $this->getFailedJsonResponse(null, 'No one clients were found for given ids', 404);
        }

        $user = $this->getUser();
        foreach ($clients as $client) {
            if (!$user->hasAccessToClient($client)) {
                return $this->getFailedJsonResponse(null, 'You can`t access this client', 403);
            }
            $res = $this->blockClient($client, false);
            if($res['acc']<=$res['err']){
                $errors[]=$client->getId();
            }
            else{
                if(!isset($managers[$client->getManagerId()])){
                    $managers[$client->getManagerId()] = [
                        'mngr'  => $client->getManager(),
                        'clients' => 0,
                    ];
                }
                $managers[$client->getManagerId()]['clients']++;
                $ids[]=$client->getId();
            }
        }
        $this->getEm()->flush();

        $log = [
            'ids' => $ids,
            'status' => Client::get('Statuses')[Client::STATUS_BLOCKED],
        ];
        if(!empty($params['blockReason'])){
            $log['blockReason'] = $params['blockReason'];
        }
        $this->createUserLog(UserLogItem::ACTION_TYPE_UPDATE, UserLogItem::OBJ_TYPE_CLIENTS, $log);

        foreach ($managers as $mId => $v) {
            if($mId == $this->getUser()->getId()){
                continue;
            }
            $event = $this->createEvent($v['mngr'], $v['clients'].' clients were blocked', 'Clients were blocked');
            $this->getEm()->persist($event);
            $this->getEm()->flush();
        }

        if(!empty($errors)){
            if(count($errors)==count($clients)){
                return $this->getFailedJsonResponse(null, 'Selected clients were not blocked', 503);
            }
            $errors = implode(', ', $errors);
            return $this->getFailedJsonResponse(null, 'These clients were not blocked: '.$errors, 503);
        }
        return $this->getJsonResponse(null, count($clients).' clients were blocked');
    }

    /**
     * @Post("/clients/{id}/block", name="v1_block_client")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "crm",
     *    resource = "crm/client",
     *    authentication = true,
     *    description = "Block selected client",
     *    statusCodes={
     *        200="Returned when successful",
     *        201="Returned when successful with some warnings",
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *          "Returned when the user have not proper acl rights",
     *          "Returned when you can`t access this client",
     *        },
     *        404={
     *          "Returned when client was not found",
     *        },
     *        500="Returned when Something went wrong",
     *        503="Platform rejection",
     *    },
     *    requirements = {
     *         {"name"="id", "dataType"="integer", "description"="select client by id", "requirement"="\d+"},
     *    },
     * )
     * @ParamConverter("client", class="NaxCrmBundle\Entity\Client")
     * @Security("has_role('ROLE_MANAGER') && is_granted('U', 'client')")
     */
    public function blockClientAction(Request $request, $client = null)
    {
        $params = $request->request->all();
        if (empty($client)) {
            return $this->getFailedJsonResponse(null, 'Client was not found', 404);
        }
        if (!$this->getUser()->hasAccessToClient($client)) {
            return $this->getFailedJsonResponse(null, 'You can`t access this client', 403);
        }
        $res = $this->blockClient($client);
        $this->getEm()->flush();

        $log = [
            'id' =>$client->getId(),
            'status' => Client::get('Statuses')[Client::STATUS_BLOCKED],
            'result' => $res['err']?'with errors':'success',
        ];
        if(!empty($params['blockReason'])){
            $log['blockReason'] = $params['blockReason'];
        }
        $this->createUserLog(UserLogItem::ACTION_TYPE_UPDATE, UserLogItem::OBJ_TYPE_CLIENTS, $log);
        $this->addEvent($client, 'was blocked');
        return $res['err']?
            $this->getJsonResponse($client->export(), "Can`t block {$res['err']} of {$res['acc']} client`s accounts", 201):
            $this->getJsonResponse($client->export(), 'Client was blocked');
    }

    /**
     * @Post("/clients/{id}/unblock", name="v1_unblock_client")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "crm",
     *    resource = "crm/client",
     *    authentication = true,
     *    description = "Unblock selected client",
     *    statusCodes={
     *        200="Returned when successful",
     *        201="Returned when successful with some warnings",
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *          "Returned when the user have not proper acl rights",
     *          "Returned when you can`t access this client",
     *        },
     *        404={
     *          "Returned when client was not found",
     *        },
     *        500="Returned when Something went wrong",
     *        503="Platform rejection",
     *    },
     *    requirements = {
     *         {"name"="id", "dataType"="integer", "description"="select client by id", "requirement"="\d+"},
     *    },
     * )
     * @ParamConverter("client", class="NaxCrmBundle\Entity\Client")
     * @Security("has_role('ROLE_MANAGER') && is_granted('U', 'client')")
     */
    public function unblockClientAction($client = null)
    {
        /* @var Client $client */
        if (empty($client)) {
            return $this->getJsonResponse(null, 'Client was not found', 404);
        }
        if (!$this->getUser()->hasAccessToClient($client)) {
            return $this->getFailedJsonResponse(null, 'You can`t access this client', 403);
        }
        $res = $this->unblockClient($client);
        $this->getEm()->flush();

        $this->createUserLog(UserLogItem::ACTION_TYPE_UPDATE, UserLogItem::OBJ_TYPE_CLIENTS, [
            'id' =>$client->getId(),
            'status' => Client::get('Statuses')[Client::STATUS_UNBLOCKED],
            'result' => $res['err']?'with errors':'success',
        ]);
        $this->addEvent($client, 'was unblocked');
        return $res['err']?
            $this->getJsonResponse($client->export(), "Can`t unblock {$res['err']} of {$res['acc']} client`s accounts", 201):
            $this->getJsonResponse($client->export(), 'Client was unblocked');
    }

    // /**
    //  * @Post("/clients/{id}/assign", name="v1_assign_client")
    //  * @ApiDoc(
    //  *    views = {"default"},
    //  *    section = "crm",
    //  *    resource = "crm/client",
    //  *    authentication = true,
    //  *    description = "Assign manager and/or department to client",
    //  *    statusCodes={
    //  *        200="Returned when successful",
    //  *        403={
    //  *          "Returned when the user is not authorized with JWT token",
    //  *          "Returned when the user have not proper acl rights",
    //  *        },
    //  *        404={
    //  *          "Returned when client was not found",
    //  *          "Returned when manager was not found",
    //  *          "Returned when department was not found",
    //  *        },
    //  *        500="Returned when Something went wrong",
    //  *    },
    //  *    parameters = {
    //  *        {"name"="managerId",    "dataType"="integer", "required"=false, "format"="\d+", "description"="set client new managerId"},
    //  *        {"name"="departmentId", "dataType"="integer", "required"=false, "format"="\d+", "description"="set client new departmentId"},
    //  *    },
    //  *    requirements = {
    //  *         {"name"="id", "dataType"="integer", "description"="select client by id", "requirement"="\d+"},
    //  *    },
    //  * )
    //  * @ParamConverter("client", class="NaxCrmBundle:Client")
    //  * @Security("has_role('ROLE_MANAGER') && is_granted('U', 'client')")
    //  */
    // public function assignClientAction(Request $request, $client = null)
    // {
    //     /* @var Client $client */
    //     if (empty($client)) {
    //         return $this->getJsonResponse(null, 'Client was not found', 404);
    //     }

    //     $diff = [];
    //     if ($newManagerId = $request->request->get('managerId')) {
    //         /** @var Manager $manager */
    //         $manager = $this->getRepository(Manager::class())->find($newManagerId);
    //         if (!$manager) {
    //             return $this->getFailedJsonResponse(null, 'Manager not found', 404);
    //         }
    //         $client->setManager($manager);
    //         $newDepartmentId = $manager->getDepartmentId();
    //         $diff['New manager ID']    = $newManagerId;
    //         $diff['New department ID'] = $newDepartmentId;
    //     } else {
    //         $newDepartmentId = $request->request->get('departmentId');
    //         $diff['New department ID'] = $newDepartmentId;
    //     }
    //     if (!$newDepartmentId) {
    //         return $this->getFailedJsonResponse(null, 'Department not found', 404);
    //     }
    //     $client->setDepartmentId($newDepartmentId);

    //     $this->getEm()->flush();

    //     if(!empty($diff)){
    //         $diff['id'] = $client->getId();
    //         $this->createUserLog(UserLogItem::ACTION_TYPE_UPDATE, UserLogItem::OBJ_TYPE_CLIENTS, $diff);
    //     }
    //     return $this->getJsonResponse($client->export());
    // }

    /**
     * @Post("/clients/assign-batch", name="v1_assign_batch_client")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "crm",
     *    resource = "crm/client",
     *    authentication = true,
     *    description = "Batch assign manager and/or department to clients",
     *    statusCodes={
     *        200="Returned when successful",
     *        400={
     *          "Returned when missing required parameter",
     *        },
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *          "Returned when the user have not proper acl rights",
     *        },
     *        404={
     *          "Returned when manager was not found",
     *          "Returned when department was not found",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     *    parameters = {
     *        {"name"="clientIds",    "dataType"="collection", "required"=true,  "format"="\d+", "description"="set array of clientId"},
     *        {"name"="managerId",    "dataType"="integer",    "required"=false, "format"="\d+", "description"="set clients new managerId"},
     *        {"name"="departmentId", "dataType"="integer",    "required"=false, "format"="\d+", "description"="set clients new departmentId"},
     *    },
     * )
     * @Security("has_role('ROLE_MANAGER') && is_granted('U', 'client')")
     */
    public function assignBatchClientAction(Request $request)
    {
        $clientIds = $this->getRequiredParam('clientIds');
        $em = $this->getEm();
        $clientRepo = $this->getRepository(Client::class());
        /** @var Manager $newManager */
        $newManager = null;
        $diff = [];
        if ($newManagerId = $this->getRequiredParam('managerId')) {
            if (!($newManager = $this->getRepository(Manager::class())->find($newManagerId))) {
                return $this->getFailedJsonResponse(null, 'Manager not found', 404);
            }
            $newDepartment = $newManager->getDepartment();
            $diff['New manager ID'] = $newManagerId;
            $diff['New department ID'] = $newDepartment->getId();
        } else {
            $newDepartmentId = $this->getRequiredParam('departmentId');
            if (!($newDepartment = $this->getRepository(Department::class())->find($newDepartmentId))) {
                return $this->getFailedJsonResponse(null, 'Department not found', 404);
            }
            $diff['New department ID'] = $newDepartmentId;
        }

        $assignment = Assignment::createInst();
        if ($newManager) {
            $assignment->setManager($newManager);
        }
        $assignment->setDepartment($newDepartment);
        $em->persist($assignment);
        $em->flush();

        $assignedClientsCount = 0;
        $oldManagers = [];
        /* @var Client $client */
        foreach ($clientRepo->findBy(['id' => $clientIds]) as $client) {
            if (isset($newManager)) {
                $assignedClientsCount++;
                $client->setManager($newManager);
            }
            //count clients assigned out from manager
            if ($client->getManager()) {
                if (empty($oldManagers[$client->getManager()->getId()])) {
                    $oldManagers[$client->getManager()->getId()] = 1;
                } else {
                    ++$oldManagers[$client->getManager()->getId()];
                }
            }
            $client->setAssignment($assignment);
            $client->setDepartment($newDepartment);
        }
        // create events for everyone of old managers
        foreach ($oldManagers as $oldManagerId => $assignedClientsCountFrom) {
            if ($oldManagerId != $this->getUser()->getId() && (!$newManager || $oldManagerId != $newManager->getId())) {
                $oldManager = $em->getPartialReference(Manager::class(), $oldManagerId);
                $eventFrom = $this->createEvent(
                    $oldManager,
                    "{$assignedClientsCountFrom} clients were reassigned from you", 'Clients reassigned',
                    Event::OBJ_TYPE_CLIENT_ASSIGNMENT, $assignment->getId()
                );
                $em->persist($eventFrom);
            }
        }
        //make one event for new manager if he exist
        if ($newManager && $newManager->getId() != $this->getUser()->getId() && ($assignedClientsCount > 0)) {
            $eventTo = $this->createEvent(
                $newManager,
                "{$assignedClientsCount} clients were assigned to you", 'New clients',
                Event::OBJ_TYPE_CLIENT_ASSIGNMENT, $assignment->getId()
            );
            $em->persist($eventTo);
        }
        $em->flush();

        if(!empty($diff)){
            $diff['ids'] = $clientIds;
            $this->createUserLog(UserLogItem::ACTION_TYPE_UPDATE, UserLogItem::OBJ_TYPE_CLIENTS, $diff);
        }
        return $this->getJsonResponse(null, 'Clients were assigned');
    }

    /**
     * @Get("/assignments/{id}/clients", name="v1_get_assignment_clients")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "crm",
     *    resource = "crm/client",
     *    authentication = true,
     *    description = "Get all assignment clients",
     *    statusCodes={
     *        200="Returned when successful",
     *        400={
     *          "Returned when missing required parameter",
     *        },
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *          "Returned when the user have not proper acl rights",
     *        },
     *        404={
     *          "Returned when assignment was not found",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     *    parameters = {
     *        {"name"="filters",   "dataType"="collection", "required"=false, "format"="filters[columnName]=value",                "description" = "set array of filters. You can get list of filters in response"},
     *        {"name"="order",     "dataType"="collection", "required"=false, "format"="order[column]=columnName&order[dir]=DESC", "description" = "add order column and direction for output."},
     *        {"name"="limit",     "dataType"="integer",    "required"=false, "format"="\d+",                                      "description" = "add limit for output."},
     *        {"name"="offset",    "dataType"="integer",    "required"=false, "format"="\d+",                                      "description" = "add offset shifting for output."},
     *    },
     *    requirements = {
     *         {"name"="id", "dataType"="integer", "description"="select assignment by id", "requirement"="\d+"},
     *    },
     * )
     * @ParamConverter("assignment", class="NaxCrmBundle:Assignment")
     * @Security("has_role('ROLE_MANAGER') && is_granted('R', 'client')")
     */
    public function getAssignmentClientsAction(Request $request, $assignment = null)
    {
        if (empty($assignment)) {
            return $this->getJsonResponse(null, 'Assignment was not found', 404);
        }

        $params = $request->query->all();
        $preFilters = [
            'assignmentIds' => [$assignment->getId()]
        ];
        $res = $this->getList($params, $preFilters);
        return $this->getJsonResponse($res);
    }

    /**
     * @Get("/clients/compliance-report", name="v1_get_compliance_report")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "crm",
     *    resource = "crm/client",
     *    authentication = true,
     *    description = "Get clients compliance report",
     *    statusCodes={
     *        200="Returned when successful",
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *          "Returned when the user have not proper acl rights",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     *    parameters = {
     *        {"name"="filters",   "dataType"="collection", "required"=false, "format"="filters[columnName]=value",                "description" = "set array of filters. You can get list of filters in response"},
     *        {"name"="order",     "dataType"="collection", "required"=false, "format"="order[column]=columnName&order[dir]=DESC", "description" = "add order column and direction for output."},
     *        {"name"="limit",     "dataType"="integer",    "required"=false, "format"="\d+",                                      "description" = "add limit for output."},
     *        {"name"="offset",    "dataType"="integer",    "required"=false, "format"="\d+",                                      "description" = "add offset shifting for output."},
     *        {"name"="exportCsv", "dataType"="string",     "required"=false, "format"="lzw encoded array",                        "description" = "used for export result in CSV file"},
     *    },
     * )
     * @Security("has_role('ROLE_MANAGER') && is_granted('R', 'compliance')")
     */
    public function getComplianceReportAction(Request $request)
    {
        $params = $request->query->all();
        $preFilters = $this->setDepAccessFilters();
        $this->exportFileName = 'Compliance report';
        $this->csvTimeField = 'lastDocumentTime';
        $this->csvTitles = [
            'clientId',
            'email',
            'firstname',
            'lastname',
            'account_type',
            'lastDocumentTime',
        ];
        $this->csvAliases = [];
        $res = $this->getList($params, $preFilters, 'getComplianceReportData');
        return $this->getJsonResponse($res);
    }

    /**
     * @Post("/clients/{id}/sendmail", name="v1_client_sendmail")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "crm",
     *    resource = "crm/client",
     *    authentication = true,
     *    description = "Send mail",
     *    statusCodes={
     *        200="Returned when successful",
     *        400={
     *          "Returned when missing required parameter",
     *        },
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *          "Returned when the user have not proper acl rights",
     *          "Returned when attachment with it mime type is not allowed",
     *          "Returned when attachment with it extension is not allowed",
     *        },
     *        404={
     *          "Returned when client was not found",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     *    parameters = {
     *        {"name"="message", "dataType"="string", "required"=true, "format"=".+", "description" = "message text"},
     *    },
     *    requirements = {
     *         {"name"="id", "dataType"="integer", "description"="select client by id", "requirement"="\d+"},
     *    },
     * )
     * @ParamConverter("client", class="NaxCrmBundle:Client")
     * @Security("has_role('ROLE_MANAGER') && is_granted('R', 'client') && user.hasAccessToClient(client)")
     */
    public function clientSendMailAction(Request $request, $client = null)
    {
        if (empty($client)) {
            return $this->getFailedJsonResponse(null, 'Client was not found', 404);
        }
        $message = $this->getRequiredParam('message');

        $attachment = false;
        $files = $request->files;
        foreach ($files as $k => $file) {
            $attach_name = mb_substr($file->getClientOriginalName(), 0, 225);
            $file_name = uniqid(time());

            $file->move($this->getAbsoluteUploadPath('/attachments/'), $file_name);
            $attachment = implode(';', [$file_name, $attach_name]);
        }

        $res = $this->getTriggerService()->sendEmails(Message::class(), compact('client', 'message', 'attachment'), $client->getLangcode());
        if($res[1]>0){
            return $this->getJsonResponse($attachment, 'Message successfully added to order for send');
        }
        else{
            return $this->getFailedJsonResponse($res, '0 messages sended. Please check email templates');
        }
    }

    // ClientController helpers



    protected function validateClient($client){
        $dupRepo = $this->getRepository(Lead::class());
        if(($dup = $dupRepo->findOneBy(['phone' => $client->getPhone()]))){
            $this->getFailedJsonResponse(null, 'This phone already used at Lead #'.$dup->getId(), 400);
            return false;
        }
        if($dup = $dupRepo->findOneBy(['email' => $client->getEmail()])){
            $this->getFailedJsonResponse(null, 'This email already used at Lead #'.$dup->getId(), 400);
            return false;
        }
        return $this->validate($client);
    }

    protected function updatePrepare($params, $client){

        if (!empty($params['countryCode'])) {
            $params['countryCode'] = strtoupper($params['countryCode']);
            $params['country'] = $this->getRepository(Country::class())->findOneBy(['code' => $params['countryCode']]);
            if (empty($params['country'])) {
                throw new HttpException(404, 'Unknown country code '.$params['countryCode']);
            }
        }
        else if(!empty($params['country'])) {//maybe not needed
            $params['country'] = $country = $this->getRepository(Country::class())->find($params['country']);
            if (empty($params['country'])) {
                throw new HttpException(404, 'Unknown country #'.$params['country']);
            }
        }

        if (!empty($params['birthday'])) {
            $birthday = \DateTime::createFromFormat('Y-m-d', $params['birthday']);
            if (!$birthday) {
               throw new HttpException(400, "Please provide birthday '{$params['birthday']}' in 'Y-m-d' format");
            }
            $params['birthday'] = $birthday;
        }

        if (!empty($params['saleStatus'])) {
            $params['saleStatus'] = $this->getRepository(SaleStatus::class())->find($params['saleStatus']);
            if (empty($params['saleStatus'])) {
                throw new HttpException(404, 'SaleStatus not found '.$params['saleStatus']);
            }
        }

        if (!empty($params['departmentId'])) {
            $params['department'] = $this->getRepository(Department::class())->find($params['departmentId']);
            if (empty($params['department'])) {
                throw new HttpException(404, 'Department not found '.$params['departmentId']);
            }
        }

        return $params;
    }

    protected function updateManager(&$client, $params){
        $manager = null;
        if (empty($params['managerId'])){

            throw new HttpException(400, 'Missing required parameter managerId');
        }
        if(!($manager = $this->getRepository(Manager::class())->find($params['managerId']))) {
            throw new HttpException(404, 'Manager not found');
        }
        $oldManager = $client->getManager();
        $oldManagerId = $oldManager ? $oldManager->getId() : 0;
        if (!empty($params['managerId'])) {
            $assignment = Assignment::createInst();
            $assignment->setManager($manager);
            $this->getEm()->persist($assignment);
            $client->setAssignment($assignment);
            $client->setManager($manager);
        }
        else{
            $manager =  $oldManager;
        }

        if (!empty($params['managerId']) && $oldManagerId != $params['managerId']) {
            if ($manager && $params['managerId'] != $this->getUser()->getId()) {
                $eventTo = $this->createEvent($manager, '1 client was assigned to you', 'New client', Event::OBJ_TYPE_CLIENT, $client->getId());
                $this->getEm()->persist($eventTo);
            }
            if ($oldManager && $oldManagerId != $this->getUser()->getId() ) {
                $eventFrom = $this->createEvent($oldManager, '1 client was reassigned from you', 'Client reassigned', Event::OBJ_TYPE_CLIENT, $client->getId());
                $this->getEm()->persist($eventFrom);
            }
        }
    }

    protected function batchFieldsPrepare($params){
        $fields = [];
        if(!empty($params['fields']['saleStatus'])){
            if (!$this->getRepository(SaleStatus::class())->find($params['fields']['saleStatus'])) {
                throw new HttpException(404, 'SaleStatus not found '.$params['fields']['saleStatus']);
            }
            $fields['c.sale_status_id'] = $params['fields']['saleStatus'];
        }

        if (empty($fields)) {
           throw new HttpException(400, 'Incorrect fields for update');
        }
        return $fields;
    }

    public function blockClient($client, $terminate = true) {
        $res = [
            'acc'=>0,
            'err'=>0,
        ];
        $status = $client->getStatus();
        $client->setStatus(Client::STATUS_BLOCKED);
        foreach ($client->getAccounts() as $account) {
            $res['acc']++;
            $res['err']++;
            if (!empty($account->getExternalId())) {
                if($this->getPlatformHandler()->blockAccount($account)){
                    $res['err']--;
                }
                else{
                    \NaxCrmBundle\Debug::$messages[]='ERR: can`t block account #'.$account->getExternalId();
                }
            }
            else{
                \NaxCrmBundle\Debug::$messages[]='WARN: account without externalId '.$account->getId();
                SlackBot::send('Account without externalId', $account->export());
            }
        }
        \NaxCrmBundle\Debug::$messages[]= $res;

        if($res['acc']==$res['err']){
            $client->setStatus($status);
            // $this->getEm()->detach($client);
            if($terminate){
                throw new HttpException(503, 'Can`t block client');
            }
        }
        else if($res['err']){
            SlackBot::send('Partly block of client', $client->export());
        }

        if($res['acc']>$res['err']){
            $this->getEm()->persist($client);
        }

        return $res;
    }

    public function unblockClient($client, $terminate = true) {
        $res = [
            'acc'=>0,
            'err'=>0,
        ];
        $status = $client->getStatus();
        $client->setStatus(Client::STATUS_UNBLOCKED);
        foreach ($client->getAccounts() as $account) {
            $res['acc']++;
            $res['err']++;
            if (!empty($account->getExternalId())) {
                if($this->getPlatformHandler()->unblockAccount($account)){
                    $res['err']--;
                }
                else{
                    \NaxCrmBundle\Debug::$messages[]='ERR: can`t unblock account #'.$account->getExternalId();
                }
            }
            else{
                \NaxCrmBundle\Debug::$messages[]='WARN: account without externalId '.$account->getId();
                SlackBot::send('Account without externalId', $account->export());
            }
        }
        \NaxCrmBundle\Debug::$messages[]= $res;

        if($res['acc']==$res['err']){
            $client->setStatus($status);
            // $this->getEm()->detach($client);
            if($terminate){
                throw new HttpException(503, 'Can`t unblock client');
            }
        }
        else if($res['err']){
            SlackBot::send('Partly block of client', $client->export());
        }

        if($res['acc']>$res['err']){
            $this->getEm()->persist($client);
        }

        return $res;
    }
}
