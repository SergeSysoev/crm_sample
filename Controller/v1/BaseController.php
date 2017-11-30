<?php

namespace NaxCrmBundle\Controller\v1;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\PersistentCollection;
use JMS\Serializer\SerializationContext;
use NaxCrmBundle\Entity\BaseEntity;
use NaxCrmBundle\Entity\Client;
use NaxCrmBundle\Entity\Document;
use NaxCrmBundle\Entity\Event;
use NaxCrmBundle\Entity\LogItem;
use NaxCrmBundle\Entity\Manager;
use NaxCrmBundle\Entity\UserLogItem;
use NaxCrmBundle\Doctrine\NaxEntityManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * Class BaseController
 * @package NaxCrmBundle\Controller\v1
 */
class BaseController extends Controller
{
    /**
     * @var Response
     */
    protected $response;
    protected $recalculateJwt = false;
    protected $platformName = '';
    protected $streem_response = null;
    protected $em = null;
    protected $user = null;
    protected $disable_debug = false;
    protected $request = null;

    protected $csvTitles = [];
    protected $csvAliases = false;
    protected $exportFileName = '';
    protected $csvTimeField = 'created';
    protected $gmt_offset = 0;


    const CSV_EXPORT_LIMIT = 1000;

    private static $objectTypeAliases = [
        'Department notes' => UserLogItem::OBJ_TYPE_FOLLOW_UPS,
    ];

    protected function setExportDefaults()
    {
        $this->csvAliases = [];
    }

    protected function getList($params = [], $preFilters = [], $func = false, $class = false)
    {
        if(!$class){
            $class = get_called_class();
            $class = explode('\\', $class);
            $class = array_pop($class);
            $class = str_replace('Controller', '', $class);
        }

        $func = $func ?: 'getList';

        $repo = $this->getRepository(BaseEntity::class($this->getBrandName(), $class));
        $repo->setPreFilters($preFilters);

        if (!empty($params['exportCsv'])) {
            return $this->exportCsvWorker($repo, $func, $params);
        }

        return $repo->$func($params);
    }

    protected function removeEntity(&$entity, $hard = false)
    {
        if ($hard) {
            $entity->hardRemove($this->getEm());
        }
        else {
            $this->getEm()->remove($entity);
            $this->getEm()->flush();
        }
    }

    protected function saveEntity(&$entity, $fields = [], $params = false)
    {
        if (!empty($fields)) {
            $this->fillEntity($entity, $fields, $params);
        }

        if (!$this->validate($entity)) {
            $this->response->send();
            exit();
        }

        $this->getEm()->persist($entity);
        $this->getEm()->flush();
        return true;
    }

    public function __construct()
    {
        $this->response = new Response();
        $this->response->headers->set('Content-Type', 'application/json');
        $this->response->headers->set('Access-Control-Allow-Origin', '*');
        #$this->response->headers->set('Access-Control-Allow-Headers', 'Authorization');
        $this->response->headers->set('Access-Control-Expose-Headers', 'Authorization');
        $this->init();
    }

    protected function init(){

    }

    protected function getTriggerService()
    {
        return $this->get('nax_crm.trigger_service');
    }

    /**
     * @param Client $client
     */
    protected function getClientService($client = null)
    {
        return $this->get('nax_crm.client_service')->setClient($client);
    }

    protected function getPlatformHandler($platform = false, $exception = true)
    {
        $service = $this->get('nax_crm.platform_service');
        return $service->getHandler($platform, $exception);
    }

    protected function getPlatformName()
    {
        return $this->getBrandName();
    }
    protected function getBrandName()
    {
        return $this->getParameter('brand');
    }

