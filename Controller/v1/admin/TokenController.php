<?php

namespace NaxCrmBundle\Controller\v1\admin;

use NaxCrmBundle\Entity\Token;
use NaxCrmBundle\Entity\TokenGroup;
use NaxCrmBundle\Repository\TokenRepository;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Put;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Delete;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use NaxCrmBundle\Controller\v1\BaseController;

class TokenController extends BaseController
{

    /**
     * @Get("/tokens")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "admin",
     *    resource = "admin/token",
     *    authentication = true,
     *    description = "Get all available languages tokens",
     *    statusCodes={
     *        200="Returned when successful",
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     * )
     */
    public function getTokensAction(Request $request)
    {
        /* @var $tokenRepo TokenRepository */
        $tokenRepo = $this->getRepository(Token::class());
        return $this->getJsonResponse($tokenRepo->findBy($request->query->all()));
    }

    /**
     * @Post("/tokens")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "admin",
     *    resource = "admin/token",
     *    authentication = true,
     *    description = "Add language token",
     *    statusCodes={
     *        200="Returned when successful",
     *        400={
     *          "Returned when missing required parameter",
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
     *        {"name"="name",    "dataType"="string",  "required"=true, "format"=".+",  "description" = "set token name"},
     *        {"name"="groupId", "dataType"="integer", "required"=true, "format"="\d+", "description" = "set token groupId"},
     *    },
     * )
     */
    public function createTokenAction(Request $request)
    {
        $groupId = $this->getRequiredParam('groupId');
        if (!$group = $this->getRepository(TokenGroup::class())->find($groupId)) {
            return $this->getFailedJsonResponse(null, "TokenGroup not found", 404);
        }

        $entity = Token::createInst();
        $fields = [
            'name' => ['req'],
            'group' => ['def' => $group],
        ];
        $this->saveEntity($entity, $fields, $request->request->all());

        return $this->getJsonResponse($entity->export(), 'Token created');
    }

    /**
     * @Put("/tokens/{id}")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "admin",
     *    resource = "admin/token",
     *    authentication = true,
     *    description = "Update language token",
     *    statusCodes={
     *        200="Returned when successful",
     *        400={
     *          "Returned when validation error",
     *        },
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *        },
     *        404={
     *          "Returned when token was not found",
     *          "Returned when tokenGroup was not found",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     *    parameters = {
     *        {"name"="name",    "dataType"="string",  "required"=false, "format"=".+",  "description" = "set token name"},
     *        {"name"="groupId", "dataType"="integer", "required"=false, "format"="\d+", "description" = "set token groupId"},
     *    },
     *    requirements = {
     *         {"name"="id", "dataType"="integer", "description"="select token by id", "requirement"="\d+"},
     *    },
     * )
     * @ParamConverter("entity", class="NaxCrmBundle:Token")
     */
    public function update(Token $entity = null, Request $request)
    {
        if (empty($entity)) {
            return $this->getFailedJsonResponse(null, "Token was not found", 404);
        }

        $post = $request->request;

        if ($groupId = $post->get('groupId')) {
            if (!$group = $this->getRepository(TokenGroup::class())->find($groupId)) {
                return $this->getFailedJsonResponse(null, "TokenGroup was not found", 404);
            }
            $entity->setGroup($group);
        }

        $this->saveEntity($entity, ['name'], $post->all());

        return $this->getJsonResponse($entity->export(), 'Token updated', 202);
    }

    /**
     * @Delete("/tokens/{id}")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "admin",
     *    resource = "admin/token",
     *    authentication = true,
     *    description = "Delete language token",
     *    statusCodes={
     *        200="Returned when successful",
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *        },
     *        404={
     *          "Returned when token was not found",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     *    requirements = {
     *         {"name"="id", "dataType"="integer", "description"="select token by id", "requirement"="\d+"},
     *    },
     * )
     * @ParamConverter("entity", class="NaxCrmBundle:Token")
     */
    public function deleteTokenAction(Token $entity = null)
    {
        if (empty($entity)) {
            return $this->getFailedJsonResponse(null, "Token was not found", 404);
        }

        $this->removeEntity($entity);

        return $this->getJsonResponse(null, 'Token was deleted');
    }

}
