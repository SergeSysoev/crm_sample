<?php
/*!
 * @file
 * Helper for repositories
 *
 * Implement common functions for queries
 */
namespace NaxCrmBundle\Modules;

use Doctrine\ORM\QueryBuilder;

class RepositoryHelper
{
    /*!
     * Convert incorrect filter types
     *
     * @param array  $v Filter array
     * @return array $v
     */
    public static function prepareFilter($v)
    {
        if (!empty($v['type'])) {
            $v['type'] = str_replace('int', 'integer', $v['type']);
            $v['type'] = str_replace('integereger', 'integer', $v['type']);
        }
        return $v;
    }
    /*!
     * Mysql escape strings and etc
     *
     * @param array  $v Filter val
     * @return array $v
     */
    public static function prepareFilterValues($v) //TODO: USE IT!
    {
        $v = mysql_real_escape_string($v);
        return $v;
    }

    public static function getAntiFilter(&$filters, $fld)
    {
        $antiFilter = '';
        if (isset($filters['!'.$fld])) {
            $antiFilter = ' NOT ';
            $filters[$fld] = $filters['!'.$fld];
            unset($filters['!'.$fld]);
        }
        return $antiFilter;
    }

    /*!
     * Prepare columns item for output array
     *
     * @param string        $columns        String with comma-separated columns
     * @param string|false  $alias_prefix   Prefix for column alias
     * @return array        $columns        Prepared columns
     */
    public static function prepareColumns($columns, $alias_prefix = false)
    {
        if(!is_array($columns)){
            $columns = str_replace(' ', '', $columns);
            $columns = explode(',', $columns);
        }
        if(count($columns)==0){
            return [];
        }
        foreach ($columns as $k => &$v) {
            if(!is_array($v)){
                $v = [
                    'data' => $v,
                    'alias' => $v,
                    'filtered' => true,
                    'sorting' => true,
                ];
            }
            else{
                if(!is_numeric($k)){
                    $v = array_merge($v, [
                        'data' => $k,
                        'alias' => $k,
                        // 'filtered' => true,
                        // 'sorting' => true,
                    ]);
                }
            }
            if(!empty($alias_prefix)){
                $v['alias'] = $alias_prefix.'_'.$v['alias'];
            }
        }
        $columns = array_values($columns);
        return $columns;
    }

    /*!
     * Rebuild and fix $filters_array for buildFilters() function structure
     *
     * @param array  $filters_array
     * @return array $ret               Prepared array for buildFilters()
     */
    public static function genFilters($filters_array)
    {
        $ret = [];
        foreach ($filters_array as $k => $v) {

            if (empty($v['type'])) continue;

            $v = self::prepareFilter($v);

            $ret[$k] = [
                'filterName' => $k,
                'type' => $v['type'],
            ];

            if (!empty($v['values'])) {
                if (!is_array($v['values'])) $v['values'] = explode(',', $v['values']);
                $ret[$k]['values'] = $v['values'];
            }
        }
        return $ret;
    }

    /*!
     * Function build query conditions with filters.
     *
     * Use only for standard(common) filters.
     * Else do not add 'table' key for filters_array item and handle this item manually
     *
     * @param array              $filters_array list of all available filters properties
     * @param array              $filters       list of applied filters and it values
     * @param QueryBuilder|false $qb            instance of QueryBuilder
     * @return QueryBuilder      $qb
     */
    public static function buildFilters($filters_array, $filters, &$qb = false)
    {
        if (!$qb) {
            return self::buildSimpleFilters($filters_array, $filters);
        }

        /* @var QueryBuilder $qb */
        foreach ($filters_array as $k => $v) {

            $v = self::prepareFilter($v);

            if (empty($v['table'])) continue;

            $antiFilter = self::getAntiFilter($filters, $k);

            if (empty($v['field'])) $v['field'] = $k;

            switch ($v['type']) {
                case 'integer':
                case 'daterange':
                    if (isset($filters[$k . 'From'])) {
                        $qb->andWhere($qb->expr()->gte($v['table'] . '.' . $v['field'], ":{$k}From"))->setParameter(
                            $k . 'From', $filters[$k . 'From']
                        );
                    }
                    if (isset($filters[$k . 'To'])) {
                        $qb->andWhere($qb->expr()->lte($v['table'] . '.' . $v['field'], ":{$k}To"))->setParameter(
                            $k . 'To', $filters[$k . 'To']
                        );
                    }
                    break;
                case 'multilist':
                    if (isset($filters[$k])) {
                        if (!is_array($filters[$k])) {
                            $filters[$k] = explode(',', $filters[$k]);
                        }

                        $andNull = '';
                        $inx = array_search('no_select', $filters[$k]);
                        if ($inx !== false) {
                            unset($filters[$k][$inx]);
                            $andNull = "{$v['table']}.{$v['field']} IS NULL";
                            if (!empty($filters[$k])) $andNull = 'OR ' . $andNull;
                        }

                        if (!empty($filters[$k])) $qb->andWhere($v['table'] . ".{$v['field']} {$antiFilter} IN (:{$k}) $andNull")->setParameter($k, $filters[$k]);
                        elseif (!empty($andNull)) $qb->andWhere($andNull);
                    }
                    break;
                default:
                    if (isset($filters[$k])) {
                        if (isset($v['isNull']) && $v['isNull'])
                            $qb->andWhere($v['table'] . ".{$v['field']} {$antiFilter} LIKE :{$k} OR {$v['table']}.{$v['field']} IS NULL")->setParameter($k, $filters[$k]);
                        else
                            $qb->andWhere($v['table'] . ".{$v['field']} {$antiFilter} LIKE :{$k}")->setParameter($k, $filters[$k]);
                    }
                    break;
            }
        }

        return $qb;
    }