    /**
     * @param array|ArrayCollection|object|null $data
     * @param string $statusText
     * @param int $status
     * @return Response
     */
    protected function getJsonResponse($data = null, $statusText = '', $status = 200)
    {
        $serializer = $this->container->get('jms_serializer');
        $context = new SerializationContext();
        $context->setSerializeNull(true);
        $context->enableMaxDepthChecks();

        if (!$statusText && isset(Response::$statusTexts[$status])) {
            $statusText = Response::$statusTexts[$status];
        }

        $data = [
            'status' => $status,
            'statusText' => $statusText,
            'data' => $data
        ];
        if ($this->container->get('kernel')->isDebug() && !$this->disable_debug) {
            $data['debug'] = \NaxCrmBundle\Debug::$messages;
        }

        $data = $serializer->serialize($data, 'json', $context);
        $this->response->setContent($data);
        $this->response->setStatusCode($status, $statusText);

        /* @var $user Manager */
        $user = $this->getUser();
        if ($user instanceof Manager && ($this->recalculateJwt || $user->getRecalculateJwt())) {
            $jwtToken = $this->get('nax.jwt_service')->generateManagersJWT($user);
            $this->response->headers->set('Authorization', $jwtToken);
            $user->setRecalculateJwt(false);
            $this->getEm()->flush();
        }

        return $this->response;
    }

    /**
     * @param array|ArrayCollection|object|null $data
     * @param string $statusText
     * @param int $status
     * @return Response
     */
    protected function getFailedJsonResponse($data = null, $statusText = '', $status = 400)
    {
        $request = $this->container->get('request');
        $params = array_merge($request->query->all(), $request->request->all());

        $user = $this->getUser();
        if (is_null($user) || $user instanceof Manager) {
            $log_data = compact('params', 'data');
            $log_data['debug']=\NaxCrmBundle\Debug::$messages;
            $logItem = LogItem::createInst();
            $logItem->setManager($user);
            $logItem->setMessage($statusText);
            $logItem->setUrl($request->getPathInfo());
            $logItem->setJsonPayload(json_encode($log_data, JSON_UNESCAPED_UNICODE));
            $logItem->setLevel($status);
            $logItem->setChannel(LogItem::CHANNEL_USER);
            $logItem->setReferer();

            $em = $this->getEm()->resetAll();
            $em->persist($logItem);
            $em->flush();
        }
        return $this->getJsonResponse($data, $statusText, $status);
    }

    /**
     * @param string $paramName
     * @return mixed
     */
    protected function getRequiredParam($paramName)
    {
        $request = $this->getReq();
        if (empty($value = $request->get($paramName))) {
            throw new HttpException(400, "Missing required parameter {$paramName}");
        }
        return $value;
    }

    protected function getGmtOffset(){
        if($this->gmt_offset){
            return $this->gmt_offset;
        }

        if($user_time = $this->getReq()->headers->get('User-Time', '0')){
            $this->gmt_offset = $user_time-(time()/*-10800*/); //10800 offset for prod
        }
        return $this->gmt_offset;
    }

    protected function getReq(){
        if(!$this->request){
            $this->request = $this->container->get('request_stack')->getCurrentRequest();
        }
        return $this->request;
    }

    public function fillEntity(&$entity, $fields, $params = false)
    {
        if (!$params) { // not good way
            $params = $this->getReq()->all();
        }

        foreach ($fields as $k => $v) {
            if(is_array($v)){
                foreach ($v as $j=>$o) {
                    if(is_numeric($j)){
                        $v[$o]=true;
                    }
                }
            }
            if (isset($v['req'])) {
                $params[$k] = $this->getRequiredParam($k);
            }
        }

        $entity->set($fields, $params);
        return true;
    }

    public function createUserLog($actionType, $objectType, $jsonPayload=[])
    {
        $user = $this->getUser();
        if (is_object($user)) {
            $userType = get_class($user);
            $userType = substr(strrchr($userType, '\\'), 1);
        }
        else{
            $userType = 'Unknown';
        }
        return $this->createLog($user, $actionType, $objectType, $jsonPayload, $userType);
    }
    public function createLog($user, $actionType, $objectType, $jsonPayload=[], $userType='Unknown')
    {
        $logger = $this->get('nax_crm.user_logger');
        return $logger->createLog($user, $actionType, $objectType, $jsonPayload, $this->getReq(), $userType);
    }

    public function logUpdates($new_data, $old_data, $bean, $obj_type){
        $diff = $this->arrayDiff($new_data, $old_data);
        if(!empty($diff)){
            $diff['id'] = $bean->getId();
            $this->createUserLog(UserLogItem::ACTION_TYPE_UPDATE, $obj_type, $diff);
        }
    }
    public function arrayDiff($new_data, $old_data){
        $new_data = array_map('json_encode', $new_data);
        $old_data = array_map('json_encode', $old_data);
        $diff = array_diff_assoc($new_data, $old_data);
        $diff = array_map('json_decode', $diff);
        return $diff;
    }

