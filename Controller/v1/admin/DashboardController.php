<?php

namespace NaxCrmBundle\Controller\v1\admin;

use NaxCrmBundle\Entity\Account;
use NaxCrmBundle\Entity\Client;
use NaxCrmBundle\Entity\Deposit;
use NaxCrmBundle\Entity\UserLogItem;
use Symfony\Component\HttpFoundation\Request;
use NaxCrmBundle\Controller\v1\banking\ClientController;
use NaxCrmBundle\Entity\CompanyStatistic;
use NaxCrmBundle\Entity\Event;
use NaxCrmBundle\Entity\Manager;
use NaxCrmBundle\Repository\ClientRepository;
use NaxCrmBundle\Repository\CompanyStatisticRepository;
use NaxCrmBundle\Repository\EventRepository;
use NaxCrmBundle\Repository\ManagerRepository;
use FOS\RestBundle\Controller\Annotations\Get;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use NaxCrmBundle\Controller\v1\BaseController;

class DashboardController extends BaseController
{
    /**
     * @Get("/dashboard")
     * @ApiDoc(
     *    views = {"default"},
     *    statusCodes={
     *        200="Returned when successful",
     *        403="Returned when the user is not authorized with JWT token",
     *        500="Returned when Something went wrong",
     *    },
     *    description = "Get managers and their activity",
     *    parameters = {
     *        {"name"="filters", "dataType"="collection", "required"=false, "format"="filters[columnName]=value",                "description" = "set array of filters. You can get list of filters in response"},
     *        {"name"="order",   "dataType"="collection", "required"=false, "format"="order[column]=columnName&order[dir]=DESC", "description" = "add order column and direction for output."},
     *        {"name"="limit",     "dataType"="integer",    "required"=false, "format"="\d+",                                      "description" = "add limit for output."},
     *        {"name"="offset",    "dataType"="integer",    "required"=false, "format"="\d+",                                      "description" = "add offset shifting for output."},
     *    },
     *    section = "admin",
     *    resource = "admin/dashboard",
     * )
     * @Security("is_granted('R', 'dashboard')")
     */
    public function getManagerDashboard(Request $request)
    {
        $params = $request->query->all();
        $preFilters = $this->setDepAccessFilters();
        $repo = $this->getRepository(Manager::class());
        $repo->setPreFilters($preFilters);
        $res = $repo->getManagerDashboardData($params);
        return $this->getJsonResponse($res);
    }

    /**
     * @Get("/dashboard/department")
     * @ApiDoc(
     *    views = {"default"},
     *    statusCodes={
     *        200="Returned when successful",
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *          "Returned when the user have not proper acl rights",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     *    description = "Get department events for current manager",
     *    parameters = {
     *        {"name"="filters", "dataType"="collection", "required"=false, "format"="filters[columnName]=value",                "description" = "set array of filters. You can get list of filters in response"},
     *        {"name"="order",   "dataType"="collection", "required"=false, "format"="order[column]=columnName&order[dir]=DESC", "description" = "add order column and direction for output."},
     *        {"name"="limit",     "dataType"="integer",    "required"=false, "format"="\d+",                                      "description" = "add limit for output."},
     *        {"name"="offset",    "dataType"="integer",    "required"=false, "format"="\d+",                                      "description" = "add offset shifting for output."},
     *    },
     *    section = "admin",
     *    resource = "admin/dashboard",
     * )
     * @Security("is_granted('R', 'dashboard')")
     */
    public function getDepartmentEvents(Request $request)
    {
        $params = $request->query->all();
        $repo = $this->getRepository(Event::class());
        $repo->setPreFilters([
            'departmentIds' => $this->getUser()->getAccessibleDepartmentIds(),
            'notes' => true,
            'actual' => true,
            'statusIds' => Event::STATUS_UNREAD,
            'objectTypes' => [
                Event::OBJ_TYPE_CLIENT,
                Event::OBJ_TYPE_LEAD
            ]
        ]);
        $res = $repo->getEvents($params);

        return $this->getJsonResponse($res);
    }

    /**
     * @Get("/dashboard/department-finance")
     * @ApiDoc(
     *    views = {"default"},
     *    statusCodes={
     *        200="Returned when successful",
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *          "Returned when the user have not proper acl rights",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     *    description = "Get department finance statistic for current manager",
     *    section = "admin",
     *    resource = "admin/dashboard",
     * )
     * @Security("is_granted('R', 'dashboard')")
     */
    public function getDepartmentFinanceAction()
    {
        if (!$this->getUser()->getIsHead()) {
            return $this->getJsonResponse('');
        }
        $repo = $this->getRepository(Client::class());
        $res = $repo->getCompanyBalanceSheet($this->getUser()->getAccessibleDepartmentIds());
        return $this->getJsonResponse($res);
    }

    /**
     * @Get("/dashboard/company-statistics")
     * @ApiDoc(
     *    views = {"default"},
     *    statusCodes={
     *        200="Returned when successful",
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *          "Returned when the user have not proper acl rights",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     *    description = "Get common company statistics",
     *    parameters = {
     *        {"name"="filters", "dataType"="collection", "required"=false, "format"="filters[columnName]=value",                "description" = "set array of filters. You can get list of filters in response"},
     *        {"name"="order",   "dataType"="collection", "required"=false, "format"="order[column]=columnName&order[dir]=DESC", "description" = "add order column and direction for output."},
     *        {"name"="limit",     "dataType"="integer",    "required"=false, "format"="\d+",                                      "description" = "add limit for output."},
     *        {"name"="offset",    "dataType"="integer",    "required"=false, "format"="\d+",                                      "description" = "add offset shifting for output."},
     *    },
     *    section = "admin",
     *    resource = "admin/dashboard",
     * )
     * @Security("is_granted('R', 'dashboard')")
     */
    public function getCompanyStatisticsAction(Request $request)
    {
        $params = $request->query->all();
        $repo = $this->getRepository(CompanyStatistic::class());
        $res = $repo->getStatistics($params);
        return $this->getJsonResponse($res);
    }

