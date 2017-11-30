<?php

namespace NaxCrmBundle\Controller\v1\banking;

use NaxCrmBundle\Entity\Account;
use NaxCrmBundle\Entity\Client;
use NaxCrmBundle\Entity\Deposit;
use NaxCrmBundle\Entity\UserLogItem;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Put;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use NaxCrmBundle\Modules\SlackBot;

use NaxCrmBundle\Controller\v1\BaseController;

class ClientController extends BaseController
{
    protected function addAccountAction($client = null, $type = Account::TYPE_DEMO, $currency = Account::DEFAULT_CURRENCY){
        if (empty($client)) {
            return $this->getFailedJsonResponse(null, 'Client was not found', 404);
        }
        $repo = $this->getRepository(Account::class());
        $account = $repo->findOneBy([//maybe need to chk currency
            'client' => $client,
            'type' => $type,
        ]);
        if ($account) {
            return $this->getFailedJsonResponse($account, Account::get('Types')[$type].' account exist');
        }
        $platform = $this->getPlatformName();
        /*if (!$platform) {
            return $this->getFailedJsonResponse(null, 'Platform is required');
        }*/
        $account = Account::createInst();
        $account->setPlatform($platform);
        $account->setClient($client);
        $account->setCurrency($currency);
        $account->setType($type);
        $result = $this->getPlatformHandler()->addAccount($account);
        if(empty($result) || empty($result['externalId'])){
            return $this->getFailedJsonResponse($account->export(), 'Can`t add account', 503);
        }
        $account->setExternalId($result['externalId']);
        if($this->validate($account, false)){//fix dbl save account on add
            $this->getEm()->persist($account);
            $this->getEm()->flush();
        }
        else{
            \NaxCrmBundle\Debug::$messages[]='WARN: ignore platform conflict';
        }
        $this->createUserLog(UserLogItem::ACTION_TYPE_UPDATE, UserLogItem::OBJ_TYPE_CLIENTS, [
            'id' =>$client->getId(),
            'message'   => Account::get('Types')[$type].' account created',
            'account_id'=>$account->getId(),
        ]);
        $this->addEvent($client, Account::get('Types')[$type].' account was created');

        return $this->getJsonResponse($account);
    }

    /**
     * @Post("/clients/{id}/pro", name="v1_upgrade_to_pro")
     * @ApiDoc(
     *    views = {"default"},
     *    authentication = true,
     *    statusCodes={
     *        400="Returned when Pro Account exist",
     *        403="Returned when the user is not authorized with JWT token",
     *        404="Returned when client was not found",
     *        500="Returned when Something went wrong",
     *        503="Returned when can`t add account on platform",
     *    },
     *    description = "Add Pro account to user",
     *    requirements = {
     *         {"name"="id", "dataType"="integer", "description"="select client by id", "requirement"="\d+"},
     *    },
     *    parameters = {
     *        {"name"="currency", "dataType"="string", "required"=false, "description"="set currency of new account."},
     *    },
     *    section = "banking",
     *    resource = "banking/client",
     * )
     * @ParamConverter("client", class="NaxCrmBundle:Client")
     */
    public function upgradeToProAction(Request $request, $client = null)
    {
        $currency = $request->request->get('currency', Account::DEFAULT_CURRENCY);
        return $this->addAccountAction($client, Account::TYPE_LIVE, $currency);
    }

    /**
     * @Get("/clients/{id}/info", name="v1_get_client_info")
     * @ApiDoc(
     *    views = {"default"},
     *    statusCodes={
     *        200="Returned when successful",
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *          "Returned when you can't access this client",
     *        },
     *        404="Returned when client was not found",
     *        500="Returned when Something went wrong",
     *    },
     *    description = "Get relationships information of client(accounts, purses)",
     *    requirements = {
     *         {"name"="id", "dataType"="integer", "description"="select client by id", "requirement"="\d+"},
     *    },
     *    section = "banking",
     *    resource = "banking/client",
     *    authentication = true,
     * )
     * @ParamConverter("client", class="NaxCrmBundle:Client")
     */
    public function getClientInfoAction($client = null)
    {
        /* @var $client Client */
        if (empty($client)) {
            return $this->getFailedJsonResponse(null, 'Client was not found', 404);
        }
        if ($this->getUser() != $client && !$this->getUser()->hasAccessToClient($client)) {
            return $this->getFailedJsonResponse(null, 'You can\'t access this client', 403);
        }
        $res = $this->getRepository(Client::class())->getClientInfo($client->getId());
        $res['purses'] = $this->getRepository(Deposit::class())->getCardNumbers($client->getId());

        return $this->getJsonResponse($res);
    }

