<?php

namespace NaxCrmBundle\Repository;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use NaxCrmBundle\Entity\Account;
use Symfony\Component\HttpFoundation\Request;
use NaxCrmBundle\Modules\RepositoryHelper;
use NaxCrmBundle\Modules\SlackBot;
use NaxCrmBundle\Entity\BaseEntity;

/**
 * AbstractEntityRepository
 */
abstract class AbstractEntityRepository extends EntityRepository
{
    protected $preFilters = [];
    protected $repo_helper = null;
    protected $stmt = null;

    protected function setSql($q){
        try{
            $this->stmt = $this->_em->getConnection()->prepare($q);
            return $this->stmt->execute();
        } catch(\PDOException $e) {
            SlackBot::send('DB query error', [
                'err' => $e->getMessage(),
                'q' => $q,
            ], SlackBot::ERR);
            throw new HttpException(500, "DB query error: \n".$e->getMessage());
        }
        return false;
    }
    protected function fetchAll($flag = \PDO::FETCH_ASSOC){
        return $this->stmt->fetchAll($flag);
    }
    protected function fetch($flag = \PDO::FETCH_ASSOC){
        return $this->stmt->fetch($flag);
    }

    public function findAll(array $orderBy = null)
    {
        return $this->findBy([], $orderBy);
    }

    public function findByIndexed($criteria, $index, $limit = null, $offset = null)
    {
        if (!$limit && !$offset) {
            $rows = $this->findBy($criteria);
        } else {
            $rows = $this->findBy($criteria, [$index], $limit, $offset);
        }
        $result = [];
        $method = 'get' . ucfirst($index);
        foreach ($rows as $row) {
            $result[$row->$method()] = $row;
        }
        return $result;
    }

    public function setPreFilters($preFilters)
    {
        $this->preFilters = array_merge($this->preFilters, $preFilters);
        return $this;
    }

    public $fieldMap = [];

    public function quote($value, $type = null)
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
            return '(' . join(',', $value) . ')';
        }
        return $this->getEntityManager()->getConnection()->quote($value, $type);
    }

    public function where($filters, $OR = false)
    {
        $where = array();
        if (!$filters) {
            return '';
        }
        foreach ($filters as $filterName => $value) {
            $field = empty($this->fieldMap[$filterName]) ? $filterName : $this->fieldMap[$filterName];
            if (is_numeric($field) && is_string($value)) {
                $where[] = $value;
                CONTINUE;
            }
            if (is_array($value) && !empty($value)) {
                $where[] = "$field IN " . $this->quote($value);
                CONTINUE;
            }
            if (is_array($value) && empty($value)) {
                $where[] = "$field IS NULL ";
                CONTINUE;
            }
            if (is_numeric($value)) {
                $where[] = "$field = " . (int)$value;
                CONTINUE;
            }
            if (empty($value)) {
                CONTINUE;
            }
            if (strpos('=!<>', $value[0]) !== false) {
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

    protected function getTableResults($limit, $offset, $where = [], $filters = [])
    {
        $meta = $this->getClassMetadata();
        list($identifier) = $meta->getIdentifier();
        $qb = $this->createQueryBuilder('t')->select("COUNT(t.$identifier)");

        if ($where = $this->where($where)) {
            $qb->where($where);
        }
        $total = (int)$qb->getQuery()->getSingleScalarResult();

        if ($where = $this->where($filters)) {
            $qb->where($where);
        }
        $filtered = (int)$qb->getQuery()->getSingleScalarResult();
        if ($filtered) {
            $qb
                ->setFirstResult($offset)
                ->setMaxResults($limit);
            $rows = $qb->select('t')->getQuery()->getArrayResult();
        } else {
            $rows = [];
        }
        return compact('rows', 'total', 'filtered');
    }

    public function getTable(Request $request, $where = [])
    {
        if (!$limit = $request->query->get('limit')) {
            $limit = null;
        }
        if (!$offset = $request->query->get('offset')) {
            $offset = null;
        }
        if (!$filters = $request->query->get('filters')) {
            $filters = [];
        }

        return $this->getTableResults($limit, $offset, $where, $filters);
    }

    protected function getRepositoryHelper(){

        if(empty($this->repo_helper)){
            $this->repo_helper = BaseEntity::class(false, 'RepositoryHelper', 'Modules');
        }
        return $this->repo_helper;
    }

    /*
     * Build filters for WHERE query section by user filter data
     *
     * @param array $filters_array list of all available filters properties
     * @param array $filters list of applied filters and it values
     * @param QueryBuilder $qb instance of QueryBuilder if not false
     */
    public function buildFilters($filters_array, $filters, &$qb = false)
    {
        $repo_helper = $this->getRepositoryHelper();
        return $repo_helper::buildFilters($filters_array, $filters, $qb);
    }

    public function setupPreFilters($pre_filters, &$qb)
    {
        $repo_helper = $this->getRepositoryHelper();
        return $repo_helper::setupPreFilters($pre_filters, $qb, $this->preFilters, $this->_em);
    }

    /*
     * Build LIMIT part of query if exists
     *
     * @param array $params from request
     * @param QueryBuilder $sq current instance
     */
    public function setPagination($params, &$qb)
    {
        $repo_helper = $this->getRepositoryHelper();
        return $repo_helper::setPagination($params, $qb);
    }

    /*
     * Build ORDER part of query if exists
     *
     * @param array $params from request
     * @param QueryBuilder $sq current instance
     * @param array $filters_array list of all available filters properties
     */
    public function setOrder($order, $filters_array, &$qb)
    {
        $repo_helper = $this->getRepositoryHelper();
        return $repo_helper::setOrder($order, $filters_array, $qb);
    }

    /*
     * Get values for multilist filters and set it for $filters_array
     *
     * @param QueryBuilder $qb current instance
     * @param array $filters_array list of all available filters properties
     * @param array $group_array with fields that need values
     */
    public function setMultiValues(&$filters_array, &$qb, $group_array = false)
    {
        $repo_helper = $this->getRepositoryHelper();
        return $repo_helper::setMultiValues($filters_array, $qb, $group_array);
    }

    /*
     * Clean filters array for frontend
     *
     * @param array $filters_array list of all available filters properties
     */
    public function genFilters($filters_array)
    {
        $repo_helper = $this->getRepositoryHelper();
        return $repo_helper::genFilters($filters_array);
    }
    public function getAntiFilter(&$filters, $fld)
    {
        $repo_helper = $this->getRepositoryHelper();
        return $repo_helper::getAntiFilter($filters, $fld);
    }

    /*!
     * Build Check Department Access Filters query part
     *
     * It is set WHERE part of query with parameters which sets from backend
     *
     * @param QueryBuilder|string  $qb  instance of QueryBuilder or pre where condition line
     * @param string  $table
     * @return QueryBuilder|string $qb
     */
    public function chkDepAccess(&$qb, $table = 'c')
    {
        if (empty($this->preFilters)){
            return $qb;
        }

        $repo_helper = $this->getRepositoryHelper();
        if($qb instanceof QueryBuilder){
            return $repo_helper::chkDepAccess($qb, $this->preFilters, $table);
        }
        return $repo_helper::chkSimpleDepAccess($qb, $this->preFilters, $table);//$qb -> $preWhere(string)
    }
}