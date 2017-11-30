<?php

namespace NaxCrmBundle\Controller\v1\crm;

use Doctrine\ORM\EntityManager;
use NaxCrmBundle\Entity\Assignment;
use NaxCrmBundle\Entity\Account;
use NaxCrmBundle\Entity\Country;
use NaxCrmBundle\Entity\Department;
use NaxCrmBundle\Entity\Event;
use NaxCrmBundle\Entity\Manager;
use NaxCrmBundle\Entity\Client;
use NaxCrmBundle\Entity\Comment;
use NaxCrmBundle\Entity\UserLogItem;
use NaxCrmBundle\Entity\Lead;
use NaxCrmBundle\Entity\LeadUpload;
use NaxCrmBundle\Entity\SaleStatus;
use NaxCrmBundle\Modules\Email\Triggers\Client\Registration;
use NaxCrmBundle\Modules\Email\Triggers\Client\Message;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Put;
use FOS\RestBundle\Controller\Annotations\Delete;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

use NaxCrmBundle\Controller\v1\BaseController;

class LeadController extends BaseController
{
    protected $csvTitles = [
        'firstname',
        'lastname',
        'email',
        'phone',
        'country',
        'langcode',
        'managerName',
        'saleStatus',
        'created',
    ];

    protected function setExportDefaults()
    {
        $this->csvAliases = [
            'saleStatus' => $this->getIdNamesArray(SaleStatus::class(), 'StatusName'),
            'managerId' => $this->getIdNamesArray(Manager::class()),
            'departmentId' => $this->getIdNamesArray(Department::class()),
            'country' => $this->getIdNamesArray(Country::class()),
        ];
    }

    /**
     * @Get("/leads", name="v1_get_leads")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "crm",
     *    resource = "crm/lead",
     *    authentication = true,
     *    description = "Get leads",
     *    statusCodes={
     *        200="Returned when successful",
     *        403={
     *          "Returned when the user is not authorized with JWT token",
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
    public function getLeadsAction(Request $request)
    {
        $params = $request->query->all();
        $preFilters = $this->setDepAccessFilters();
        $res = $this->getList($params, $preFilters);
        return $this->getJsonResponse($res);
    }

    /**
     * @Get("/leads/deleted", name="v1_get_leads_deleted")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "crm",
     *    resource = "crm/lead",
     *    authentication = true,
     *    description = "Get deleted leads",
     *    statusCodes={
     *        200="Returned when successful",
     *        403={
     *          "Returned when the user is not authorized with JWT token",
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
    public function getLeadsDeletedAction(Request $request)
    {
        $params = $request->query->all();
        $preFilters =[
            'showDeleted' => true,
        ];
        $res = $this->getList($params, $preFilters);
        return $this->getJsonResponse($res);
    }

    /**
     * @Get("/managers/{managerId}/leads", name="v1_get_manager_leads")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "crm",
     *    resource = "crm/lead",
     *    authentication = true,
     *    description = "Get selected manager leads",
     *    statusCodes={
     *        200="Returned when successful",
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *          "Returned when manager has no access to this leads",
     *        },
     *        404={
     *          "Returned when deposit was not found",
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
     *         {"name"="managerId", "dataType"="integer", "description"="select manager by id", "requirement"="\d+"},
     *    },
     * )
     */
    public function getManagerLeadsAction(Request $request, $managerId)
    {
        if (!$this->getUser()->getIsHead() && $this->getUser()->getId() != $managerId) {
            return $this->getFailedJsonResponse(null, 'Manager has no access to this leads', 403);
        }
        $params = $request->query->all();
        $preFilters =[
            'managerIds' => [$managerId],
        ];
        $res = $this->getList($params, $preFilters);
        return $this->getJsonResponse($res);
    }

    /**
     * @Get("/leads/{id}", name="v1_get_lead")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "crm",
     *    resource = "crm/lead",
     *    authentication = true,
     *    description = "Get selected lead",
     *    statusCodes={
     *        200="Returned when successful",
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *          "Returned when manager has no access to this leads",
     *        },
     *        404={
     *          "Returned when lead was not found",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     *    requirements = {
     *         {"name"="id", "dataType"="integer", "description"="select lead by id", "requirement"="\d+"},
     *    },
     * )
     * @ParamConverter("bean", class="NaxCrmBundle:Lead")
     */
    public function getLeadAction(Lead $bean = null)
    {
        if (empty($bean)) {
            return $this->getFailedJsonResponse(null, 'Lead was not found', 404);
        }
        if (!$this->getUser()->hasAccessToLead($bean)) {
            return $this->getFailedJsonResponse(null, 'Manager has no access to this lead', 403);
        }
        return $this->getJsonResponse($bean->export());
    }

