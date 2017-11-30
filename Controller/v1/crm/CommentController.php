<?php

namespace NaxCrmBundle\Controller\v1\crm;

use NaxCrmBundle\Entity\Client;
use NaxCrmBundle\Entity\Comment;
use NaxCrmBundle\Entity\Lead;
use NaxCrmBundle\Entity\UserLogItem;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Put;
use FOS\RestBundle\Controller\Annotations\Delete;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use NaxCrmBundle\Controller\v1\BaseController;

class CommentController extends BaseController
{
    /**
     * @Get("/leads/{id}/comments", name="v1_get_lead_comments")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "crm",
     *    resource = "crm/comment",
     *    authentication = true,
     *    description = "Get lead comments",
     *    statusCodes={
     *        200="Returned when successful",
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *          "Returned when manager has no access to this lead",
     *        },
     *        404={
     *          "Returned when lead was not found",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     *    parameters = {
     *        {"name"="filters",   "dataType"="collection", "required"=false, "format"="filters[columnName]=value",                "description" = "set array of filters. You can get list of filters in response"},
     *        {"name"="order",     "dataType"="collection", "required"=false, "format"="order[column]=columnName&order[dir]=DESC", "description" = "add order column and direction for output."},
     *        {"name"="limit",     "dataType"="integer",    "required"=false, "format"="\d+",                                      "description" = "add limit for output."},
     *        {"name"="offset",    "dataType"="integer",    "required"=false, "format"="\d+",                                      "description" = "add offset shifting for output."},
     *    },
     *    requirements = {
     *         {"name"="id", "dataType"="integer", "description"="select lead by id", "requirement"="\d+"},
     *    },
     * )
     * @ParamConverter("lead", class="NaxCrmBundle:Lead")
     */
    public function getLeadCommentsAction(Request $request, Lead $lead = null)
    {
        if (empty($lead)) {
            return $this->getFailedJsonResponse(null, 'Lead was not found', 404);
        }
        if (!$this->getUser()->hasAccessToLead($lead)) {
            return $this->getFailedJsonResponse(null, 'Manager has no access to this lead', 403);
        }
        $params = $request->query->all();
        $preFilters = [
            'leadId' => [$lead->getId()]
        ];
        $res = $this->getList($params, $preFilters);
        return $this->getJsonResponse($res);
    }

    /**
     * @Post("/leads/{id}/comments", name="v1_create_lead_comment")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "crm",
     *    resource = "crm/comment",
     *    authentication = true,
     *    description = "Add lead comment",
     *    statusCodes={
     *        200="Returned when successful",
     *        400={
     *          "Returned when missing required parameter",
     *        },
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *        },
     *        404={
     *          "Returned when lead was not found",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     *    parameters = {
     *        {"name"="text",    "dataType"="string", "required"=true,  "format"=".+", "description" = "add comment text"},
     *    },
     *    requirements = {
     *         {"name"="id", "dataType"="integer", "description"="select lead by id", "requirement"="\d+"},
     *    },
     * )
     * @ParamConverter("lead", class="NaxCrmBundle:Lead")
     */
    public function createLeadCommentAction(Request $request, Lead $lead = null)
    {
        if (empty($lead)) {
            return $this->getFailedJsonResponse(null, 'Lead was not found', 404);
        }
        // if (!$this->getUser()->hasAccessToLead($lead)) {
        //     return $this->getJsonResponse(null, "Manager has no access to this lead", 403);
        // }
        $params = $request->request->all();
        $fields = [
            'text' => ['req'],
            'manager' => ['def' => $this->getUser()],
            'lead' => ['def' => $lead],
        ];
        $bean = Comment::createInst();

        $this->saveEntity($bean, $fields, $params);

        $data = $bean->export();
        $this->addEvent($lead, 'comment was added', $bean->getText());
        $this->createUserLog(UserLogItem::ACTION_TYPE_CREATE, UserLogItem::OBJ_TYPE_COMMENTS, $data);
        return $this->getJsonResponse($data);
    }

