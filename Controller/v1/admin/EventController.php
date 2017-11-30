<?php

namespace NaxCrmBundle\Controller\v1\admin;

use NaxCrmBundle\Entity\Event;
use NaxCrmBundle\Entity\Client;
use NaxCrmBundle\Entity\Lead;
use NaxCrmBundle\Entity\BaseEntity;
use NaxCrmBundle\Entity\LeadUpload;
use NaxCrmBundle\Entity\Assignment;
use NaxCrmBundle\Entity\Manager;
use NaxCrmBundle\Entity\UserLogItem;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Put;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Delete;
use FOS\RestBundle\Controller\Annotations\RequestParam;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use NaxCrmBundle\Controller\v1\BaseController;

class EventController extends BaseController
{
    const TYPE_FOLLOWUP = 1;
    const TYPE_ASSIGNED = 2;
    const TYPE_UPLOADED = 3;

    protected $csvTitles = [
        'managerId',
        'title',
        'message',
        'created',
        'status',
    ];

    protected function setExportDefaults()
    {
        $this->csvAliases = [
            'managerId' => $this->getIdNamesArray(Manager::class()),
            'status' => Event::get('statuses')
        ];
    }

    /**
     * @Get("/events/own")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "admin",
     *    resource = "admin/event",
     *    authentication = true,
     *    description = "Get all events for current manager",
     *    statusCodes={
     *        200="Returned when successful",
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     * )
     */
    public function getOwnAction()
    {
        $repo = $this->getRepository(Event::class());
        $res = $repo->getOwn($this->getUser()->getId());
        return $this->getJsonResponse($res);
    }

    /**
     * @Get("/events")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "admin",
     *    resource = "admin/event",
     *    authentication = true,
     *    description = "Get all unread current manager events with type != follow_up",
     *    statusCodes={
     *        200="Returned when successful",
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     *    parameters = {
     *        {"name"="filters",   "dataType"="collection", "required"=false, "format"="filters[columnName]=value",                "description" = "set array of filters. You can get list of filters in response"},
     *        {"name"="order",     "dataType"="collection", "required"=false, "format"="order[column]=columnName&order[dir]=DESC", "description" = "add order column and direction for output."},
     *        {"name"="limit",     "dataType"="integer",    "required"=false, "format"="\d+",                                      "description" = "add limit for output."},
     *        {"name"="offset",    "dataType"="integer",    "required"=false, "format"="\d+",                                      "description" = "add offset shifting for output."},
     *        {"name"="exportCsv", "dataType"="string",     "required"=false, "format"="lzw encoded array",                        "description" = "used for export result in CSV file"},
     *    },
     * )
     */
    public function getEventsAction(Request $request)
    {
        $params = $request->query->all();
        $preFilters = [
            'managerIds' => [$this->getUser()->getId()],
            'statusIds' => [Event::STATUS_UNREAD],
            'system' => true,
        ];
        $res = $this->getList($params, $preFilters);
        return $this->getJsonResponse($res);
    }

    /**
     * @Get("/events/archive")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "admin",
     *    resource = "admin/event",
     *    authentication = true,
     *    description = "Get all read current manager events with type != follow_up",
     *    statusCodes={
     *        200="Returned when successful",
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     *    parameters = {
     *        {"name"="filters",   "dataType"="collection", "required"=false, "format"="filters[columnName]=value",                "description" = "set array of filters. You can get list of filters in response"},
     *        {"name"="order",     "dataType"="collection", "required"=false, "format"="order[column]=columnName&order[dir]=DESC", "description" = "add order column and direction for output."},
     *        {"name"="limit",     "dataType"="integer",    "required"=false, "format"="\d+",                                      "description" = "add limit for output."},
     *        {"name"="offset",    "dataType"="integer",    "required"=false, "format"="\d+",                                      "description" = "add offset shifting for output."},
     *        {"name"="exportCsv", "dataType"="string",     "required"=false, "format"="lzw encoded array",                        "description" = "used for export result in CSV file"},
     *    },
     * )
     */
    public function getArchiveEventsAction(Request $request)
    {
        $params = $request->query->all();
        $preFilters = [
            'managerIds' => [$this->getUser()->getId()],
            'statusIds' => [Event::STATUS_READ],
            'system' => true,
        ];
        $res = $this->getList($params, $preFilters);
        return $this->getJsonResponse($res);
    }

