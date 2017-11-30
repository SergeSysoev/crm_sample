<?php

namespace NaxCrmBundle\Controller\v1\admin;

use NaxCrmBundle\Entity\AclRoles;
use NaxCrmBundle\Entity\Manager;
use NaxCrmBundle\Entity\Department;
use NaxCrmBundle\Entity\UserLogItem;
use NaxCrmBundle\Modules\GoogleAuthenticator;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Put;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Delete;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use NaxCrmBundle\Controller\v1\BaseController;

class ManagerController extends BaseController
{
    protected $csvTitles = [
        'id',
        'name',
        'email',
        'phone',
        'departmentId',
        'skype',
        'isHead',
        'aclRoles',
    ];

    protected function setExportDefaults()
    {
        $this->csvAliases = [
            'isHead' => [
                0 => '',
                1 => 'Head'
            ],
            'aclRoles' => $this->getIdNamesArray(AclRoles::class()),
            'departmentId' => $this->getIdNamesArray(Department::class()),
        ];
    }

    /**
     * @Get("/managers/self", name="v1_managers_self")
     * @ApiDoc(
     *    views = {"default"},
     *    statusCodes={
     *        200="Returned when successful",
     *        403="Returned when the user is not authorized with JWT token",
     *        500="Returned when Something went wrong",
     *    },
     *    description = "Get self(manager) information",
     *    section = "admin",
     *    resource = "admin/manager",
     *    authentication = true,
     * )
     * @Security("has_role('ROLE_MANAGER')")
     */
    public function getSelf()
    {
        $bean = $this->getUser();
        $data = $bean->export();
        $var = $this->getRepository(Department::class())->getChildsIdList($bean->getDepartment());
        array_unshift($var, $bean->getDepartment()->getId());
        $data['allowedDepartments'] = $var;
        $data['resourcePermissions'] = $bean->getManagerPermissions();

        return $this->getJsonResponse($data);
    }

    /**
     * @Get("/managers", name="v1_managers")
     * @ApiDoc(
     *    views = {"default"},
     *    statusCodes = {
     *        200="Returned when successful",
     *        403="Returned when the user is not authorized with JWT token",
     *        500="Returned when Something went wrong",
     *    },
     *    authentication = true,
     *    description = "Get all managers information",
     *    section = "admin",
     *    resource = "admin/manager",
     *    parameters = {
     *        {"name"="filters",   "dataType"="collection", "required"=false, "format"="filters[columnName]=value",                "description" = "set array of filters. You can get list of filters in response"},
     *        {"name"="order",     "dataType"="collection", "required"=false, "format"="order[column]=columnName&order[dir]=DESC", "description" = "add order column and direction for output."},
     *        {"name"="limit",     "dataType"="integer",    "required"=false, "format"="\d+",                                      "description" = "add limit for output."},
     *        {"name"="offset",    "dataType"="integer",    "required"=false, "format"="\d+",                                      "description" = "add offset shifting for output."},
     *        {"name"="exportCsv", "dataType"="string",     "required"=false, "format"="lzw encoded array",                        "description" = "used for export result in CSV file"},
     *    },
     * )
     */
     // * @Security("is_granted('R', 'manager')")
    public function getAll(Request $request)
    {
        $params = $request->query->all();
        $preFilters = $this->setDepAccessFilters();

        $this->exportFileName = 'Managers';
        $res = $this->getList($params, $preFilters);
        return $this->getJsonResponse($res);
    }

