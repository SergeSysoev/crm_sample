<?php

namespace NaxCrmBundle\Controller\v1\banking;

use NaxCrmBundle\Entity\Client;
use NaxCrmBundle\Entity\UserLogItem;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use NaxCrmBundle\Controller\v1\BaseController;

class OrderController extends BaseController
{
    protected $csvTitles = [
        'id',
        'login',
        'symbol',
        'flags',
        'price_open',
        'binary_option',
        'price_open1',
        'price_close',
        'amount',
        'currency',
        'time_open',
        'time',
        'expiration',
        'winrate',
        'profit',
        'email',
        'acc_group',
    ];

    protected function getOrders($params = [], $trader_group = false, $client = false)
    {
        $preFilters = [];
        if (!isset($params['filters'])) {
            $params['filters'] = [];
        }

        if ($trader_group) {
            $params['filters']['acc_group'] = $trader_group;
        }
        else {
            $trader_group = ($client) ? 'client#' . $client->getId() : 'all';
        }

        $params['handler'] = $this->getPlatformHandler();
        $this->exportFileName = "orders({$trader_group})";
        $this->csvTimeField = 'time_open';
        $res = $this->getList($params, $preFilters, 'getAllOrders');
        return $res;
    }

    /**
     * @Get("/clients/{id}/orders", name="v1_get_client_orders")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "banking",
     *    resource = "banking/order",
     *    authentication = true,
     *    description = "Get all selected client orders",
     *    statusCodes={
     *        200="Returned when successful",
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *          "Returned when the user have not proper acl rights",
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
     * @Security("(has_role('ROLE_MANAGER') and is_granted('R', 'client')) || (has_role('ROLE_CLIENT') and user == client)")
     */
    public function getClientsOrdersAction(Request $request, $client = null)
    {
        /* @var $client Client */
        if (empty($client)) {
            return $this->getFailedJsonResponse(null, "Client was not found", 404);
        }

        $params = $request->query->all();
        if(!isset($params['filters'])){
            $params['filters'] = array();
        }
        $params['filters']['logins'] = [];

        $accounts = $client->getAccounts();
        foreach ($accounts as $acc) {
            if(!empty($acc->getExternalId())){
                $params['filters']['logins'][] = $acc->getExternalId();
            }
            else{
                \NaxCrmBundle\Debug::$messages[]='WARN: Acc '.$acc->getId().' has no external_id';
            }
        }

        $res = $this->getOrders($params, false, $client);
        return $this->getJsonResponse($res);
    }

    /**
     * @Get("/orders", name="v1_get_all_orders")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "banking",
     *    resource = "banking/order",
     *    authentication = true,
     *    description = "Get all orders",
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
     * @Security("has_role('ROLE_MANAGER')")
     */
    public function getOrdersAction(Request $request)
    {
        $params = $request->query->all();
        $res = $this->getOrders($params);
        return $this->getJsonResponse($res);
    }


/*
 * Our base variant
 */

    // /**
    //  * @Get("/cpi/orders", name="v1_get_cpi_orders")
    //  * @Security("has_role('ROLE_MANAGER')")
    //  */
    // public function getCpiOrdersAction(Request $request)
    // {
    //     /* @var $orderRepo OrderRepository */
    //     $orderRepo = $this->getRepository(Order::class());
    //     $preFilters['cpi'] = true;
    //     if ($this->getUser()->getIsHead()) {
    //         $preFilters['departmentIds'] = $this->getUser()->getAccessibleDepartmentIds();
    //         $preFilters['isHead'] = true;
    //     } else {
    //         $preFilters['managerIds'] = [$this->getUser()->getId()];
    //     }
    //     return $this->getJsonResponse($orderRepo->setPreFilters($preFilters)->getOrders($request->query->all()));
    // }

    // /**
    //  * @Get("/cpa/orders", name="v1_get_cpa_orders")
    //  * @Security("has_role('ROLE_MANAGER')")
    //  */
    // public function getCpaOrdersAction(Request $request)
    // {
    //     /* @var $orderRepo OrderRepository */
    //     $orderRepo = $this->getRepository(Order::class());
    //     $preFilters['cpa'] = true;
    //     if ($this->getUser()->getIsHead()) {
    //         $preFilters['departmentIds'] = $this->getUser()->getAccessibleDepartmentIds();
    //         $preFilters['isHead'] = true;
    //     } else {
    //         $preFilters['managerIds'] = [$this->getUser()->getId()];
    //     }
    //     return $this->getJsonResponse($orderRepo->setPreFilters($preFilters)->getOrders($request->query->all()));
    // }

    // /**
    //  * @Post("/clients/{id}/orders", name="v1_open_order")
    //  * @ParamConverter("client", class="NaxCrmBundle\Entity\Client")
    //  * @Security("(has_role('ROLE_MANAGER') and is_granted('U', 'client')) || (has_role('ROLE_CLIENT') and user == client)")
    //  */
    // public function openClientsOrderAction($client = null, Request $request)
    // {
    //     /* @var $client Client */
    //     if (empty($client)) {
    //         return $this->getJsonResponse(null, "Client was not found", 404);
    //     }
    //     $em = $this->getEm();