    /**
     * @Delete("/leads", name="v1_batch_remove_lead")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "crm",
     *    resource = "crm/lead",
     *    authentication = true,
     *    description = "Batch delete leads",
     *    statusCodes={
     *        200="Returned when successful",
     *        400={
     *          "Returned when missing required parameter",
     *        },
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     *    parameters = {
     *        {"name"="leadIds",   "dataType"="collection", "required"=true, "format"="\d+", "description"="set array of lead id"},
     *    },
     * )
     */
    public function batchDeleteLeadAction()
    {
        $managers = [];
        $leadIds = $this->getRequiredParam('leadIds');
        $leadRepo = $this->getRepository(Lead::class());
        foreach ($leadRepo->findBy(['id' => $leadIds]) as $lead) {
            if(!isset($managers[$lead->getManagerId()])){
                $managers[$lead->getManagerId()] = [
                    'mngr'  => $lead->getManager(),
                    'leads' => 0,
                ];
            }
            $managers[$lead->getManagerId()]['leads']++;
            $this->getEm()->remove($lead);
        }
        $this->getEm()->flush();

        $this->createUserLog(UserLogItem::ACTION_TYPE_UPDATE, UserLogItem::OBJ_TYPE_LEADS, [
            'ids' => $leadIds,
            'archive' => true,
        ]);
        foreach ($managers as $mId => $v) {
            if($mId == $this->getUser()->getId()){
                continue;
            }
            $event = $this->createEvent($v['mngr'], $v['leads'].' leads were archived', 'Leads were archived');
            $this->getEm()->persist($event);
            $this->getEm()->flush();
        }
        return $this->getJsonResponse(null, 'Leads were removed');
    }

    /**
     * @Delete("/leads/{id}", name="v1_remove_lead")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "crm",
     *    resource = "crm/lead",
     *    authentication = true,
     *    description = "Delete lead",
     *    statusCodes={
     *        200="Returned when successful",
     *        400={
     *          "Returned when missing required parameter",
     *        },
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *        },
     *        404={
     *          "Returned when lead was not found",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     *    requirements = {
     *         {"name"="id", "dataType"="integer", "description"="select lead by id", "requirement"="\d+"},
     *    },
     * )
     * @ParamConverter("bean", class="NaxCrmBundle:Lead")
     */
    public function removeLeadAction(Lead $bean = null)
    {
        if (empty($bean)) {
            return $this->getFailedJsonResponse(null, 'Lead was not found', 404);
        }
        $data = $bean->export();
        $this->removeEntity($bean);

        $this->createUserLog(UserLogItem::ACTION_TYPE_UPDATE, UserLogItem::OBJ_TYPE_LEADS, [
            'id' => $data['id'],
            'archive' => true,
        ]);
        $this->addEvent($bean, 'was archived');
        return $this->getJsonResponse(null, 'Lead has been removed');
    }