    /**
     * @Get("/clients/accounts", name="v1_get_clients_accounts")
     * @ApiDoc(
     *    views = {"default"},
     *    statusCodes={
     *        200="Returned when successful",
     *        403="Returned when the user is not authorized with JWT token",
     *        500="Returned when Something went wrong",
     *    },
     *    description = "Get accounts grouped by client email",
     *    parameters = {
     *        {"name"="email", "dataType"="string", "required"=false, "description"="set email for filter."},
     *    },
     *    section = "banking",
     *    resource = "banking/client",
     *    authentication = true,
     * )
     */
    public function getClientsAccountsAction(Request $request)
    {
        $repo = $this->getRepository(Account::class());

        $params = $request->query->all();
        $filters = [];
        if (!empty($params['email'])) {
            $filters['email'] = $params['email'];
        }
        $accounts = $repo->getClientsAccounts($filters);
        if (!empty($params['email']) && !empty($params['account']) && !empty($params['account']['login']
                && !empty($accounts[$params['email']]) && !empty($accounts[$params['email']][0]['clientId']))
        ) {
            $acc_exist = false;
            foreach ($accounts[$params['email']] as $v) {
                if ($v['externalId'] == $params['account']['login']) {
                    $acc_exist = true;
                }
            }
            $repo = $this->getRepository(Client::class());
            if (!$acc_exist && $client = $repo->find($accounts[$params['email']][0]['clientId'])) {
                $type = $params['account']['is_demo'] ? Account::TYPE_DEMO : Account::TYPE_LIVE;

                $account = Account::createInst();
                $account->setPlatform($this->getPlatformName());
                $account->setExternalId($params['account']['login']);
                $account->setClient($client);
                $account->setCurrency($params['account']['currency']);
                $account->setType($type);

                $em = $this->getEm();
                $em->persist($account);
                $em->flush();

                array_unshift($accounts[$params['email']], [
                    'email' => $client->getEmail(),
                    'clientId' => $client->getId(),
                    'accountId' => $account->getId(),
                    'platform' => $account->getPlatform(),
                    'type' => $account->getType(),
                    'currency' => $account->getCurrency(),
                    'externalId' => $account->getExternalId(),
                ]);
            }
        }

        return $this->getJsonResponse($accounts);
    }

    /**
     * @Get("/clients/{id}/accounts", name="v1_get_client_accounts")
     * @ApiDoc(
     *    views = {"default"},
     *    statusCodes = {
     *        200="Returned when successful",
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *          "Returned when the user have not proper acl rights",
     *        },
     *        404={
     *          "Return if client not found",
     *          "Return if client has no accounts",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     *    description = "Get all accounts for selected client",
     *    section = "banking",
     *    resource = "banking/client",
     *    requirements = {
     *         {"name"="id", "dataType"="integer", "description"="select client by id", "requirement"="\d+"},
     *    },
     * )
     * @ParamConverter("client", class="NaxCrmBundle:Client")
     * @Security("(has_role('ROLE_MANAGER') and is_granted('R', 'client')) || (has_role('ROLE_CLIENT') and user == client)")
     */
    public function getClientAccountsAction($client = null, Request $request)
    {
        /* @var $client Client */
        if (empty($client)) {
            return $this->getFailedJsonResponse(null, 'Client was not found', 404);
        }
        $accounts = $client->getAccounts();
        $type = $request->query->get('type', 'nope');
        if ($type != 'nope') {
            foreach ($accounts as $k => $v) {
                if ($v->getType() != $type) {
                    unset($accounts[$k]);
                }
            }
        }
        $accounts = array_values($accounts->toArray());
        if (empty($accounts)) {
            SlackBot::send('Client without accounts', $client->export());
            return $this->getFailedJsonResponse(null, 'Client has no accounts',404);
        }
        return $this->getJsonResponse($accounts);
    }