    //     $extId = $this->getRequiredParam('extId');
    //     if (!$order = $this->getRepository(Order::class())->findOneBy(['extId' => $extId])) {
    //         $order = Order::createInst();
    //     } else {
    //         return $this->confirmClientsOrderAction($client, $order);
    //     }
    //     $type = $this->getRequiredParam('type');
    //     if (!in_array($type, [Order::TYPE_CALL, Order::TYPE_PUT])) {
    //         return $this->getFailedJsonResponse(null, "Invalid order type");
    //     }
    //     $currency = $request->request->get('currency');
    //     if (!$currency) {
    //         $accountId = $this->getRequiredParam('accountId');
    //         $account = $this->getRepository(Account::class())->find($accountId);
    //     } else {
    //         $account = $this->getClientService($client)->getAccount($currency);
    //     }
    //     if ((!$account instanceof Account)) {
    //         return $this->getFailedJsonResponse(null, "Client has no account for $currency. Please make deposit");
    //     }

    //     $tsOpen = $this->getRequiredParam('timeOpen');
    //     if (strlen($tsOpen) >= 14) {
    //         return $this->getFailedJsonResponse($tsOpen, 'Invalid time open, need timestamp with milliseconds');
    //     }
    //     $tsExpiration = $this->getRequiredParam('timeExpiration');
    //     if (strlen($tsExpiration) >= 14) {
    //         return $this->getFailedJsonResponse($tsExpiration, 'Invalid time expiration, need timestamp with milliseconds');
    //     }
    //     $tsOpenDate = substr($tsOpen, 0, 10);
    //     $tsOpenMilli = substr($tsOpen, 10) ?: 0;
    //     $tsExpirationDate = substr($tsOpen, 0, 10);
    //     $tsExpirationMilli = substr($tsOpen, 10) ?: 0;

    //     $order->setAmount($this->getRequiredParam('amount'));
    //     $order->setExtId($extId);
    //     $order->setPayout($this->getRequiredParam('payout'));
    //     $order->setPayoutRate($this->getRequiredParam('payoutRate'));
    //     $order->setState($this->getRequiredParam('state'));
    //     $order->setTimeOpen((new \DateTime())->setTimestamp($tsOpenDate));
    //     $order->setTimeOpenMillisec($tsOpenMilli);
    //     $order->setTimeExpiration((new \DateTime())->setTimestamp($tsExpirationDate));
    //     $order->setTimeExpirationMillisec($tsExpirationMilli);
    //     $order->setPriceOpen($this->getRequiredParam('priceOpen'));
    //     $order->setPriceStrike($this->getRequiredParam('priceStrike'));
    //     $order->setSymbol($this->getRequiredParam('symbol'));
    //     $order->setType($type);
    //     $order->setClient($client);
    //     $order->setAccount($account);
    //     $order->setInfo($request->request->get('info', ''));
    //     $em->persist($order);
    //     $em->flush();

    //     $this->createUserLog(UserLogItem::ACTION_TYPE_CREATE,UserLogItem::OBJ_TYPE_ORDERS, $order->export());


    //     return $this->getJsonResponse($order->export());
    // }

    // /**
    //  * @Post("/clients/{id}/orders/{orderId}", name="v1_confirm_order")
    //  * @ParamConverter("client", class="NaxCrmBundle\Entity\Client")
    //  * @ParamConverter("order", class="NaxCrmBundle:Order", options={"id" = "orderId"})
    //  * @Security("(has_role('ROLE_MANAGER') and is_granted('U', 'client')) || (has_role('ROLE_CLIENT') and user == client)")
    //  */
    // public function confirmClientsOrderAction(Client $client = null, Order $order = null)
    // {
    //     /* @var $client Client */
    //     if (empty($client)) {
    //         return $this->getJsonResponse(null, "Client was not found", 404);
    //     }
    //     if (empty($order)) {
    //         return $this->getJsonResponse(null, "Order was not found", 404);
    //     }
    //     if ($order->getClientId() != $client->getId()) {
    //         return $this->getJsonResponse(null, "Order does not belong to client");
    //     }

    //     $tsClose = $this->getRequiredParam('timeClose');
    //     if (strlen($tsClose) >= 14) {
    //         return $this->getFailedJsonResponse($tsClose, 'Invalid time close, need timestamp with milliseconds');
    //     }
    //     $tsCloseDate = substr($tsClose, 0, 10);
    //     $tsCloseMilli = substr($tsClose, 10) ?: 0;
    //     $em = $this->getEm();

    //     $order->setProfit($this->getRequiredParam('profit'));
    //     $order->setPriceClose($this->getRequiredParam('priceClose'));
    //     $order->setTimeClose((new \DateTime())->setTimestamp($tsCloseDate));
    //     $order->setTimeCloseMillisec($tsCloseMilli);
    //     $order->check();

    //     if ($order->getProfit() > 0) {
    //         $account = $order->getAccount();
    //         if ((!$account instanceof Account)) {
    //             return $this->getFailedJsonResponse(null, "Client has no account for this order.", 404);
    //         }
    //     }
    //     $em->flush();

    //     $this->createUserLog(UserLogItem::ACTION_TYPE_UPDATE, UserLogItem::OBJ_TYPE_ORDERS, ['Order confirmed' => $order->getId()]);
    //     return $this->getJsonResponse($order->export());
    // }
}
