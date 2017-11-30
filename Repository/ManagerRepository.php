<?php

namespace NaxCrmBundle\Repository;

use NaxCrmBundle\Entity\Deposit;

class ManagerRepository extends AbstractEntityRepository
{
    public function getList($params)
    {
        $filters = [];
        $order = [];
        if (isset($params['filters'])) $filters = $params['filters'];
        if (isset($params['order'])) $order = $params['order'];

        $filters_array = [
            'id'           => ['type' => 'integer',   'table' => 'm',],
            'name'         => ['type' => 'string',    'table' => 'm',],
            'email'        => ['type' => 'string',    'table' => 'm',],
            'phone'        => ['type' => 'string',    'table' => 'm',],
            'skype'        => ['type' => 'string',    'table' => 'm',],
            'departmentId' => ['type' => 'multilist', 'table' => 'm',],
            'isHead'       => ['type' => 'multilist', 'table' => 'm',],
            'aclRoles'     => ['type' => 'multilist', 'table' => 'r', 'field' => 'id',],
            'blocked'      => ['type' => 'multilist', 'table' => 'm',],
        ];
        $pre_filters = [
            'blocked'    => ['table' => 'm',],
        ];

        $qb = $this->createQueryBuilder('m')
            ->select('COUNT(m.id)');


        if (!isset($filters['blocked'])) {//just for paginated list
            $this->preFilters['blocked'] = false;
        }

        $this->chkDepAccess($qb, 'm');
        $this->setupPreFilters($pre_filters, $qb);

        $total = $qb->getQuery()->getSingleScalarResult();

        $qb->leftJoin("m.aclRoles", 'r');

        $this->setMultiValues($filters_array, $qb);

        $this->buildFilters($filters_array, $filters, $qb);

        $qb->select('COUNT(DISTINCT m.id) filtered');
        $count = $qb->getQuery()->getScalarResult();
        $count = $count ? $count[0]['filtered'] : 0;

        $this->setPagination($params, $qb);

        $qb->select(
            'm.id',
            'm.name',
            'm.email',
            'm.phone',
            'm.departmentId',
            'm.skype',
            'm.isHead',
            'GROUP_CONCAT(r.id) aclRoles',
            "IF(m.blocked = 0, 'Unblocked', 'Blocked') blocked"
        )->groupBy('m.id');

        $this->setOrder($order, $filters_array, $qb);

        \NaxCrmBundle\Debug::$messages[]=$qb->getQuery()->getSQL();
        $data = $qb->getQuery()->getResult();

        $filters_array['status'] = ['type' => 'multilist', 'values' => ['All','Blocked', 'Unblocked'],];

        return [
            'total' => (int)$total,
            'filtered' => (int)$count,
            'filters' => $this->genFilters($filters_array),
            'rows' => $data,
        ];
    }

    public function getAdminManager()
    {
        $qb = $this->createQueryBuilder('m');
        $qb->leftJoin('m.aclRoles', 'r');
        $qb->where('m.isHead=1');
        $qb->andWhere('r.id=1');
        $qb->orderBy('m.id', 'ASC');
        return $qb->getQuery()->getSingleResult();
    }

