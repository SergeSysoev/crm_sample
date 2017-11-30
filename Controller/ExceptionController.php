<?php

namespace NaxCrmBundle\Controller;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;
use NaxCrmBundle\Doctrine\NaxEntityManager;
use Monolog\Logger;
use NaxCrmBundle\Debug;
use NaxCrmBundle\Entity\LogItem;
use NaxCrmBundle\Entity\Manager;
use NaxCrmBundle\Modules\SlackBot;
// use Symfony\Component\BrowserKit\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use FOS\RestBundle\Util\ExceptionValueMap;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Router;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class ExceptionController extends Controller
{

    /**
     * @var ExceptionValueMap
     */
    private $exceptionCodes;

    /**
     * @var Registry
     */
    private $doctrine;

    private $em = null;
    private $debug = false;

    public function __construct(ExceptionValueMap $exceptionCodes, $container, $debug = false)
    {
        $this->exceptionCodes   = $exceptionCodes;
        $this->container        = $container;
        $this->doctrine         = $this->getDoctrine();
        $this->debug            = $debug;
    }

    /**
     * @return EntityManager
     */
    protected function getEm()
    {
        if (!$this->em) {
            $this->em = new NaxEntityManager($this->doctrine->getManager());
        }
        if (!$this->em->isOpen()) {//maybe not need
            $this->resetEm();
        }
        return $this->em;
    }
    /**
     * @return EntityManager
     */
    protected function resetEm()//not work
    {
        $this->doctrine->resetManager();
        $this->em = new NaxEntityManager($this->doctrine->getManager());
        return $this->em;
    }

    public function showAction(Request $request, \Exception $exception)
    {

        $params = array_merge($request->query->all(), $request->request->all());
        $params['trace'] = $exception->getTraceAsString();
        $response = $this->getResponse();
        $code = $this->getStatusCode($exception);
        //dont show exception message if it is not HTTP exception or it is not mapped in config

        if (!$code) {
            $code = 500;
            $statusText = 'Something went wrong';
            $slack_data = [
                'url' => $request->getPathInfo(),
                'exception' => $exception->getMessage(),
                'params' => $params,
                'debug' => Debug::$messages,
            ];
            SlackBot::send($statusText, $slack_data);
        }
        else {
            $statusText = $this->getStatusText($exception);
        }

        if ($exception instanceof \Firebase\JWT\ExpiredException){
            $code = 440;
        }

        $data = [
            'status'     => $code,
            'statusText' => $statusText,
        ];
        if ($this->debug) {
            $debug = Debug::$messages;
            if(!is_array($debug) || empty($debug['disable_debug'])){
                $data['exception'] = $exception->getMessage();
                $data['debug'] = $debug;
                if ($exception instanceof \Firebase\JWT\ExpiredException || $exception instanceof \UnexpectedValueException) {
                    $data['trace'] = $exception->getTraceAsString();
                }
                else {
                    $data['trace'] = $exception->getTrace();
                }
            }
        }

        $user = $this->getUser();
        if (is_null($user) || $user instanceof Manager) {
            $log_data = [
                'params' => $params,
                'exception' => $exception->getMessage(),
                'debug' => Debug::$messages,
            ];
            $logItem = LogItem::createInst();
            $logItem->setManager($user);
            $logItem->setMessage($statusText);
            $logItem->setUrl($request->getPathInfo());
            $logItem->setJsonPayload(json_encode($log_data, JSON_UNESCAPED_UNICODE));
            $logItem->setLevel($code);
            $logItem->setChannel(LogItem::CHANNEL_SYSTEM);
            $logItem->setReferer();

            $em = $this->getEm()->resetAll();
            $em->persist($logItem);
            $em->flush();
        }

        $response->setData($data);
        $response->setStatusCode($code, $statusText);
        return $response;
    }

    /**
     * Determines the status code to use for the response.
     *
     * @param \Exception $exception
     *
     * @return int
     */
    private function getStatusCode(\Exception $exception)
    {
        // If matched
        if ($statusCode = $this->exceptionCodes->resolveException($exception)) {
            return $statusCode;
        }
        // Otherwise, default
        if ($exception instanceof HttpExceptionInterface) {
            return $exception->getStatusCode();
        }

        if(in_array($exception->getMessage(), [
            'Wrong number of segments',
            'Signature verification failed',
        ])){
            return 401;// 401,440 - auto logout
        }
        return false;
    }

    /**
     * Determines the status text to use for the response.
     *
     * @param \Exception $exception
     *
     * @return int
     */
    private function getStatusText(\Exception $exception)
    {
        $statusText = $exception->getMessage();

        if(in_array($statusText, [
            'Wrong number of segments',
            'Signature verification failed',
        ])){
            return 'Invalid auth token';
        }

        $statusText = explode("\n", $statusText)[0];
        return $statusText;
    }

    /**
     * @return JsonResponse
     */
    private function getResponse()
    {
        $response = new JsonResponse();
        $response->setEncodingOptions(JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
        // $this->response->headers->set('Content-Type', 'application/json');
        $response->headers->set('Access-Control-Allow-Origin', '*');
        #$response->headers->set('Access-Control-Allow-Headers', 'Authorization');
        // $response->headers->set('Access-Control-Expose-Headers', 'Authorization');
        // \NaxCrmBundle\Debug::$messages[]=$response->headers->allPreserveCase();
        return $response;
    }
}
