<?php

namespace NaxCrmBundle\Controller\v1\admin;

use NaxCrmBundle\Entity\Department;
use NaxCrmBundle\Entity\Manager;
use NaxCrmBundle\Entity\UserLogItem;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Put;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Delete;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use NaxCrmBundle\Controller\v1\BaseController;

class DepartmentController extends BaseController
{
    /**
     * @Get("/departments/{id}")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "admin",
     *    resource = "admin/department",
     *    authentication = true,
     *    description = "Get selected department information",
     *    statusCodes={
     *        200="Returned when successful",
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *          "Returned when manager has no access to department",
     *        },
     *        404={
     *          "Returned when department was not found",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     *    requirements = {
     *         {"name"="id", "dataType"="integer", "description"="select department by id", "requirement"="\d+"},
     *    },
     * )
     * @ParamConverter("department", class="NaxCrmBundle\Entity\Department")
     */
    public function getOne(Department $department)
    {
        if (!$this->getUser()->hasAccessToDepartment($department)) {
            return $this->getFailedJsonResponse(null, 'Manager has no access to department', 403);
        }
        return $this->getJsonResponse($department->export());
    }

    /**
     * @Get("/departments")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "admin",
     *    resource = "admin/department",
     *    authentication = true,
     *    description = "Get all departments for current manager",
     *    statusCodes = {
     *        200="Returned when successful",
     *        403="Returned when the user is not authorized with JWT token",
     *        500="Returned when Something went wrong",
     *    },
     * )
     */
    public function getAll(Request $request)
    {
        $user = $this->getUser();
        $repo = $this->getRepository(Department::class());
        // if (!$user->getIsHead()) {
        //     return $this->getJsonResponse($this->export($repo->findBy(['id' => $user->getAccessibleDepartmentIds()/*, 'deletedAt'=>'NULL'*/]), ['manager' => 1]));
        // }

        $departments = $repo->getDepartmentBranch($user->getDepartment());

        return $this->getJsonResponse($departments);
    }

    // /**
    //  * @Get("/departments-tree")
    //  */
    // public function getTree(Request $request)
    // {
    //     $user = $this->getUser();
    //     $repo = $this->getRepository(Department::class());
    //     // if (!$user->getIsHead()) {
    //     //     return $this->getJsonResponse($this->export($repo->findBy(['id' => $user->getAccessibleDepartmentIds()/*, 'deletedAt'=>'NULL'*/]), ['manager' => 1]));
    //     // }

    //     $departments = $repo->getDepartmentTree($user->getDepartment());

    //     return $this->getJsonResponse($departments);
    // }

    /**
     * @Post("/departments")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "admin",
     *    resource = "admin/department",
     *    authentication = true,
     *    description = "Add new department",
     *    statusCodes = {
     *        200="Returned when successful",
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     *    parameters = {
     *        {"name"="parent",      "dataType"="integer", "required"=false, "format"="\d+", "description"="set department parent id"},
     *        {"name"="name",        "dataType"="string",  "required"=true,  "format"=".+",  "description"="set department name"},
     *        {"name"="description", "dataType"="string",  "required"=false, "format"=".+",  "description"="set department description"},
     *    },
     * )
     */
    public function createDepartmentAction(Request $request)
    {
        $department = Department::createInst();
        $post = $request->request;

        $repo = $this->getRepository(Department::class());

        $parent = $repo->find($post->get('parent'));
        if (!$parent) $department->setRoot(1);
        else $department->setParent($parent);

        $fields = [
            'name',
            'description',
        ];
        $this->saveEntity($department, $fields, $post->all());

        $data = $department->export();
        $this->createUserLog(UserLogItem::ACTION_TYPE_CREATE, UserLogItem::OBJ_TYPE_DEPARTMENTS, $data);
        $this->recalculateJwt = true;
        return $this->getJsonResponse($data);
    }