    /**
     * @Get("/accounts/{id}/balance", name="v1_get_client_balance")
     * @ApiDoc(
     *    views = {"default"},
     *    statusCodes = {
     *        200="Returned when successful",
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *          "Returned when the user have not proper acl rights",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     *    description = "Get account balance and information",
     *    section = "banking",
     *    resource = "banking/client",
     *    requirements = {
     *         {"name"="id", "dataType"="integer", "description"="select account by id", "requirement"="\d+"},
     *    },
     * )
     * @ParamConverter("account", class="NaxCrmBundle:Account")
     * @Security("(has_role('ROLE_MANAGER') and is_granted('R', 'client')) || (has_role('ROLE_CLIENT') and user == account.getClient() )")
     */
    public function getBalance($account = null)
    {
        $handler = $this->getPlatformHandler();
        $res = $handler->getAccount($account->getExternalId());
        return $this->getJsonResponse($res);
    }

    /**
     * @Get("/clients/balance", name="v1_get_clients_balance")
     * @ApiDoc(
     *    views = {"default"},
     *    statusCodes={
     *        200="Returned when successful",
     *        400="Returned when missing required parameter",
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *          "Returned when the user have not proper acl rights",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     *    description = "Get clients balance",
     *    parameters = {
     *        {"name"="clients", "dataType"="collection",  "required"=true,  "description"="set array of client Ids for filter."},
     *    },
     *    section = "banking",
     *    resource = "banking/client",
     * )
     * @Security("(has_role('ROLE_MANAGER') and is_granted('R', 'client')) || (has_role('ROLE_CLIENT') )")
     */
    public function getClientBalance(Request $request)
    {
        $clients = $this->getRequiredParam('clients');
        if (!is_array($clients)) {
            $clients = explode(',', $clients);
        }
        $repo = $this->getRepository(Client::class());
        $balances = [];
        foreach ($clients as $id) {
            $client = $repo->find($id);
            if (!empty($client)) {
                $result = $this->getPlatformHandler()->getAccount($client->getAccountExtId());
                $balances[$id] = !empty($result['balance']) ? $result['balance'] : 0;
            }
        }
        return $this->getJsonResponse([
            'balances' => $balances,
        ]);
    }

    /**
     * @Post("/clients/{id}/accounts", name="v1_create_account")
     * @ApiDoc(
     *    views = {"default"},
     *    statusCodes={
     *        200="Returned when successful",
     *        400={
     *           "Returned when missing required parameter",
     *           "Returned when externalId is not integer",
     *        },
     *        403="Returned when the user is not authorized with JWT token",
     *        404="Returned when client was not found",
     *        500="Returned when Something went wrong",
     *    },
     *    description = "Add account to client",
     *    requirements = {
     *         {"name"="id", "dataType"="integer", "description"="select client by id", "requirement"="\d+"},
     *    },
     *    parameters = {
     *        {"name"="currency",      "dataType"="string",  "required"=true,  "description"="set new account currency."},
     *        {"name"="group",         "dataType"="string",  "required"=false, "description"="set new account group."},
     *        {"name"="type",          "dataType"="string",  "required"=false, "description"="required=true if not set group. set new account type."},
     *        {"name"="externalId",    "dataType"="integer", "required"=false, "description"="set externalId for new account."},
     *        {"name"="platform",      "dataType"="string",  "required"=false, "description"="required=true if set externalId. set new account platform."},
     *    },
     *    authentication = true,
     *    section = "banking",
     *    resource = "banking/client",
     * )
     * @ParamConverter("client", class="NaxCrmBundle:Client")
     */
    public function createClientAccountAction(Client $client = null, Request $request)
    {
        /* @var $client Client */
        if (empty($client)) {
            return $this->getFailedJsonResponse(null, 'Client was not found', 404);
        }
        $currency = $this->getRequiredParam('currency');
        if (!$group = $request->request->get('group')) {
            $type = $this->getRequiredParam('type');
        } else {
            $type = $group == 'live' ? Account::TYPE_LIVE : Account::TYPE_DEMO;
        }
        $externalId = $request->request->get('externalId');
        if ($externalId) {
            $platform = $this->getRequiredParam('platform');
            if (!is_numeric($externalId)) {
                $this->getFailedJsonResponse(null, 'ExternalId should be integer');
            }
            $account = $this->getClientService($client)->getAccount($platform, $externalId);
            if ($account instanceof Account) {
                return $this->replenishBalanceAction($request, $client);
            }
        }
        $platform = $this->getPlatformName();
        $account = Account::createInst();
        $account->setPlatform($platform);
        $account->setExternalId($externalId);
        $account->setClient($client);
        $account->setCurrency($currency);
        $account->setType($type);

        $em = $this->getEm();
        $em->persist($account);
        $em->flush();

        return $this->getJsonResponse($account);
    }

