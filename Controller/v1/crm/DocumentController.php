<?php

namespace NaxCrmBundle\Controller\v1\crm;

use NaxCrmBundle\Entity\Client;
use NaxCrmBundle\Entity\Document;
use NaxCrmBundle\Entity\DocumentType;
use NaxCrmBundle\Entity\Manager;
use NaxCrmBundle\Entity\UserLogItem;
use NaxCrmBundle\Modules\Email\Triggers\Client\DocumentApproved;
use NaxCrmBundle\Modules\Email\Triggers\Client\DocumentDeclined;
use NaxCrmBundle\Modules\Email\Triggers\Client\DocumentNew;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Put;
use FOS\RestBundle\Controller\Annotations\Delete;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use NaxCrmBundle\Modules\SlackBot;

use NaxCrmBundle\Controller\v1\BaseController;

class DocumentController extends BaseController
{
    protected $csvTimeField = 'time';
    protected $csvTitles = [
        'id',
        'clientId',
        'firstname',
        'lastname',
        'declineReason',
        'documentTypeId',
        'managerId',
        'managerName',
        'mimeType',
        'path',
        'status',
        'time',
    ];

    protected function setExportDefaults()
    {
        $this->csvAliases = [
            'status' => Document::get('Statuses'),
            'documentTypeId' => $this->getIdNamesArray(DocumentType::class(), 'type'),
        ];
    }

    /**
     * @Get("/clients/{id}/documents", name="v1_get_client_documents")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "crm",
     *    resource = "crm/document",
     *    authentication = true,
     *    description = "Get client documents",
     *    statusCodes={
     *        200="Returned when successful",
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *          "Returned when the user have not proper acl rights",
     *          "Returned when manager has no access to the client",
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
     * @Security("(has_role('ROLE_MANAGER') && is_granted('R', 'document')) || (has_role('ROLE_CLIENT') && user == client)")
     */
    public function getClientDocumentsAction(Request $request, Client $client = null)
    {
        if (empty($client)) {
            return $this->getFailedJsonResponse(null, 'Client was not found', 404);
        }
        if (($this->getUser() instanceof Manager) && !$this->getUser()->hasAccessToClient($client)) {
            return $this->getFailedJsonResponse(null, 'Manager has no access to the client', 403);
        }
        $params = $request->query->all();
        $preFilters = [
            'clientIds' => [$client->getId()],
        ];
        $res = $this->getList($params, $preFilters);
        return $this->getJsonResponse($res);
    }

    /**
     * @Get("/department-documents", name="v1_get_department_documents")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "crm",
     *    resource = "crm/document",
     *    authentication = true,
     *    description = "Get department documents",
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
    public function getDepartmentDocumentsAction(Request $request)
    {
        $params = $request->query->all();
        $preFilters = [
            'departmentIds' => $this->getUser()->getAccessibleDepartmentIds(),
        ];
        $res = $this->getList($params, $preFilters);
        return $this->getJsonResponse($res);
    }

    /**
     * @Get("/documents/{id}", name="v1_get_document")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "crm",
     *    resource = "crm/document",
     *    authentication = true,
     *    description = "Get selected document information",
     *    statusCodes={
     *        200="Returned when successful",
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *          "Returned when you have no access to this document"
     *        },
     *        404={
     *          "Returned when document was not found",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     *    requirements = {
     *         {"name"="id", "dataType"="integer", "description"="select document by id", "requirement"="\d+"},
     *    },
     * )
     * @ParamConverter("document", class="NaxCrmBundle:Document")
     */
    public function getDocumentAction($document = null, Request $request)
    {
        if (empty($document)) {
            return $this->getFailedJsonResponse(null, 'Document was not found', 404);
        }
        if($this->getUser() instanceof Client){
            if($this->getUser()->getId() != $document->getClientId()){
                return $this->getFailedJsonResponse(null, 'It`s not your document', 403);
            }
        }
        else if (!$this->getUser()->hasAccessToDocument($document)) {
            return $this->getFailedJsonResponse(null, 'You have no access to this document', 403);
        }
        $res = $document->export();
        return $this->getJsonResponse($res);
    }