    /**
     * @Delete("/leads/{leadId}/comments/{commentId}", name="v1_remove_lead_comment")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "crm",
     *    resource = "crm/comment",
     *    authentication = true,
     *    description = "Delete lead comment",
     *    statusCodes={
     *        200="Returned when successful",
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *          "Returned when comment does not belong to given lead",
     *          "Returned when the user is not authorized with JWT token",
     *          "Returned when manager has no access to this lead",
     *        },
     *        404={
     *          "Returned when lead was not found",
     *          "Returned when comment was not found",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     *    parameters = {
     *        {"name"="text",    "dataType"="string", "required"=true,  "format"=".+", "description" = "add comment text"},
     *    },
     *    requirements = {
     *         {"name"="leadId",    "dataType"="integer", "description"="select lead by id",    "requirement"="\d+"},
     *         {"name"="commentId", "dataType"="integer", "description"="select comment by id", "requirement"="\d+"},
     *    },
     * )
     * @ParamConverter("lead", class="NaxCrmBundle:Lead", options={"id" = "leadId"})
     * @ParamConverter("bean", class="NaxCrmBundle:Comment", options={"id" = "commentId"})
     */
    public function removeLeadCommentAction(Lead $lead = null, Comment $bean = null)
    {
        if (empty($lead)) {
            return $this->getFailedJsonResponse(null, 'Lead was not found', 404);
        }
        if (empty($bean)) {
            return $this->getFailedJsonResponse(null, 'Comment was not found', 404);
        }

        if ($bean->getLead() != $lead) {
            return $this->getFailedJsonResponse(null, 'Comment does not belong to given lead', 403);
        }
        if (!$this->getUser()->hasAccessToLead($lead)) {
            return $this->getFailedJsonResponse(null, 'Manager has no access to this lead', 403);
        }
        $data = $bean->export();
        $this->removeEntity($bean);

        $this->createUserLog(UserLogItem::ACTION_TYPE_DELETE, UserLogItem::OBJ_TYPE_COMMENTS, $data);
        return $this->getJsonResponse(null, 'Comment was removed');
    }

    /**
     * @Put("/leads/{leadId}/comments/{commentId}", name="v1_update_lead_comment")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "crm",
     *    resource = "crm/comment",
     *    authentication = true,
     *    description = "Update lead comment",
     *    statusCodes={
     *        200="Returned when successful",
     *        400={
     *          "Returned when missing required parameter",
     *          "Returned on validation error",
     *        },
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *          "Returned when comment does not belong to given lead",
     *          "Returned when the user is not authorized with JWT token",
     *          "Returned when manager has no access to this lead",
     *        },
     *        404={
     *          "Returned when lead was not found",
     *          "Returned when comment was not found",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     *    parameters = {
     *        {"name"="text",    "dataType"="string", "required"=true,  "format"=".+", "description" = "add comment text"},
     *    },
     *    requirements = {
     *         {"name"="leadId", "dataType"="integer", "description"="select lead by id", "requirement"="\d+"},
     *         {"name"="commentId", "dataType"="integer", "description"="select comment by id", "requirement"="\d+"},
     *    },
     * )
     * @ParamConverter("lead", class="NaxCrmBundle:Lead", options={"id" = "leadId"})
     * @ParamConverter("bean", class="NaxCrmBundle:Comment", options={"id" = "commentId"})
     */
    public function updateLeadCommentAction(Request $request, Lead $lead = null, Comment $bean = null)
    {
        if (empty($lead)) {
            return $this->getFailedJsonResponse(null, 'Lead was not found', 404);
        }
        if (empty($bean)) {
            return $this->getFailedJsonResponse(null, 'Comment was not found', 404);
        }
        if ($bean->getLead() != $lead) {
            return $this->getFailedJsonResponse(null, 'Comment does not belong to given lead', 403);
        }
        if (!$this->getUser()->hasAccessToLead($lead)) {
            return $this->getFailedJsonResponse(null, 'Manager has no access to this lead', 403);
        }

        $params = $request->request->all();
        $fields = [
            'text' => ['req'],
        ];
        $old_data = $bean->export();

        $this->saveEntity($bean, $fields, $params);

        $data = $bean->export();
        $diff = $this->arrayDiff($data, $old_data);
        if(!empty($diff)){
            $diff['id'] = $bean->getId();
            $diff['lead_id'] = $lead->getId();
            $this->createUserLog(UserLogItem::ACTION_TYPE_UPDATE, UserLogItem::OBJ_TYPE_COMMENTS, $diff);
        }
        return $this->getJsonResponse($data);
    }

