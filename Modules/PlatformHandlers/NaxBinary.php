<?php

namespace NaxCrmBundle\Modules\PlatformHandlers;

use NaxCrmBundle\Modules\SlackBot;
use NaxCrmBundle\Modules\RepositoryHelper;
use Symfony\Component\HttpKernel\Exception\HttpException;

class NaxBinary
{
    protected $api = [
        'getAccountsByEmail' => [
            'url' => '',
            'context' => [],
        ],
        'addAccount' => [
            'url' => '',
            'context' => [],
        ],
        'upgradeAccount' => [
            'url' => '',
            'context' => [],
        ],
        'setAccount' => [
            'url' => '',
            'context' => [],
        ],
        'blockAccount' => [
            'url' => '',
            'context' => [],
        ],
        'unblockAccount' => [
            'url' => '',
            'context' => [],
        ],
        'getAccount' => [
            'url' => '',
            'context' => [],
        ],
        'setDeposit' => [
            'url' => '',
            'context' => [],
        ],
        'setWithdrawal' => [
            'url' => '',
            'context' => [],
        ],
        'addWithdrawal' => [
            'url' => '',
            'context' => [],
        ],
        'getOrders' => [
            'url' => '',
            'context' => [],
        ],
        'addDocument' => [
            'url' => '',
            'context' => [],
        ],
        'setDocStatus' => [
            'url' => '',
            'context' => [],
        ],
        'getLoginHash' => [
            'url' => '/api/traders/get/login/hash?owner=company&password=Aa1234',
            'context' => null,
        ],
        'getTime' => [
            'url' => '/api/get_time',
            'context' => null,
        ],
    ];

    protected $config = [];
    protected $container = null;

    /**
     * @var \PDO
     */
    protected $db, $stmt = null;

    public function __construct($container)
    {
        $this->container = $container;
        $this->config = $this->container->getParameter('trading_platform');
        return $this;
    }

    protected function getConnection()
    {
        if (!$this->db) {
            try{
                $db_cfg = "mysql:host={$this->config['db']['host']};port={$this->config['db']['port']};dbname={$this->config['db']['name']};charset=utf8";
                $this->db = new \PDO(
                    $db_cfg,
                    $this->config['db']['user'], $this->config['db']['pass'],[
                        \PDO::ATTR_TIMEOUT => 8,
                        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                ]);
            } catch(\Exception $e) {
                SlackBot::send('Platform DB connection error', [
                    'err' => $e->getMessage(),
                    'db_cfg' => $db_cfg,
                ], SlackBot::ERR);
                throw new HttpException(503, "Platform DB connection error \n".$e->getMessage());
            }
        }
        return $this->db;
    }

    protected function setSql($q){
        $this->stmt = $this->getConnection()->prepare($q);
        try{
            $this->stmt->execute();
        } catch(\Exception $e) {
            SlackBot::send('Platform DB query error', [
                    'err' => $e->getMessage(),
                    'q' => $q,
                ], SlackBot::ERR);
            throw new HttpException(503, "Platform DB query error \n".$e->getMessage());
        }
    }
    protected function fetchAll($flag = \PDO::FETCH_ASSOC){
        return $this->stmt->fetchAll($flag);
    }
    protected function fetch($flag = \PDO::FETCH_ASSOC){
        return $this->stmt->fetch($flag);
    }