    /*!
     * Function build AND-based result for selected filters, for non QueryBuilder queries
     *
     * If $filters is empty array function return just 1=1 condition
     *
     * @param array     $filters_array  list of all available filters properties
     * @param array     $filters        list of applied filters and it values
     * @return string   $w              return compiled where condition without WHERE keyword
     */
    public static function buildSimpleFilters($filters_array, $filters)
    {
        $w = ' 1=1 ';
        foreach ($filters_array as $k => $v) {

            $v = self::prepareFilter($v);

            if (empty($v['table'])) continue;

            if (empty($v['field'])) $v['field'] = $k;

            $v = self::prepareFilter($v);

            $antiFilter = self::getAntiFilter($filters, $k);

            if($v['type'] == 'integer'){
                if (isset($filters[$k])){
                    $w.= " AND {$v['table']}.`{$v['field']}` = {$filters[$k]}";
                }
                if (isset($filters[$k.'From'])){
                    $w.= " AND {$v['table']}.`{$v['field']}` >= {$filters[$k.'From']}";
                }
                if (isset($filters[$k.'To'])){
                    $w.= " AND {$v['table']}.`{$v['field']}` <= {$filters[$k.'To']}";
                }
            }
            elseif($v['type'] == 'daterange'){ //some filters not work with timestamp value, other do not work with date-time formatted filter!
                if (isset($filters[$k.'From'])){
//                    $w.= " AND {$v['table']}.`{$v['field']}` >= ".((new \DateTime())->createFromFormat('Y-m-d H:i:s', $filters[$k.'From'])->getTimestamp());
                    $w.= " AND {$v['table']}.`{$v['field']}` >= '{$filters[$k.'From']}'";
                }
                if (isset($filters[$k.'To'])){
//                    $w.= " AND {$v['table']}.`{$v['field']}` <= ".((new \DateTime())->createFromFormat('Y-m-d H:i:s', $filters[$k.'To'])->getTimestamp());
                    $w.= " AND {$v['table']}.`{$v['field']}` <= '{$filters[$k.'To']}'";
                }
            }
            elseif($v['type'] == 'timerange'){ //some filters not work with timestamp value, other do not work with date-time formatted filter!
                if (isset($filters[$k.'From'])){
                   $w.= " AND {$v['table']}.`{$v['field']}` >= ".((new \DateTime())->createFromFormat('Y-m-d H:i:s', $filters[$k.'From'])->getTimestamp());
                }
                if (isset($filters[$k.'To'])){
                   $w.= " AND {$v['table']}.`{$v['field']}` <= ".((new \DateTime())->createFromFormat('Y-m-d H:i:s', $filters[$k.'To'])->getTimestamp());
                }
            }
            elseif($v['type'] == 'multilist'){
                if (isset($filters[$k])) {
                    if(!is_array($filters[$k])){
                        $filters[$k] = explode(',', $filters[$k]);
                    }
                    $filters[$k] = implode('\',\'', $filters[$k]);
                    if(!empty($v['uppercase'])){//TODO: add to QB version
                        $filters[$k] = strtoupper($filters[$k]);
                        $w .= " AND UPPER({$v['table']}.`{$v['field']}`) {$antiFilter} IN ('{$filters[$k]}')";
                    }
                    else{
                        $w .= " AND {$v['table']}.`{$v['field']}` {$antiFilter} IN ('{$filters[$k]}')";
                    }
                }
            }
            else{
                if (isset($filters[$k])) {
                    $w .= " AND {$v['table']}.`{$v['field']}` {$antiFilter} LIKE '{$filters[$k]}'";
                }
            }
        }
        return $w;
    }

