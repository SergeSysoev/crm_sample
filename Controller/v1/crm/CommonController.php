<?php

namespace NaxCrmBundle\Controller\v1\crm;

use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use NaxCrmBundle\Controller\v1\BaseController;

class CommonController extends BaseController
{
    /**
     * @Get("/platform/{action}/{action2}", name="v1_get_from_platform")
     */
    // * @Security("is_granted('R', 'dashboard_risk_man')")
    public function getFromPlatformAction(Request $request, $action = false, $action2 = false)
    {
        /*
            $h = $this->getPlatformHandler();
            if (!$h || !$action) {
                return $this->getFailedJsonResponse([], 'Bad params');
            }
            $qs = $request->getQueryString();
            $url = $action2 ? "/api/$action/$action2" : "/api/$platform/$action";
            $url .= $qs ? "?$qs" : '';
            $result = $h->sendRequest($url, null);

            return $this->getJsonResponse($result);
        */
        return $this->getFailedJsonResponse([], 'Bad request');
    }

    /**
     * @Post("/platform/{action}/{action2}", name="v1_post_to_platform")
     *   POST /set_stoploss {amount}
     *   POST /set_warning {amount}
     *   POST /warning_email_add {email}
     *   POST /warning_email_del {email}
     *   POST /stop_bids
     *   POST /start_bids
     *   POST /reset_stoploss
     *   POST /warning_email_add
     *   POST /warning_email_del
     * @Security("is_granted('U', 'dashboard_risk_man')")
     */
    public function postFromPlatformAction(Request $request = null, $action = false, $action2 = false)
    {
        /*
            $h = $this->getPlatformHandler();
            if (!$h || !$action) {
                return $this->getFailedJsonResponse([], 'Bad params');
            }
            $data = $request->request->all();
            $data = http_build_query($data);
            $url = $h->getConfig()['host'];
            $url .= $action2 ? "/api/$action/$action2" : "/api/$platform/$action";
            $context = stream_context_create(['http' => [
                'method' => 'POST',
                'content' => $data,
                'header' => "Content-type: application/x-www-form-urlencoded\r\n"
                    . "Content-Length: " . strlen($data) . "\r\n"
            ]]);
            $result = $h->sendRequest($url, $context);

            return $this->getJsonResponse($result);
        */
        return $this->getFailedJsonResponse([], 'Bad request');
    }

    /**
     * @Get("/risk_man", name="v1_get_risk_man")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "crm",
     *    resource = "crm/common",
     *    authentication = true,
     *    description = "Get risk management dashboard",
     *    statusCodes={
     *        200="Returned when successful",
     *        400={
     *          "Returned when date_range is required",
     *          "Returned when dateFrom and dateTo is required for period date_range",
     *        },
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     *    parameters = {
     *        {"name"="date_range", "dataType"="datetime", "required"=true, "format"="datetime format", "description" = "set date range for output"},
     *    },
     * )
     */
    public function getRiskManAction(Request $request)
    {
        $params = $request->query->all();
        if (empty($params['date_range'])) {
            return $this->getFailedJsonResponse(null, "date_range is required");
        }
        if ($params['date_range'] == 'period' &&
            (empty($params['dateFrom']) && empty($params['dateTo']))
        ) {
            return $this->getFailedJsonResponse(null, "dateFrom and dateTo is required for period date_range");
        }

        $handler = $this->getPlatformHandler();
        $res = $handler->getRiskMan($params, $this->getPlatformName());
        return $this->getJsonResponse($res);
    }

    /**
     * @Get("/common-resources", name="v1_get_common_resources")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "crm",
     *    resource = "crm/common",
     *    deprecated = true,
     *    authentication = false,
     *    description = "Get common resources list",
     *    statusCodes={
     *        200="Returned when successful",
     *        500="Returned when Something went wrong",
     *    },
     * )
     */
    public function getCommonResourcesAction() //FIXME: moved to open routes. Remove here and firewall when succeed
    {
        return $this->forward('NaxCrmBundle\Controller\v1\open\CommonController::getCommonResourcesAction');
    }
}