    /**
     * @Get("/notes/department", name="v1_get_notes_department")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "admin",
     *    resource = "admin/event",
     *    authentication = true,
     *    description = "Get all department notes",
     *    statusCodes={
     *        200="Returned when successful",
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     *    parameters = {
     *        {"name"="filters",   "dataType"="collection", "required"=false, "format"="filters[columnName]=value",                "description" = "set array of filters. You can get list of filters in response"},
     *        {"name"="order",     "dataType"="collection", "required"=false, "format"="order[column]=columnName&order[dir]=DESC", "description" = "add order column and direction for output."},
     *        {"name"="limit",     "dataType"="integer",    "required"=false, "format"="\d+",                                      "description" = "add limit for output."},
     *        {"name"="offset",    "dataType"="integer",    "required"=false, "format"="\d+",                                      "description" = "add offset shifting for output."},
     *        {"name"="exportCsv", "dataType"="string",     "required"=false, "format"="lzw encoded array",                        "description" = "used for export result in CSV file"},
     *    },
     * )
     */
    public function getDepartmentNotesAction(Request $request)
    {
        $params = $request->query->all();
        $preFilters = $this->setDepAccessFilters();
        $preFilters['follow_up'] = true;
        $this->exportFileName = 'Follow-ups';
        $res = $this->getList($params, $preFilters);
        return $this->getJsonResponse($res);
    }

    /**
     * @Get("/notes/own", name="v1_get_notes_own")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "admin",
     *    resource = "admin/event",
     *    authentication = true,
     *    description = "Get all current manger notes",
     *    statusCodes={
     *        200="Returned when successful",
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     *    parameters = {
     *        {"name"="filters",   "dataType"="collection", "required"=false, "format"="filters[columnName]=value",                "description" = "set array of filters. You can get list of filters in response"},
     *        {"name"="order",     "dataType"="collection", "required"=false, "format"="order[column]=columnName&order[dir]=DESC", "description" = "add order column and direction for output."},
     *        {"name"="limit",     "dataType"="integer",    "required"=false, "format"="\d+",                                      "description" = "add limit for output."},
     *        {"name"="offset",    "dataType"="integer",    "required"=false, "format"="\d+",                                      "description" = "add offset shifting for output."},
     *        {"name"="exportCsv", "dataType"="string",     "required"=false, "format"="lzw encoded array",                        "description" = "used for export result in CSV file"},
     *    },
     * )
     */
    public function getOwnNotesAction(Request $request)
    {
        $params = $request->query->all();
        $preFilters = [
            'managerIds' => [$this->getUser()->getId()],
            'follow_up' => true,
        ];
        $this->exportFileName = 'My notes';
        $res = $this->getList($params, $preFilters);
        return $this->getJsonResponse($res);
    }

    /**
     * @Get("/clients/{id}/notes")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "admin",
     *    resource = "admin/event",
     *    authentication = true,
     *    description = "Get selected client notes",
     *    statusCodes={
     *        200="Returned when successful",
     *        403={
     *          "Returned when the user is not authorized with JWT token",
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
     *        {"name"="exportCsv", "dataType"="string",     "required"=false, "format"="lzw encoded array",                        "description" = "used for export result in CSV file"},
     *    },
     *    requirements = {
     *         {"name"="id", "dataType"="integer", "description"="select client by id", "requirement"="\d+"},
     *    },
     * )
     * @ParamConverter("client", class="NaxCrmBundle:Client")
     */
    public function getClientNotesAction(Request $request, Client $client = null)
    {
        if (empty($client)) {
            return $this->getFailedJsonResponse(null, 'Client was not found', 404);
        }
        $params = $request->query->all();
        $preFilters = [
            'objectTypes' => [Event::OBJ_TYPE_CLIENT],
            'objectIds' => [$client->getId()],
            'follow_up' => true,
        ];

        if($this->getUser()->getId() != $client->getManagerId()){
            $preFilters['managerIds'] = [$this->getUser()->getId()];
        }
        $res = $this->getList($params, $preFilters);
        return $this->getJsonResponse($res);
    }

    /**
     * @Get("/leads/{id}/notes")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "admin",
     *    resource = "admin/event",
     *    authentication = true,
     *    description = "Get selected lead notes",
     *    statusCodes={
     *        200="Returned when successful",
     *        403={
     *          "Returned when the user is not authorized with JWT token",
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
     *        {"name"="exportCsv", "dataType"="string",     "required"=false, "format"="lzw encoded array",                        "description" = "used for export result in CSV file"},
     *    },
     *    requirements = {
     *         {"name"="id", "dataType"="integer", "description"="select lead by id", "requirement"="\d+"},
     *    },
     * )
     * @ParamConverter("lead", class="NaxCrmBundle:Lead")
     */
    public function getLeadNotesAction(Request $request, Lead $lead = null)
    {
        if (empty($lead)) {
            return $this->getFailedJsonResponse(null, 'Lead was not found', 404);
        }
        $params = $request->query->all();
        $preFilters = [
            'objectTypes' => [Event::OBJ_TYPE_LEAD],
            'objectIds' => [$lead->getId()],
            'follow_up' => true,
        ];
        if($this->getUser()->getId() != $lead->getManagerId()){
            $preFilters['managerIds'] = [$this->getUser()->getId()];
        }
        $res = $this->getList($params, $preFilters);
        return $this->getJsonResponse($res);
    }