    /*!
     * Build LIMIT part of query if exists
     *
     * @param array         $params from request with limit and offset keys
     * @param QueryBuilder  $qb     current instance
     * @return QueryBuilder $qb
     */
    public static function setPagination($params, &$qb)
    {
        if (isset($params['limit'])) $qb->setMaxResults($params['limit']);
        if (isset($params['offset'])) $qb->setFirstResult($params['offset']);

        return $qb;
    }

    /*!
     * Build ORDER part of query if exists
     *
     * If $qb == false return simple sql with order part of query
     *
     * @param array                 $order          from request with order keys
     * @param QueryBuilder|false    $qb             instance of QueryBuilder
     * @param array                 $filters_array  list of all available filters properties
     * @return QueryBuilder|string
     */
    public static function setOrder($order, $filters_array = [], &$qb = false)
    {
        if (empty($order['dir'])) $order['dir'] = 'ASC';
        $order['dir'] = strtoupper($order['dir']);
        $dir = in_array($order['dir'], ['ASC', 'DESC']) ? $order['dir'] : 'ASC';

        if (!empty($order['column'])) {
            if (isset($filters_array[$order['column']]) && isset($filters_array[$order['column']]['table'])) {
                if (empty($filters_array[$order['column']]['field'])) {
                    $filters_array[$order['column']]['field'] = $order['column'];
                }
                $order['column'] = "{$filters_array[$order['column']]['table']}.{$filters_array[$order['column']]['field']}";
            }
            if($qb){
                $qb->orderBy($order['column'], $dir);
                return $qb;
            }
            else{
                return "ORDER BY {$order['column']} {$dir}";
            }
        }

        return $qb ?? '';
    }

    /*!
     * Get values for multilist filters and set it for $filters_array
     *
     * @param array         $filters_array  list of all available filters properties
     * @param QueryBuilder  $qb             current instance
     * @param array|false   $group_array    with fields that need values
     * @return array        $filters_array
     */
    public static function setMultiValues(&$filters_array, &$qb, $group_array = false)
    {
        $select = [];
        if (!$group_array) {
            $group_array = [];
            foreach ($filters_array as $key => $val) {
                if ($val['type'] == 'multilist') $group_array[] = $key;
            }
        }

        foreach ($group_array as $k) {
            if (!isset($filters_array[$k])) continue;
            if (empty($filters_array[$k]['table'])) continue;
            if (empty($filters_array[$k]['field'])) $filters_array[$k]['field'] = $k;
            if (isset($filters_array[$k]['skipNoSelect']))
                $select[] = "GROUP_CONCAT(DISTINCT {$filters_array[$k]['table']}.{$filters_array[$k]['field']} SEPARATOR ';') Z{$k}Z";
            else $select[] = "GROUPCONCATCOL({$filters_array[$k]['table']}.{$filters_array[$k]['field']}) Z{$k}Z";

        }
        if (!empty($select)) {
            $select = implode(', ', $select);
            $qb->select($select);
            $filteredData = $qb->getQuery()->getScalarResult()[0];
            foreach ($filteredData as $key => $val) {
                $k = trim($key, 'Z');
                if(!empty($filters_array[$k]['uppercase'])){
                    $val = strtoupper($val);
                }
                $filters_array[$k]['values'] = explode(';', $val);
                sort($filters_array[$k]['values'], SORT_REGULAR);
            }
        }

        return $filters_array;
    }

