<?php

namespace NaxCrmBundle\Controller\v1\admin;

use NaxCrmBundle\Entity\TokenGroup;
use NaxCrmBundle\Repository\TokenGroupRepository;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Put;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Delete;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use NaxCrmBundle\Controller\v1\BaseController;

class TokenGroupController extends BaseController
{

    /**
     * @Get("/token-groups")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "admin",
     *    resource = "admin/token",
     *    authentication = true,
     *    description = "Get all available languages token groups",
     *    statusCodes={
     *        200="Returned when successful",
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     * )
     */
    public function getTokenGroupsAction(Request $request)
    {
        /* @var $tokenRepo TokenGroupRepository */
        $tokenRepo = $this->getRepository(TokenGroup::class());
        return $this->getJsonResponse($tokenRepo->findBy($request->query->all()));
    }

    /**
     * @Post("/token-groups")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "admin",
     *    resource = "admin/token",
     *    authentication = true,
     *    description = "Add language token group",
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
     *        {"name"="name",    "dataType"="string",  "required"=true, "format"=".+",  "description" = "set token group name"},
     *    },
     * )
     */
    public function createTokenGroupAction(Request $request)
    {
        $entity = TokenGroup::createInst();

        $this->saveEntity($entity, ['name' => ['req']], $request->request->all());

        return $this->getJsonResponse($entity->export(), 'TokenGroup created');
    }

    /**
     * @Put("/token-groups/{id}")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "admin",
     *    resource = "admin/token",
     *    authentication = true,
     *    description = "Update language token group",
     *    statusCodes={
     *        200="Returned when successful",
     *        400={
     *          "Returned when validation error",
     *        },
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *        },
     *        404={
     *          "Returned when tokenGroup was not found",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     *    parameters = {
     *        {"name"="name",    "dataType"="string",  "required"=false, "format"=".+",  "description" = "set token group name"},
     *    },
     *    requirements = {
     *         {"name"="id", "dataType"="integer", "description"="select token group by id", "requirement"="\d+"},
     *    },
     * )
     * @ParamConverter("entity", class="NaxCrmBundle:TokenGroup")
     */
    public function update(TokenGroup $entity = null, Request $request)
    {
        if (empty($entity)) {
            return $this->getFailedJsonResponse(null, "TokenGroup was not found", 404);
        }

        $this->saveEntity($entity, ['name'], $request->request->all());

        return $this->getJsonResponse($entity->export(), 'TokenGroup updated');
    }

    /**
     * @Delete("/token-groups/{id}")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "admin",
     *    resource = "admin/token",
     *    authentication = true,
     *    description = "Delete language token group",
     *    statusCodes={
     *        200="Returned when successful",
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *        },
     *        404={
     *          "Returned when tokenGroup was not found",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     *    requirements = {
     *         {"name"="id", "dataType"="integer", "description"="select token group by id", "requirement"="\d+"},
     *    },
     * )
     * @ParamConverter("entity", class="NaxCrmBundle:TokenGroup")
     */
    public function deleteTokenGroupAction(TokenGroup $entity = null)
    {
        if (empty($entity)) {
            return $this->getFailedJsonResponse(null, "TokenGroup was not found", 404);
        }

        $this->removeEntity($entity);

        return $this->getJsonResponse(null, 'TokenGroup was deleted');
    }

}