    protected function getEm()
    {
        if (!$this->em) {
            $this->em = new NaxEntityManager($this->getDoctrine()->getManager());
        }

        if (!$this->em->isOpen()) {//maybe not need
            $this->resetEm();
        }
        return $this->em;
    }
    protected function resetEm()//not work
    {
        $this->getDoctrine()->resetManager();
        $this->em = new NaxEntityManager($this->getDoctrine()->getManager());
        return $this->em;
    }

    /**
     * Get a user from the Security Token Storage.
     * @return mixed
     */
    public function getUser()
    {
        if (empty($this->user)) {
            $this->user = parent::getUser();
        }
        return $this->user;
    }

    /**
     * @param $name
     */
    protected function getRepository($name)
    {
        return $this->getEm()->getRepository($name);
    }

    /**
     * @param $id
     * @return Entity
     */
    protected function getReference($class, $id)
    {
        return $this->getEm()->getReference($class, $id);
    }

    /*protected function getRepositoryHelper(){

        if(empty($this->repo_helper)){
            $brand = $this->getContainer()->getParameter('brand');
            $this->repo_helper = BaseEntity::class($brand, 'RepositoryHelper', 'Modules');
        }
        return $this->repo_helper;
    }*/

    public function setDepAccessFilters($preFilters = [], $user = false)
    {
        if (!$user) {
            $user = $this->getUser();
        }

        if ($user->getPlatform()) {
            $preFilters['platform'] = $user->getPlatform();
        }

        if ($user->getIsHead()) {
            $preFilters['departmentIds'] = $user->getAccessibleDepartmentIds();
            $preFilters['isHead'] = true;
        }
        else {
            $preFilters['managerIds'] = [$user->getId()];
        }
        return $preFilters;
    }

    protected function validate($entity, $create_response = true)
    {
        $validator = $this->get('validator');
        $errors = $validator->validate($entity);

        $errorMessages = [];
        foreach ($errors as $error) {
            $errorMessages[] = [
                'key' => $error->getPropertyPath(),
                'mes' => $error->getMessage(),
            ];
        }

        if (empty($errorMessages)) {
            return true;
        }

        if (count($errorMessages) == 1) {
            $err = $errorMessages[0];
            if($create_response){
                $this->getFailedJsonResponse($err, "Invalid data. {$err['mes']}");
            }
            return false;
        }

        if($create_response){
            $this->getFailedJsonResponse($errorMessages, 'Invalid data');
        }
        return false;
    }

    public function export($rows, $includes = [])
    {
        if (is_array($rows) || $rows instanceof ArrayCollection || $rows instanceof PersistentCollection) {
            foreach ($rows as $i => $item) {
                if (method_exists($item, 'export')) {
                    $rows[$i] = $item->export($includes);
                }
            }

            if (!$rows instanceof PersistentCollection) {
                $rows = array_values($rows);
            }
        }
        return $rows;
    }

    /*
     * Create stream for csv export and set headers
     */
    public function exportCsvInit($filename = 'export')
    {
        $now = gmdate("D, d M Y H:i:s");
        $this->response = new Response();

        // disable caching
        $this->response->headers->set('Expires', 'Tue, 03 Jul 2001 06:00:00 GMT');
        $this->response->headers->set('Cache-Control', 'max-age=0, no-cache, must-revalidate, proxy-revalidate');
        $this->response->headers->set('Last-Modified', "{$now} GMT");

        // force download
        $this->response->headers->set('Content-Type', 'application/force-download');
        $this->response->headers->set('Content-Type', 'application/octet-stream');
        $this->response->headers->set('Content-Type', 'application/download');

        // disposition / encoding on response body
        $this->response->headers->set('Content-Disposition', "attachment;filename=\"{$filename}.csv\"");
        $this->response->headers->set('Content-Transfer-Encoding', 'binary');

        // Send headers before outputting anything
        $this->response->sendHeaders();

        ob_start();
        ob_implicit_flush(true);
        ob_end_flush();

        $this->streem_response = fopen("php://output", 'w');
        fputcsv($this->streem_response, $this->csvTitles, ',');
        ob_flush();
    }