    /*!
     * Build Pre-Filters query part
     *
     * It is WHERE part of query with parameters which sets from backend
     *
     * @param array         $filters_array      list of all available preFilters parameters
     * @param QueryBuilder  $qb                 instance of QueryBuilder
     * @param array         $preFilters         list of applied preFilters
     * @param EntityManager $em                 instance ofEntityManager
     * @return QueryBuilder $qb
     */
    public static function setupPreFilters($filters_array, &$qb, $preFilters, &$em)
    {
        if (isset($preFilters['showDeleted'])) {
            if ($em->getFilters()->isEnabled('softdeleteable')){
                $em->getFilters()->disable('softdeleteable');
            }
        }
        else{
            $em->getFilters()->enable('softdeleteable');
        }

        if (empty($filters_array) or empty($preFilters)) return true;

        foreach ($filters_array as $filterName => &$filterData) {
            if (empty($filterData['table'])) continue;
            if (!isset($preFilters[$filterName])) continue;
            if (empty($filterData['field'])) $filterData['field'] = $filterName;
            if (empty($filterData['op'])) $filterData['op'] = 'IN';
            if (empty($filterData['value'])) $filterData['value'] = $preFilters[$filterName];
//            if (is_array($filterData['value'])) $filterData['value'] = implode(',', $filterData['value']);

            if($filterData['op']=='IS'){
                if(!in_array($filterData['value'], ['NULL','NOT NULL'])){
                    $filterData['value'] = 'NULL';
                }
                $qb->andWhere("{$filterData['table']}.{$filterData['field']} {$filterData['op']} {$filterData['value']}");
            }
            else if (isset($filterData['isNull']) && $filterData['isNull']) {
                $qb->andWhere("{$filterData['table']}.{$filterData['field']} {$filterData['op']} (:{$filterName}) OR {$filterData['table']}.{$filterData['field']} IS NULL")
                    ->setParameter($filterName, $filterData['value']);
            }
            else {
                $qb->andWhere("{$filterData['table']}.{$filterData['field']} {$filterData['op']} (:{$filterName})")->setParameter($filterName, $filterData['value']);
            }
        }

        return $qb;
    }

    /*!
     * Build Check Department Access Filters query part
     *
     * It is WHERE part of query with parameters which sets from backend
     *
     * @param QueryBuilder  $qb                 instance of QueryBuilder
     * @param array         $preFilters         list of applied preFilters
     * @return QueryBuilder $qb
     */
    public static function chkDepAccess(&$qb, $preFilters = [], $table = false){
        if(empty($preFilters)){
            return $qb;
        }
        if(!$table){
            $table = $qb->getRootAliases()[0];
        }

        $flds = [
            'departmentIds' => 'departmentId',
            'managerIds' => 'managerId',
        ];
        if($table == 'm'){
            $flds['managerIds'] = 'id';
        }

        if (isset($preFilters['departmentIds'])) {
            // \NaxCrmBundle\Debug::$messages['departmentIds']=$preFilters['departmentIds'];
            $qb->andWhere("{$table}.{$flds['departmentIds']} IN (:departmentIds) OR {$table}.{$flds['departmentIds']} IS NULL")->setParameter('departmentIds', $preFilters['departmentIds']);
        }
        if (isset($preFilters['managerIds'])) {
            if (isset($preFilters['isHead'])) {
                // \NaxCrmBundle\Debug::$messages['managerIds']=$preFilters['managerIds'];
                $qb->andWhere("{$table}.{$flds['managerIds']} IN (:managerIds) OR {$table}.{$flds['managerIds']} IS NULL")
                    ->setParameter('managerIds', $preFilters['managerIds']);
            }
            else {
                $qb->andWhere("{$table}.{$flds['managerIds']} IN (:managerIds)")->setParameter('managerIds', $preFilters['managerIds']);
            }
        }

        return $qb;
    }
    /*!
     * Build Check Department Access Filters query part
     *
     * It is WHERE part of query with parameters which sets from backend
     *
     * @param string  $preWhere    pre where condition line
     * @param array   $preFilters  list of applied preFilters
     * @return string $preWhere
     */
    public static function chkSimpleDepAccess(&$preWhere, $preFilters = [], $table = false){
        if(empty($preFilters)){
            return $preWhere;
        }
        $flds = [
            'departmentIds' => 'department_id',
            'managerIds' => 'manager_id',
        ];
        if($table == 'm'){
            $flds['managerIds'] = 'id';
        }
        if (isset($preFilters['departmentIds'])) {
            $departmentIds = implode(',', $preFilters['departmentIds']);
            $preWhere .= "AND {$table}.{$flds['departmentIds']} IN ({$departmentIds}) ";
        }
        if (isset($preFilters['managerIds'])) {
            $managerIds = implode(',', $preFilters['managerIds']);
            if (isset($preFilters['isHead'])) {
                $preWhere .= "AND ({$table}.{$flds['managerIds']} IN ({$managerIds}) OR {$table}.{$flds['managerIds']} IS NULL) ";
            }
            else {
                $preWhere .= "AND {$table}.{$flds['managerIds']} IN ({$managerIds}) ";
            }
        }

        return $preWhere;
    }
}