    /**
     * @Post("/leads/upload", name="v1_upload_leads")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "crm",
     *    resource = "crm/lead",
     *    authentication = true,
     *    description = "Upload leads from file",
     *    statusCodes={
     *        200="Returned when successful",
     *        400={
     *          "Returned when missing required parameter",
     *          "Returned when send no one file with leads in parameter `list`",
     *          "Returned when department has no managers",
     *          "Returned when parameter `fields` should be an array",
     *          "Returned when missing one of field",
     *          "Returned when incorrect delimiter or file structure",
     *        },
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *          "Returned when the user have not proper acl rights",
     *        },
     *        404={
     *          "Returned when department was not found",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     *    parameters = {
     *        {"name"="list",         "dataType"="file",       "required"=false, "format"="file", "description" = "set file with leads for import"},
     *        {"name"="delimiter",    "dataType"="string",     "required"=false, "format"=".+",   "description" = "set row delimiter"},
     *        {"name"="departmentId", "dataType"="integer",    "required"=false, "format"="\d+",  "description" = "set departmentId for new leads"},
     *        {"name"="getFirstRow",  "dataType"="string",     "required"=false, "format"=".+",   "description" = "set first row for step 2"},
     *        {"name"="fields",       "dataType"="collection", "required"=false, "format"=".+",   "description" = "set fields map for step 2. Required!"},
     *    },
     * )
     * @Security("has_role('ROLE_MANAGER')")
     * //&& is_granted('C', 'lead')
     */
    public function uploadLeadsAction(Request $request)
    {
        $files = $request->files->all();
        if (count($files) != 1 || !isset($files['list'])) {
            return $this->getFailedJsonResponse(null, 'Please send one file with leads in parameter `list`');
        }
        $file = $files['list'];
        $delimiter = $request->get('delimiter');
        $loader = $this->container->get('nax_crm.lead_loader');

        $manager = null;
        $department = null;

        $assignToAdmin = false;
        $departmentId = $request->get('departmentId');
        if ($departmentId) {
            $department = $this->getRepository(Department::class())->find($departmentId);
            if (!$department) {
                return $this->getFailedJsonResponse(null, 'Department not found', 404);
            }
            if ($department->getManagers()->count() == 0) {
                return $this->getFailedJsonResponse(null, 'Department has no managers');
            }
        }
        $managerId = $request->get('managerId');
        if ($managerId) {
            $manager = $this->getRepository(Manager::class())->find($managerId);
        }
        $user = $this->getUser();
        // Debug::$messages['user'] = (!is_null($user))?get_class($user):'null';
        if (!$manager) {
            $manager = $user;
            if (!$manager OR !($manager instanceof Manager)) {
                $assignToAdmin = true;
            }
        }

        if (!($getFirstRow = (bool)$request->request->get('getFirstRow'))) {
            $fields = $this->getRequiredParam('fields');

            if (!is_array($fields)) {
                return $this->getFailedJsonResponse(null, 'Parameter `fields` should be an array');
            }
            $missingFields = array_diff(
                Lead::get('requiredFields'),
                array_keys($fields)
            );
            if (!empty($missingFields)) {
                $missingFieldNames = implode(',', $missingFields);
                return $this->getFailedJsonResponse(null, "Parameters {$missingFieldNames} are missing");
            }
        }
        else {
            $result = $loader->getFirstRow($file, $delimiter);
            if (!is_array($result)) {
                $mime = $file->getClientMimeType();
                throw new HttpException(400, 'Can`t parse file with mime type'.$mime);
            }
            if (count($result) < count(Lead::get('requiredFields'))) {
                return $this->getFailedJsonResponse($result, 'Incorrect delimiter or file structure');
            }
            return $this->getJsonResponse($result);
        }

        $leadUpload = $loader->loadLeads($file, $fields, $delimiter, $user, $manager, $department, $assignToAdmin);
        $failedLeads = $loader->getFailedLeads();

        $data = [
            'rows_processed' => $loader->getTotalRows(),
            'success' => $leadUpload->getNumberOfUploadedLeads(),
            'failed' => $loader->getNumberOfFailedLeads(),
        ];
        if (!empty($failedLeads)) {
            $fileName = uniqid(time()) . '.txt';
            $failedLeadsDir = $this->getAbsoluteUploadPath();
            $fileWithFailedLeads = $failedLeadsDir . $fileName;
            if (!file_exists($failedLeadsDir)) {
                mkdir($failedLeadsDir);
            }
            file_put_contents($fileWithFailedLeads, $failedLeads);
            $leadUpload->setFileWithFailedLeads($fileName);
            // $data['filePathWithFailedLeads'] = $this->get('router')->generate('v1_get_failed_leads', ['id' => $leadUpload->getId()]);
            $data['lead_uploads_id'] = $leadUpload->getId();
        }
        $this->getEm()->persist($leadUpload);
        $this->getEm()->flush();

        $this->createUserLog(UserLogItem::ACTION_TYPE_IMPORT, UserLogItem::OBJ_TYPE_LEADS, array_merge([
            'UploadedFile'    => $file->getClientOriginalName(),
            'Number of uploaded leads'  => $leadUpload->getNumberOfUploadedLeads(),
            'Link to uploaded files'    => $this->get('router')->generate('v1_get_upload_leads',['id' => $leadUpload->getId()]),
        ], $data));

        return $this->getJsonResponse($data, 'Leads were added: ' . $leadUpload->getNumberOfUploadedLeads());
    }

    /**
     * @Get("/lead-uploads/{id}/failed", name="v1_get_failed_leads")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "crm",
     *    resource = "crm/lead",
     *    authentication = true,
     *    description = "Get failed on upload leads",
     *    statusCodes={
     *        200="Returned when successful",
     *        400={
     *          "Returned when can`t read file with failed leads",
     *        },
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *          "Returned when manager has no access to this clients",
     *        },
     *        404={
     *          "Returned when upload was not found",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     *    requirements = {
     *         {"name"="id", "dataType"="integer", "description"="select manager by id", "requirement"="\d+"},
     *    },
     * )
     * @param LeadUpload $leadUpload
     * @ParamConverter("leadUpload", class="NaxCrmBundle:LeadUpload")
     * @return Response
     */
    public function viewFailedLeadsAction(Request $request, $leadUpload = null)
    {
        if (empty($leadUpload)) {
            return $this->getFailedJsonResponse(null, 'Upload was not found', 404);
        }
        $offset = $request->query->get('offset', 0);
        $limit = $request->query->get('limit', 25);
        // $headers = array(
        //     'Content-Type'     => 'text/csv',
        //     'Content-Disposition' => 'inline; filename="'.basename($leadUpload->getFileWithFailedLeads()).'"');
        // $content = file_get_contents($this->getParameter('failed_leads_directory') . $leadUpload->getFileWithFailedLeads());
        // return new Response($content, 200, $headers);
        $data = [];
        $f = fopen($this->getParameter('uploads_directory').'/failed_leads/'.$leadUpload->getFileWithFailedLeads(), 'r');
        $i = 0;
        if ($f) {
            while (($line = fgets($f)) !== false) {
                $i++;
                if ($i <= $offset) continue;
                if ($i > ($offset + $limit)) break;

                $row = explode(' - ', $line);
                $data[] = [
                    'row' => $row[0],
                    'error' => trim($row[1]),
                ];
            }
            fclose($f);
        } else {
            return $this->getFailedJsonResponse(null, 'Can`t read file with failed leads');
        }
        $res = [
            'rows' => $data,
        ];
        $res['total'] = $res['filtered'] = count($data);
        return $this->getJsonResponse($res, 'Ok');
    }

