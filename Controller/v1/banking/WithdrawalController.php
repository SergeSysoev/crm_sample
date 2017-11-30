<?php

namespace NaxCrmBundle\Controller\v1\banking;

use NaxCrmBundle\Entity\Account;
use NaxCrmBundle\Entity\Manager;
use NaxCrmBundle\Entity\UserLogItem;
use NaxCrmBundle\Entity\Withdrawal;
use NaxCrmBundle\Modules\Email\Triggers\Client\WithdrawalApproved;
use NaxCrmBundle\Modules\Email\Triggers\Client\WithdrawalDeclined;
use NaxCrmBundle\Modules\Email\Triggers\Client\WithdrawalNew;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Put;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use NaxCrmBundle\Controller\v1\BaseController;

class WithdrawalController extends BaseController
{
    protected $csvTitles = [
        'id',
        'clientId',
        'email',
        'firstname',
        'lastname',
        'amount',
        'cardLastNumbers',
        'paymentSystemName',
        'payMethod',
        'accountType',
        'accountExtId',
        'status',
        // 'comment',
        'created',
        'updated',
    ];

    protected function setExportDefaults()
    {
        $this->csvAliases = [
            'status' => Withdrawal::get('Statuses'),
        ];
    }

    /**
     * @Get("/clients/{id}/withdrawals", name="v1_client_withdrawals")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "banking",
     *    resource = "banking/withdrawal",
     *    authentication = true,
     *    description = "Get all client withdrawals",
     *    statusCodes={
     *        200="Returned when successful",
     *        400={
     *          "Returned when missing required parameter",
     *        },
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *          "Returned when the user have not proper acl rights",
     *          "Returned when manager has no access to client",
     *        },
     *        404={
     *          "Returned when client was not found",
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
     *    requirements = {
     *         {"name"="id", "dataType"="integer", "description"="select client by id", "requirement"="\d+"},
     *    },
     * )
     * @ParamConverter("client", class="NaxCrmBundle\Entity\Client")
     * @Security("(has_role('ROLE_MANAGER') and is_granted('R', 'withdrawal')) || user == client")
     */
    public function getClientWithdrawalsAction(Request $request, $client = null)
    {
        if (empty($client)) {
            return $this->getFailedJsonResponse(null, 'Client was not found', 404);
        }
        if (($this->getUser() instanceof Manager) && !$this->getUser()->hasAccessToClient($client)) {
            return $this->getFailedJsonResponse(null, 'Manager has no access to client', 403);
        }
        $params = $request->query->all();
        $preFilters =[
            'clientIds' => [$client->getId()],
        ];
        $res = $this->getList($params, $preFilters);
        return $this->getJsonResponse($res);
    }

    /**
     * @Get("/own-withdrawals", name="v1_own_withdrawals")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "banking",
     *    resource = "banking/withdrawal",
     *    authentication = true,
     *    description = "Get all withdrawals for current manager",
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
     * @Security("has_role('ROLE_MANAGER') and is_granted('R', 'withdrawal')")
     */
    public function getOwnWithdrawalsAction(Request $request)
    {
        $params = $request->query->all();
        $preFilters = $this->setDepAccessFilters();

        $res = $this->getList($params, $preFilters);
        return $this->getJsonResponse($res);
    }