    /**
     * @Get("/managers/2fauth", name="v1_managers_2faut")
     * @ApiDoc(
     *    views = {"default"},
     *    statusCodes = {
     *        200="Returned when successful",
     *        403="Returned when the user is not authorized with JWT token",
     *        500="Returned when Something went wrong",
     *    },
     *    authentication = true,
     *    description = "Generate 2factor google auth secret link",
     *    section = "admin",
     *    resource = "admin/manager",
     *    parameters = {},
     * )
     * @Security("has_role('ROLE_MANAGER')")
     */
     // * @Security("is_granted('R', 'manager')")
    public function get2fauth()
    {
        $user = $this->getUser();
        $email_parts = explode('@', $user->getEmail());
        $ga = new GoogleAuthenticator();
        $secret2fa = $ga->createSecret();
        $app_link = $ga->getAppUrl($email_parts[0].'@'.$this->getBrandName().'_crm', $secret2fa);
        $qr_code = $ga->getQRCodeGoogleUrl($email_parts[0].'@'.$this->getBrandName().'_crm', $secret2fa);

        return $this->getJsonResponse(compact('secret2fa','app_link','qr_code'));
    }

    /**
     * @Get("/managers/{id}")
     * @ApiDoc(
     *    views = {"default"},
     *    statusCodes={
     *        200="Returned when successful",
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *          "Returned when the user have not proper acl rights",
     *          "Returned when you can`t access this manager",
     *        },
     *        404="Returned when manager not found",
     *        500="Returned when Something went wrong",
     *    },
     *    description = "Get selected manager information",
     *    requirements = {
     *         {"name"="id", "dataType"="integer", "description"="select manager by id", "requirement"="\d+"},
     *    },
     *    section = "admin",
     *    resource = "admin/manager",
     * )
     * @Security("is_granted('R', 'manager')")
     * @ParamConverter("bean", class="NaxCrmBundle:Manager")
     */
    public function getOne($bean)
    {
        if (empty($bean)) {
            return $this->getFailedJsonResponse(null, 'Manager not found', 404);
        }
        if (!$this->getUser()->hasAccessToDepartment($bean->getDepartment())) {
            return $this->getFailedJsonResponse(null, 'You can`t access this manager', 403);
        }
        $data = $bean->export();
        return $this->getJsonResponse($data);
    }

    /**
     * @Post("/managers")
     * @ApiDoc(
     *    views = {"default"},
     *    statusCodes={
     *        200="Returned when successful",
     *        400={
     *           "Returned if missing required parameter",
     *           "Returned if manager email already exists",
     *           "Returned if validate errors",
     *        },
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *          "Returned when the user have not proper acl rights",
     *          "Returned when you can`t access this department",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     *    description = "Add new manager",
     *    parameters = {
     *        {"name"="name",          "dataType"="string",  "required"=true,  "description"="set new manager name."},
     *        {"name"="email",         "dataType"="string",  "required"=true,  "description"="set new manager email."},
     *        {"name"="departmentId",  "dataType"="integer", "required"=true,  "description"="set new manager department Id."},
     *        {"name"="phone",         "dataType"="integer", "required"=true,  "description"="set new manager phone."},
     *        {"name"="password",      "dataType"="string",  "required"=false, "description"="set new manager password."},
     *        {"name"="skype",         "dataType"="string",  "required"=false, "description"="set new manager skype."},
     *        {"name"="isHead",        "dataType"="boolean", "required"=false, "description"="set is new manager isHead of department."},
     *    },
     *    section = "admin",
     *    resource = "admin/manager",
     * )
     * @Security("is_granted('C', 'manager')")
     */
    public function create(Request $request)
    {
        $post = $request->request;
        if ($departmentId = $this->getRequiredParam('departmentId')) {
            $department = $this->getRepository(Department::class())->find($departmentId);
            if (!$this->getUser()->hasAccessToDepartment($department)) {
                return $this->getFailedJsonResponse(null, 'You can`t access this department', 403);
            }
        } else {
            $department = $this->getUser()->getDepartment();
        }

        $email = $this->getRequiredParam('email');
        $isEmailExists = $this->getRepository(Manager::class())->findBy(['email' => $email]);
        if (!empty($isEmailExists)) {
            return $this->getFailedJsonResponse($email, 'Manager email already exists');
        }

        $bean = Manager::createInst();

        $password = $post->get('password');
        $password = $this->get('security.password_encoder')->encodePassword($bean, $password);
        $bean->setPassword($password);
        $bean->setDepartment($department);

        $fields = [
            'name' => ['req'],
            'email' => ['req'],
            'phone' => ['req'],
            'skype' => ['def' => ''],
            'isHead' => ['def' => 0],
            'canResetPswd' => ['def' => 0],
            'platform' => ['def' => ''],
        ];
        $this->saveEntity($bean, $fields, $post->all());

        $data = $bean->export();
        $this->createUserLog(UserLogItem::ACTION_TYPE_CREATE, UserLogItem::OBJ_TYPE_MANAGERS, $data);
        return $this->getJsonResponse($data);
    }

