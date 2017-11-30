<?php

namespace NaxCrmBundle\Controller\v1\crm;

use NaxCrmBundle\Entity\Account;
use NaxCrmBundle\Entity\Client;
use NaxCrmBundle\Entity\Manager;
use NaxCrmBundle\Entity\UserLogItem;
use Firebase\JWT\JWT;
use NaxCrmBundle\Modules\PlatformHandler;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Put;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use NaxCrmBundle\Controller\v1\BaseController;

class OpenController extends BaseController
{
    /**
     * @Get("/clients/confirm-email", name="v1_confirm_email")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "crm",
     *    resource = "crm/open",
     *    authentication = false,
     *    deprecated = true,
     *    description = "Confirm client email",
     *    statusCodes={
     *        200="Returned when successful",
     *        400={
     *          "Returned when missing required parameter",
     *          "Client email doesn`t match",
     *        },
     *        404={
     *          "Returned when client was not found",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     *    parameters = {
     *        {"name"="token", "dataType"="string", "required"=true, "format"=".+", "description" = "get JWT token"},
     *    },
     * )
     */
    public function confirmEmailAction(Request $request) //FIXME: moved to open routes. Remove here and firewall when succeed
    {
        return $this->forward('WeberCrmBundle\Controller\v1\open\ClientController::confirmEmailAction', $request->query->all());
    }

    /**
     * @Get("/accounts/{id}/hash", name="v1_get_platform_hash")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "crm",
     *    resource = "crm/open",
     *    authentication = true,
     *    description = "Get account login hash",
     *    statusCodes={
     *        200="Returned when successful",
     *        500="Returned when Something went wrong",
     *    },
     *    requirements = {
     *         {"name"="id", "dataType"="integer", "description"="select account by id", "requirement"="\d+"},
     *    },
     * )
     * @ParamConverter("account", class="NaxCrmBundle:Account")
     */
    public function getPlatformHash(Account $account)
    {
        /** @var PlatformHandler $platform */
        $platform = $this->getPlatformHandler();
        return $this->getJsonResponse($platform->getLoginHash($account->getExternalId()));
    }

    /**
     * @Get("/clients/reset-password-link", name="v1_get_reset_password_link")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "crm",
     *    resource = "crm/open",
     *    authentication = false,
     *    deprecated = true,
     *    description = "Send to client reset password link",
     *    statusCodes={
     *        200="Returned when successful",
     *        400={
     *          "Returned when missing required parameter",
     *        },
     *        404={
     *          "Returned when client was not found",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     *    parameters = {
     *        {"name"="email", "dataType"="string", "required"=true, "format"="email format", "description" = "set email for reset password"},
     *    },
     * )
     */
    public function getResetLink(Request $request)  //FIXME: moved to open routes. Remove here and firewall when succeed
    {
        return $this->forward('NaxCrmBundle\Controller\v1\open\ClientController::getResetLink', $request->query->all());
    }
}