    /**
     * @Put("/clients/{id}/accounts", name="v1_replenish_balance")
     * @ApiDoc(
     *    views = {"default"},
     *    statusCodes={
     *        200="Returned when successful",
     *        400={
     *           "Returned when missing required parameter",
     *           "Returned when invalid params, accountId or externalId should be present",
     *           "Returned when externalId should be integer",
     *        },
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *          "Returned when account does not belong to given client",
     *        },
     *        404={
     *          "Returned when client was not found",
     *          "Returned when client has no account for selected currency",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     *    description = "Update account balance",
     *    requirements = {
     *         {"name"="id", "dataType"="integer", "description"="select client by id", "requirement"="\d+"},
     *    },
     *    parameters = {
     *        {"name"="accountId",  "dataType"="string",  "required"=false, "description"="set account Id."},
     *        {"name"="currency",   "dataType"="string",  "required"=false, "description"="set account currency."},
     *        {"name"="externalId", "dataType"="string",  "required"=false, "description"="set account externalId."},
     *        {"name"="platform",   "dataType"="integer", "required"=false, "description"="set externalId for new account."},
     *        {"name"="type",       "dataType"="string",  "required"=false, "description"="set account type."},
     *        {"name"="amount",     "dataType"="string",  "required"=false, "description"="set replenish amount."},
     *    },
     *    authentication = true,
     *    section = "banking",
     *    resource = "banking/client",
     * )
     * @ParamConverter("client", class="NaxCrmBundle\Entity\Client")
     */
    public function replenishBalanceAction(Request $request, Client $client = null)
    {
        /* @var $client Client */
        if (empty($client)) {
            return $this->getFailedJsonResponse(null, 'Client was not found', 404);
        }
        $em = $this->getEm();
        $params = $request->request->all();
        $accountId = $params['accountId'];
        $currency = $params['currency']?:Account::DEFAULT_CURRENCY;
        $externalId = $params['externalId'];
        $platform = $params['platform'];
        $type = $params['type'];

        if (!$accountId && !($externalId && $platform)) {
            return $this->getFailedJsonResponse(null, 'Invalid params, accountId or externalId should be present');
        }

        if ($accountId) {
            $account = $em->getRepository(Account::class())->find($accountId);
        } else {
            if (!is_numeric($externalId)) {
                return $this->getFailedJsonResponse(null, 'ExternalId should be integer');
            }
            $account = $this->getClientService($client)->getAccount($externalId, $platform);
        }

        $account->setPlatform($platform);
        if ((!$account instanceof Account)) {
            return $this->getFailedJsonResponse(null, "Client has no account for {$currency}", 404);
        }
        if ($account->getClient()->getId() != $client->getId()) {
            return $this->getFailedJsonResponse(null, 'Account does not belong to given client', 403);
        }
        if ($params['amount']) {
            // ???
        }

        if ($type != $account->getType()) {
            $account->setType($type);
        }

        $em->flush();

        return $this->getJsonResponse($account);
    }

    /**
     * @Get("/accounts/platform-profit", name="v1_get_platform_profit")
     * @ApiDoc(
     *    views = {"default"},
     *    statusCodes = {
     *        200="Returned when successful",
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *          "Returned when the user have not proper acl rights",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     *    description = "Get platform profit",
     *    section = "banking",
     *    resource = "banking/client",
     * )
     * @Security("(has_role('ROLE_MANAGER') and is_granted('R', 'client'))")
     */
    public function getPlatformProfit()
    {
        $handler = $this->getPlatformHandler();
        $repo = $this->getRepository(Account::class());

        $res = $repo->getOperationsTotal();
        foreach ($res as $acc_id => &$v) {
            $acc = $handler->getAccount($acc_id);
            $v['balance'] = $acc['balance'];
            $v['profit'] = ($v['deposits']-$v['withdrawals']-$v['balance']);
        }

        return $this->getJsonResponse($res);
    }
}