    /**
     * @Put("/leads/batch/update", name="v1_update_batch_lead")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "crm",
     *    resource = "crm/lead",
     *    authentication = true,
     *    description = "Batch update of clients",
     *    statusCodes={
     *        200="Returned when successful",
     *        400="Invalid request",
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *          "Returned when the user have not proper acl rights",
     *          "Returned when you can't edit another lead",
     *        },
     *        404="Returned when saleStatus not found",
     *        500="Returned when Something went wrong",
     *    },
     *    parameters = {
     *        {"name"="ids",    "dataType"="array", "required"=true, "format"=".+",  "description" = "ids of leads"},
     *        {"name"="fields", "dataType"="array", "required"=true, "format"="\d+", "description" = "fields for change"},
     *    },
     * )
     */
    public function batchUpdateLeadAction(Request $request)
    {
        $params = $request->request->all();
        if(empty($params['ids'])){
            return $this->getFailedJsonResponse(null, 'You must provide lead ids');
        }
        if(is_array($params['ids'])){
            $params['ids'] = implode(',',$params['ids']);
        }

        $fields = $this->batchFieldsPrepare($params);
        $repo = $this->getRepository(Lead::class());
        $res = $repo->batchUpdate($params['ids'], $fields);

        $data=[
            'ids' => $params['ids'],
            'fields' => $fields,
            'result' => $res?'success':'failed',
        ];
        $this->createUserLog(UserLogItem::ACTION_TYPE_UPDATE, UserLogItem::OBJ_TYPE_LEADS, $data);
        return $res?
            $this->getJsonResponse('Success'):
            $this->getFailedJsonResponse(null, 'Update operation failed', 500);
    }

