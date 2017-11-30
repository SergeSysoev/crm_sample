<?php

namespace NaxCrmBundle\Controller\v1\integration;

use NaxCrmBundle\Entity\Account;
use NaxCrmBundle\Entity\Client;
use NaxCrmBundle\Entity\UserLogItem;
use NaxCrmBundle\Entity\Withdrawal;
use NaxCrmBundle\Modules\Email\Triggers\Client\WithdrawalApproved;
use NaxCrmBundle\Modules\Email\Triggers\Client\WithdrawalDeclined;
use NaxCrmBundle\Modules\Email\Triggers\Client\WithdrawalNew;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use NaxCrmBundle\Controller\v1\BaseController;

class WithdrawalController extends BaseController
{
    /**
     * @Post("/client/{clientId}/withdrawal/{ext_id}", name="v1_integrate_withdrawal")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "integration",
     *    resource = "integration/withdrawal",
     *    authentication = true,
     *    description = "Check and update or create withdrawal",
     *    statusCodes={
     *        200="Returned when successful",
     *        400={
     *          "Returned when missing required parameter",
     *          "Returned when amount must be greater then zero",
     *          "Returned when withdrawal already in this status",
     *          "Returned when invalid withdrawal status",
     *          "Returned when when status is declined, you must provide comment",
     *        },
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *          "Returned when the user have not proper acl rights",
     *          "Returned when account doesn`t belong to that client",
     *        },
     *        404={
     *          "Returned when client was not found",
     *          "Returned when account not found",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     *    parameters = {
     *        {"name"="amount",    "dataType"="integer",    "required"=true,  "format"="\d+", "description" = "set withdrawal amount"},
     *        {"name"="accountId", "dataType"="integer",    "required"=true,  "format"="\d+", "description" = "set withdrawal accountId"},
     *        {"name"="status",    "dataType"="integer",    "required"=true,  "format"="\d+", "description" = "set withdrawal status"},
     *        {"name"="comment",   "dataType"="string",     "required"=false, "format"=".+",  "description" = "set withdrawal comment. Required if status=3 (declined)"},
     *    },
     *    requirements = {
     *         {"name"="clientId", "dataType"="integer", "description"="select client by id", "requirement"="\d+"},
     *         {"name"="ext_id",   "dataType"="integer", "description"="select external transaction by id", "requirement"="\d+"},
     *    },
     * )
     * @ParamConverter("client", class="NaxCrmBundle:Client", options={"id" = "clientId"})
     * @Security("has_role('ROLE_MANAGER') and is_granted('C', 'deposit')")
     */
    public function integrationWithdrawalAction(Request $request, Client $client = null, $ext_id = 0)
    {
        return $this->getJsonResponse(null, 'Ignore');
    }
}