    /**
     * @Get("/documents/{id}/view", name="v1_view_document")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "crm",
     *    resource = "crm/document",
     *    authentication = true,
     *    description = "Get document for view",
     *    statusCodes={
     *        200="Returned when successful",
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *          "Returned when you have no access to this document",
     *        },
     *        404={
     *          "Returned when document was not found",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     *    requirements = {
     *         {"name"="id", "dataType"="integer", "description"="select document by id", "requirement"="\d+"},
     *    },
     * )
     * @ParamConverter("document", class="NaxCrmBundle:Document")
     */
    public function viewDocumentAction(Request $request, $document = null)
    {
        /* @var Document $document */
        if (empty($document)) {
            return $this->getFailedJsonResponse(null, 'Document was not found', 404);
        }
        if($this->getUser() instanceof Client){
            if($this->getUser()->getId() != $document->getClientId()){
                return $this->getFailedJsonResponse(null, 'It`s not your document', 403);
            }
        }
        else if (!$this->getUser()->hasAccessToDocument($document)) {
            return $this->getFailedJsonResponse(null, 'You have no access to this document', 403);
        }
        $headers = array(
            'Content-Type'        => $document->getMimeType(),
            'Content-Disposition' => 'inline; filename="' . $document->getPath() . '"');

        $filePath = $this->getAbsoluteDocumentPath($document);
        if(!file_exists($filePath)){
            $mes = 'Can`t access to file of document';
            SlackBot::send($mes, $document->export(), SlackBot::WARN);
            return $this->getFailedJsonResponse(null, $mes, 500);
        }

        return new Response(file_get_contents($filePath), 200, $headers);
    }

    /**
     * @Post("/clients/{id}/documents", name="v1_client_upload_document")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "crm",
     *    resource = "crm/document",
     *    authentication = true,
     *    description = "Upload document",
     *    statusCodes={
     *        200="Returned when successful",
     *        400={
     *          "Returned when missing required parameter",
     *        },
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *          "Returned when the user have not proper acl rights",
     *          "Returned when document with it mime type is not allowed",
     *          "Returned when document with it extension is not allowed",
     *        },
     *        404={
     *          "Returned when client was not found",
     *          "Returned when files are not present",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     *    parameters = {
     *        {"name"="documentXdocumentTypeId", "dataType"="integer", "required"=true, "format"="\d+", "description" = "set document typeId per file"},
     *    },
     *    requirements = {
     *         {"name"="id", "dataType"="integer", "description"="select client by id", "requirement"="\d+"},
     *    },
     * )
     * @ParamConverter("client", class="NaxCrmBundle:Client")
     * @Security("(has_role('ROLE_MANAGER') && is_granted('C', 'document') && user.hasAccessToClient(client)) || (has_role('ROLE_CLIENT') && user == client)")
     */
    public function clientUploadDocumentAction(Request $request, $client = null)
    {
        if (empty($client)) {
            return $this->getFailedJsonResponse(null, 'Client was not found', 404);
        }

        $files = $request->files;
        if ($files->count() == 0) {
            return $this->getFailedJsonResponse(null, 'Files are not present', 404);
        }
        $documents = [];
        $user = $this->getUser();
        foreach ($files as $fileParameter => $documentFile) {
            $documentTypeId = $this->getRequiredParam($fileParameter . 'documentTypeId');

            if (!in_array($documentFile->getClientMimeType(), Document::get('allowedMimeTypes'))) {
                return $this->getFailedJsonResponse(null, 'Document with mime type ' . $documentFile->getClientMimeType() . ' is not allowed',403);
            }
            if (!in_array($documentFile->getClientOriginalExtension(), Document::get('allowedExtensions'))) {
                return $this->getFailedJsonResponse(null, 'Document with extension ' . $documentFile->getClientOriginalExtension() . ' is not allowed',403);
            }

            $newFileName = $documentFile->getFilename() . '.' . $documentFile->getClientOriginalExtension();

            $manager = null;
            if($user instanceof Manager){
                $manager = $user;
            }
            elseif(($user instanceof Client) && $user->getManager()){
                $manager = $user->getManager();
            }

            $documentFile->move($this->getAbsoluteDocumentPath(), $newFileName);

            $document = Document::createInst();
            $document->setManager($manager);
            $document->setClient($client);
            $document->setPath($newFileName);
            $document->setMimeType($documentFile->getClientMimeType());
            $document->setDocumentType($documentType = $this->getReference(DocumentType::class(), $documentTypeId));

            $this->getEm()->persist($document);
            $this->getEm()->flush();
            $documents[] = $document->export();
        }

        $log = empty($documents[1])? $documents[0]: $documents;
        $this->getTriggerService()->sendEmails(DocumentNew::class(), compact('client', 'document'), $client->getLangcode());
        $this->createUserLog(UserLogItem::ACTION_TYPE_CREATE, UserLogItem::OBJ_TYPE_CLIENT_DOCUMENTS, $log);

        $this->addEvent($client, 'document was uploaded', 'document of '.$documentType->getType().' type was uploaded');
        return $this->getJsonResponse($documents, 'Documents where uploaded');
    }