    /**
     * @Put("/leads/{id}", name="v1_update_lead")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "crm",
     *    resource = "crm/lead",
     *    authentication = true,
     *    description = "Update lead",
     *    statusCodes={
     *        200="Returned when successful",
     *        400={
     *          "Returned when birthday not in Y-m-d format",
     *          "Returned when unknown country code and name",
     *        },
     *        404={
     *          "Returned when lead was not found",
     *          "Returned when manager was not found",
     *          "Returned when department was not found",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     *    parameters = {
     *        {"name"="firstname",    "dataType"="string",  "required"=false, "format"=".+",     "description"="set new lead firstname"},
     *        {"name"="lastname",     "dataType"="string",  "required"=false, "format"=".+",     "description"="set new lead lastname"},
     *        {"name"="email",        "dataType"="string",  "required"=false, "format"=".+",     "description"="set new lead email"},
     *        {"name"="phone",        "dataType"="string",  "required"=false, "format"=".+",     "description"="set new lead phone"},
     *        {"name"="langcode",     "dataType"="string",  "required"=false, "format"=".+",     "description"="set new lead langcode"},
     *        {"name"="city",         "dataType"="string",  "required"=false, "format"=".+",     "description"="set new lead city"},
     *        {"name"="birthday",     "dataType"="date",    "required"=false, "format"="Y-m-d",  "description"="set new lead birthday"},
     *        {"name"="countryCode",  "dataType"="string",  "required"=false, "format"=".+",     "description"="set new lead countryCode"},
     *        {"name"="managerId",    "dataType"="integer", "required"=false, "format"="\d+",    "description"="set new lead managerId"},
     *        {"name"="departmentId", "dataType"="integer", "required"=false, "format"="\d+",    "description"="set new lead departmentId"},
     *        {"name"="saleStatus",   "dataType"="integer", "required"=false, "format"="\d+",    "description"="set new lead saleStatus"},
     *    },
     *    requirements = {
     *         {"name"="id", "dataType"="integer", "description"="select lead by id", "requirement"="\d+"},
     *    },
     * )
     * @ParamConverter("bean", class="NaxCrmBundle:Lead")
     */
    public function updateLeadAction(Request $request, Lead $bean = null)
    {
        if (empty($bean)) {
            return $this->getFailedJsonResponse(null, 'Lead was not found', 404);
        }
        $old_data = $bean->export();

        $params = $request->request->all();

        $bean->setFirstname($params['firstname']);
        $bean->setLastname($params['lastname']);
        $bean->setEmail($params['email']);
        $bean->setPhone($params['phone']);
        $bean->setLangcode($params['langcode']);
        $bean->setCity($params['city']);
        $birthday = $params['birthday'];
        if ($birthday) {
            $birthday = \DateTime::createFromFormat('Y-m-d', $birthday);
            if (!$birthday) {
                return $this->getFailedJsonResponse($birthday, 'Please provide birthday in Y-m-d format');
            }
            $bean->setBirthday($birthday);
        }

        $countryCode = $params['countryCode'];
        if (!$country = $this->getRepository(Country::class())->findOneBy(['code' => $countryCode])) {
            if (!$country = $this->getRepository(Country::class())->findOneBy(['name' => $countryCode])) {
                return $this->getFailedJsonResponse($countryCode, 'Unknown country code and name');
            }
        }
        if($country){
            $bean->setCountry($country);
        }
        $managerId = $this->getRequiredParam('managerId');
        $manager = null;
        if ($managerId && !($manager = $this->getRepository(Manager::class())->find($managerId))) {
            return $this->getFailedJsonResponse(null, 'Manager not found', 404);
        }
        $oldManager = $bean->getManager();
        $bean->setManager($manager);

        if (!empty($params['departmentId'])) {
            if (!($department = $this->getRepository(Department::class())->find($params['departmentId']))) {
                return $this->getFailedJsonResponse(null, 'Department not found', 404);
            }
            $bean->setDepartment($department);
        }
        $status_change = false;
        if (!empty($params['saleStatus'])) {
            if (!($saleStatus = $this->getRepository(SaleStatus::class())->find($params['saleStatus']))) {
                return $this->getFailedJsonResponse(null, 'SaleStatus not found', 404);
            }
            if (($manager = $bean->getManager()) && (is_null($bean->getSaleStatus()) || $params['saleStatus'] != $bean->getSaleStatus()->getId())) {
                $status_change = true;
            }
            $bean->setSaleStatus($saleStatus);
        }

        $dupRepo = $this->getRepository(Client::class());
        if(($dup = $dupRepo->findOneBy(['phone' => $bean->getPhone()]))){
            return $this->getFailedJsonResponse(null, 'This phone already used at Client #'.$dup->getId(), 400);
        }
        if(($dup = $dupRepo->findOneBy(['email' => $bean->getEmail()]))){
            return $this->getFailedJsonResponse(null, 'This email already used at Client #'.$dup->getId(), 400);
        }

        if (!$this->validate($bean)) {
            return $this->response;
        }
        $em = $this->getEm();

        $oldManagerId = $oldManager ? $oldManager->getId() : 0;
        if ($managerId) {
            $assignment = Assignment::createInst();
            $assignment->setManager($manager);
            $this->getEm()->persist($assignment);
            $bean->setAssignment($assignment);
        }
        if ($oldManagerId != $managerId) {
            if ($manager && $managerId != $this->getUser()->getId()) {
                $eventTo = $this->createEvent($manager, '1  leads was assigned to you', 'New lead', Event::OBJ_TYPE_LEAD, $bean->getId());
                $this->getEm()->persist($eventTo);
            }
            if ($oldManager && $oldManagerId != $this->getUser()->getId()) {
                $eventFrom = $this->createEvent($oldManager, '1 lead was reassigned from you', 'Lead reassigned', Event::OBJ_TYPE_LEAD, $bean->getId());
                $this->getEm()->persist($eventFrom);
            }
        }

        $this->getEm()->flush();

        $data = $bean->export();
        $diff = $this->arrayDiff($data, $old_data);
        if(!empty($diff)){
            $this->addEvent($bean, 'updated', $diff);
            $diff['id'] = $bean->getId();
            $this->createUserLog(UserLogItem::ACTION_TYPE_UPDATE, UserLogItem::OBJ_TYPE_LEADS, $diff);
        }

        //auto_promote lead
        if($status_change && $bean->getSaleStatus()==$this->getRepository(SaleStatus::class())->findOneBy(['statusName'=>'processed'])){
            $client_data = $this->promoteLead($bean);
            if(!is_array($client_data)){
                return $client_data;
            }
            if(!empty($client_data['id'])){
                $data['client_id'] = $client_data['id'];
            }
        }
        if($bean->getPlatform()){
            $handler = $this->getPlatformHandler($bean->getPlatform(), false);
            if(!empty($handler) && method_exists($handler, 'setLead')){
                $handler->setLead($bean);
            }
            else{
                \NaxCrmBundle\Debug::$messages[]='WARN: missing platform handler for '.$bean->getPlatform();
            }
        }
        return $this->getJsonResponse($data);
    }