    public function getManagerDashboardData($params)
    {
        /*return [
            'total' => 0,
            'filtered' => 0,
            'filters' => [],
            'rows' => [],
        ];*/
        $filters = [];
        $order = [];
        if (isset($params['filters'])) $filters = $params['filters'];
        if (isset($params['order'])) $order = $params['order'];
        if(empty($filters['currency'])) $filters['currency'] = ['USD'];

        $filters_array = [
            'id' => ['type' => 'integer', 'table' => 'm',],
            'name' => ['type' => 'string', 'table' => 'm',],
            'email' => ['type' => 'string', 'table' => 'm',],
        ];

        $this->preFilters['blocked'] = false;
        $pre_filters = [
            'blocked' => ['table'=> 'm', 'value' => false],
        ];

        $qb = $this->createQueryBuilder('m');

        $this->chkDepAccess($qb, 'm');
        $this->setupPreFilters($pre_filters, $qb);

        $qb->select('COUNT(m.id)');
        $total = $qb->getQuery()->getSingleScalarResult();

        $qb->leftJoin('m.clients', 'c');
        $qb->leftJoin('c.deposits', 'd', 'WITH', 'd.status = :status AND d.currency = :currency')
                ->setParameter('status', Deposit::STATUS_PROCESSED)
                ->setParameter('currency', $filters['currency']);

        $qb->select(
            'm.id',
            'SUM(IF(d.id IS NULL, 0, d.amount)) as deposits'
        )->groupBy('m.id');

        $result = $qb->getQuery()->getArrayResult();
        $deposits = [];
        foreach ($result as $item)
            $deposits[$item['id']] = $item['deposits'];

        //----------------------------

        $pre_w = ' AND m.blocked = 0';
        if (isset($this->preFilters['departmentIds'])) {
            if (is_array($this->preFilters['departmentIds'])) $this->preFilters['departmentIds'] = implode(',', $this->preFilters['departmentIds']);
            $pre_w .= ' AND m.department_id IN (' . $this->preFilters['departmentIds'] . ')';
        }

        $w = $this->buildFilters($filters_array, $filters);
        $w .= $pre_w;

        $h = [];
        if (isset($filters['callsFrom'])) $h[] = ' calls >= ' . $filters['callsFrom'];
        if (isset($filters['callsTo'])) $h[] = ' calls <= ' . $filters['callsTo'];
        $h = !empty($h) ? ' HAVING ' . implode(' AND ', $h) : '';

        $filters_array['calls'] = ['type' => 'integer'];
        $filters_array['currency'] = ['type' => 'string', 'table' => 'd',];

        $pagination = '';
        if (!empty($request['limit'])) {
            $pagination .= "LIMIT ";
            if (!empty($request['offset'])) {
                $pagination .= $request['offset'] . ',';
            }
            $pagination .= $request['limit'];
        }

        $ordr = '';
        if (!empty($order['column'])) {
            $ordr = $order['column'];
            $dir = !empty($order['dir']) ? $order['dir'] : 'ASC';
            $table = isset($filters_array[$order['column']]['table']) ? $filters_array[$order['column']]['table'] . '.' : '';
            $ordr = " ORDER BY {$table}{$ordr} {$dir} ";
        }

        $sql = "
SELECT
    m.id,
    m.email,
    m.name,
    COALESCE((evt.evts + cmnt.cmnts), 0) AS calls,
    COALESCE(COUNT(DISTINCT c.id), 0) as clients
FROM
    managers m
LEFT JOIN
    clients c ON m.id = c.manager_id AND (c.deleted_at IS NULL)
LEFT JOIN
    (SELECT e.manager_id, count(e.manager_id) as evts FROM `events` e WHERE (e.deleted_at IS NULL) AND (e.time_followup IS NOT NULL) GROUP BY e.manager_id) as evt ON m.id = evt.manager_id
LEFT JOIN
    (SELECT cm.manager_id,  count(cm.manager_id) as cmnts FROM comments cm) as cmnt ON m.id = cmnt.manager_id
WHERE
    m.deleted_at IS NULL AND
	{$w}
GROUP BY
    m.id
{$h}
{$ordr}
{$pagination}";
        // \NaxCrmBundle\Debug::$messages[]=$sql;

        $this->setSql($sql);
        $data = $this->fetchAll();

        foreach ($data as &$item) {
            $item['deposits'] = $deposits[$item['id']];
        }
        $filter_list = $this->genFilters($filters_array);

        return [
            'total' => (int)$total,
            'filtered' => count($data),
            'filters' => $filter_list,
            'rows' => $data,
        ];
    }
}
