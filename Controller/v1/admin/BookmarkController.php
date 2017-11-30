<?php

namespace NaxCrmBundle\Controller\v1\admin;

use NaxCrmBundle\Entity\Bookmark;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Put;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Delete;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use NaxCrmBundle\Controller\v1\BaseController;

class BookmarkController extends BaseController
{
    /**
     * @Get("/bookmarks")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "admin",
     *    resource = "admin/bookmark",
     *    authentication = true,
     *    description = "Get all current manager bookmarks",
     *    statusCodes = {
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
     *    },
     * )
     * @Security("has_role('ROLE_MANAGER')")
     */
    public function getBookmarksAction(Request $request)
    {
        $params = $request->query->all();
        $preFilters = [
            'managerIds' => [$this->getUser()->getId()],
        ];

        $res = $this->getList($params, $preFilters);
        return $this->getJsonResponse($res);
    }

    /**
     * @Post("/bookmarks")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "admin",
     *    resource = "admin/bookmark",
     *    authentication = true,
     *    description = "Add new bookmark",
     *    statusCodes = {
     *        200="Returned when successful",
     *        400={
     *          "Returned when missing required parameter",
     *          "Returned when invalid request parameters",
     *        },
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     *    parameters = {
     *        {"name"="name",          "dataType"="string", "required"=true,  "format"=".+", "description"="set bookmark name"},
     *        {"name"="bookmark",      "dataType"="string", "required"=true,  "format"=".+", "description"="set bookmark content"},
     *        {"name"="table",         "dataType"="string", "required"=true,  "format"=".+", "description"="set table name assigned to bookmark"},
     *        {"name"="bookmarkGroup", "dataType"="string", "required"=false, "format"=".+", "description"="set bookmark group"},
     *        {"name"="url",           "dataType"="string", "required"=false, "format"=".+", "description"="set url to go to from bookmark"},
     *    },
     * )
     * @Security("has_role('ROLE_MANAGER')")
     */
    public function createBookmarkAction(Request $request)
    {
        $bookmark = Bookmark::createInst();

        $fields = [
            'name' => ['req'],
            'bookmark' => ['req'],
            'table',
            'url',
            'bookmarkGroup',
            'manager' => ['def' => $this->getUser()],
        ];
        $this->saveEntity($bookmark, $fields, $request->request->all());

        $res = $bookmark->export();
        return $this->getJsonResponse($res, 'Bookmark created');
    }

    /**
     * @Put("/bookmarks/{id}")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "admin",
     *    resource = "admin/bookmark",
     *    authentication = true,
     *    description = "Update bookmark",
     *    statusCodes = {
     *        200="Returned when successful",
     *        400={
     *          "Returned when invalid request parameters",
     *        },
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *          "Returned when you can`t access this bookmark",
     *        },
     *        404={
     *          "Returned when bookmark was not found",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     *    parameters = {
     *        {"name"="name",          "dataType"="string", "required"=false, "format"=".+", "description"="set bookmark name"},
     *        {"name"="bookmark",      "dataType"="string", "required"=false, "format"=".+", "description"="set bookmark content"},
     *        {"name"="bookmarkGroup", "dataType"="string", "required"=false, "format"=".+", "description"="set bookmark group"},
     *    },
     *    requirements = {
     *         {"name"="id", "dataType"="integer", "description"="select bookmark by id", "requirement"="\d+"},
     *    },
     * )
     * @ParamConverter("bookmark", class="NaxCrmBundle:Bookmark")
     * @Security("has_role('ROLE_MANAGER')")
     */
    public function update(Bookmark $bookmark = null, Request $request)
    {
        if (empty($bookmark)) {
            return $this->getFailedJsonResponse(null, 'Bookmark was not found', 404);
        }

        if ($bookmark->getManager() != $this->getUser()) {
            return $this->getFailedJsonResponse(null, 'You can`t access this bookmark', 403);
        }

        $fields = [
            'name',
            'bookmark',
            'table',
            'url',
            'bookmarkGroup',
        ];
        $this->saveEntity($bookmark, $fields, $request->request->all());

        $res = $bookmark->export();
        return $this->getJsonResponse($res, 'Bookmark updated');
    }

    /**
     * @Delete("/bookmarks/{id}")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "admin",
     *    resource = "admin/bookmark",
     *    authentication = true,
     *    description = "Delete bookmark",
     *    statusCodes = {
     *        200="Returned when successful",
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *          "Returned when you can`t access this bookmark",
     *        },
     *        404={
     *          "Returned when bookmark was not found",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     *    requirements = {
     *         {"name"="id", "dataType"="integer", "description"="select bookmark by id", "requirement"="\d+"},
     *    },
     * )
     * @ParamConverter("bookmark", class="NaxCrmBundle\Entity\Bookmark")
     * @Security("has_role('ROLE_MANAGER')")
     */
    public function deleteBookmarkAction(Bookmark $bookmark = null)
    {
        if (empty($bookmark)) {
            return $this->getFailedJsonResponse(null, 'Bookmark was not found', 404);
        }

        if ($bookmark->getManager() != $this->getUser()) {
            return $this->getFailedJsonResponse(null, 'You can`t access this bookmark', 403);
        }

        $this->removeEntity($bookmark);

        return $this->getJsonResponse(null, 'Bookmark was deleted');
    }

}
