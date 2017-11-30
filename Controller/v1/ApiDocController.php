<?php
namespace NaxCrmBundle\Controller\v1;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Response;

use Nelmio\ApiDocBundle\Controller\ApiDocController as BaseApiDocController;

class ApiDocController extends BaseApiDocController
{
    public function indexAction($view = ApiDoc::DEFAULT_VIEW)
    {
        $request = $this->container->get('Request');
        $token = $request->query->get('access', false);
        $isWhite = in_array($request->getClientIp(), $this->getParameter('api_doc')['white']);

        if ($view != ApiDoc::DEFAULT_VIEW && (!$token OR !$isWhite OR !in_array($token, $this->getParameter('api_doc')['token']))) $view = ApiDoc::DEFAULT_VIEW;

        $extractedDoc = $this->get('nelmio_api_doc.extractor.api_doc_extractor')->all($view);
        $htmlContent = $this->get('nelmio_api_doc.formatter.html_formatter')->format($extractedDoc);

        return new Response($htmlContent, 200, array('Content-Type' => 'text/html'));
    }
}