    //Clients comments
    /**
     * @Get("/clients/{id}/comments", name="v1_get_client_comments")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "crm",
     *    resource = "crm/comment",
     *    authentication = true,
     *    description = "Get client comments",
     *    statusCodes={
     *        200="Returned when successful",
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *          "Returned when manager has no access to this client",
     *        },
     *        404={
     *          "Returned when client was not found",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     *    parameters = {
     *        {"name"="filters",   "dataType"="collection", "required"=false, "format"="filters[columnName]=value",                "description" = "set array of filters. You can get list of filters in response"},
     *        {"name"="order",     "dataType"="collection", "required"=false, "format"="order[column]=columnName&order[dir]=DESC", "description" = "add order column and direction for output."},
     *        {"name"="limit",     "dataType"="integer",    "required"=false, "format"="\d+",                                      "description" = "add limit for output."},
     *        {"name"="offset",    "dataType"="integer",    "required"=false, "format"="\d+",                                      "description" = "add offset shifting for output."},
     *    },
     *    requirements = {
     *         {"name"="id", "dataType"="integer", "description"="select client by id", "requirement"="\d+"},
     *    },
     * )
     * @ParamConverter("client", class="NaxCrmBundle:Client")
     */
    public function getClientCommentsAction(Request $request, Client $client = null)
    {
        if (empty($client)) {
            return $this->getFailedJsonResponse(null, 'Client was not found', 404);
        }
        if (!$this->getUser()->hasAccessToClient($client)) {
            return $this->getFailedJsonResponse(null, 'Manager has no access to this client', 403);
        }
        $params = $request->query->all();
        $preFilters = [
            'clientId' => [$client->getId()]
        ];
        $res = $this->getList($params, $preFilters);
        return $this->getJsonResponse($res);
    }

    /**
     * @Post("/clients/{id}/comments", name="v1_create_client_comment")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "crm",
     *    resource = "crm/comment",
     *    authentication = true,
     *    description = "Add client comment",
     *    statusCodes={
     *        200="Returned when successful",
     *        400={
     *          "Returned when missing required parameter",
     *        },
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *        },
     *        404={
     *          "Returned when client was not found",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     *    parameters = {
     *        {"name"="text",    "dataType"="string", "required"=true,  "format"=".+", "description" = "add comment text"},
     *    },
     *    requirements = {
     *         {"name"="id", "dataType"="integer", "description"="select client by id", "requirement"="\d+"},
     *    },
     * )
     * @ParamConverter("client", class="NaxCrmBundle:Client")
     */
    public function createClientCommentAction(Request $request, $client = null)
    {
        if (empty($client)) {
            return $this->getFailedJsonResponse(null, 'Client was not found', 404);
        }
        // if (!$this->getUser()->hasAccessToClient($client)) {
        //     return $this->getJsonResponse(null, "Manager has no access to this client", 403);
        // }
        $params = $request->request->all();
        $fields = [
            'text' => ['req'],
            'manager' => ['def' => $this->getUser()],
            'client' => ['def' => $client],
        ];
        $bean = Comment::createInst();

        $this->saveEntity($bean, $fields, $params);

        $data = $bean->export();
        $this->addEvent($client, 'comment was added', $bean->getText());
        $this->createUserLog(UserLogItem::ACTION_TYPE_CREATE, UserLogItem::OBJ_TYPE_COMMENTS, $data);
        return $this->getJsonResponse($data);
    }

