<?php

namespace NaxCrmBundle\Controller\v1\crm;

use NaxCrmBundle\Entity\SaleStatus;
use NaxCrmBundle\Entity\UserLogItem;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Put;
use FOS\RestBundle\Controller\Annotations\Delete;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use NaxCrmBundle\Controller\v1\BaseController;

class SaleStatusesController extends BaseController
{
    /**
     * @Get("/sale-statuses", name="v1_get_sale_statuses")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "crm",
     *    resource = "crm/saleStatus",
     *    authentication = true,
     *    description = "Get all sale statuses",
     *    statusCodes={
     *        200="Returned when successful",
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     * )
     */
    public function getSaleStatusesAction()
    {
        $saleStatuses = $this->getRepository(SaleStatus::class())->findBy([], ['id' => 'ASC']);
        $count = count($saleStatuses);
        return $this->getJsonResponse([
            'total'     => $count,
            'filtered'  => $count,
            'rows'      => $saleStatuses
        ]);
    }

    /**
     * @Get("/sale-statuses/{id}", name="v1_get_sale_status")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "crm",
     *    resource = "crm/saleStatus",
     *    authentication = true,
     *    description = "Get selected sale status",
     *    statusCodes={
     *        200="Returned when successful",
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *        },
     *        404={
     *          "Returned when sale status was not found",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     *    requirements = {
     *         {"name"="id", "dataType"="integer", "description"="select sale status by id", "requirement"="\d+"},
     *    },
     * )
     * @ParamConverter("saleStatus", class="NaxCrmBundle:SaleStatus")
     */
    public function getSaleStatusAction(SaleStatus $saleStatus = null)
    {
        if (empty($saleStatus)) {
            return $this->getFailedJsonResponse(null, 'Sale status was not found', 404);
        }
        return $this->getJsonResponse($saleStatus);
    }

    /**
     * @Post("/sale-statuses", name="v1_create_sale_status")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "crm",
     *    resource = "crm/saleStatus",
     *    authentication = true,
     *    description = "Add sale status",
     *    statusCodes={
     *        200="Returned when successful",
     *        400={
     *          "Returned when missing required parameter",
     *          "Returned when validation error",
     *        },
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     *    parameters = {
     *        {"name"="statusName", "dataType"="string", "required"=true, "format"=".+", "description" = "set new sale status name"},
     *    },
     * )
     */
    public function createSaleStatusAction()
    {
        $statusName = $this->getRequiredParam('statusName');
        $saleStatus = SaleStatus::createInst();
        $saleStatus->setStatusName($statusName);
        $saleStatus->setManager($this->getUser());

        $this->saveEntity($saleStatus);
        $data = $saleStatus->export();

        $this->createUserLog(UserLogItem::ACTION_TYPE_CREATE, UserLogItem::OBJ_TYPE_SALE_STATUSES, $data);
        return $this->getJsonResponse($saleStatus);
    }

    /**
     * @Put("/sale-statuses/{id}", name="v1_update_sale_status")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "crm",
     *    resource = "crm/saleStatus",
     *    authentication = true,
     *    description = "Update sale status",
     *    statusCodes={
     *        200="Returned when successful",
     *        400={
     *          "Returned when missing required parameter",
     *          "Returned when validation error",
     *        },
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *        },
     *        404={
     *          "Returned when sale status was not found",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     *    parameters = {
     *        {"name"="statusName", "dataType"="string", "required"=true, "format"=".+", "description" = "set new sale status name"},
     *    },
     *    requirements = {
     *         {"name"="id", "dataType"="integer", "description"="select sale status by id", "requirement"="\d+"},
     *    },
     * )
     * @ParamConverter("saleStatus", class="NaxCrmBundle:SaleStatus")
     */
    public function updateSaleStatusAction(SaleStatus $saleStatus = null)
    {
        if (empty($saleStatus)) {
            return $this->getFailedJsonResponse(null, 'Sale status was not found', 404);
        }
        $statusName = $this->getRequiredParam('statusName');
        $saleStatus->setStatusName($statusName);

        $this->saveEntity($saleStatus);
        $data = $saleStatus->export();

        $this->createUserLog(UserLogItem::ACTION_TYPE_UPDATE, UserLogItem::OBJ_TYPE_SALE_STATUSES, $data);
        return $this->getJsonResponse($saleStatus);
    }

    /**
     * @Delete("/sale-statuses/{id}", name="v1_remove_sale_status")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "crm",
     *    resource = "crm/saleStatus",
     *    authentication = true,
     *    description = "Delete sale status",
     *    statusCodes={
     *        200="Returned when successful",
     *        400={
     *          "Returned when missing required parameter",
     *          "Returned when there are clients in that sale status with ids",
     *          "Returned when there are leads in that sale status with ids",
     *        },
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *        },
     *        404={
     *          "Returned when sale status was not found",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     *    requirements = {
     *         {"name"="id", "dataType"="integer", "description"="select sale status by id", "requirement"="\d+"},
     *    },
     * )
     * @ParamConverter("saleStatus", class="NaxCrmBundle:SaleStatus")
     */
    public function removeSaleStatusAction(SaleStatus $saleStatus = null)
    {
        if (empty($saleStatus)) {
            return $this->getFailedJsonResponse(null, 'Sale status was not found', 404);
        }
        $clients = $saleStatus->getClients();
        if (!$clients->isEmpty()) {
            return $this->getFailedJsonResponse($clients->map(function($entity) { return $entity->getId(); }),
                'There are clients with this sale status');
        }
        $leads = $saleStatus->getLeads();
        if (!$leads->isEmpty()) {
            return $this->getFailedJsonResponse($leads->map(function($entity) { return $entity->getId(); }),
                'There are leads with this sale status');
        }
        $data = $saleStatus->export();
        $this->removeEntity($saleStatus);

        $this->createUserLog(UserLogItem::ACTION_TYPE_DELETE, UserLogItem::OBJ_TYPE_SALE_STATUSES, $data);
        return $this->getJsonResponse(null, 'Sale status has been removed');
    }
}