    /**
     * @Post("/leads/{id}/promote", name="v1_promote_lead")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "crm",
     *    resource = "crm/lead",
     *    authentication = true,
     *    description = "Promote lead to client",
     *    statusCodes={
     *        200="Returned when successful",
     *        400={
     *          "Returned when client with that phone already exists",
     *          "Returned when client with that email already exists",
     *        },
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *        },
     *        404={
     *          "Returned when lead was not found",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     *    requirements = {
     *         {"name"="id", "dataType"="integer", "description"="select client by id", "requirement"="\d+"},
     *    },
     * )
     * @ParamConverter("bean", class="NaxCrmBundle:Lead")
     */
    public function promoteLeadAction(Lead $bean = null){
        $client_data = $this->promoteLead($bean);
        if(!is_array($client_data)){
            return $client_data;
        }
        return $this->getJsonResponse($client_data);
    }

    public function promoteLead(Lead $lead = null)
    {
        if (empty($lead)) {
            return $this->getFailedJsonResponse(null, 'Lead was not found', 404);
        }
        $repo = $this->getRepository(Client::class());
        $client = $repo->findOneBy(['phone' => $lead->getPhone()]);
        if ($client) {
            return $this->getFailedJsonResponse(['lead_phone' => $lead->getPhone(), 'client'=>$client->export()], 'Client with that phone already exists');
        }
        $client = $repo->findOneBy(['email' => $lead->getEmail()]);
        if ($client) {
            return $this->getFailedJsonResponse(['lead_email' => $lead->getEmail(), 'client'=>$client->export()], 'Client with that email already exists');
        }

        $client = Client::createInst();
        // $password  = substr(md5(uniqid($lead->getEmail(), true)), 0, 10);
        $password = $this->getParameter('client_default_password');
        $hashedPassword = $this->get('security.password_encoder')->encodePassword($client, $password);
        $client->setPassword($hashedPassword);
        $client->setStatus(Client::STATUS_UNBLOCKED);
        $this->fillClientFromLead($client, $lead);

        if (!$this->validate($client)) {
            return $this->response;
        }
        $this->getEm()->persist($client);
        $this->getEm()->flush();

        if ($platform = $this->getPlatformName()) {
            $account = Account::createInst();
            $account->setClient($client);

            $account->setCurrency(Account::DEFAULT_CURRENCY);
            $account->setType(Account::TYPE_LIVE);
            $account->setPlatform($platform);

            $result = $this->getPlatformHandler()->addAccount($account);
            if (empty($result['externalId'])) {
                $this->removeEntity($client, true);
                return $this->getFailedJsonResponse(null, 'Platform reject client', 503);
            }
            $account->setExternalId($result['externalId']);
            if($this->validate($account, false)){
                $this->getEm()->persist($account);
                $this->getEm()->flush();
            }
            else{
                \NaxCrmBundle\Debug::$messages[]='WARN: ignore platform conflict';
            }
        }

        //move lead's comments to client
        $comments = $lead->getComments();
        foreach ($comments as $comment) {
            $comment->setLead(null);
            $comment->setClient($client);
            $this->getEm()->persist($comment);
        }

        $repo = $this->getRepository(Event::class());
        $events = $repo->findBy([
            'objectType' => 'lead',
            'objectId' => $lead->getId(),
        ]);
        foreach ($events as $event) {
            $event->setObjectId($client->getId());
            $event->setObjectType('client');
            $this->getEm()->persist($event);
        }
        if($client->getManager()->getId() != $this->getUser()->getId()){
            $event = $this->createEvent($client->getManager(), 'Client was promoted from lead', 'New client', Event::OBJ_TYPE_CLIENT, $client->getId());
            $this->getEm()->persist($event);
        }
        $this->getEm()->flush();

        $this->removeEntity($lead, true);

        //TODO
        $confirmLink = $this->getParameter('urls')['confirm_url'] . '?hash=' . urlencode('TODO token');
        $this->getTriggerService()->sendEmails(Registration::class(), compact('client', 'password', 'confirmLink'), $client->getLangcode());

        $res = $client->export();
        $res['message'] = 'Promoted from lead';
        $this->createUserLog(UserLogItem::ACTION_TYPE_CREATE, UserLogItem::OBJ_TYPE_CLIENTS, $res);

        return $res;
    }

    protected function fillClientFromLead(&$client, $lead){
        /* @var Client $client */
        /* @var Lead $lead */
        $client->setManager($lead->getManager());
        $client->setDepartment($lead->getDepartment());
        $client->setEmail($lead->getEmail());
        $client->setPhone($lead->getPhone());
        $client->setFirstname($lead->getFirstname());
        $client->setLastname($lead->getLastname());
        $client->setCity($lead->getCity());
        $client->setCountry($lead->getCountry());
        $client->setBirthday($lead->getBirthday());
        $client->setSaleStatus($lead->getSaleStatus());
        $client->setLangcode($lead->getLangcode());
        $client->setSource($lead->getSource());
        $client->setPlatform($lead->getPlatform());
        $client->setExternalId($lead->getExternalId());
    }