    /**
     * @Delete("/clients/{clientId}/comments/{commentId}", name="v1_remove_client_comment")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "crm",
     *    resource = "crm/comment",
     *    authentication = true,
     *    description = "Delete client comment",
     *    statusCodes={
     *        200="Returned when successful",
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *          "Returned when comment does not belong to given client",
     *          "Returned when manager has no access to this client",
     *        },
     *        404={
     *          "Returned when client was not found",
     *          "Returned when comment was not found",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     *    parameters = {
     *        {"name"="text",    "dataType"="string", "required"=true,  "format"=".+", "description" = "add comment text"},
     *    },
     *    requirements = {
     *         {"name"="clientId",    "dataType"="integer", "description"="select client by id",    "requirement"="\d+"},
     *         {"name"="commentId", "dataType"="integer", "description"="select comment by id", "requirement"="\d+"},
     *    },
     * )
     * @ParamConverter("client", class="NaxCrmBundle:Client", options={"id" = "clientId"})
     * @ParamConverter("bean", class="NaxCrmBundle:Comment", options={"id" = "commentId"})
     */
    public function removeClientCommentAction(Client $client = null, Comment $bean = null)
    {
        if (empty($client)) {
            return $this->getFailedJsonResponse(null, 'Client was not found', 404);
        }
        if (empty($bean)) {
            return $this->getFailedJsonResponse(null, 'Comment was not found', 404);
        }

        if ($bean->getClient() != $client) {
            return $this->getFailedJsonResponse(null, 'Comment does not belong to given client', 403);
        }
        if (!$this->getUser()->hasAccessToClient($client)) {
            return $this->getFailedJsonResponse(null, 'Manager has no access to this client', 403);
        }
        $data = $bean->export();
        $this->removeEntity($bean);

        $this->createUserLog(UserLogItem::ACTION_TYPE_DELETE, UserLogItem::OBJ_TYPE_COMMENTS, $data);
        return $this->getJsonResponse(null, 'Comment was removed');
    }

    /**
     * @Put("/clients/{clientId}/comments/{commentId}", name="v1_update_client_comment")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "crm",
     *    resource = "crm/comment",
     *    authentication = true,
     *    description = "Update client comment",
     *    statusCodes={
     *        200="Returned when successful",
     *        400={
     *          "Returned when missing required parameter",
     *          "Returned on validation error",
     *        },
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *          "Returned when comment does not belong to given client",
     *          "Returned when manager has no access to this client",
     *        },
     *        404={
     *          "Returned when client was not found",
     *          "Returned when comment was not found",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     *    parameters = {
     *        {"name"="text",    "dataType"="string", "required"=true,  "format"=".+", "description" = "add comment text"},
     *    },
     *    requirements = {
     *         {"name"="clientId",  "dataType"="integer", "description"="select client by id", "requirement"="\d+"},
     *         {"name"="commentId", "dataType"="integer", "description"="select comment by id", "requirement"="\d+"},
     *    },
     * )
     * @ParamConverter("client", class="NaxCrmBundle:Client", options={"id" = "clientId"})
     * @ParamConverter("bean", class="NaxCrmBundle:Comment", options={"id" = "commentId"})
     */
    public function updateClientCommentAction(Request $request, Client $client = null, Comment $bean = null)
    {
        if (empty($client)) {
            return $this->getFailedJsonResponse(null, 'Client was not found', 404);
        }
        if (empty($bean)) {
            return $this->getFailedJsonResponse(null, 'Comment was not found', 404);
        }
        if ($bean->getClient() != $client) {
            return $this->getFailedJsonResponse(null, 'Comment does not belong to given client', 403);
        }
        if (!$this->getUser()->hasAccessToClient($client)) {
            return $this->getFailedJsonResponse(null, 'Manager has no access to this client', 403);
        }

        $params = $request->request->all();
        $fields = [
            'text' => ['req'],
        ];
        $old_data = $bean->export();

        $this->saveEntity($bean, $fields, $params);

        $data = $bean->export();
        $diff = $this->arrayDiff($data, $old_data);
        if(!empty($diff)){
            $diff['id'] = $bean->getId();
            $diff['client_id'] = $client->getId();
            $this->createUserLog(UserLogItem::ACTION_TYPE_UPDATE, UserLogItem::OBJ_TYPE_COMMENTS, $diff);
        }
        return $this->getJsonResponse($data);
    }
}