    /**
     * @Put("/clients/{clientId}/withdrawals/{id}", name="v1_update_client_withdrawal")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "banking",
     *    resource = "banking/withdrawal",
     *    authentication = true,
     *    description = "Update withdrawal status",
     *    statusCodes={
     *        200="Returned when successful",
     *        400={
     *          "Returned when missing required parameter",
     *          "Returned when withdrawal should be in status pending",
     *          "Returned when invalid withdrawal status",
     *          "Returned when when status is declined, you must provide comment",
     *        },
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *          "Returned when the user have not proper acl rights",
     *          "Returned when manager has no access to client",
     *        },
     *        404={
     *          "Returned when client was not found",
     *          "Returned when withdrawal was not found",
     *        },
     *        500="Returned when Something went wrong",
     *        503={
     *          "Platform error, perhaps not enough money on client's balance",
     *          "Platform error, can`t change client`s balance",
     *        },
     *    },
     *    parameters = {
     *        {"name"="status", "dataType"="integer", "required"=true, "format"="2|3", "description" = "set new withdrawal status STATUS_APPROVED | STATUS_DECLINED"},
     *        {"name"="comment", "dataType"="string", "required"=false,                 "description" = "required if status=3. set comment for new withdrawal status"},
     *    },
     *    requirements = {
     *         {"name"="clientId", "dataType"="integer", "description"="select client by id", "requirement"="\d+"},
     *         {"name"="id", "dataType"="integer", "description"="select withdrawal by id", "requirement"="\d+"},
     *    },
     * )
     * @ParamConverter("client", class="NaxCrmBundle\Entity\Client", options={"id" = "clientId"})
     * @ParamConverter("withdrawal", class="NaxCrmBundle\Entity\Withdrawal")
     * @Security("has_role('ROLE_MANAGER') and is_granted('U', 'withdrawal') and user.hasAccessToClient(client)")
     */
    public function updateClientWithdrawalsAction(Request $request, $client = null, $withdrawal = null)
    {
        if (empty($client)) {
            return $this->getFailedJsonResponse(null, 'Client was not found', 404);
        }
        if (empty($withdrawal)) {
            return $this->getFailedJsonResponse(null, 'Withdrawal was not found', 404);
        }
        if ($withdrawal->getStatus() != Withdrawal::STATUS_PENDING) {
            return $this->getFailedJsonResponse(null, 'Withdrawal should be in status pending');
        }
        if (!$this->getUser()->hasAccessToClient($client)) {
            return $this->getFailedJsonResponse(null, 'Manager has no access to client', 403);
        }

        $status = $this->getRequiredParam('status');
        if ($comment = $request->request->get('comment', '')) {
            $withdrawal->setComment($comment);
        }
        if (!in_array($status, [Withdrawal::STATUS_APPROVED, Withdrawal::STATUS_DECLINED])) {
            return $this->getFailedJsonResponse($status, 'Invalid withdrawal status');
        }
        if ($status == Withdrawal::STATUS_APPROVED) {
            $triggerName = WithdrawalApproved::class();
            $account = $withdrawal->getAccount();
            if ($account->getPlatform()) {
                $handler = $this->getPlatformHandler($account->getPlatform());
                $balance = $handler->getBalance($account->getExternalId());
                if ($balance['balance'] < $withdrawal->getAmount()) {
                    return $this->getFailedJsonResponse([
                        'balance' => $balance['balance'],
                        'withdrawal' => $withdrawal->getAmount()
                    ], 'Platform error, perhaps not enough money on client`s balance', 503);
                }
                $res = $handler->setWithdrawal($withdrawal);//change balance
                if(!$res || $res==='false'){
                    return $this->getFailedJsonResponse(null, 'Platform error, can`t change client`s balance',503);
                }
            }
        } else {
            if (empty($comment)) {
                return $this->getFailedJsonResponse(null, 'When status is declined, you must provide comment');
            }
            $triggerName = WithdrawalDeclined::class();
        }
        $withdrawal->setStatus($status);
        $withdrawal->setUpdated(new \DateTime('now'));
        $this->getEm()->flush();

        $data = $withdrawal->export();

        $this->getTriggerService()->sendEmails($triggerName, compact('client', 'withdrawal'), $client->getLangcode());

        $this->createUserLog(UserLogItem::ACTION_TYPE_UPDATE, UserLogItem::OBJ_TYPE_WITHDRAWALS, [
            'id' => $withdrawal->getId(),
            'status' => $withdrawal->getStatus(),
        ]);
        $action = $status == Withdrawal::STATUS_DECLINED ? 'declined' : 'approved';
        $this->addEvent($client, 'withdrawal was '.$action, $withdrawal->getAmount().' '.$withdrawal->getAccount()->getCurrency().' withdrawal was '.$action);

        return $this->getJsonResponse($data);
    }

