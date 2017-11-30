<?php

namespace NaxCrmBundle\Controller\v1\admin;

use NaxCrmBundle\Entity\PaymentSystem;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Put;
use FOS\RestBundle\Controller\Annotations\Post;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use NaxCrmBundle\Controller\v1\BaseController;

class PaymentSystemController extends BaseController
{
    /**
     * @Get("/payment_systems", name="v1_get_payment_systems")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "admin",
     *    resource = "admin/paymentSystem",
     *    authentication = true,
     *    description = "Get all available payment systems",
     *    statusCodes={
     *        200="Returned when successful",
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *          "Returned when the user have not proper acl rights",
     *          "Returned when manager id != 1",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     * )
     * @Security("has_role('ROLE_MANAGER')")
     */
    public function getPaymentSystems(Request $request)
    {
        $user = $this->getUser();
        if ($user->getId() <> 1) {
            return $this->getFailedJsonResponse(null, 'No access', 403);
        }

        $repo = $this->getRepository(PaymentSystem::class());
        // $res =$repo->findBy($request->query->all());
        $res = $repo->findAll();
        return $this->getJsonResponse([
            'rows' => $res,
        ]);
    }

    /**
     * @Put("/payment_systems/{id}", name="v1_update_payment_system")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "admin",
     *    resource = "admin/paymentSystem",
     *    authentication = true,
     *    description = "Update payment system",
     *    statusCodes={
     *        200="Returned when successful",
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *          "Returned when the user have not proper acl rights",
     *          "Returned when manager id != 1",
     *        },
     *        404={
     *          "Returned when paymentSystem was not found",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     *    parameters = {
     *        {"name"="paymentSystemName", "dataType"="string",  "required"=false, "format"=".+",  "description" = "set human readable payment system name"},
     *        {"name"="status",            "dataType"="integer", "required"=false, "format"="\d+", "description" = "set payment system status"},
     *        {"name"="justCrm",           "dataType"="boolean", "required"=false, "format"="0|1", "description" = "set is payment system just for CRM"},
     *    },
     *    requirements = {
     *         {"name"="id", "dataType"="string", "description"="select paymentSystem by id", "requirement"=".+"},
     *    },
     * )

     * @ParamConverter("bean", class="NaxCrmBundle:PaymentSystem")
     * @Security("has_role('ROLE_MANAGER')")
     */
    public function update($bean = null, Request $request)
    {
        if (empty($bean)) {
            return $this->getFailedJsonResponse(null, 'PaymentSystem was not found', 404);
        }

        if ($this->getUser()->getId() <> 1) {
            return $this->getFailedJsonResponse(null, 'No access', 403);
        }
        $params = $request->request->all();
        $fields = [
            'paymentSystemName',
            'status',
            'justCrm',
        ];
        $this->saveEntity($bean, $fields, $params);

        return $this->getJsonResponse($bean->export(), 'PaymentSystem was updated');
    }


    /**
     * @Post("/payment_systems", name="v1_add_payment_systems")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "admin",
     *    resource = "admin/paymentSystem",
     *    authentication = true,
     *    description = "Create payment system",
     *    statusCodes={
     *        200="Returned when successful",
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *          "Returned when the user have not proper acl rights",
     *          "Returned when manager id != 1",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     *    parameters = {
     *        {"name"="id",                "dataType"="string",  "required"=true,  "format"=".+",  "description" = "set id payment system name"},
     *        {"name"="paymentSystemName", "dataType"="string",  "required"=false, "format"=".+",  "description" = "set human readable payment system name"},
     *        {"name"="status",            "dataType"="integer", "required"=false, "format"="0|1", "description" = "set payment system status"},
     *        {"name"="justCrm",           "dataType"="boolean", "required"=false, "format"="0|1", "description" = "set is payment system just for CRM"},
     *    },
     *    requirements = {
     *         {"name"="id", "dataType"="string", "description"="select paymentSystem by id", "requirement"=".+"},
     *    },
     * )
     */
    public function create(Request $request)
    {
        if ($this->getUser()->getId() <> 1) {
            return $this->getFailedJsonResponse(null, 'No access', 403);
        }
        $params = $request->request->all();
        $fields = [
            'id' => ['req'],
            'paymentSystemName',
            'status',
            'justCrm',
        ];
        $bean = PaymentSystem::createInst();
        $this->saveEntity($bean, $fields, $params);

        return $this->getJsonResponse($bean->export(), 'PaymentSystem was created');
    }


    // /**
    //  * @Delete("/payment_systems/{id}")
    //  * @ParamConverter("bean", class="NaxCrmBundle:Token")
    //  *
    //  * @param Token $bean
    //  * @return Response
    //  */
    // public function deleteTokenAction(Token $bean = null)
    // {
    //     if (empty($bean)) {
    //         return $this->getJsonResponse(null, "Token was not found", 404);
    //     }
    //     $em = $this->getEm();
    //     $em->remove($bean);
    //     $em->flush();
    //     return $this->getJsonResponse(null, 'Token was deleted');
    // }

}
