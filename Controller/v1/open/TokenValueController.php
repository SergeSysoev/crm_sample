<?php
namespace NaxCrmBundle\Controller\v1\open;

use NaxCrmBundle\Entity\TokenValue;
use NaxCrmBundle\Repository\TokenValueRepository;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations\Get;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use NaxCrmBundle\Controller\v1\BaseController;

class TokenValueController extends BaseController
{
    /**
     * @Get("/token-values/list")
     * @ApiDoc(
     *    section = "open",
     *    resource = "open/token",
     *    authentication = false,
     *    description = "Get all available languages token values list",
     *    statusCodes={
     *        200="Returned when successful",
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     * )
     */
    public function getTokenValuesListAction(Request $request)
    {
        /* @var $tokenRepo TokenValueRepository */
        $tokenRepo = $this->getRepository(TokenValue::class());
        /** @var TokenValue[] $tokens */
        $tokens = $tokenRepo->getTokenValues($request->query->all());
        $result = [];
        foreach ($tokens as $token) {
            $result[$token['code']][$token['group']][$token['token']] = $token['translate'];
        }
        return $this->getJsonResponse($result);
    }
}