    /**
     * @Put("/managers/{id}")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "admin",
     *    resource = "admin/manager",
     *    description = "Update manager",
     *    statusCodes={
     *        200="Returned when successful",
     *        400={
     *           "Returned if missing required parameter",
     *           "Returned if manager email already in use",
     *        },
     *        403={
     *           "Returned when the user is not authorized with JWT token",
     *           "Returned when the user have not proper acl rights",
     *           "Returned when you can`t access this manager",
     *           "Returned when you can`t access this department",
     *        },
     *        404={
     *          "Returned when manager not found",
     *          "Returned when department not found",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     *    parameters = {
     *        {"name"="department_id", "dataType"="integer",     "required"=false, "description"="set manager department Id."},
     *        {"name"="name",          "dataType"="string",      "required"=false, "description"="set manager name."},
     *        {"name"="email",         "dataType"="string",      "required"=false, "description"="set manager email."},
     *        {"name"="phone",         "dataType"="integer",     "required"=false, "description"="set manager phone."},
     *        {"name"="skype",         "dataType"="string",      "required"=false, "description"="set manager skype."},
     *        {"name"="password",      "dataType"="string",      "required"=false, "description"="set manager password."},
     *        {"name"="isHead",        "dataType"="boolean",     "required"=false, "description"="set is manager isHead of department."},
     *        {"name"="aclRoles",      "dataType"="collection",  "required"=false, "description"="set manager list of aclRoles."},
     *        {"name"="secret2fa",     "dataType"="string",      "required"=false, "description"="enable 2 factor auth"},
     *        {"name"="code2fa",       "dataType"="integer",     "required"=false, "description"="confirm code for enable 2 factor auth"},
     *        {"name"="disable2fa",    "dataType"="boolean",     "required"=false, "description"="disable 2 factor auth"},
     *    },
     *    requirements = {
     *         {"name"="id", "dataType"="integer", "description"="select manager by id", "requirement"="\d+"},
     *    },
     * )
     * @ParamConverter("bean", class="NaxCrmBundle:Manager")
     * @Security("is_granted('U', 'manager')")
     */
    public function update(Request $request, $bean = null)
    {
        if (empty($bean)) {
            return $this->getFailedJsonResponse(null, 'Manager not found', 404);
        }

        if (!$this->getUser()->hasAccessToDepartment($bean->getDepartment())) {
            return $this->getFailedJsonResponse(null, 'You can`t access this manager', 403);
        }

        $post = $request->request;
        $params = $post->all();
        $old_data = $bean->export();

        if ($departmentId = $post->get('departmentId')) {
            $department = $this->getRepository(Department::class())->find($departmentId);
            if (!$department) return $this->getFailedJsonResponse($departmentId, 'Department was not found', 404);
            if (!$this->getUser()->hasAccessToDepartment($department)) return $this->getFailedJsonResponse(null, 'You can`t access this department', 403);
        }

        //TODO make a repository method for this
        if ($departmentId && $departmentId != $bean->getDepartment()->getId()) {
            //change department for leads
            foreach ($bean->getLeads() as $lead) {
                $lead->setDepartment($department);
            }
            //change department for clients
            foreach ($bean->getClients() as $client) {
                $client->setDepartment($department);
            }
            $bean->setDepartment($department);
        }
        $fields = [
            'name',
            'skype',
            'canResetPswd',
            'platform',
        ];

        if($password = $post->get('password')){
            $password = $this->get('security.password_encoder')->encodePassword($bean, $password);
            $bean->setPassword($password);
            $this->createUserLog(UserLogItem::ACTION_TYPE_UPDATE, UserLogItem::OBJ_TYPE_MANAGERS, [
                'id' => $bean->getId(),
                'password' => 'changed',
            ]);
        }

        if ($bean->getId() !== 1) {
            $fields = array_merge($fields, [
                'isHead',
                'email',
                'phone',
            ]);
            if(isset($params['aclRoles'])){
                $aclRoles = [];
                if(!empty($params['aclRoles'])){
                    $aclRoles = $this->getRepository(AclRoles::class())->findById($params['aclRoles']);
                }
                $bean->setAclRoles($aclRoles);
            }
        }

        if($this->getUser()->getId() == $bean->getId()){
            if($secret = $post->get('secret2fa')){
                if(!($code = $post->get('code2fa', false))){
                    return $this->getFailedJsonResponse(null, 'You must confirm 2FA code for activate two factor authenticate', 400);
                }

                $ga = new GoogleAuthenticator($this->getGmtOffset());
                if(!$ga->verifyCode($secret, $code)){//, 2
                    return $this->getFailedJsonResponse(null, 'Not valid 2FA code', 400);
                }
                $bean->setSecret2fa($secret);
            }
            if($post->get('disable2fa')){
                $bean->setSecret2fa();
            }
        }

        $this->saveEntity($bean, $fields, $params);

        $data = $bean->export();
        $this->logUpdates($data, $old_data, $bean, UserLogItem::OBJ_TYPE_MANAGERS);
        return $this->getJsonResponse($data);
    }

