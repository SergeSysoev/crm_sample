<?php

namespace NaxCrmBundle\Controller\v1\banking;

use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use NaxCrmBundle\Controller\v1\BaseController;

class OpenController extends BaseController
{
    /**
     * @Post("/pl-callback", name="v1_pl_callback")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "banking",
     *    resource = "banking/open",
     *    authentication = false,
     *    deprecated = true,
     *    description = "Callback for PL",
     *    statusCodes={
     *        200="Returned when successful",
     *        400={
     *          "Returned when Content is empty",
     *          "Returned when Content is not a valid json",
     *          "Returned when Can`t get transaction data",
     *          "Returned when Can`t get reference_id",
     *        },
     *        404={
     *          "Returned when deposit was not found",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     *    parameters = {
     *        {"name"="", "dataType"="string", "required"=true, "format"=".+", "description" = "set JSON with information"},
     *    },
     * )
     */
    public function postCallbackPlAction(Request $request)//FIXME: moved to open routes. Remove here and firewall when succeed
    {
        return $this->forward('NaxCrmBundle\Controller\v1\open\PaymentController::postCallbackPlAction', $request->query->all());
    }

    /**
     * @Get("/payboutique-callback-decline", name="v1_payboutique_callback_decline")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "banking",
     *    resource = "banking/open",
     *    authentication = false,
     *    deprecated = true,
     *    description = "Callback for PayBoutique decline PS",
     *    statusCodes={
     *        200="Returned when successful",
     *        400={
     *          "Returned when missing required parameter",
     *        },
     *        404={
     *          "Returned when deposit was not found",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     *    parameters = {
     *        {"name"="merchant_order", "dataType"="integer", "required"=true, "format"=".+", "description" = "set depositId"},
     *    },
     * )
     */
    public function postCallbackPayboutiqueDeclineAction(Request $request)//FIXME: moved to open routes. Remove here and firewall when succeed
    {
        return $this->forward('NaxCrmBundle\Controller\v1\open\PaymentController::postCallbackPayboutiqueDeclineAction', $request->query->all());
    }


    /**
     * @Post("/payboutique-callback", name="v1_payboutique_callback")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "banking",
     *    resource = "banking/open",
     *    authentication = false,
     *    deprecated = true,
     *    description = "Callback for PayBoutique PS",
     *    statusCodes={
     *        200="Returned when successful",
     *        404={
     *          "Returned when deposit was not found",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     *    parameters = {
     *        {"name"="xml", "dataType"="string", "required"=true, "format"=".+", "description" = "set XML with information"},
     *    },
     * )
     */
    public function postCallbackPayboutiqueAction(Request $request) //FIXME: moved to open routes. Remove here and firewall when succeed
    {
        return $this->forward('NaxCrmBundle\Controller\v1\open\PaymentController::postCallbackPayboutiqueAction', $request->query->all());
    }

    /**
     * @Post("/orangepay-callback", name="v1_orangepay_callback")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "banking",
     *    resource = "banking/open",
     *    authentication = false,
     *    deprecated = true,
     *    description = "Callback for OrangePay PS",
     *    statusCodes={
     *        200="Returned when successful",
     *        400={
     *          "Returned when Content is empty",
     *          "Returned when Content is not a valid json",
     *          "Returned when Can`t get transaction data",
     *          "Returned when Can`t get reference_id",
     *          "Returned when Invalid reference_id",
     *        },
     *        404={
     *          "Returned when deposit was not found",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     *    parameters = {
     *        {"name"="", "dataType"="string", "required"=true, "format"=".+", "description" = "set JSON with information"},
     *    },
     * )
     */
    public function postCallbackOrangePayAction(Request $request)//FIXME: moved to open routes. Remove here and firewall when succeed
    {
        return $this->forward('NaxCrmBundle\Controller\v1\open\PaymentController::postCallbackOrangePayAction', $request->query->all());
    }

    /**
     * @Get("/netpay-callback", name="v1_netpay_callback")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "banking",
     *    resource = "banking/open",
     *    authentication = false,
     *    deprecated = true,
     *    description = "Callback for NetPay PS",
     *    statusCodes={
     *        200="Returned when successful",
     *        200="Returned when Deposit was already processed before",
     *        404={
     *          "Returned when deposit was not found",
     *        },
     *        500="Invalid data",
     *    },
     *    parameters = {
     *        {"name"="Reply",     "dataType"="integer", "required"=true, "format"="\d+",},
     *        {"name"="TransID",   "dataType"="integer", "required"=true, "format"="\d+",},
     *        {"name"="Order",     "dataType"="integer", "required"=true, "format"="\d+",},
     *        {"name"="Amount",    "dataType"="integer", "required"=true, "format"="\d+",},
     *        {"name"="Currency",  "dataType"="string",  "required"=true, "format"=".+", },
     *        {"name"="signature", "dataType"="string",  "required"=true, "format"=".+", },
     *    },
     * )
     */
    public function postCallbackNetpayAction(Request $request) //FIXME: moved to open routes. Remove here and firewall when succeed
    {
        return $this->forward('NaxCrmBundle\Controller\v1\open\PaymentController::postCallbackNetpayAction', $request->query->all());
    }

    /**
     * @Get("/payboutique-stub", name="v1_payboutique_stub")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "banking",
     *    resource = "banking/open",
     *    authentication = false,
     *    deprecated = true,
     *    description = "PayBoutique stub",
     *    statusCodes={
     *        200="Returned when successful",
     *        500="Returned when Something went wrong",
     *    },
     * )
     */
    public function getPayboutiqueStubAction(Request $request) //FIXME: moved to open routes. Remove here and firewall when succeed
    {
        return $this->forward('NaxCrmBundle\Controller\v1\open\PaymentController::getPayboutiqueStubAction', $request->query->all());
    }
}
