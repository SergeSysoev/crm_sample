<?php

namespace NaxCrmBundle\Controller\v1\banking;

use NaxCrmBundle\Entity\Client;
use NaxCrmBundle\Entity\Deposit;
use NaxCrmBundle\Entity\UserLogItem;
use NaxCrmBundle\Entity\PaymentSystem;
use NaxCrmBundle\Modules\Email\Triggers\Client\Deposit as DepositTrigger;
use NaxCrmBundle\Modules\Email\Triggers\Client\DepositSuccess;
use NaxCrmBundle\Modules\Email\Triggers\Client\DepositFailed;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use NaxCrmBundle\Controller\v1\BaseController;

class DepositController extends BaseController
{
    protected $csvTitles = [
        'clientId',
        'firstname',
        'lastname',
        'amount',
        'created',
        'paymentSystem',
        'currency',
        'status',
        'comment',
    ];

    protected function setExportDefaults()
    {
        $this->csvAliases = [
            'status' => Deposit::get('Statuses'),
            'paymentSystem' => $this->getIdNamesArray(PaymentSystem::class(), 'paymentSystemName', ['status' => 1,]),
        ];
    }

    /**
     * @Get("/own-deposits", name="v1_own_deposits")
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
     *    description = "Get deposits for accessible manager departments for current manager",
     *    section = "banking",
     *    resource = "banking/deposit",
     *    parameters = {
     *        {"name"="filters",   "dataType"="collection", "required"=false, "format"="filters[columnName]=value",                "description" = "set array of filters. You can get list of filters in response"},
     *        {"name"="order",     "dataType"="collection", "required"=false, "format"="order[column]=columnName&order[dir]=DESC", "description" = "add order column and direction for output."},
     *        {"name"="limit",     "dataType"="integer",    "required"=false, "format"="\d+",                                      "description" = "add limit for output."},
     *        {"name"="offset",    "dataType"="integer",    "required"=false, "format"="\d+",                                      "description" = "add offset shifting for output."},
     *        {"name"="exportCsv", "dataType"="string",     "required"=false, "format"="lzw encoded array",                        "description" = "used for export result in CSV file"},
     *    },
     * )
     * @Security("has_role('ROLE_MANAGER') and is_granted('R', 'deposit')")
     */
    public function getOwnDepositsAction(Request $request)
    {
        $params = $request->query->all();
        $preFilters = $this->setDepAccessFilters();

        $res = $this->getList($params, $preFilters);
        return $this->getJsonResponse($res);
    }

    /**
     * @Get("/clients/{id}/deposits", name="v1_client_deposits")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "banking",
     *    resource = "banking/deposit",
     *    description = "Get deposits for accessible manager departments",
     *    statusCodes = {
     *        200="Returned when successful",
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *          "Returned when the user have not proper acl rights",
     *        },
     *        404="Returned when client was not found",
     *        500="Returned when Something went wrong",
     *    },
     *    requirements = {
     *         {"name"="id", "dataType"="integer", "description"="select client by id", "requirement"="\d+"},
     *    },
     *    parameters = {
     *        {"name"="filters",   "dataType"="collection", "required"=false, "format"="filters[columnName]=value",                "description" = "set array of filters. You can get list of filters in response"},
     *        {"name"="order",     "dataType"="collection", "required"=false, "format"="order[column]=columnName&order[dir]=DESC", "description" = "add order column and direction for output."},
     *        {"name"="limit",     "dataType"="integer",    "required"=false, "format"="\d+",                                      "description" = "add limit for output."},
     *        {"name"="offset",    "dataType"="integer",    "required"=false, "format"="\d+",                                      "description" = "add offset shifting for output."},
     *        {"name"="exportCsv", "dataType"="string",     "required"=false, "format"="lzw encoded array",                        "description" = "used for export result in CSV file"},
     *    },
     * )
     * @ParamConverter("client", class="NaxCrmBundle:Client")
     * @Security("user == client || (has_role('ROLE_MANAGER') and is_granted('R', 'deposit') and user.hasAccessToClient(client))")
     */
    public function getClientDepositsAction(Request $request, $client = null)
    {
        if (empty($client)) {
            return $this->getFailedJsonResponse(null, "Client was not found", 404);
        }
        $params = $request->query->all();
        $preFilters = [
            'clientIds' => [$client->getId()],
        ];
        $res = $this->getList($params, $preFilters);
        return $this->getJsonResponse($res);
    }