    /**
     * @Post("/leads/assign-batch", name="v1_assign_batch_lead")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "crm",
     *    resource = "crm/lead",
     *    authentication = true,
     *    description = "Batch assign lead to manager or department",
     *    statusCodes={
     *        200="Returned when successful",
     *        400={
     *          "Returned when missing required parameter",
     *        },
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *        },
     *        404={
     *          "Returned when manager was not found",
     *          "Returned when department was not found",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     *    parameters = {
     *        {"name"="leadIds",      "dataType"="collection", "required"=true,  "format"="\d+", "description"="set array of lead ids"},
     *        {"name"="managerId",    "dataType"="integer",    "required"=false, "format"="\d+", "description"="set leads managerId"},
     *        {"name"="departmentId", "dataType"="integer",    "required"=true,  "format"="\d+", "description"="set leads departmentId"},
     *    },
     * )
     */
    public function assignBatchLeadAction(Request $request)
    {
        /** @var EntityManager $em */
        $em = $this->getEm();
        if ($em->getFilters()->isEnabled('softdeleteable')) {
            $em->getFilters()->disable('softdeleteable');
        }
        $leadIds = $this->getRequiredParam('leadIds');
        $leadRepo = $this->getRepository(Lead::class());
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

        $assignedLeadsCount = 0;
        $oldManagers = [];
        foreach ($leadRepo->findBy(['id' => $leadIds]) as $lead) {
            if (isset($newManager)) {
                $assignedLeadsCount++;
                $lead->setManager($newManager);
            }
            //count lead assigned out from manager
            if ($lead->getManager()) {
                if (empty($oldManagers[$lead->getManager()->getId()])) {
                    $oldManagers[$lead->getManager()->getId()] = 1;
                } else {
                    ++$oldManagers[$lead->getManager()->getId()];
                }
            }
            $lead->setAssignment($assignment);
            $lead->setDepartment($newDepartment);
        }
        // create events for everyone of old managers
        foreach ($oldManagers as $oldManagerId => $assignedLeadsCountFrom) {
            if ($oldManagerId != $this->getUser()->getId() && (!$newManager || $oldManagerId != $newManager->getId())) {
                $oldManager = $em->getPartialReference(Manager::class(), $oldManagerId);
                $eventFrom = $this->createEvent(
                    $oldManager,
                    "{$assignedLeadsCountFrom} leads were reassigned from you", 'Leads reassign',
                    Event::OBJ_TYPE_LEAD_ASSIGNMENT, $assignment->getId()
                );
                $em->persist($eventFrom);
            }
        }
        //make one event for new manager if he exist
        if ($newManager && $newManager->getId() != $this->getUser()->getId() && ($assignedLeadsCount > 0)) {
            $eventTo = $this->createEvent(
                $newManager,
                "{$assignedLeadsCount} leads were assigned to you", 'New leads',
                Event::OBJ_TYPE_LEAD_ASSIGNMENT, $assignment->getId()
            );
            $em->persist($eventTo);
        }
        $em->flush();

        if(!empty($diff)){
            $diff['ids'] = implode(',', $leadIds);
            $this->createUserLog(UserLogItem::ACTION_TYPE_UPDATE, UserLogItem::OBJ_TYPE_LEADS, $diff);
        }
        return $this->getJsonResponse(null, 'Leads were assigned');
    }

    /**
     * @Post("/leads/restore-batch", name="v1_restore_batch_client")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "crm",
     *    resource = "crm/lead",
     *    authentication = true,
     *    description = "Restore deleted leads",
     *    statusCodes={
     *        200="Returned when successful",
     *        400={
     *          "Returned when missing required parameter",
     *        },
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     *    parameters = {
     *        {"name"="leadIds", "dataType"="collection", "required"=true, "format"=".+", "description"="set array of lead ids"},
     *    },
     * )
     */
    public function restoreBatchClientAction()
    {
        $leadIds = $this->getRequiredParam('leadIds');
        $em = $this->getEm();
        if ($em->getFilters()->isEnabled('softdeleteable')) {
            $em->getFilters()->disable('softdeleteable');
        }

        $badLeads = [];
        $goodLeads = [];
        $invalidLeads = [];
        $validator  = $this->get('validator');
        $leads = $this->getRepository(Lead::class())->findBy(['id' => $leadIds]);
        foreach ($leads as $lead) {
            $deletedAt = $lead->getDeletedAt();
            $lead->setDeletedAt(null);
            $errors = $validator->validate($lead);
            $errorMessages = [];
            if (count($errors) > 0) {
                foreach($errors as $error) {
                    $errorMessages[] = $error->getMessage();
                }
                $invalidLeads[] = [
                    'leadId' => $lead->getId(),
                    'messages' => implode(',', $errorMessages),
                ];
                $lead->setDeletedAt($deletedAt);
                continue;
            }
            $manager = $lead->getManager();
            $department = $lead->getDepartment();
            if (($manager && $manager->getBlocked()) || ($department && !is_null($department->getDeletedAt()))) {
                $badLeads[] = $lead->getId();
            } else {
                $goodLeads[] = $lead->getId();
            }
        }
        $em->flush();

        if (!empty($badLeads) || !empty($invalidLeads)) {
            return $this->getFailedJsonResponse([
                'badLeads'  => $badLeads,
                'goodLeads' => $goodLeads,
                'invalidLeads' => $invalidLeads,
            ] , 'There are invalid leads and/or leads with removed managers or departments');
        }
        $data = [
            'goodLeads' => $goodLeads,
            'notFound'=>(count($leadIds) -count($leads))
        ];
        $this->createUserLog(UserLogItem::ACTION_TYPE_UPDATE, UserLogItem::OBJ_TYPE_LEADS, [
            'ids' => $goodLeads,
            'archive' => false,
        ]);
        return $this->getJsonResponse($data, 'Leads were restored');
    }

