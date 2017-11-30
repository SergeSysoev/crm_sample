<?php

namespace NaxCrmBundle\Controller\v1\admin;

use NaxCrmBundle\Entity\AclPrivileges;
use NaxCrmBundle\Entity\AclResources;
use NaxCrmBundle\Entity\AclRoles;
use NaxCrmBundle\Entity\Manager;
use NaxCrmBundle\Repository\AclRolesRepository;
use NaxCrmBundle\Entity\UserLogItem;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Put;
use FOS\RestBundle\Controller\Annotations\Delete;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use NaxCrmBundle\Controller\v1\BaseController;

class AclController extends BaseController
{
    /**
     * @Get("/acl/roles", name="v1_get_roles")
     * @ApiDoc(
     *    views = {"default"},
     *    statusCodes = {
     *        200="Returned when successful",
     *        403="Returned when the user is not authorized with JWT token",
     *        500="Returned when Something went wrong",
     *    },
     *    authentication = true,
     *    description = "Get all roles information",
     *    section = "admin",
     *    resource = "admin/acl",
     *    parameters = {
     *        {"name"="filters", "dataType"="collection", "required"=false, "format"="filters[columnName]=value",                "description" = "set array of filters. You can get list of filters in response"},
     *        {"name"="order",   "dataType"="collection", "required"=false, "format"="order[column]=columnName&order[dir]=DESC", "description" = "add order column and direction for output."},
     *        {"name"="limit",     "dataType"="integer",    "required"=false, "format"="\d+",                                      "description" = "add limit for output."},
     *        {"name"="offset",    "dataType"="integer",    "required"=false, "format"="\d+",                                      "description" = "add offset shifting for output."},
     *    },
     * )
     */
    public function getRoles(Request $request)
    {
        $params = $request->query->all();
        $repo = $this->getRepository(AclRoles::class());
        return $this->getJsonResponse($repo->getList($params));
    }

    /**
     * @Get("/acl/roles/{id}", name="v1_get_role")
     * @ApiDoc(
     *    views = {"default"},
     *    statusCodes={
     *        200="Returned when successful",
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *          "Returned when the user have not proper acl rights",
     *        },
     *        404="Returned when role not found",
     *        500="Returned when Something went wrong",
     *    },
     *    description = "Get selected role information",
     *    requirements = {
     *         {"name"="id", "dataType"="integer", "description"="select role by id", "requirement"="\d+"},
     *    },
     *    section = "admin",
     *    resource = "admin/acl",
     * )
     * @Security("is_granted('R', 'role')")
     * @ParamConverter("role", class="NaxCrmBundle\Entity\AclRoles")
     * to get list of roles @see OpenController::getRoles
     */
    public function getRole($role = null)
    {
        if (!$role) {
            return $this->getFailedJsonResponse(null, 'Role not found', 404);
        }
        $repo = $this->getRepository(AclPrivileges::class());
        $result = [
            'id' => $role->getId(),
            'name' => $role->getName(),
            'description' => $role->getDescription(),
            'created' => $role->getCreated()
        ];
        $qb = $repo->createQueryBuilder('p')
            ->select(
                'p.privileges',
                'r.name as resourceName',
                'r.defaultPrivileges'
            )
            ->leftJoin('p.resource', 'r')
            ->where('p.role = ' . $role->getId());
        $privs = $qb->getQuery()->getArrayResult();
        /** @var AclResources[] $resources */
        $resources = $this->getRepository(AclResources::class())->findAll();
        foreach ($resources as $r) {
            if (empty($result['privileges'][$r->getName()])) {
                $result['privileges'][$r->getName()] = [
                    'default' => $r->getDefaultPrivileges(),
                    'privileges' => ''
                ];
            }
        }
        foreach ($privs as $p) {
            $result['privileges'][$p['resourceName']] = [
                'default' => $p['defaultPrivileges'],
                'privileges' => $p['privileges']
            ];
        }

        return $this->getJsonResponse($result);
    }

