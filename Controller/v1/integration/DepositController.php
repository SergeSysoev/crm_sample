<?php

namespace NaxCrmBundle\Controller\v1\integration;

use NaxCrmBundle\Entity\Client;
use NaxCrmBundle\Entity\Deposit;
use NaxCrmBundle\Entity\PaymentSystem;
use NaxCrmBundle\Modules\Deposits\DepositService;
use NaxCrmBundle\Modules\Email\Triggers\Client\Deposit as DepositTrigger;
use NaxCrmBundle\Modules\Email\Triggers\Client\DepositFailed;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations\Post;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use NaxCrmBundle\Controller\v1\BaseController;

class DepositController extends BaseController
{

    /**
     * @Post("/client/{clientId}/deposit/{ext_id}", name="v1_integrate_deposit")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "integration",
     *    resource = "integration/deposit",
     *    authentication = true,
     *    description = "Check and update or create deposit",
     *    statusCodes={
     *        200="Returned when successful",
     *        400={
     *          "Returned when missing required parameter",
     *        },
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *          "Returned when the user have not proper acl rights",
     *        },
     *        404={
     *          "Returned when client was not found",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     *    parameters = {
     *        {"name"="accountId",      "dataType"="integer",    "required"=true,  "format"="\d+",   "description"="set deposit accountId"},
     *        {"name"="amount",         "dataType"="integer",    "required"=true,  "format"="\d+",   "description"="set deposit amount"},
     *        {"name"="card",           "dataType"="string",     "required"=false, "format"=".+",    "description"="set deposit card number"},
     *        {"name"="payment_system", "dataType"="string",     "required"=true,  "format"=".+",    "description"="set deposit payment_system"},
     *        {"name"="payment_method", "dataType"="string",     "required"=false, "format"=".+",    "description"="set deposit payment_method"},
     *    },
     *    requirements = {
     *         {"name"="clientId", "dataType"="integer", "description"="select client by id", "requirement"="\d+"},
     *         {"name"="ext_id",   "dataType"="integer", "description"="select external transaction by id", "requirement"="\d+"},
     *    },
     * )
     * @ParamConverter("client", class="NaxCrmBundle:Client", options={"id" = "clientId"})
     * @Security("has_role('ROLE_MANAGER') and is_granted('C', 'deposit')")
     */
    public function integrationDepositAction(Request $request, Client $client = null, $ext_id = 0)
    {
        return $this->getJsonResponse(null, 'Ignore');
    }
}