    /**
     * @Post("/clients/{id}/withdrawals", name="v1_create_withdrawal_order")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "banking",
     *    resource = "banking/withdrawal",
     *    authentication = true,
     *    description = "Create new withdrawal",
     *    statusCodes={
     *        200="Returned when successful",
     *        202="Platform error, perhaps not enough money on client`s balance. Withdrawal created but was declined",
     *        400={
     *          "Returned when missing required parameter",
     *          "Returned when amount should be greater than zero",
     *        },
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *          "Returned when the user have not proper acl rights",
     *          "Returned when manager has no access to client",
     *          "Returned when account doesn`t belong to that client",
     *        },
     *        404={
     *          "Returned when client was not found",
     *          "Returned when account was not found",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     *    parameters = {
     *        {"name"="comment",   "dataType"="string",  "required"=false,                 "description" = "set comment for withdrawal"},
     *        {"name"="accountId", "dataType"="integer", "required"=true, "format"="\d+",  "description" = "set accountId for withdrawal"},
     *        {"name"="amount",    "dataType"="integer", "required"=true, "format"="\d+",  "description" = "set amount of withdrawal"},
     *    },
     *    requirements = {
     *         {"name"="id", "dataType"="integer", "description"="select client by id", "requirement"="\d+"},
     *    },
     * )
     * @ParamConverter("client", class="NaxCrmBundle\Entity\Client")
     */
     // * @Security("has_role('ROLE_MANAGER') and is_granted('C', 'withdrawal') and user.hasAccessToClient(client)")
    public function createWithdrawalAction(Request $request, $client = null)
    {
        if (empty($client)) {
            return $this->getFailedJsonResponse(null, 'Client was not found', 404);
        }
        if ($this->getUser() != $client && !$this->getUser()->hasAccessToClient($client)) {
            return $this->getFailedJsonResponse(null, 'Manager has no access to client', 403);
        }
        $lastDigits = $this->getRequiredParam('lastDigits');
        //TODO: validation value of lastDigits

        $accountId = $this->getRequiredParam('accountId');
        /** @var Account $account */
        if (!($account = $this->getRepository(Account::class())->find($accountId))) {
            return $this->getFailedJsonResponse(null, 'Account was not found', 404);
        }
        if ($account->getClient()->getId() != $client->getId()) {
            return $this->getFailedJsonResponse(null, 'Account doesnt belong to that client', 403);
        }
        $amount = $this->getRequiredParam('amount');
        if (!is_numeric($amount) || $amount <= 0) {
            return $this->getFailedJsonResponse($amount, 'Amount should be greater than zero');
        }

        $withdrawal = Withdrawal::createInst();
        $withdrawal->setClient($client);
        $withdrawal->setAccount($account);
        $withdrawal->setAmount($amount);
        $withdrawal->setLastDigits($lastDigits);
        $withdrawal->setComment($request->request->get('comment', ''));

        if ( $account->getPlatform()) {
            $res = $this->getPlatformHandler($account->getPlatform())->addWithdrawal($withdrawal);
            if(empty($res) || $res==='false'){
                // $withdrawal->setStatus(Withdrawal::STATUS_DECLINED);
                // $this->getEm()->persist($withdrawal);
                // $this->getEm()->flush();
                // $data = $withdrawal->export();
                // $this->getTriggerService()->sendEmails(DepositFailed::class(), compact('deposit', 'client'), $client->getLangcode());
                $data = null;
                return $this->getFailedJsonResponse($data, 'Platform error, perhaps not enough money on client`s balance. Withdrawal rejected', 503);
            }
        }
        $this->getEm()->persist($withdrawal);
        $this->getEm()->flush();
        $data = $withdrawal->export();

        $this->getTriggerService()->sendEmails(WithdrawalNew::class(), compact('client', 'withdrawal'), $client->getLangcode());
        $this->createUserLog(UserLogItem::ACTION_TYPE_CREATE, UserLogItem::OBJ_TYPE_WITHDRAWALS, $data);
        $this->addEvent($client, 'withdrawal was made', $withdrawal->getAmount().' '.$account->getCurrency().' withdrawal was made');
        return $this->getJsonResponse($data);
    }


