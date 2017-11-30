<?php

namespace NaxCrmBundle\Controller\v1\admin;

use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations\Post;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use NaxCrmBundle\Controller\v1\BaseController;

class AuthController extends BaseController
{
    /**
     * @Post("/managers/login", name="v1_manager_login")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "admin",
     *    resource = "admin/auth",
     *    authentication = false,
     *    deprecated = true,
     *    description = "Do login as manager and get token",
     *    statusCodes={
     *        200="Returned when successful",
     *        400="Returned when missing required parameter",
     *        401="Returned when Invalid password",
     *        404="Returned when manager with email does not exist",
     *        500="Returned when Something went wrong",
     *    },
     *    parameters = {
     *        {"name"="email",    "dataType"="string", "required"=true, "format"="email format", "description" = "set email as login"},
     *        {"name"="password", "dataType"="string", "required"=true, "format"=".+",           "description" = "set password"},
     *    },
     * )
     */
    public function loginAction(Request $request) //FIXME: moved to open routes. Remove here and firewall when succeed
    {
        return $this->forward('NaxCrmBundle\Controller\v1\open\AuthController::loginAction', $request->query->all());
    }
}