    /**
     * @Get("/clients/{clientId}/deposits/{id}", name="v1_get_deposit")
     * @ApiDoc(
     *    views = {"default"},
     *    authentication = true,
     *    statusCodes = {
     *        200="Returned when successful",
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *          "Returned when manager has no access to client",
     *          "Returned when deposit does not belong to client",
     *        },
     *        404={
     *          "Returned when client was not found",
     *          "Returned when deposit was not found",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     *    requirements = {
     *         {"name"="clientId", "dataType"="integer", "description"="select client by id", "requirement"="\d+"},
     *         {"name"="id",       "dataType"="integer", "description"="select deposit id",   "requirement"="\d+"},
     *    },
     *    description = "Get selected deposit information for selected client",
     *    section = "banking",
     *    resource = "banking/deposit",
     * )
     * @ParamConverter("client", class="NaxCrmBundle:Client", options={"id" = "clientId"})
     * @ParamConverter("deposit", class="NaxCrmBundle:Deposit")
     */
    public function getDepositAction($client = null, Deposit $deposit = null)
    {
        if (empty($client)) {
            return $this->getFailedJsonResponse(null, 'Client was not found', 404);
        }
        if (empty($deposit)) {
            return $this->getFailedJsonResponse(null, 'Deposit was not found', 404);
        }
        if ($this->getUser() != $client && !$this->getUser()->hasAccessToClient($client)) {
            return $this->getFailedJsonResponse(null, 'Manager has no access to client', 403);
        }
        if ($deposit->getClientId() != $client->getId()) {
            return $this->getFailedJsonResponse(null, 'Deposit does not belong to client');
        }
        return $this->getJsonResponse($deposit->export());
    }

    /**
     * @Post("/clients/{id}/deposits", name="v1_create_deposit_order")
     * @ApiDoc(
     *    views = {"default"},
     *    statusCodes={
     *        200="Returned when successful",
     *        202="Returned when deposit created but was declined",
     *        400={
     *          "Returned when missing required parameter",
     *          "Returned when amount should be greater than zero",
     *        },
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *          "Returned when manager has no access to client",
     *        },
     *        404="Returned when client was not found",
     *        500="Returned when Something went wrong",
     *    },
     *    description = "Create new deposit for selected client",
     *    requirements = {
     *         {"name"="id", "dataType"="integer", "description"="select client by id", "requirement"="\d+"},
     *    },
     *    parameters = {
     *        {"name"="paymentSystemId", "dataType"="integer", "required"=true, "format"="\d+", "description" = "set paymentSystemId for new deposit."},
     *        {"name"="accountId",       "dataType"="integer", "required"=true, "format"="\d+", "description" = "set accountId for new deposit."},
     *        {"name"="amount",          "dataType"="integer", "required"=true, "format"="\d+", "description" = "set amount of new deposit."},
     *    },
     *    section = "banking",
     *    resource = "banking/deposit",
     *    authentication = true,
     * )
     * @ParamConverter("client", class="NaxCrmBundle:Client")
     */
     // * @Security("has_role('ROLE_MANAGER') and is_granted('C', 'deposit')")
    public function createDepositAction(Request $request, $client = null)
    {
        if (empty($client)) {
            return $this->getFailedJsonResponse(null, 'Client was not found', 404);
        }
        if ($client->isBlocked()) {
            return $this->getFailedJsonResponse(null, 'Client is blocked', 403);
        }
        if ($this->getUser() != $client && !$this->getUser()->hasAccessToClient($client)) {
            return $this->getFailedJsonResponse(null, 'Manager has no access to client', 403);
        }
        $params = $request->request;
        $dep_data = $params->all();

        $psId = $this->getRequiredParam('paymentSystemId');
        $accountId = (int) $this->getRequiredParam('accountId');
        $amount = $this->getRequiredParam('amount');
        if (!is_numeric($amount) || $amount <= 0) {
            return $this->getFailedJsonResponse($amount, 'Amount should be greater than zero');
        }

        $depositService = $this->get('nax.deposit_service');
        $data = $depositService->createDeposit($psId, $accountId, $amount, $dep_data);
        $deposit = $depositService->getDeposit();

        $platform = $depositService->getAccount()->getPlatform();
        if ($platform) {
            $res = $this->getPlatformHandler($platform)->setDeposit($deposit);
            if(!$res || $res==='false'){
                $deposit->setStatus(Deposit::STATUS_DECLINED);
                $this->getEm()->flush();
                $this->getTriggerService()->sendEmails(DepositFailed::class(), compact('deposit', 'client'), $client->getLangcode());
                return $this->getJsonResponse($data, 'Deposit created but was declined', 202);
            }
        }
        if($deposit->getStatus()==Deposit::STATUS_PROCESSED){
            $this->getTriggerService()->sendEmails(DepositSuccess::class(), compact('deposit', 'client'), $client->getLangcode());
        }

        $this->getTriggerService()->sendEmails(DepositTrigger::class(), compact('deposit', 'client'), $client->getLangcode());
        $this->createUserLog(UserLogItem::ACTION_TYPE_CREATE, UserLogItem::OBJ_TYPE_DEPOSITS, $deposit->export());
        $this->addEvent($client, 'deposit was made', $deposit->getAmount().' '.$deposit->getCurrency().' deposit was made');
        return $this->getJsonResponse($data, 'Deposit has been successfully created');
    }
}