    /**
     * @Post("/managers/{id}/block", name="v1_block_managers")
     * @ApiDoc(
     *    views = {"default"},
     *    statusCodes={
     *        200="Returned when successful",
     *        400={
     *           "Returned if missing required parameter",
     *        },
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *          "Returned when the user have not proper acl rights",
     *          "Returned you can`t access this manager",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     *    description = "Batch block managers",
     *    parameters = {
     *         {"name"="id", "dataType"="integer", "description"="select manager by id", "required"=true,},
     *         {"name"="assignTo",  "dataType"="integer", "description"="set manager id for reassign", "required"=true,},
     *    },
     *    section = "admin",
     *    resource = "admin/manager",
     * )
     * @ParamConverter("bean", class="NaxCrmBundle:Manager")
     * @Security("is_granted('D', 'manager')")
     */
    public function blockAction(Request $request, $bean = null)
    {
        $assignTo = $this->getRepository(Manager::class())->find($this->getRequiredParam('assignTo'));
        if (empty($assignTo)) {
            return $this->getFailedJsonResponse(null, 'Cannot find manager to assign', 404);
        }
        if (empty($bean)) {
            return $this->getFailedJsonResponse(null, 'Cannot find this manager', 404);
        }
        if ($bean->getId()===1 || !$this->getUser()->hasAccessToManager($bean) || !$this->getUser()->hasAccessToManager($assignTo)) {
            return $this->getFailedJsonResponse(null, 'You can`t access to this manager', 403);
        }
        $reassign = [
            'managerId' => $assignTo->getId(),
            'clients' => [],
            'leads' => [],
            'events' => [],
        ];

        foreach ($bean->getClients() as $rel) {
            $rel->setManager($assignTo);
            $rel->setDepartment($bean->getDepartment());
            $this->getEm()->persist($rel);
            $reassign['clients'][]= $rel->getId();
        }
        foreach ($bean->getLeads() as $rel) {
            $rel->setManager($assignTo);
            $rel->setDepartment($bean->getDepartment());
            $this->getEm()->persist($rel);
            $reassign['leads'][]= $rel->getId();
        }
        foreach ($bean->getEvents() as $rel) {
            $rel->setManager($assignTo);
            $this->getEm()->persist($rel);
            $reassign['events'][]= $rel->getId();
        }
        foreach ($bean->getAssignments() as $rel) {
            $rel->setManager($assignTo);
            $this->getEm()->persist($rel);
            $reassign['assignment'][]= $rel->getId();
        }
        /*foreach ($bean->getComments() as $rel) {
            $rel->setManager($assignTo);
            $this->getEm()->persist($rel);
            $reassign['comments'][]= $rel->getId();
        }*/

        $bean->setBlocked(true);
        $this->getEm()->persist($bean);
        $this->getEm()->flush();

        $this->createUserLog(UserLogItem::ACTION_TYPE_UPDATE, UserLogItem::OBJ_TYPE_MANAGERS, [
            'ids' => $bean->getId(),
            'blocked' => true,
            'reassign' => $reassign,
        ]);

        return $this->getJsonResponse($bean->export(), 'Managers was blocked');
    }