    /**
     * @Get("/events/{id}")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "admin",
     *    resource = "admin/event",
     *    authentication = true,
     *    description = "Get selected event",
     *    statusCodes={
     *        200="Returned when successful",
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *        },
     *        404={
     *          "Returned when event was not found",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     *    requirements = {
     *         {"name"="id", "dataType"="integer", "description"="select event by id", "requirement"="\d+"},
     *    },
     * )
     * @ParamConverter("bean", class="NaxCrmBundle:Event")
     */
    public function getOne(Event $bean)
    {
        if (empty($bean)) {
            return $this->getFailedJsonResponse(null, 'Event was not found', 404);
        }
        $data = $bean->export();
        switch ($data['objectType']) {
            case Event::OBJ_TYPE_CLIENT:
                $repo = $this->getRepository(Client::class());
                break;
            case Event::OBJ_TYPE_LEAD:
                $repo = $this->getRepository(Lead::class());
                break;
            case Event::OBJ_TYPE_CLIENT_ASSIGNMENT: case Event::OBJ_TYPE_LEAD_ASSIGNMENT:
                $repo = $this->getRepository(Assignment::class());
                break;
            case Event::OBJ_TYPE_LEAD_UPLOAD:
                $repo = $this->getRepository(LeadUpload::class());
                break;
            default:
                return $this->getJsonResponse($data);
        }
        $object = $repo->find($data['objectId']);
        if ($object) {
            $data['object'] = $object->export();
        }
        return $this->getJsonResponse($data);
    }

    /**
     * @Post("/events")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "admin",
     *    resource = "admin/event",
     *    authentication = true,
     *    description = "Add event",
     *    statusCodes={
     *        200="Returned when successful",
     *        400={
     *          "Returned when missing required parameter",
     *        },
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     *    parameters = {
     *        {"name"="message",      "dataType"="string",   "required"=true,  "format"=".+",       "description" = "set event content"},
     *        {"name"="title",        "dataType"="string",   "required"=true,  "format"=".+",       "description" = "set event title"},
     *        {"name"="timeFollowup", "dataType"="datetime", "required"=true,  "format"="datetime", "description" = "set event datetime"},
     *        {"name"="objectId",     "dataType"="integer",  "required"=false, "format"="\d+",      "description" = "set event assigned objectId"},
     *        {"name"="objectType",   "dataType"="string",   "required"=false, "format"=".+",       "description" = "set event assigned objectType"},
     *    },
     * )
     * @RequestParam(name="message",      allowBlank=false)
     * @RequestParam(name="title",        allowBlank=false)
     * @RequestParam(name="timeFollowup", allowBlank=false)
     */
    public function create(Request $request)
    {
        $bean = Event::createInst();
        $params = $request->request->all();
        $fields = [
            'title' => ['req'],
            'message' => ['req'],
            'manager' => ['def' => $this->getUser()],
            'objectType' => ['def' => 'custom'],
            'timeFollowup' => ['req'],
            'objectId',
        ];

        $this->saveEntity($bean, $fields, $params);

        $data = $bean->export();
        $this->createUserLog(UserLogItem::ACTION_TYPE_CREATE, UserLogItem::OBJ_TYPE_FOLLOW_UPS, $data);

        if(in_array($bean->getObjectType(), ['Client', 'Lead'])){
            $object = $this->getReference(BaseEntity::class($this->getBrandName(), $bean->getObjectType()), $bean->getObjectId());
            $this->addEvent($object, 'follow-up was added', $bean->getMessage());
        }

        return $this->getJsonResponse($data);
    }

