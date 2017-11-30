<?php

namespace NaxCrmBundle\Controller\v1\open;

use NaxCrmBundle\Entity\Language;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations\Get;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use NaxCrmBundle\Controller\v1\BaseController;

class LanguageController extends BaseController
{
    /**
     * @Get("/languages")
     * @ApiDoc(
     *    section = "open",
     *    resource = "open/language",
     *    authentication = false,
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
    public function getLanguagesAction(Request $request)
    {
        $languageRepo = $this->getRepository(Language::class());
        return $this->getJsonResponse($languageRepo->findBy($request->query->all()));
    }
}