    /**
     * @Put("/documents/{id}", name="v1_update_document")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "crm",
     *    resource = "crm/document",
     *    authentication = true,
     *    description = "Update document",
     *    statusCodes={
     *        200="Returned when successful",
     *        400={
     *          "Returned when missing required parameter",
     *        },
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *          "Returned when you have no access to this document",
     *        },
     *        404={
     *          "Returned when document was not found",
     *          "Returned when files are not present",
     *        },
     *        500="Returned when Something went wrong",
     *        503="Returned when Platform reject changes",
     *    },
     *    parameters = {
     *        {"name"="documentTypeId", "dataType"="integer", "required"=false, "format"="\d+", "description" = "set document typeId"},
     *        {"name"="status",         "dataType"="integer", "required"=false, "format"="\d+", "description" = "set document status"},
     *        {"name"="comment",        "dataType"="integer", "required"=false, "format"="\d+", "description" = "set document status comment. Required if status=2(declined)"},
     *    },
     *    requirements = {
     *         {"name"="id", "dataType"="integer", "description"="select document by id", "requirement"="\d+"},
     *    },
     * )
     * @ParamConverter("document", class="NaxCrmBundle:Document")
     */
    public function updateDocumentAction(Request $request, $document = null)
    {
        /* @var Document $document */
        if (empty($document)) {
            return $this->getFailedJsonResponse(null, 'Document was not found', 404);
        }
        if (!$this->getUser()->hasAccessToDocument($document)) {
            return $this->getFailedJsonResponse(null, 'You have no access to this document', 403);
        }

        $post = $request->request;
        $old_data = $document->export();

        if ($documentTypeId = $post->get('documentTypeId')) {
            $document->setDocumentType($this->getReference(DocumentType::class(), $documentTypeId));
        }

        if ($status = (int)$post->get('status')) {
            if ($status == Document::STATUS_DECLINED) {
                $document->setComment($this->getRequiredParam('comment'));
            }
            $document->setStatus($status);
            $document->setUpdated(new \DateTime('now'));
            //it is can`t be executed
//            if (empty($document)) {
//                $res = $this->getPlatformHandler($this->getPlatformName())->setDocStatus($document);
//                if(!$res || $res==='false'){
//                    return $this->getFailedJsonResponse(null, 'Platform reject changes', 403);
//                }
//            }
        }
        $this->saveEntity($document);

        $client = $document->getClient();
        if(!empty($client)){
            $triggerName = ($status == Document::STATUS_APPROVED ? DocumentApproved::class() : DocumentDeclined::class());
            $this->getTriggerService()->sendEmails($triggerName, compact('client', 'document'), $client->getLangcode());
        }

        $data = $document->export();
        $this->logUpdates($data, $old_data, $document, UserLogItem::OBJ_TYPE_CLIENT_DOCUMENTS);
        return $this->getJsonResponse($data);
    }

    /**
     * @Delete("/documents", name="v1_batch_remove_document")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "crm",
     *    resource = "crm/document",
     *    authentication = true,
     *    description = "Batch delete documents",
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
     *        {"name"="documentIds", "dataType"="collection", "required"=true, "format"="\d+", "description" = "set array of documentId for delete"},
     *    },
     * )
     */
    public function batchDeleteDocumentAction()
    {
        $documentIds = $this->getRequiredParam('documentIds');
        $documentRepo = $this->getRepository(Document::class());
        $basePath = $this->getAbsoluteDocumentPath();

        foreach ($documentRepo->findBy(['id' => $documentIds]) as $document) {
            $filePath = $basePath . $document->getPath();
            if (file_exists($filePath)) unlink($filePath);
            $this->getEm()->remove($document);
        }
        $this->getEm()->flush();

        $this->createUserLog(UserLogItem::ACTION_TYPE_DELETE, UserLogItem::OBJ_TYPE_CLIENT_DOCUMENTS, ['ids' => $documentIds]);
        // $this->addEvent($document->getClient(), 'document was removed', 'document of '.$document->getDocumentType->getType().' type was removed');
        return $this->getJsonResponse(null, 'Documents were removed');
    }

    /**
     * @Delete("/documents/{id}", name="v1_remove_document")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "crm",
     *    resource = "crm/document",
     *    authentication = true,
     *    description = "Delete selected document",
     *    statusCodes={
     *        200="Returned when successful",
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *        },
     *        404="Returned when document was not found",
     *        500="Returned when Something went wrong",
     *    },
     *    requirements = {
     *         {"name"="id", "dataType"="integer", "description"="select document by id", "requirement"="\d+"},
     *    },
     * )
     * @ParamConverter("document", class="NaxCrmBundle:Document")
     */
    public function removeDocumentAction($document = null)
    {
        if (empty($document)) {
            return $this->getFailedJsonResponse(null, 'Document was not found', 404);
        }

        $filePath = $this->getAbsoluteDocumentPath($document);

        if (file_exists($filePath)) unlink($filePath);

        $data = $document->export();
        $this->removeEntity($document);

        $this->createUserLog(UserLogItem::ACTION_TYPE_DELETE, UserLogItem::OBJ_TYPE_CLIENT_DOCUMENTS, $data);
        $this->addEvent($document->getClient(), 'document was removed', 'document of '.$document->getDocumentType()->getType().' type was removed');
        return $this->getJsonResponse(null, 'Document has been removed');
    }
}
