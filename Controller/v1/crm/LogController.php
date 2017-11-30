<?php

namespace NaxCrmBundle\Controller\v1\crm;

use NaxCrmBundle\Entity\Department;
use NaxCrmBundle\Entity\LogItem;
use NaxCrmBundle\Entity\Manager;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations\Get;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use NaxCrmBundle\Controller\v1\BaseController;

class LogController extends BaseController
{
    protected $csvTimeField = 'time';
    protected $csvTitles = [
        'id',
        'managerId',
        'departmentId',
        'time',
        'objectType',
        'actionType',
        'jsonPayload',
    ];

    protected function setExportDefaults()
    {
        $this->csvAliases = [
            'managerId' => $this->getIdNamesArray(Manager::class()),
            'departmentId' => $this->getIdNamesArray(Department::class()),
        ];
    }
    protected function getUserLogList($params = [], $preFilters = [], $func = 'getUserLogs')
    {
        $this->exportFileName = 'User logs';
        return parent::getList($params, $preFilters, $func, 'UserLogItem');
    }

    protected function getLogList($params = [], $preFilters = [], $func = 'getSystemLogs')
    {
        $this->exportFileName = 'System logs';
        $this->csvAliases = [];
        unset($this->csvTitles['departmentId']);
        return parent::getList($params, $preFilters, $func, 'LogItem');
    }

    /**
     * @Get("/user-logs", name="v1_get_user_logs")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "crm",
     *    resource = "crm/log",
     *    authentication = true,
     *    description = "Get user logs",
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
     * @Security("has_role('ROLE_MANAGER') && is_granted('R', 'userlog')")
     */
    public function getUserLogsAction(Request $request)
    {
        $params = $request->query->all();
        $params['gmt_offset'] = $this->getGmtOffset();
        $preFilters = $this->setDepAccessFilters();

        $res = $this->getUserLogList($params, $preFilters);
        return $this->getJsonResponse($res);
    }

    /**
     * @Get("/system-logs", name="v1_get_system_logs")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "crm",
     *    resource = "crm/log",
     *    authentication = true,
     *    description = "Get system logs",
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
     */
    public function getSystemLogsAction(Request $request)
    {
        $params = $request->query->all();
        $res = $this->getLogList($params);
        return $this->getJsonResponse($res);
    }
}