    /**
     * @return array
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    public function sendRequest($url, $context)
    {
        $url = $this->config['host'].$url;
        $post = ($context['http']['method']=='POST');
        $headers = !empty($context['http']['header'])?explode("\r\n", $context['http']['header']):[];
        $content = !empty($context['http']['content'])?$context['http']['content']:'';
        // $context = $context ? stream_context_create($context) : null;

        $ch = $this->prepareCurl($url, $content, $post, $headers);

        try{
            $res = curl_exec($ch);
            // $res = file_get_contents($this->config['host'] . $url, null, $context);
            if($err = curl_errno($ch)){
                SlackBot::send('Platform connection error', [
                    'err' => curl_error($ch),
                    'con_cfg' => [$url, $context],
                ], SlackBot::ERR);
                \NaxCrmBundle\Debug::$messages[] = ['exception' => 'ERROR: connection error: '.curl_error($ch)];
                return false;
            }
            if ($decoded = json_decode($res, true)) {
                $res = $decoded;
            }
        }
        catch(\Exception $e){
            // throw  new HttpException(400, "Bad response from Platform \n".$e->getMessage());
            \NaxCrmBundle\Debug::$messages[] = ['exception' => 'ERROR: '.$e->getMessage()];
            return false;
        }
        \NaxCrmBundle\Debug::$messages[] = ['response' => $res];

        if($res==='false') $res = false;

        return $res;
    }
    public function prepareCurl($url, $data = [], $post = false, $headers = []){
        $ch = curl_init();
        if($post){
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
        else{
           $url.=(strpos($url, '?') === FALSE ? '?' : '&').$data;
        }
        \NaxCrmBundle\Debug::$messages[] = [$url => $data];

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 8);
        curl_setopt($ch, CURLOPT_TIMEOUT, 8);
        return $ch;
    }

    public function setContent($context, $data) {
        // \NaxCrmBundle\Debug::$messages[]=['data'=>$data];
        $context['http']['content'] = http_build_query($data);
        $context['http']['header'] =
            (empty($context['http']['header']) ? '' : $context['http']['header'])
            . "Content-type: application/x-www-form-urlencoded\r\n"
            . "Content-Length: " . strlen($context['http']['content']) . "\r\n";
        return $context;
    }


    /**
     * @param string $email
     * @return array
     */
    public function getAccountsByEmail($email)
    {
        $this->setSql("SELECT * FROM traders WHERE email = '{$email}'");
        return $this->fetchAll();
    }

    /*public function getAccountsByEmail($email)
    {
        $context = $this->api['getAccountsByEmail']['context'];
        return $this->sendRequest($this->api['getAccountsByEmail']['url'] . '?email=' . urlencode($email), $context);
    }*/

    public function getBalancesByEmail($email)
    {
        //traders/get_by_email
    }

    public function addAccount($account)
    {
        $context = $this->api['addAccount']['context'];
        $context['http']['method'] = empty($context['http']['method']) ? 'POST' : $context['http']['method'];
        $context = $this->setContent($context, $account);
        return $this->sendRequest($this->api['addAccount']['url'], $context);
    }

    public function upgradeAccount($account)
    {
        $context = $this->api['upgradeAccount']['context'];
        $context['http']['method'] = empty($context['http']['method']) ? 'POST' : $context['http']['method'];
        $context = $this->setContent($context, $account);
        return $this->sendRequest($this->api['upgradeAccount']['url'], $context);
    }

    public function getAccount($accountId)
    {
        $context = $this->api['getAccount']['context'];
        return $this->sendRequest($this->api['getAccount']['url'] . '?login=' . $accountId, $context);
    }

    public function setAccount($account)
    {
        $context = $this->api['setAccount']['context'];
        $context['http']['method'] = empty($context['http']['method']) ? 'POST' : $context['http']['method'];
        $context = $this->setContent($context, $account);
        return $this->sendRequest($this->api['setAccount']['url'], $context);
    }

    public function blockAccount($account)
    {
        $context = $this->api['blockAccount']['context'];
        $context['http']['method'] = empty($context['http']['method']) ? 'POST' : $context['http']['method'];
        $context = $this->setContent($context, $account);
        return $this->sendRequest($this->api['blockAccount']['url'], $context);
    }

    public function unblockAccount($account)
    {
        $context = $this->api['unblockAccount']['context'];
        $context['http']['method'] = empty($context['http']['method']) ? 'POST' : $context['http']['method'];
        $context = $this->setContent($context, $account);
        return $this->sendRequest($this->api['unblockAccount']['url'], $context);
    }

    public function setDeposit($deposit)
    {
        $context = $this->api['setDeposit']['context'];
        $context['http']['method'] = empty($context['http']['method']) ? 'POST' : $context['http']['method'];
        $context = $this->setContent($context, $deposit);
        return $this->sendRequest($this->api['setDeposit']['url'], $context);
    }