    /**
     * @Post("/managers/{id}/unblock", name="v1_unblock_managers")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "admin",
     *    resource = "admin/manager",
     *    authentication = true,
     *    description = "Batch unblock managers",
     *    statusCodes={
     *        200="Returned when successful",
     *        400={
     *          "Returned when missing required parameter",
     *        },
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *        },
     *        500="Returned when Something went wrong",
     *    },requirements = {
     *         {"name"="id", "dataType"="integer", "description"="select manager by id", "required"=true,},
     *    },
     * )
     * @ParamConverter("bean", class="NaxCrmBundle:Manager")
     */
    public function unblockAction($bean = null)
    {
        // $beanId = $this->getRequiredParam('managerId');
        // $bean = $this->getRepository(Manager::class())->find($beanId);
        $bean->setBlocked(false);
        $this->saveEntity($bean);

        $this->createUserLog(UserLogItem::ACTION_TYPE_UPDATE, UserLogItem::OBJ_TYPE_MANAGERS, [
            'id' => $bean->getId(),
            'blocked' => false,
        ]);
        return $this->getJsonResponse($bean->export(), 'Manager was unblocked');
    }

//     /**
//      * @Post("/managers/upload", name="v1_upload_managers")
//      * @ApiDoc(
//      *    views = {"default"},
//      *    statusCodes={
//      *        200="Returned when successful",
//      *        400={
//      *           "Returned when missing required parameter",
//      *           "Returned when missing file with managers in parameter `list`",
//      *           "Returned when parameter `fields` should be an array",
//      *        },
//      *        403={
//      *          "Returned when the user is not authorized with JWT token",
//      *          "Returned when the user have not proper acl rights",
//      *        },
//      *        500="Returned when Something went wrong",
//      *    },
//      *    description = "Upload managers from file",
//      *    parameters = {
//      *        {"name"="list",        "dataType"="file",       "required"=true,  "description"="file with managers list."},
//      *        {"name"="delimiter",   "dataType"="string",     "required"=true,  "description"="set delimiter for parameters in file line."},
//      *        {"name"="password",    "dataType"="string",     "required"=false, "description"="set password for uploaded managers."},
//      *        {"name"="getFirstRow", "dataType"="boolean",    "required"=false, "description"="it is two step action. If set getFirstRow than mean first step. Get list of parameters."},
//      *        {"name"="fields",      "dataType"="collection", "required"=false, "description"="it is required for 2nd step. Set map for parameters from file and manager parameters."},
//      *    },
//      *    section = "admin",
//      *    resource = "admin/manager",
//      * )
//      * @Security("is_granted('C', 'manager')")
//      */
//     public function uploadManagersAction(Request $request)
//     {
//         $files = $request->files->all();
//         if (count($files) != 1 || !isset($files['list'])) {
//             return $this->getFailedJsonResponse(null, 'Please send one file with managers in parameter `list`');
//         }
//         $file = $files['list'];
//         $delimiter = $this->getRequiredParam('delimiter');
//         $password = $request->request->get('password', '');
//         $loader = $this->container->get('nax_crm.manager_loader');
//         if (!($getFirstRow = (bool) $request->request->get('getFirstRow'))) {
//             $fields = $this->getRequiredParam('fields');

//             if (!is_array($fields)) {
//                 return $this->getFailedJsonResponse(null, 'Parameter `fields` should be an array');
//             }
//             $missingFields = array_diff(
//                 Manager::get('requiredFields'),
//                 array_keys($fields)
//             );
//             if (!empty($missingFields)) {
//                 $missingFieldNames = implode(',', $missingFields);
//                 return $this->getFailedJsonResponse(null, "Parameters $missingFieldNames are missing");
//             }
//         } else {
//             return $this->getJsonResponse($loader->getFirstRow($file, $delimiter));
//         }

//         $numberOfUploadedManagers = $loader->loadManagers($file, $fields, $delimiter, $this->getUser()->getDepartment(), $password);

//         $em = $this->getEm();
// //        $event = $this->createEvent($this->getUser(), 'You have uploaded new managers', 'new managers upload');
// //        $em->persist($event);
//         $em->flush();

//         $this->createUserLog(UserLogItem::ACTION_TYPE_CREATE, UserLogItem::OBJ_TYPE_MANAGERS, ['managers count' => $numberOfUploadedManagers]);

//         return $this->getJsonResponse(null, "$numberOfUploadedManagers managers were successfully uploaded");
//     }

