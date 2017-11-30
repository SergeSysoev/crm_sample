<?php

namespace NaxCrmBundle\Controller\v1\admin;

use NaxCrmBundle\Entity\Language;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Put;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use NaxCrmBundle\Controller\v1\BaseController;

class LanguageController extends BaseController
{
    /**
     * @Get("/languages")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "admin",
     *    resource = "admin/language",
     *    authentication = false,
     *    deprecated = true,
     *    description = "Get all available",
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
    public function getLanguagesAction(Request $request) //FIXME: moved to open routes. Remove here and firewall when succeed
    {
        return $this->forward('NaxCrmBundle\Controller\v1\open\LanguageController::getLanguagesAction', $request->query->all());
    }

    /**
     * @Put("/languages/{id}")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "admin",
     *    resource = "admin/language",
     *    authentication = true,
     *    description = "Update language isSupported status",
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
     *        },
     *        500="Returned when Something went wrong",
     *    },
     *    parameters = {
     *        {"name"="isSupported", "dataType"="boolean", "required"=true, "format"="0|1", "description" = "set isSupported language status"},
     *    },
     *    requirements = {
     *         {"name"="id", "dataType"="integer", "description"="select language by id", "requirement"="\d+"},
     *    },
     * )
     * @ParamConverter("entity", class="NaxCrmBundle:Language")
     */
    public function update(Language $entity = null, Request $request)
    {
        if (empty($entity)) {
            return $this->getFailedJsonResponse(null, "Language was not found", 404);
        }

        $this->saveEntity($entity, ['isSupported' => ['req']], $request->request->all());

        return $this->getJsonResponse($entity, 'Language updated');
    }

}
