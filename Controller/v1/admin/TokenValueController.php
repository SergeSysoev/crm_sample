<?php

namespace NaxCrmBundle\Controller\v1\admin;

use NaxCrmBundle\Entity\Language;
use NaxCrmBundle\Entity\Token;
use NaxCrmBundle\Entity\TokenValue;
use NaxCrmBundle\Repository\TokenValueRepository;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Delete;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use NaxCrmBundle\Controller\v1\BaseController;

class TokenValueController extends BaseController
{

    /**
     * @Get("/token-values")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "admin",
     *    resource = "admin/token",
     *    authentication = true,
     *    description = "Get all available languages token values",
     *    statusCodes={
     *        200="Returned when successful",
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     * )
     */
    public function getTokenValuesAction(Request $request)
    {
        /* @var $tokenRepo TokenValueRepository */
        $tokenRepo = $this->getRepository(TokenValue::class());
        $tokenRepo->setPreFilters(['empty' => true]);
        /** @var TokenValue[] $tokens */
        $tokens = $tokenRepo->getTokenValues($request->query->all());
        return $this->getJsonResponse($tokens);
    }

    /**
     * @Post("/token-values")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "admin",
     *    resource = "admin/token",
     *    authentication = true,
     *    description = "Add or Update language token value",
     *    statusCodes={
     *        200="Returned when successful",
     *        400={
     *          "Returned when missing required parameter",
     *        },
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *        },
     *        404={
     *          "Returned when language was not found",
     *          "Returned when token was not found",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     *    parameters = {
     *        {"name"="languageId", "dataType"="integer", "required"=true, "format"="\d+", "description" = "set languageId for token value"},
     *        {"name"="tokenId",    "dataType"="integer", "required"=true, "format"="\d+", "description" = "set token value owner tokenId"},
     *        {"name"="translate",  "dataType"="string",  "required"=true, "format"=".+",  "description" = "set token value translation"},
     *    },
     * )
     */
    public function createOrUpdateTokenValueAction(Request $request)
    {
        $languageId = $this->getRequiredParam('languageId');
        if (!$language = $this->getRepository(Language::class())->find($languageId)) {
            return $this->getFailedJsonResponse(null, "Language was not found", 404);
        }

        $tokenId = $this->getRequiredParam('tokenId');
        if (!$token = $this->getRepository(Token::class())->find($tokenId)) {
            return $this->getFailedJsonResponse(null, "Token was not found", 404);
        }

        $entity = $this->getRepository(TokenValue::class())->find(compact('tokenId', 'languageId'));
        $new = false;

        if (empty($entity)) {
            $new = true;
            $entity = TokenValue::createInst();
        }

        $fields = [
            'token' => ['def' => $token],
            'language' => ['def' => $language],
            'tokenId' => ['req'],
            'languageId' => ['req'],
            'translate' => ['req'],
            'manager' => ['def' => $this->getUser()],
        ];
        $this->saveEntity($entity, $fields, $request->request->all());

        return $this->getJsonResponse($entity->export(), $new ? 'TokenValue created' : 'TokenValue updated');
    }

    /**
     * @Delete("/token-values/{id}")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "admin",
     *    resource = "admin/token",
     *    authentication = true,
     *    description = "Delete language token value",
     *    statusCodes={
     *        200="Returned when successful",
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *        },
     *        404={
     *          "Returned when tokenValue was not found",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     *    requirements = {
     *         {"name"="id", "dataType"="integer", "description"="select token by id", "requirement"="\d+"},
     *    },
     * )
     */
    public function deleteTokenValueAction($id)
    {
        $entity = $this->getRepository(TokenValue::class())->findBy(['tokenId' => $id]);

        if (empty($entity)) {
            return $this->getFailedJsonResponse(null, "TokenValue was not found", 404);
        }

        $em = $this->getEm();

        foreach ($entity as $tv)
            $em->remove($tv);

        $em->flush();

        return $this->getJsonResponse(null, 'TokenValue was deleted');
    }

}