    /**
     * @Post("/managers/assign-batch", name="v1_assign_batch_manager")
     * @ApiDoc(
     *    views = {"default"},
     *    statusCodes={
     *        200="Returned when successful",
     *        400={
     *           "Returned if missing required parameter",
     *        },
     *        403="Returned when the user is not authorized with JWT token",
     *        404="Returned when department was not found",
     *        500="Returned when Something went wrong",
     *    },
     *    authentication = true,
     *    description = "Batch assign managers to department",
     *    parameters = {
     *        {"name"="managerIds",   "dataType"="collection", "required"=true, "description"="set managers Id to assign."},
     *        {"name"="departmentId", "dataType"="integer",    "required"=true, "description"="set department Id to assign to."},
     *    },
     *    section = "admin",
     *    resource = "admin/manager",
     * )
     */
    public function assignBatchClientAction()
    {
        $managerIds = $this->getRequiredParam('managerIds');
        $departmentId = $this->getRequiredParam('departmentId');
        $managerRepo = $this->getRepository(Manager::class());
        $department = $this->getRepository(Department::class())->find($departmentId);

        if (!$department) {
            return $this->getFailedJsonResponse($departmentId, 'Department was not found',404);
        }

        foreach ($managerRepo->findBy(['id' => $managerIds]) as $manager) {
            if ($departmentId != $manager->getDepartment()->getId()) {
                //change department for leads
                foreach ($manager->getLeads() as $lead) {
                    $lead->setDepartment($department);
                    $this->getEm()->persist($lead);
                }
                //change department for clients
                foreach ($manager->getClients() as $client) {
                    $client->setDepartment($department);
                    $this->getEm()->persist($client);
                }
                $manager->setDepartment($department);
            }
        }
        $this->getEm()->flush();

        $this->createUserLog(UserLogItem::ACTION_TYPE_UPDATE,UserLogItem::OBJ_TYPE_MANAGERS, [
            'ids'    => $managerIds,
            'new department' => $department->export(),
        ]);
        return $this->getJsonResponse(null, 'Managers were assigned');
    }
}