    /**
     * @Get("/clients/{clientId}/withdrawals/{id}", name="v1_get_withdrawal")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "banking",
     *    resource = "banking/withdrawal",
     *    authentication = true,
     *    description = "Get selected withdrawal for selected client",
     *    statusCodes={
     *        200="Returned when successful",
     *        400={
     *          "Returned when missing required parameter",
     *        },
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *          "Returned when the user have not proper acl rights",
     *          "Returned when withdrawal does not belong to client",
     *          "Returned when manager has no access to client",
     *        },
     *        404={
     *          "Returned when client was not found",
     *          "Returned when withdrawal was not found",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     *    requirements = {
     *         {"name"="clientId", "dataType"="integer", "description"="select client by id", "requirement"="\d+"},
     *         {"name"="id", "dataType"="integer", "description"="select withdrawal by id", "requirement"="\d+"},
     *    },
     * )
     * @ParamConverter("client", class="NaxCrmBundle\Entity\Client", options={"id" = "clientId"})
     * @ParamConverter("withdrawal", class="NaxCrmBundle\Entity\Withdrawal")
     * @Security("has_role('ROLE_MANAGER') and is_granted('R', 'withdrawal') and user.hasAccessToClient(client)")
     */
    public function getWithdrawalAction($client = null, $withdrawal = null)
    {
        if (empty($client)) {
            return $this->getFailedJsonResponse(null, 'Client was not found', 404);
        }
        if (empty($withdrawal)) {
            return $this->getFailedJsonResponse(null, 'Withdrawal was not found', 404);
        }
        if ($withdrawal->getClientId() != $client->getId()) {
            return $this->getFailedJsonResponse(null, 'Withdrawal does not belong to client', 403);
        }
        if (!$this->getUser()->hasAccessToClient($client)) {
            return $this->getFailedJsonResponse(null, 'Manager has no access to client', 403);
        }

        return $this->getJsonResponse($withdrawal->export());
    }

    /**
     * @Put("/withdrawals/status", name="v1_update_withdrawals_status")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "banking",
     *    resource = "banking/withdrawal",
     *    authentication = true,
     *    description = "Batch update withdrawals status",
     *    statusCodes={
     *        200="Returned when successful",
     *        400={
     *          "Returned when missing required parameter",
     *          "Returned when invalid status",
     *        },
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *          "Returned when the user have not proper acl rights",
     *          "Returned when manager has no access to client",
     *        },
     *        404={
     *          "Returned when deposit was not found",
     *        },
     *        500="Returned when Something went wrong",
     *        503="Platform error, perhaps not enough money on client's balance",
     *    },
     *    parameters = {
     *        {"name"="status",        "dataType"="integer",    "required"=true, "format"="2|3",  "description" = "set new withdrawal status STATUS_APPROVED | STATUS_DECLINED"},
     *        {"name"="withdrawalIds", "dataType"="collection", "required"=true, "format"="\d+", "description" = "set array of withdrawals Id for update status"},
     *        {"name"="comment",       "dataType"="string",     "required"=false,                 "description" = "required if status=3. set comment for new withdrawal status"},
     *    },
     * )
     * @Security("has_role('ROLE_MANAGER') and is_granted('U', 'withdrawal')")
     */
    public function updateWithdrawalsStatusesAction(Request $request)
    {
        $wdIds = $this->getRequiredParam('withdrawalIds');
        $status = $this->getRequiredParam('status');

        if (!in_array($status, [Withdrawal::STATUS_DECLINED, Withdrawal::STATUS_APPROVED])) {
            return $this->getFailedJsonResponse('Invalid status');
        }
        if ($status == Withdrawal::STATUS_DECLINED) {
            $comment = $this->getRequiredParam('comment');
            $triggerName = WithdrawalApproved::class();
        } else {
            $triggerName = WithdrawalDeclined::class();
            $comment = $this->getRequiredParam('comment');
        }

        $em = $this->getEm();
        /* @var $withdrawal Withdrawal */
        foreach ($this->getRepository(Withdrawal::class())->findBy(['id' => $wdIds]) as $withdrawal) {
            if (!$this->getUser()->hasAccessToClient($withdrawal->getClient())) {
                return $this->getFailedJsonResponse(null, 'Manager has no access to client', 403);
            }
            $withdrawal->setStatus($status);
            $withdrawal->setUpdated(new \DateTime('now'));
            if ($comment) {
                $withdrawal->setComment($comment);
            }
            $platform = $withdrawal->getAccount()->getPlatform();
            if ($platform) {
                $res = $this->getPlatformHandler($platform)->setWithdrawal($withdrawal);
                if(!$res || $res==='false'){
                    return $this->getFailedJsonResponse(null, 'Platform error, perhaps not enough money on client\'s balance', 503);
                }
            }
            $client = $withdrawal->getClient();
            $this->getTriggerService()->sendEmails($triggerName, compact('client', 'withdrawal'), $client->getLangcode());
            $em->persist($withdrawal);
        }
        $em->flush();

        $this->createUserLog(UserLogItem::ACTION_TYPE_UPDATE, UserLogItem::OBJ_TYPE_WITHDRAWALS, [
            'ids' => $wdIds,
            'status' => $status,
        ]);

        return $this->getJsonResponse(null, 'Withdrawals have status changed');
    }
}