    public function addWithdrawal($withdrawal)
    {
        $context = $this->api['addWithdrawal']['context'];
        $context['http']['method'] = empty($context['http']['method']) ? 'POST' : $context['http']['method'];
        $context = $this->setContent($context, $withdrawal);
        return $this->sendRequest($this->api['addWithdrawal']['url'], $context);
    }

    public function setWithdrawal($withdrawal)
    {
        $context = $this->api['setWithdrawal']['context'];
        $context['http']['method'] = empty($context['http']['method']) ? 'POST' : $context['http']['method'];
        $context = $this->setContent($context, $withdrawal);
        return $this->sendRequest($this->api['setWithdrawal']['url'], $context);
    }

    public function getAllOrders($filters)
    {
        $context = $this->api['getOrders']['context'];
        $query = http_build_query($filters);
        $query = $query ? '?' . $query : '';
        return $this->sendRequest($this->api['getOrders']['url'] . $query, $context);
    }

    public function getLoginHash($accountId)
    {
        return $this->sendRequest($this->api['getLoginHash']['url']. '&hash_login=' . $accountId, null);
    }

    public function getTime()
    {
        return $this->sendRequest($this->api['getTime']['url'], null);
    }

    public function addDocument($document, $document_path = false)
    {
        $context = $this->api['addDocument']['context'];
        $context['http']['method'] = empty($context['http']['method']) ? 'POST' : $context['http']['method'];
        $context = $this->setContent($context, $document);
        $result = $this->sendRequest($this->api['addDocument']['url'], $context);
        return $result;
    }

    public function setDocStatus($document)
    {
        $context = $this->api['setDocStatus']['context'];
        $context['http']['method'] = empty($context['http']['method']) ? 'POST' : $context['http']['method'];
        $context = $this->setContent($context, $document);
        return $this->sendRequest($this->api['setDocStatus']['url'], $context);
    }

    public function upClientsOpenTradeTime(array $accs = [])
    {
        return [];
    }

    /**
     * @param array $params
     * @return array
     */
    public function getDocuments($params = [])
    {
        $q = "
            SELECT *
            FROM `docs`
            WHERE 1";
        $stmt = $this->getConnection()->prepare($q);
        $stmt->execute();
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return $rows;
    }

    public function getTotalOrders($params = [])
    {
        return [];
    }

    /**
     * @param array $params
     * @return array
     */
    public function getAccountFromDB($params = [])
    {
        $filters_array = [
            'login' => ['type' => 'int', 'table' => 't'],
            'email' => ['type' => 'string', 'table' => 't'],
        ];
        $filters = isset($params['filters']) ? $params['filters'] : [];
        // Debug::$messages['filters'] = $filters;
        $limit_arr = array();
        if (isset($params['offset'])) {
            $limit_arr[] = $params['offset'];
        }
        $limit_arr[] = (!empty($params['limit'])) ? $params['limit'] : 30;
        $limit = implode(', ', $limit_arr);

        // date_default_timezone_set('Europe/Moscow');//fix timeZone filters

        $w = RepositoryHelper::buildFilters($filters_array, $filters);

        $q = "
            SELECT *
            FROM traders t
            WHERE {$w}
            LIMIT $limit
        ";
        // Debug::$messages[] = $q;
        $this->setSql($q);
        $rows = $this->fetchAll();

        return $rows;
    }

    public function getRiskMan($params = [])
    {
        return [];
    }

    public function getSymbols(){
        $currency = [];
        $res =  $this->sendRequest('/api/symbols_list_get', null);
        foreach ($res as $cur) {
            if(empty($cur['category']) || $cur['category']=='currency'){
                $currency[]=$cur['symbol'];
            }
        }
        return $currency;

        $to_cur = 'EUR';
        $rates = [];

        $exes = $this->getExchanges();

        foreach ($currency as $cur) {
            $ex = $this->getEx($exes, $cur, $to_cur);
            $rates[$cur] = $ex;
        }

        $rates[$to_cur] = 1;
        return $rates;
    }