    /**
     * @Get("/dashboard/clients-balances")
     * @ApiDoc(
     *    views = {"default"},
     *    statusCodes={
     *        200="Returned when successful",
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *          "Returned when the user have not proper acl rights",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     *    description = "Get common company statistics",
     *    parameters = {
     *        {"name"="filters", "dataType"="collection", "required"=false, "format"="filters[columnName]=value",                "description" = "set array of filters. You can get list of filters in response"},
     *        {"name"="order",   "dataType"="collection", "required"=false, "format"="order[column]=columnName&order[dir]=DESC", "description" = "add order column and direction for output."},
     *        {"name"="limit",     "dataType"="integer",    "required"=false, "format"="\d+",                                      "description" = "add limit for output."},
     *        {"name"="offset",    "dataType"="integer",    "required"=false, "format"="\d+",                                      "description" = "add offset shifting for output."},
     *    },
     *    section = "admin",
     *    resource = "admin/dashboard",
     * )
     * @Security("is_granted('R', 'dashboard')")
     */
    public function getClientsBalances(Request $request)
    {
        set_time_limit(60);
        $this->disable_debug = true;
        \NaxCrmBundle\Debug::$messages['disable_debug'] = true;
        $repo = $this->getRepository(Account::class());

        $params = $request->query->all();
        $filters = [];
        if (!empty($params['filters']['email'])) {
            $filters['email'] = $params['filters']['email'];
        }
        if (!empty($params['filters']['type'])) {
            $filters['a.type'] = $params['filters']['type'];
        }
        $accounts = $repo->getClientsAccounts($filters);
        $handler = $this->getPlatformHandler();
        $result = '';
        foreach ($accounts as $key => &$account) {
            $result .= $account[0]['clientId'] . ',' .
                $account[0]['email'] . ',' .
                $handler->getBalance($account[0]['externalId'])['balance'] .
                "\n";
        }

        return $this->getJsonResponse($result);
    }

    /**
     * @Get("/dashboard/clients-profits")
     * @ApiDoc(
     *    views = {"default"},
     *    statusCodes={
     *        200="Returned when successful",
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *          "Returned when the user have not proper acl rights",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     *    description = "Get common company statistics",
     *    parameters = {
     *        {"name"="filters", "dataType"="collection", "required"=false, "format"="filters[columnName]=value",                "description" = "set array of filters. You can get list of filters in response"},
     *        {"name"="order",   "dataType"="collection", "required"=false, "format"="order[column]=columnName&order[dir]=DESC", "description" = "add order column and direction for output."},
     *        {"name"="limit",     "dataType"="integer",    "required"=false, "format"="\d+",                                      "description" = "add limit for output."},
     *        {"name"="offset",    "dataType"="integer",    "required"=false, "format"="\d+",                                      "description" = "add offset shifting for output."},
     *    },
     *    section = "admin",
     *    resource = "admin/dashboard",
     * )
     * @Security("is_granted('R', 'dashboard')")
     */
    public function getClientsProfits(Request $request)
    {
        set_time_limit(60);
//        $this->disable_debug = true;
//        \NaxCrmBundle\Debug::$messages['disable_debug'] = true;
        $repo = $this->getRepository(Account::class());

        $params = $request->query->all();
        $filters = [];
        if (!empty($params['filters']['email'])) {
            $filters['email'] = $params['filters']['email'];
        }
        if (!empty($params['filters']['type'])) {
            $filters['a.type'] = $params['filters']['type'];
        }

        $accounts = $repo->getClientsAccounts($filters);

        $params['filters']['logins'] = [];

        $accountsWithProfits = [];

        foreach ($accounts as $account) {
            $params['filters']['logins'][] = $account[0]['externalId'];
            $accountsWithProfits[$account[0]['externalId']]['clientId'] = $account[0]['clientId'];
            $accountsWithProfits[$account[0]['externalId']]['email'] = $account[0]['email'];
            $accountsWithProfits[$account[0]['externalId']]['profit'] = 0;
        }

        $params['withLogin'] = true;
        $h = $this->getPlatformHandler();
        $res = $h->getClosedOrders($params);

//        $handler = $this->getPlatformHandler();
//        $params['limit'] = 1000;
//
//
//        for ($params['offset'] = 0; $params['offset'] < $params['max']; $params['offset']+=1000) {
//            $res = $handler->getAllOrders($params);
            foreach ($res['rows'] as $order) {
                $accountsWithProfits[$order['login']]['profit'] += $order['profit'];
            }
//        }
//
        $result = '';
        foreach ($accountsWithProfits as $accountWithProfit) {
            $result .=  $accountWithProfit['clientId'] . ',' .
                        $accountWithProfit['email'] . ',' .
                        $accountWithProfit['profit'] . "\n";
        }

        return $this->getJsonResponse($accountsWithProfits);
    }
}