    /**
     * @Get("/assignments/{id}/leads", name="v1_get_assignment_leads")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "crm",
     *    resource = "crm/lead",
     *    authentication = true,
     *    description = "Get assignment leads",
     *    statusCodes={
     *        200="Returned when successful",
     *        403={
     *          "Returned when the user is not authorized with JWT token",
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
     *        {"name"="exportCsv", "dataType"="string",     "required"=false, "format"="lzw encoded array",                        "description" = "used for export result in CSV file"},
     *    },
     *    requirements = {
     *         {"name"="id", "dataType"="integer", "description"="select assignment by id", "requirement"="\d+"},
     *    },
     * )
     * @ParamConverter("assignment", class="NaxCrmBundle:Assignment")
     */
    public function getAssignmentLeadsAction(Request $request, Assignment $assignment = null)
    {
        if (empty($assignment)) {
            return $this->getFailedJsonResponse(null, 'Assignment was not found', 404);
        }
        $params = $request->query->all();
        $preFilters = [
            'assignmentIds' => [$assignment->getId()]
        ];
        $res = $this->getList($params, $preFilters);
        return $this->getJsonResponse($res);
    }

    /**
     * @Get("/lead-uploads/{id}/leads", name="v1_get_upload_leads")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "crm",
     *    resource = "crm/lead",
     *    authentication = true,
     *    description = "Get selected upload leads",
     *    statusCodes={
     *        200="Returned when successful",
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *        },
     *        404={
     *          "Returned when leadUpload was not found",
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
     *         {"name"="id", "dataType"="integer", "description"="select leadUpload by id", "requirement"="\d+"},
     *    },
     * )
     * @ParamConverter("leadUpload", class="NaxCrmBundle:LeadUpload")
     */
    public function getLeadUploadLeadsAction(Request $request, LeadUpload $leadUpload = null)
    {
        if (empty($leadUpload)) {
            return $this->getFailedJsonResponse(null, 'LeadUpload was not found', 404);
        }
        $params = $request->query->all();
        $preFilters = [
            'leadUploadIds' => [$leadUpload->getId()],
            'managerIds' => $this->getUser()->getId(),
        ];
        $res = $this->getList($params, $preFilters);
        return $this->getJsonResponse($res);
    }



    /**
     * @Post("/leads/{id}/sendmail", name="v1_lead_sendmail")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "crm",
     *    resource = "crm/lead",
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
     *          "Returned when lead was not found",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     *    parameters = {
     *        {"name"="message", "dataType"="string", "required"=true, "format"=".+", "description" = "message text"},
     *    },
     *    requirements = {
     *         {"name"="id", "dataType"="integer", "description"="select lead by id", "requirement"="\d+"},
     *    },
     * )
     * @ParamConverter("lead", class="NaxCrmBundle:Lead")
     * @Security("has_role('ROLE_MANAGER') && is_granted('R', 'lead')")
     */
    public function leadSendMailAction(Request $request, $lead = null)
    {
        if (empty($lead)) {
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

        $res = $this->getTriggerService()->sendEmails(Message::class(), compact('lead', 'message', 'attachment'), $lead->getLangcode());
        if($res[1]>0){
            return $this->getJsonResponse($attachment, 'Message successfully added to order for send');
        }
        else{
            return $this->getFailedJsonResponse($res, '0 messages sended. Please check email templates');
        }
    }


    /*
    * Helpers
    */

    protected function batchFieldsPrepare($params){
        $fields = [];
        if(!empty($params['fields']['saleStatus'])){
            if (!$this->getRepository(SaleStatus::class())->find($params['fields']['saleStatus'])) {
                throw new HttpException(404, 'SaleStatus not found '.$params['fields']['saleStatus']);
            }
            $fields['l.sale_status_id'] = $params['fields']['saleStatus'];
        }

        if (empty($fields)) {
           throw new HttpException(400, 'Incorrect fields for update');
        }
        return $fields;
    }
}