    /**
     * @Post("/acl/roles", name="v1_add_role")
     * @ApiDoc(
     *    views = {"default"},
     *    statusCodes={
     *        200="Returned when successful",
     *        400={
     *           "Returned if missing required parameter",
     *           "Returned if role name already exists",
     *        },
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *          "Returned when the user have not proper acl rights",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     *    description = "Add new role",
     *    parameters = {
     *        {"name"="name",        "dataType"="string", "required"=true, "description" = "set new role name."},
     *        {"name"="description", "dataType"="string", "required"=true, "description" = "set new role description."},
     *    },
     *    section = "admin",
     *    resource = "admin/acl",
     * )
     * @Security("is_granted('C', 'role')")
     */
    public function createRole(Request $request)
    {
        $roleRepo = $this->getRepository(AclRoles::class());
        $existRole = $roleRepo->findBy(['name' => $this->getRequiredParam('name')]);

        if (!empty($existRole)) {
            return $this->getFailedJsonResponse($this->getRequiredParam('name'), 'Role name already exists');
        }

        $role = AclRoles::createInst();
        $fields = [
            'name' => ['req'],
            'description' => ['req'],
        ];
        $this->saveEntity($role, $fields, $request->request->all());

        $data = $role->export([]);

        $this->createUserLog(UserLogItem::ACTION_TYPE_CREATE, UserLogItem::OBJ_TYPE_ROLES, $data);

        $this->recalculateJwt = true;
        $response = $this->getRole($role);

        return $response->setStatusCode(200);
    }

    /**
     * @Put("/acl/roles/{id}", name="v1_change_role")
     * @ApiDoc(
     *    views = {"default"},
     *    statusCodes={
     *        200="Returned when successful",
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *          "Returned when the user have not proper acl rights",
     *        },
     *        404="Returned if role not found",
     *        500="Returned when Something went wrong",
     *    },
     *    description = "Update selected role information",
     *    requirements = {
     *         {"name"="id", "dataType"="integer", "description"="select role by id", "requirement"="\d+"},
     *    },
     *    parameters = {
     *         {"name"="name",          "dataType"="string",     "required"=false,  "description"="set role name"},
     *         {"name"="description",   "dataType"="string",     "required"=false, "description"="set role description"},
     *         {"name"="privileges",    "dataType"="collection", "required"=false, "description"="set array of role privileges with privileges[privilegeName]=CRUDI"},
     *    },
     *    section = "admin",
     *    resource = "admin/acl",
     * )
     * @Security("is_granted('U', 'role')")
     * @ParamConverter("role", class="NaxCrmBundle\Entity\AclRoles")
     */
    public function updateRole($role = null, Request $request)
    {
        if (!$role) {
            return $this->getFailedJsonResponse(null, 'Role not found', 404);
        }
        $old_data = $role->export(['privileges' => 1]);

        $em = $this->getEm();
        if ($roleRights = $request->get('privileges')) {
            $resources = $this->getRepository(AclResources::class())->findBy([
                'name' => array_keys($roleRights)
            ]);
            foreach ($role->getPrivileges() as $privilege) {
                $em->remove($privilege);
            }
            $em->flush();
            foreach ($resources as $resource) {
                $privilege = AclPrivileges::createInst();
                $privilege->setResource($resource);
                $privilege->setPrivileges($roleRights[$resource->getName()]);
                $privilege->setRole($role);
                $role->addPrivilege($privilege);
                $em->persist($privilege);
            }
            $em->flush();
        }

        $fields = [
            'name',
            'description',
        ];
        $this->saveEntity($role, $fields, $request->request->all());

        $this->recalculateJwt = true;
        $response = $this->getRole($role);

        //calculate diff in fields and privileges
        $data = $role->export(['privileges' => 1]);
        $diff = $this->arrayDiff($data, $old_data);
        if (!empty($diff['privileges'])) {
            $diff['privileges'] = ['old'=>[],'new'=>[],];
            $privileges = $this->arrayDiff($data['privileges'], $old_data['privileges']);
            foreach ($privileges as $k => $v) {
                if(empty($old_data['privileges'][$k]) && empty($data['privileges'][$k])){
                    continue;
                }
                $diff['privileges']['old'][$k]=isset($old_data['privileges'][$k])? $old_data['privileges'][$k] : '';
                $diff['privileges']['new'][$k]=$data['privileges'][$k];
            }
            if(empty($diff['privileges']['old'])){
                unset($diff['privileges']);
            }
        }
        if (!empty($diff)) {
            $diff['id'] = $role->getId();
            $this->createUserLog(UserLogItem::ACTION_TYPE_UPDATE, UserLogItem::OBJ_TYPE_ROLES, $diff);
        }
        $this->recalculateJwt = true;
        return $response->setStatusCode(200);
    }

