<?php

namespace NaxCrmBundle\Repository;

class WithdrawalRepository extends AbstractEntityRepository
{
    public function getList($params)
    {
        $filters = [];
        $order = [];
        if (isset($params['filters'])) $filters = $params['filters'];
        if (isset($params['order'])) $order = $params['order'];

        $qb = $this->createQueryBuilder('w')
            ->join('w.client', 'c')
            ->join('w.account', 'a')
            ->select('COUNT(w.id)');
        $pre_filters = [
            'clientIds'       => ['table' => 'w', 'field' => 'clientId',],
//            'accountType'       => ['table' => 'c', 'op' => '='],
            'platform'        => ['table' => 'c', 'field' => 'platform'],
        ];

        $this->chkDepAccess($qb);
        $this->setupPreFilters($pre_filters, $qb);
        $total = $qb->getQuery()->getSingleScalarResult();

        // $qb->join('w.account', 'a');
        // ->leftJoin('p.paymentSystem', 'ps');

        $filters_array = [
            'id'                => ['type' => 'integer',   'table' => 'w',],
            'amount'            => ['type' => 'integer',   'table' => 'w',],
            'firstname'         => ['type' => 'string',    'table' => 'c',],
            'lastname'          => ['type' => 'string',    'table' => 'c',],
            // 'paymentSystemName' => ['type' => 'string',    'table' => 'ps',],
            'lastDigits'        => ['type' => 'int',       'table' => 'w',],
            'currency'          => ['type' => 'multilist', 'table' => 'a',],
            'status'            => ['type' => 'multilist', 'table' => 'w', 'field' => 'status',],
            'created'           => ['type' => 'daterange', 'table' => 'w',],
            'updated'           => ['type' => 'daterange', 'table' => 'w',],
            'comment'           => ['type' => 'string',    'table' => 'w',],
        ];

        $this->setMultiValues($filters_array, $qb);

        //apply filters
        $this->buildFilters($filters_array, $filters, $qb);

        $qb->select('COUNT(w.id) filtered');
        $count = $qb->getQuery()->getScalarResult()[0]['filtered'];
        $this->setPagination($params, $qb);


        $qb->select(
            'w.id',
            'w.amount',
            'w.clientId',
            'c.firstname',
            'c.lastname',
            // "ps.paymentSystemName paymentSystemName",
            'w.lastDigits',
            'a.currency',
            'w.status',
            'w.comment',
            "DATE_FORMAT(w.created, '%Y-%m-%d %H:%i:%s') created",
            "DATE_FORMAT(w.updated, '%Y-%m-%d %H:%i:%s') updated"
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
