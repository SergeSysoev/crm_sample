<?php

namespace NaxCrmBundle\Repository;

class LogItemRepository extends AbstractEntityRepository
{

    public function getSystemLogs($params)
    {
        $filters = [];
        $order = [];
        if (isset($params['filters'])) $filters = $params['filters'];
        if (isset($params['order'])) $order = $params['order'];

        $qb = $this->createQueryBuilder('l')
            ->select('COUNT(l.id)');
        $total = $qb->getQuery()->getSingleScalarResult();

        $filters_array = [
            'id'          => ['table' => 'l', 'type' => 'integer'],
            'time'        => ['table' => 'l', 'type' => 'daterange',],
            'managerId'   => ['table' => 'l', 'type' => 'multilist',],
            'channel'     => ['table' => 'l', 'type' => 'multilist',],
            'level'       => ['table' => 'l','type' => 'multilist',],
            'url'         => ['table' => 'l','type' => 'string',],
            'referer'     => ['table' => 'l','type' => 'string',],
            'jsonPayload' => ['table' => 'l','type' => 'string',],
            'message'     => ['table' => 'l','type' => 'string',],
        ];

        $qb->leftJoin('l.manager', 'm');

        $this->setMultiValues($filters_array, $qb);
        $this->buildFilters($filters_array, $filters, $qb);

        $qb->select('COUNT(DISTINCT l.id) filtered');
        $count = $qb->getQuery()->getSingleScalarResult();

        $qb->select(
            'l.id',
            "DATE_FORMAT(l.time, '%Y-%m-%d %H:%i:%s') time",
            'l.managerId',
            'l.channel',
            'l.url',
            'l.referer',
            'l.level',
            'l.jsonPayload',
            'l.message'
        )->groupBy('l.id');

        $this->setOrder($order, $filters_array, $qb);
        $this->setPagination($params, $qb);

        $data = $qb->getQuery()->getResult();

        return [
            'total'     => (int) $total,
            'filtered'  => (int) $count,
            'filters'   => $this->genFilters($filters_array),
            'rows'      => $data,
        ];
    }
}