    /**
     * @Put("/departments/{id}")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "admin",
     *    resource = "admin/department",
     *    authentication = true,
     *    description = "Update department",
     *    statusCodes = {
     *        200="Returned when successful",
     *        400={
     *          "Returned when invalid request parameters",
     *        },
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *        },
     *        404={
     *          "Returned when parent department was not found",
     *          "Returned when department was not found",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     *    requirements = {
     *         {"name"="id", "dataType"="integer", "description"="select department by id", "requirement"="\d+"},
     *    },
     *    parameters = {
     *        {"name"="parent",      "dataType"="integer", "required"=false, "format"="\d+", "description"="set department parent id"},
     *        {"name"="name",        "dataType"="string",  "required"=false, "format"=".+",  "description"="set department name"},
     *        {"name"="description", "dataType"="string",  "required"=false, "format"=".+",  "description"="set department description"},
     *    },
     * )
     * @ParamConverter("department", class="NaxCrmBundle\Entity\Department")
     */
    public function update(Request $request, Department $department = null)
    {
        $post = $request->request;
        $parentId = $post->get('parent');
        $parent = null;
        if ($parentId && !$parent = $this->getRepository(Department::class())->find($parentId)) {
            return $this->getFailedJsonResponse(null, 'Parent department not found', 404);
        }
        if (!$department) {
            return $this->getFailedJsonResponse(null, 'Department not found', 404);
        }
        $old_data = $department->export();
        if ($parent) {
            $department->setParent($parent);
        }

        $fields = [
            'name',
            'description',
        ];
        $this->saveEntity($department, $fields, $post->all());

        $data = $department->export();
        $this->logUpdates($data, $old_data, $department, UserLogItem::OBJ_TYPE_DEPARTMENTS);
        $this->recalculateJwt = true;
        return $this->getJsonResponse($data);
    }

    /**
     * @Delete("/departments/{id}")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "admin",
     *    resource = "admin/department",
     *    authentication = true,
     *    description = "Delete department",
     *    statusCodes = {
     *        200="Returned when successful",
     *        400="Returned when department has blocking data",
     *        403={
     *          "Returned when manager has no access to department",
     *        },
     *        404={
     *          "Returned when department was not found",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     *    requirements = {
     *         {"name"="id", "dataType"="integer", "description"="select department by id", "requirement"="\d+"},
     *    },
     * )
     * @ParamConverter("department", class="NaxCrmBundle:Department")
     */
    public function delete(Department $department)
    {
        if (!$this->getUser()->hasAccessToDepartment($department)) {
            return $this->getFailedJsonResponse(null, 'Manager has no access to department', 403);
        }

        $blockingData = [];

        if (empty($blockingData)) {
            $repo = $this->getRepository(Department::class());
            if (!empty($repo->getChildsIdList($department))){
                return $this->getFailedJsonResponse(null, 'Department has subsidiaries');
            }
        }

        if (empty($blockingData)) {
            $managers = $department->getManagers(true);
            if ($managers instanceof ArrayCollection && !$managers->isEmpty()) {
                $managerIds = $managers->map(function ($el) {
                    return [
                        'id' => $el->getId(),
                        'name' => $el->getName(),
                        'blocked' => $el->getBlocked(),
                    ];
                });
                $blockingData['managers'] = $managerIds;
            }
        }

        // if (empty($blockingData)) {
        //     $clients = $department->getClients();
        //     if (!empty($clients) && $clients instanceof ArrayCollection) {
        //         $clientIds = $clients->map(function ($el) {
        //             return $el->export();
        //         });
        //         $blockingData['clients'] = $clientIds;
        //     }

        //     $leads = $department->getLeads();
        //     if (!empty($leads) && $leads instanceof ArrayCollection) {
        //         $leadIds = $leads->map(function ($el) {
        //             return $el->export();
        //         });
        //         $blockingData['leads'] = $leadIds;
        //     }
        // }

        if (!empty($blockingData)) {
            return $this->getFailedJsonResponse($blockingData, 'Department has child data');
        }

        $data = $department->export();
        $this->removeEntity($department);

        $this->createUserLog(UserLogItem::ACTION_TYPE_DELETE, UserLogItem::OBJ_TYPE_DEPARTMENTS, $data);
        $this->recalculateJwt = true;
        return $this->getJsonResponse(null, 'Department was deleted');
    }

    /**
     * @Post("/departments/recover")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "admin",
     *    resource = "admin/department",
     *    authentication = true,
     *    description = "Recover department tree",
     *    statusCodes={
     *        200="Returned when successful",
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     * )
     */
    public function recover(Request $request)
    {
        $repo = $this->getRepository(Department::class());
        $repo->recover();
        $this->getEm()->flush();
        return $this->getAll($request);
    }
}