    /**
     * @Get("/acl/roles/{id}/managers", name="v1_get_role_managers")
     * @ApiDoc(
     *    views = {"default"},
     *    statusCodes={
     *        200="Returned when successful",
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *          "Returned when the user have not proper acl rights",
     *        },
     *        404="Returned when role not found",
     *        500="Returned when Something went wrong"
     *    },
     *    description = "Get all information for assigned to role managers",
     *    requirements = {
     *         {"name"="id", "dataType"="integer", "description"="select role by id", "requirement"="\d+"},
     *    },
     *    section = "admin",
     *    resource = "admin/acl",
     * )
     * @Security("is_granted('R', 'role')")
     * @ParamConverter("role", class="NaxCrmBundle\Entity\AclRoles")
     */
    public function getManagersByRole($role = null)
    {
        if (!$role) {
            return $this->getFailedJsonResponse(null, 'Role not found', 404);
        }
        $managers = $role->getManagers();

        return $this->getJsonResponse([
            'total'    => count($managers),
            'rows'     => $this->export($managers, ['aclRoles' => 1]),
            'filtered' => count($managers),
        ]);
    }

    /**
     * @Post("/acl/roles/{role}/managers/batch", name="v1_role_batch_add_managers")
     * @ApiDoc(
     *    views = {"default"},
     *    statusCodes={
     *        200="Returned when successful",
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *          "Returned when the user have not proper acl rights",
     *        },
     *        404={
     *          "Returned if role  not found",
     *          "Returned if manager not found",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     *    description = "Batch assign managers to role",
     *    requirements = {
     *         {"name"="role",    "dataType"="integer", "description"="select role by id",    "requirement"="\d+"},
     *         {"name"="managerIds", "dataType"="collection", "description"="select managers by id", "requirement"="\d+"},
     *    },
     *    section = "admin",
     *    resource = "admin/acl",
     * )
     * @Security("is_granted('R', 'role') and is_granted('U', 'manager')")
     * @ParamConverter("role", class="NaxCrmBundle\Entity\AclRoles")
     */
    public function batchAddManagerToRole($role = null, Request $request)
    {
        if (!$role) {
            return $this->getFailedJsonResponse(null, 'Role not found', 404);
        }
        $params = $request->request->all();
        if(empty($params['managerIds'])){
            return $this->getFailedJsonResponse(null, 'You must provide manager ids');
        }
        if(!is_array($params['managerIds'])){
            $params['managerIds'] = explode(',',$params['managerIds']);
        }

        $managers = $this->getRepository(Manager::class())->findBy([
            'id' => $params['managerIds'],
            // 'blocked' => 0,
        ]);
        foreach ($managers as $manager) {
            $manager->addAclRole($role);
            $this->getEm()->persist($manager);
            $this->getEm()->flush();
            $this->createUserLog(UserLogItem::ACTION_TYPE_UPDATE, UserLogItem::OBJ_TYPE_ROLES, [
                'id' => $role->getId(),
                'new manager' => $manager->getId()/*export()*/,
            ]);
        }

        $this->recalculateJwt = true;
        return $this->getJsonResponse($role->export(), 'Managers added to role', 200);
    }

    /**
     * @Post("/acl/roles/{role}/managers/{manager}", name="v1_role_add_manager")
     * @ApiDoc(
     *    views = {"default"},
     *    statusCodes={
     *        200="Returned when successful",
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *          "Returned when the user have not proper acl rights",
     *        },
     *        404={
     *          "Returned if role  not found",
     *          "Returned if manager not found",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     *    description = "Assign manager to role",
     *    requirements = {
     *         {"name"="role",    "dataType"="integer", "description"="select role by id",    "requirement"="\d+"},
     *         {"name"="manager", "dataType"="integer", "description"="select manager by id", "requirement"="\d+"},
     *    },
     *    section = "admin",
     *    resource = "admin/acl",
     * )
     * @Security("is_granted('R', 'role') and is_granted('U', 'manager')")
     * @ParamConverter("role", class="NaxCrmBundle\Entity\AclRoles")
     * @ParamConverter("manager", class="NaxCrmBundle\Entity\Manager")
     */
    public function addManagerToRole($role = null, Manager $manager = null)
    {
        if (!$role) {
            return $this->getFailedJsonResponse(null, 'Role not found', 404);
        }
        if (!$manager) {
            return $this->getFailedJsonResponse(null, 'Manager not found', 404);
        }
        $manager->addAclRole($role);
        $this->getEm()->flush();

        $this->createUserLog(UserLogItem::ACTION_TYPE_UPDATE, UserLogItem::OBJ_TYPE_ROLES, [
            'id' => $role->getId(),
            'new manager' => $manager->getId()/*export()*/,
        ]);
        $this->recalculateJwt = true;
        return $this->getJsonResponse($role->export(), 'Manager added to role', 200);
    }