    /**
     * @Put("/events/{id}")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "admin",
     *    resource = "admin/event",
     *    authentication = true,
     *    description = "Update event",
     *    statusCodes={
     *        200="Returned when successful",
     *        400={
     *          "Returned when invalid event status",
     *        },
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *          "Returned when you cannot change event that was read",
     *          "Returned when you cannot change not your event",
     *        },
     *        404={
     *          "Returned when event was not found",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     *    parameters = {
     *        {"name"="message",      "dataType"="string",   "required"=false, "format"=".+",       "description" = "set event content"},
     *        {"name"="title",        "dataType"="string",   "required"=false, "format"=".+",       "description" = "set event title"},
     *        {"name"="timeFollowup", "dataType"="datetime", "required"=false, "format"="datetime", "description" = "set event datetime"},
     *        {"name"="status",       "dataType"="boolean",  "required"=false, "format"="0|1",      "description" = "set event status"},
     *    },
     *    requirements = {
     *         {"name"="id", "dataType"="integer", "description"="select event by id", "requirement"="\d+"},
     *    },
     * )

     * @ParamConverter("bean", class="NaxCrmBundle:Event")
     */
    public function updateAction($bean = null, Request $request)
    {
        if (empty($bean)) {
            return $this->getFailedJsonResponse(null, 'Event was not found', 404);
        }

        if ($bean->getType() == Event::TYPE_EVENT) {
            return $this->getFailedJsonResponse(null, 'You can change just follow-ups');
        }

        if ($bean->getStatus() == Event::STATUS_READ) {
            return $this->getFailedJsonResponse(null, 'You cannot change event that was read', 403);
        }

        if ($this->getUser()->getId() != $bean->getManagerId()) {
            if(in_array($bean->getObjectType(), ['Client', 'Lead'])){
                $object = $this->getReference(BaseEntity::class($this->getBrandName(), $bean->getObjectType()), $bean->getObjectId());
                if($this->getUser()->getId() != $object->getManagerId()){
                    return $this->getFailedJsonResponse(null, 'You cannot change not your event', 403);
                }
            }
            else{
                return $this->getFailedJsonResponse(null, 'You cannot change not your event', 403);
            }
        }

        $old_data = $bean->export();
        $params = $request->request->all();

        $fields = [
            'title',
            'message',
            'timeFollowup' => ['req'],
            'status',
        ];

        $this->saveEntity($bean, $fields, $params);

        $data = $bean->export();
        $this->logUpdates($data, $old_data, $bean, UserLogItem::OBJ_TYPE_FOLLOW_UPS);
        return $this->getJsonResponse($data);
    }

    /**
     * @Delete("/events/{id}")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "admin",
     *    resource = "admin/event",
     *    authentication = true,
     *    description = "Delete event",
     *    statusCodes={
     *        200="Returned when successful",
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *        },
     *        404={
     *          "Returned when event was not found",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     *    requirements = {
     *         {"name"="id", "dataType"="integer", "description"="select event by id", "requirement"="\d+"},
     *    },
     * )
     * @ParamConverter("bean", class="NaxCrmBundle:Event")
     */
    public function deleteAction($bean = null)
    {
        if (empty($bean)) {
            return $this->getFailedJsonResponse(null, 'Event was not found', 404);
        }

        if ($bean->getType() == Event::TYPE_EVENT) {
            return $this->getFailedJsonResponse(null, 'You can change just follow-ups');
        }

        $data = $bean->export();
        $this->removeEntity($bean);

        $this->createUserLog(UserLogItem::ACTION_TYPE_DELETE, UserLogItem::OBJ_TYPE_FOLLOW_UPS, $data);
        return $this->getJsonResponse(null, 'Event deleted');
    }

    /**
     * @Put("/events/{id}/read")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "admin",
     *    resource = "admin/event",
     *    authentication = true,
     *    description = "Set event read",
     *    statusCodes={
     *        200="Returned when successful",
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *        },
     *        404={
     *          "Returned when event was not found",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     *    requirements = {
     *         {"name"="id", "dataType"="integer", "description"="select event by id", "requirement"="\d+"},
     *    },
     * )
     * @ParamConverter("bean", class="NaxCrmBundle:Event")
     */
    public function readEventAction($bean = null)
    {
        if (empty($bean)) {
            return $this->getFailedJsonResponse(null, 'Event was not found', 404);
        }
        $bean->setStatus(Event::STATUS_READ);
        $this->getEm()->flush();

        if ($bean->getType() == Event::TYPE_FOLLOW_UP) {
            $this->createUserLog(UserLogItem::ACTION_TYPE_UPDATE,UserLogItem::OBJ_TYPE_FOLLOW_UPS, [
                'id' => $bean->getId(),
                'status' => Event::STATUS_READ,
            ]);
        }
        return $this->getJsonResponse($bean->export(), 'Event was read');
    }

    /**
     * @Post("/events/{id}/invite", name="v1_invite_events")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "admin",
     *    resource = "admin/event",
     *    authentication = true,
     *    description = "Batch invite manager and/or department to events",
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
     *          "Returned when manager was not found",
     *          "Returned when department was not found",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     *    parameters = {
     *        {"name"="managerIds",   "dataType"="collection", "required"=true,  "format"="\d+", "description"="set array of managerIds"},
     *    },
     *    requirements = {
     *         {"name"="id", "dataType"="integer", "description"="select event by id", "requirement"="\d+"},
     *    },
     * )
     * @ParamConverter("bean", class="NaxCrmBundle:Event")
     */
    public function assignBatchClientAction($bean = null, Request $request = null)
    {
        $managerIds = $this->getRequiredParam('managerIds');
        if(!is_array($managerIds)){
            return $this->getFailedJsonResponse(null, 'Invalid managerIds');
        }
        $bean->setParticipants($managerIds);
        $this->getEm()->persist($bean);
        $this->getEm()->flush();

        return $this->getJsonResponse($bean->export(), 'Participants were added');
    }
}
