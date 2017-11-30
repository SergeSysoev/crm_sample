<?php

namespace NaxCrmBundle\Controller\v1\crm;

use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations\Post;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use NaxCrmBundle\Controller\v1\BaseController;

class AuthController extends BaseController
{
    /**
     * @Post("/clients", name="v1_client_add")
     * @Post("/clients/registration", name="v1_client_register")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "crm",
     *    resource = "crm/auth",
     *    authentication = false,
     *    deprecated = true,
     *    description = "Register new client",
     *    statusCodes={
     *        200="Returned when successful",
     *        400={
     *          "Returned when missing required parameter",
     *        },
     *        500="Returned when Something went wrong",
     *        503="Platform reject client",
     *    },
     *    parameters = {
     *        {"name"="email",           "dataType"="string",   "required"=true,  "format"="email",    "description" = "set client email"},
     *        {"name"="password",        "dataType"="string",   "required"=true,  "format"=".+",       "description" = "set client password"},
     *        {"name"="phone",           "dataType"="string",   "required"=true,  "format"=".+",       "description" = "set client  phone"},
     *        {"name"="firstname",       "dataType"="string",   "required"=true,  "format"=".+",       "description" = "set client Firstname"},
     *        {"name"="lastname",        "dataType"="string",   "required"=true,  "format"=".+",       "description" = "set client Lastname"},
     *        {"name"="countryId",       "dataType"="integer",  "required"=false, "format"="\d+",      "description" = "set countryId"},
     *        {"name"="countryCode",     "dataType"="string",   "required"=false, "format"=".+",       "description" = "set countryCode "},
     *        {"name"="birthday",        "dataType"="date",     "required"=true,  "format"="datetime", "description" = "set client birthday"},
     *        {"name"="langcode",        "dataType"="string",   "required"=false, "format"=".+",       "description" = "set langcode"},
     *        {"name"="is_demo",         "dataType"="integer",  "required"=false, "format"="\d+",      "description" = "set is demo client account"},
     *        {"name"="currency",        "dataType"="string",   "required"=false, "format"=".+",       "description" = "set client account currency"},
     *        {"name"="externalId",      "dataType"="integer",  "required"=true,  "format"="\d+",      "description" = "set client account externalId"},
     *    },
     * )
     */
    public function registerClientAction(Request $request) //FIXME: moved to open routes. Remove here and firewall when succeed
    {
        return $this->forward('NaxCrmBundle\Controller\v1\open\AuthController::registerClientAction', $request->query->all());
    }

    /**
     * @Post("/clients/login", name="v1_client_login")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "crm",
     *    resource = "crm/auth",
     *    authentication = false,
     *    deprecated = true,
     *    description = "Login client",
     *    statusCodes={
     *        200="Returned when successful",
     *        400={
     *          "Returned when missing required parameter",
     *        },
     *        401="Invalid password",
     *        404={
     *           "Client with email does not exist",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     *    parameters = {
     *        {"name"="email",    "dataType"="string", "required"=true, "format"="email format", "description" = "set email as login"},
     *        {"name"="password", "dataType"="string", "required"=true, "format"=".+",           "description" = "set password"},
     *    },
     * )
     */
    public function loginClientAction(Request $request) //FIXME: moved to open routes. Remove here and firewall when succeed
    {
        return $this->forward('NaxCrmBundle\Controller\v1\open\AuthController::loginClientAction', $request->query->all());
    }
}