    public function getExchangeRates($params)
    {
        $to_cur = empty($params['currency'])? 'USD' : $params['currency'];
        $rates = [];
        $q = "SELECT DISTINCT t.`currency` FROM traders t WHERE t.`currency` <> '{$to_cur}'";
        $this->setSql($q);
        $currency = $this->fetchAll();

        $exes = $this->getExchanges();

        foreach ($currency as $cur) {
            $cur = $cur['currency'];

            $ex = $this->getEx($exes, $cur, $to_cur);
            $rates[$cur] = $ex;
        }

        $rates[$to_cur] = 1;
        return $rates;
    }


    /**
     * Helpers
     */

    public function getExchanges(){
        return $exes = [];
        $url = 'http://52.57.18.199:8090';
        $ch = $this->prepareCurl($url.'/currency', http_build_query(['cur'=>'all']));
        try{
            $res = curl_exec($ch);
            if($err = curl_errno($ch)){
                SlackBot::send('ExchangeRates provider connection error', curl_error($ch), SlackBot::ERR);
                throw  new HttpException(503, "ExchangeRates provider connection error \n".curl_error($ch));
                return [];
            }
            $exes = json_decode($res, true);
        }
        catch(\Exception $e){
            SlackBot::send('Bad response from ExchangeRates provider', $e->getMessage(), SlackBot::ERR);
            throw  new HttpException(503, "Bad response from ExchangeRates provider \n".$e->getMessage());
            return [];
        }
        return $exes;
    }
    public function getEx($exes, $cur, $to_cur = 'USD'){
        return 1;
        $ex = 0;
        if(!empty($exes[$cur.$to_cur])){
            $ex = $exes[$cur.$to_cur]['bid'];
        }
        else if(!empty($exes[$to_cur.$cur])){
            $ex = round(1/$exes[$to_cur.$cur]['bid'],5);
        }
        return $ex;
    }

    public function genEquivalent($amount_fld, $currency_fld, $ex_rates){
        $cases = [];
        foreach ($ex_rates as $c => $k) {
            $cases[]=" WHEN '{$c}' THEN ({$amount_fld}*$k) ";
        }
        $s = "IF({$amount_fld} IS NULL,0, CASE {$currency_fld} \n".implode("\n", $cases)."\n ELSE 0 END)";
        return $s;
    }

    protected function quote($value, $type = null)
    {
        if (is_numeric($value) && $type != 'string') {
            return (int)$value;
        }
        if (is_null($value)) {
            return 'null';
        }
        if (is_array($value)) {
            foreach ($value as &$item) {
                $item = $this->quote($item);
            }
            return '(' . join(',', $value) .')';
        }
        return $this->getConnection()->quote($value, $type);
    }

    protected function where($filters, $OR = false)
    {
        $where = array();
        if (!$filters) {
            return '';
        }
        foreach ($filters as $field => $value) {
            if (is_numeric($field) && is_string($value)) {
                $where[] = $value;
                CONTINUE;
            }
            if (is_array($value) && !empty($value)) {
                $where[] = "$field IN ".$this->quote($value);
                CONTINUE;
            }
            if (is_array($value) && empty($value)) {
                $where[] = "$field IS NULL ";
                CONTINUE;
            }
            if (is_numeric($value)){
                $where[] = "$field = ".(int)$value;
                CONTINUE;
            }
            if (empty($value)){
                CONTINUE;
            }
            if (strpos('=!<>', $value[0])!==false) {
                list($operand, $value) = explode(' ', $value, 2);
                $where[] = "$field $operand" . $this->quote($value);
                CONTINUE;
            }
            if (strpos($value, '%') && (preg_match('#^%[^%].+#', $value) || preg_match('#.+[^%]%$#', $value))) {
                $where[] = "$field LIKE " . $this->quote($value);
            }
            $where[] = "$field = " . $this->quote($value);
        }
        if (empty($where)) {
            return '';
        }
        $word = $OR ? ' OR ' : ' AND ';
        return implode($word, $where);
    }
}