    /*
     * Build export array
     */
    public function exportCsv($rows = [])
    {
        if (empty($rows)) {
            return null;
        }

        $columns = [];
        if (!empty($this->csvTitles)) {
            $columns = array_keys($this->csvTitles);
        }

        foreach ($rows as $row) {
            $row_out = $row;
            if (!empty($columns)) {
                $row_out = [];
                foreach ($columns as $k) {
                    if (isset($row[$k])) {
                        $row_out[$k] = iconv("UTF8", "CP1251//IGNORE", $row[$k]);
                        if (isset($this->csvAliases[$k])) {
                            if (is_array($row_out[$k])){
                                $val = $row_out[$k];
                            }
                            else {
                                $val = explode(',', $row_out[$k]);
                            }

                            $val = array_map('trim', $val);
                            $out = [];
                            foreach ($val as $element) {
                                if(isset($this->csvAliases[$k][$element])){
                                    $element = $this->csvAliases[$k][$element];
                                }
                                else if(isset($this->csvAliases[$k][(int)$element])){
                                    $element = $this->csvAliases[$k][(int)$element];
                                }
                                $out[] = $element;
                            }
                            $row_out[$k] = implode(',', $out);
                        }
                    }
                    else {
                        $row_out[$k] = '';
                    }
                }
            }
            fputcsv($this->streem_response, $row_out, ',');
            ob_flush();
        }
    }

    /*
     * Send export file
     */
    public function exportCsvClose()
    {
        fclose($this->streem_response);
        $this->response->setContent(ob_get_clean());
        $this->response->setStatusCode(200, 'Ok');

        $this->response->send();
        exit();
    }

    /*
     * Do all export magic
     */
    public function exportCsvWorker($repo, $repo_func, $params)
    {
        $params['offset'] = 0;
        $params['limit'] = self::CSV_EXPORT_LIMIT;

        if(!is_array($this->csvAliases)){
            $this->setExportDefaults();
        }

        $filename = $this->exportCsvFileName($params, $this->csvTimeField);

        $this->exportCsvTitles($params);
        $this->exportCsvInit($filename);

        $result = $repo->$repo_func($params);

        $this->exportCsv($result['rows']);

        if (!empty($result['filtered'])) {
            $result['total'] = $result['filtered'];
        }

        if (!empty($result['total']) && $result['total'] > $params['limit']) {
            for ($params['offset'] = $params['limit']; $params['offset'] < $result['total']; $params['offset'] += $params['limit']) {
                $result = $repo->$repo_func($params);
                $this->exportCsv($result['rows']);
                if (!empty($result['filtered'])) $result['total'] = $result['filtered'];
            }
        }

        $filters = isset($params['filters']) ? $params['filters'] : [];
        $this->exportCsvLog($filters, $result['total']);

        $this->exportCsvClose();
    }

    /*
     * Construct file name for export
     */
    function exportCsvFileName($params = [], $time_field = 'created')
    {
        $filename = $this->getExportFileName();
        $filename = str_replace(' ', '_', $filename);
        $filters = (!empty($params['filters'])) ? $params['filters'] : [];
        if (!empty($filters[$time_field . 'From'])) {
            $from = str_replace(' 00:00:00', '', $filters[$time_field . 'From']);
            $filename .= '_' . $from;
            if (!empty($filters[$time_field . 'To'])) {
                $to = date('Y-m-d H:i:s', strtotime('-1 day',
                    strtotime($filters[$time_field . 'To'])
                ));
                if ($filters[$time_field . 'From'] != $to) {
                    $to = str_replace(' 00:00:00', '', $to);
                    $filename .= '_' . $to;
                }
            }
        }
        return $filename;
    }

    /*
     * Get columns for csv export
     */
    protected function exportCsvTitles($params = [])
    {
        $titles_lbl = $params['exportCsv'];
        $titles_lbl = json_decode($titles_lbl, true);

        if (!empty($titles_lbl)) {
            $this->csvTitles = $titles_lbl;
        }
        return $this->csvTitles;
    }

