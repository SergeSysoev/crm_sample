<?php

namespace NaxCrmBundle\Repository;

use Doctrine\ORM\EntityRepository;

class CompanyStatisticRepository extends AbstractEntityRepository
{
    public function getStatistics($params)
    {
        $filters = [];
        $order = [];
        if (isset($params['filters'])) $filters = $params['filters'];
        if (isset($params['order'])) $order = $params['order'];

        $qb = $this->createQueryBuilder('s')
            ->select('COUNT(s.id)');
        $total = $qb->getQuery()->getSingleScalarResult();

        $filters_array = [
            'departmentId'    => ['type' => 'multilist', 'table' => 's', 'field' => 'department', 'skipNoSelect' => true,],
            'id'              => ['type' => 'integer',   'table' => 's',],
            'companyProfit'   => ['type' => 'integer',   'table' => 's',],
            'numberOfTraders' => ['type' => 'integer',   'table' => 's',],
            'ordersOpened'    => ['type' => 'integer',   'table' => 's',],
            'ordersClosed'    => ['type' => 'integer',   'table' => 's',],
            'date'            => ['type' => 'daterange', 'table' => 's',],
        ];

        $this->setMultiValues($filters_array, $qb);

        //apply filters
        $this->buildFilters($filters_array, $filters, $qb);

        $qb->select('COUNT(s.id) filtered');
        $count = $qb->getQuery()->getScalarResult()[0]['filtered'];

        $this->setPagination($params, $qb);

        if ($count) {
            $qb->select(
                's.id',
                'IDENTITY(s.department) departmentId',
                's.companyProfit',
                's.numberOfTraders',
                's.ordersOpened',
                's.ordersClosed',
                "DATE_FORMAT(s.date, '%Y-%m-%d') date"
            );

            $this->setOrder($order, $filters_array, $qb);

            $data = $qb->getQuery()->getResult();
        } else {
            $data = [];
        }
        return [
            'total'     => (int) $total,
            'filtered'  => (int) $count,
            'filters'   => $this->genFilters($filters_array),
            'rows'      => $data,
        ];
    }
}
