<?php

namespace NaxCrmBundle\Repository;

use Doctrine\ORM\EntityRepository;

class BookmarkRepository extends AbstractEntityRepository
{
    public function getList($params)
    {
        $filters = [];
        $order = [];
        if (isset($params['filters'])) $filters = $params['filters'];
        if (isset($params['order'])) $order = $params['order'];

        $qb = $this->createQueryBuilder('b')
            ->leftJoin('b.manager','m')
            ->select('COUNT(b.id)');

        $pre_filters = [
            'managerIds'    => ['table' => 'b',  'field' => 'manager',],
            'departmentsIds' => ['table' => 'm', 'field' => 'departmentId']
        ];

        $this->setupPreFilters($pre_filters, $qb);

        $total = $qb->getQuery()->getSingleScalarResult();

        $filters_array = [
            'id'            => ['type' => 'integer',   'table' => 'b',],
            'name'          => ['type' => 'string',    'table' => 'b',],
            'table'         => ['type' => 'string',    'table' => 'b',],
            'url'           => ['type' => 'string',    'table' => 'b',],
            'bookmark'      => ['type' => 'string',    'table' => 'b',],
            'bookmarkGroup' => ['type' => 'string',    'table' => 'b',],
            'created'       => ['type' => 'daterange', 'table' => 'b',],
        ];

        //apply filters
        $this->buildFilters($filters_array, $filters, $qb);

        $qb->select('COUNT(b.id) filtered');

        $filteredData = $qb->getQuery()->getScalarResult()[0];
        $filtered = $filteredData['filtered'];

        $qb->select(
            'b.id',
            'b.name',
            'b.table',
            'b.url',
            'b.bookmark',
            'b.bookmarkGroup',
            "DATE_FORMAT(b.created, '%Y-%m-%d %H:%i:%s') created"
        );

        $this->setPagination($params, $qb);
        $this->setOrder($order, $filters_array, $qb);

        $data = $qb->getQuery()->getResult();

        return [
            'total'     => (int) $total,
            'filtered'  => (int) $filtered,
            'filters'   => $this->genFilters($filters_array),
            'rows'      => $data,
        ];
    }
}