    /**
     * @Delete("/acl/roles/{id}/managers")
     * @ApiDoc(
     *    views = {"default"},
     *    statusCodes={
     *        200="Returned when successful",
     *        400="Returned if missing required parameter",
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *          "Returned when the user have not proper acl rights",
     *        },
     *        404="Returned when role not found",
     *        500="Returned when Something went wrong",
     *    },
     *    description = "Batch unAssign managers from role",
     *    requirements = {
     *         {"name"="id", "dataType"="integer", "description"="select role by id", "requirement"="\d+"},
     *    },
     *    parameters = {
     *         {"name"="managerId", "dataType"="collection", "required"=true, "description"="set array of managers Id", "format"="\d+"},
     *    },
     *    section = "admin",
     *    resource = "admin/acl",
     * )
     * @Security("is_granted('R', 'role') and is_granted('U', 'manager')")
     * @ParamConverter("role", class="NaxCrmBundle\Entity\AclRoles")
     */
    public function deleteManagerFromRole(Request $request, $role = null)
    {
        if (!$role) {
            return $this->getFailedJsonResponse(null, 'Role not found', 404);
        }
        $managerId = $this->getRequiredParam('managerId');
        $managerId = join(',', $managerId);
        $sql = "DELETE m FROM acl_roles_manager m
                WHERE m.manager_id IN ($managerId) AND m.acl_roles_id = :roleId";

        $stmt = $this->getEm()->getConnection()->prepare($sql);
        $stmt->execute([
            ':roleId' => $role->getId()
        ]);
        $this->getEm()->refresh($role);

        $this->createUserLog(UserLogItem::ACTION_TYPE_DELETE, UserLogItem::OBJ_TYPE_ROLES, [
            'id' => $role->getId(),
            'removed managers ids' => $managerId
        ]);
        $this->recalculateJwt = true;
        return $this->getJsonResponse($role->export(), 'Manager removed from role', 200);
    }

    /**
     * @Delete("/acl/roles/{id}")
     * @ApiDoc(
     *    views = {"default"},
     *    statusCodes={
     *        200="Returned when successful",
     *        400={
     *          "Role is not empty",
     *        },
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *          "Returned when the user have not proper acl rights",
     *        },
     *        404="Returned when role not found",
     *        500="Returned when Something went wrong"
     *    },
     *    description = "Delete role",
     *    requirements = {
     *         {"name"="id", "dataType"="integer", "description"="select role by id", "requirement"="\d+"},
     *    },
     *    section = "admin",
     *    resource = "admin/acl",
     * )
     * @Security("is_granted('D', 'role')")
     * @ParamConverter("role", class="NaxCrmBundle\Entity\AclRoles")
     */
    public function deleteRole($role = null)
    {
        if (!$role) {
            return $this->getFailedJsonResponse(null, 'Role not found', 404);
        }
        if (count($role->getManagers())) {
            return $this->getFailedJsonResponse(null, 'Role is not empty');
        }
        $em = $this->getEm();
        foreach ($role->getPrivileges() as $privilege) {
            $em->remove($privilege);
        }
        $em->remove($role);
        $em->flush();

        $this->createUserLog(UserLogItem::ACTION_TYPE_DELETE, UserLogItem::OBJ_TYPE_ROLES, $role->export());
        $this->recalculateJwt = true;
        return $this->getJsonResponse(null, 'Role deleted');
    }

    /**
     * @Get("/acl/resources")
     * @ApiDoc(
     *    views = {"default"},
     *    statusCodes={
     *        200="Returned when successful",
     *        403="Returned when the user is not authorized with JWT token",
     *        500="Returned when Something went wrong",
     *    },
     *    authentication = true,
     *    description = "Get all available privileges information",
     *    section = "admin",
     *    resource = "admin/acl",
     * )
     */
    public function getResources()
    {
        $resources = $this->getRepository(AclResources::class())->findAll();
        return $this->getJsonResponse($resources);
    }
}
