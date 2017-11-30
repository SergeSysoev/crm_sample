<?php

namespace NaxCrmBundle\Controller\v1\integration;

use Doctrine\ORM\EntityManager;
use NaxCrmBundle\Entity\Account;
use NaxCrmBundle\Entity\Client;
use NaxCrmBundle\Entity\Document;
use NaxCrmBundle\Entity\DocumentType;
use NaxCrmBundle\Entity\Manager;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

use NaxCrmBundle\Controller\v1\BaseController;

class DocumentController extends BaseController
{

    /**
     * @Post("/client/{clientId}/document/{ext_id}", name="v1_integrate_document")
     * @ParamConverter("client", class="NaxCrmBundle:Client", options={"id" = "clientId"})
     * @Security("has_role('ROLE_MANAGER') and is_granted('C', 'document')")
     */
    public function integrationDocumentAction(Request $request, Client $client = null, $ext_id = 0)
    {
        return $this->getJsonResponse(null, 'Ignore');
    }

    /**
     * @Get("/documents/sync", name="v1_sync_documents")
     */
    public function syncPlatformDocumentsAction(Request $request)
    {
        return $this->getJsonResponse(null, 'Ignore');
    }
}