    protected function createEvent($manager, $message, $title, $objectType = null, $objectId = null)
    {
        $event = Event::createInst();
        $event->setManager($manager);
        $event->setMessage($message);
        $event->setTitle($title);
        $event->setObjectType($objectType);
        $event->setObjectId($objectId);
        return $event;
    }

    protected function addEvent($bean, $title, $message = false)
    {
        $manager = $bean->getManager();
        if(empty($manager) || (($this->getUser() instanceof Manager) && $manager->getId() == $this->getUser()->getId())){
            return false;
        }

        $objectType = get_class($bean);
        $objectType = substr(strrchr($objectType, '\\'), 1);
        if(!in_array($objectType, ['Lead', 'Client'])){
            return false;
        }

        if(is_array($message)){
            if($title == 'updated'){// kostyl fixes
                unset($message['managerId']);
                unset($message['manager']);
                if(empty($message)){
                    return false;
                }
            }
            $message = ucfirst($title).' '.implode(', ', array_keys($message));
        }
        if(!$message){
            $message = ucfirst($title);
        }

        if($message && strlen($message)>140){
            $message = mb_substr($message, 0, 140).'..';
        }

        $objectId = $bean->getId();
        $event = $this->createEvent($manager, $message, $objectType.' '.$title, $objectType, $objectId);
        $this->getEm()->persist($event);
        $this->getEm()->flush();
        return true;
    }

    protected function getAbsoluteUploadPath($path = '/failed_leads/', $filename = false)
    {
        $root = $this->getParameter('uploads_directory').$path;
        if (substr($root, 0, 1) != '/') {
            $root = $this->getParameter('kernel.root_dir') . DIRECTORY_SEPARATOR . '..'
                . DIRECTORY_SEPARATOR . 'web' . DIRECTORY_SEPARATOR . $root;
        }
        $root = rtrim($root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $root .= ($filename ? $filename : '');
        return $root;
    }

    protected function getAbsoluteDocumentPath(Document $document = null)
    {
        $filename = ($document ? $document->getPath() : '');
        $root = $this->getAbsoluteUploadPath('/documents/', $filename);
        return $root;
    }

    protected function getIdNamesArray($entity, $field = 'name', $where = [])
    {
        $field = ucfirst($field);
        $result = [];
        $repo = $this->getRepository($entity);
        if(empty($where)){
            $types = $repo->findAll();
        }
        else{
            $types = $repo->findBy($where);
        }

        foreach ($types as $type) {
            $result[$type->getId()] = $type->{'get' . $field}();
        }

        return $result;
    }

    protected function genEnum($enum, $old_column_name = 'name'/*, $where = []*/)
    {
        $res = [];
        asort($enum);
        foreach ($enum as $k => $v) {
            /*foreach ($where as $fld => $val) {
                if($enum[$fld]!=$val){
                    continue;
                }
            }*/
            $res[] = [
                'id' => $k,
                'alias' => $v,
                $old_column_name => $v,
            ];
        }
        return $res;
    }

    /*
     * Save info Managers log export operation
     */
    private function exportCsvLog($filters, $count)
    {
        //do logging export
        foreach (array_keys($filters) as $k) {
            if (isset($this->csvAliases[$k])) {
                foreach ($filters[$k] as $key => $val)
                    if (isset($this->csvAliases[$k][$val])) $filters[$k][$key] = $this->csvAliases[$k][$val];
            }
        }

        $this->createUserLog(UserLogItem::ACTION_TYPE_EXPORT, $this->getExportObjectType(), [
            'count' => $count,
            'filters' => $filters,
        ]);
    }

    private function getExportFileName()
    {
        if (empty($this->exportFileName)) {
            $name = get_called_class();
            $name = explode('\\', $name);
            $name = array_pop($name);
            $name = str_replace('Controller', '', $name);
            $this->exportFileName = $name;
        }

        return ucfirst($this->exportFileName);
    }

    private function getExportObjectType()
    {
        $filename = $this->getExportFileName();
        $type = isset(self::$objectTypeAliases[$filename]) ? self::$objectTypeAliases[$filename] : $filename;
        $type.='s';
        $type = str_replace('ss', 's', $type); //maybe not need
        return $type;
    }
}
