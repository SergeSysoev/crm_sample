<?php

namespace NaxCrmBundle\Repository;

class UserLogItemRepository extends AbstractEntityRepository
{
    public function getUserLogs($params)
    {
        $filters = [];
        $order = [];
        if (isset($params['filters'])) $filters = $params['filters'];
        if (isset($params['order'])) $order = $params['order'];

        $qb = $this->createQueryBuilder('u')
            ->select('COUNT(u.id)');

        $pre_filters = [];

        $this->chkDepAccess($qb, 'u');
        $this->setupPreFilters($pre_filters, $qb);

        $total = $qb->getQuery()->getSingleScalarResult();

        $filters_array = [
            'id'            => ['type' => 'integer',   'table'=>'u'],
            'managerId'     => ['type' => 'multilist', 'table'=>'u'],
            'departmentId'  => ['type' => 'multilist', 'table'=>'u'],
            'time'          => ['type' => 'daterange', 'table'=>'u'],
            'objectId'      => ['type' => 'integer',   'table'=>'u'],
            'objectType'    => ['type' => 'multilist', 'table'=>'u'],
            'actionType'    => ['type' => 'multilist', 'table'=>'u'],
            'userType'      => ['type' => 'multilist', 'table'=>'u'],
            'clientIp'      => ['type' => 'string',    'table'=>'u'],
            'jsonPayload'   => ['type' => 'string',    'table'=>'u'],
        ];

        $this->setMultiValues($filters_array, $qb);

        //apply filters
        $this->buildFilters($filters_array, $filters, $qb);

        $qb->select('COUNT(u.id) filtered');

        $count = $qb->getQuery()->getScalarResult()[0]['filtered'];

        $this->setPagination($params, $qb);

        $qb->select(
            'u.id',
            'u.managerId',
            'u.departmentId',
            // "DATE_FORMAT(u.time, '%Y-%m-%d %H:%i:%s') time",
            "DATE_ADD(u.time, ({$params['gmt_offset']}), 'SECOND') time",
            'u.objectId',
            'u.objectType',
            'u.actionType',
            'u.userType',
            'u.clientIp',
            'u.jsonPayload'
        );

        $this->setOrder($order, $filters_array, $qb);

        $data = $qb->getQuery()->getResult();

        return [
            'total'     => (int) $total,
            'filtered'  => (int) $count,
            'filters'   => $this->genFilters($filters_array),
            'rows'      => $data,
        ];
    }
}
