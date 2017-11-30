<?php

namespace NaxCrmBundle\Repository;

class DepositRepository extends AbstractEntityRepository
{
    public function getList($params)
    {
        $columns = $filters = $order = [];
        if (isset($params['filters'])) $filters = $params['filters'];
        if (isset($params['order'])) $order = $params['order'];

        $qb = $this->createQueryBuilder('d')
            ->join('d.client', 'c')
            // ->join('d.account', 'a')
            ->select('COUNT(d.id)');
        if (!empty($this->preFilters)) {

            if (isset($this->preFilters['clientIds'])) {
                $columns = [
                    'id',
                    'amount',
                    'currency',
                    'created',
                    'paymentSystem',
                    'status',
                    'comment',
                    // 'message',
                ];
                $qb->andWhere('d.clientId IN (:clientIds)')->setParameter('clientIds', $this->preFilters['clientIds']);
            }
            if(isset($this->preFilters['platform'])) {
                $columns = [
                    'id',
                    'amount',
                    'currency',
                    'created',
                    'paymentSystem',
                    'status',
                    'comment',
                    // 'message',
                ];
                $qb->andWhere('c.platform = :platform')->setParameter('platform', $this->preFilters['platform']);
            }
        }
        $this->chkDepAccess($qb);

        $total = $qb->getQuery()->getSingleScalarResult();

        $filters_array = [
            'id'              => ['type' => 'integer',   'table' => 'd',],
            'amount'          => ['type' => 'integer',   'table' => 'd',],
            'firstname'       => ['type' => 'string',    'table' => 'c',],
            'lastname'        => ['type' => 'string',    'table' => 'c',],
            'comment'         => ['type' => 'string',    'table' => 'd',],
            'message'         => ['type' => 'string',    'table' => 'd',],
            'created'         => ['type' => 'daterange', 'table' => 'd',],
            'currency'        => ['type' => 'multilist', 'table' => 'd', 'field' => 'currency',],
            'status'          => ['type' => 'multilist', 'table' => 'd', 'field' => 'status',],
            'paymentSystem'   => ['type' => 'multilist', 'table' => 'd', 'skipNoSelect' => true,],
            'cardLastNumbers' => ['type' => 'string',    'table'=>'d', 'field'=>'lastDigits'],
            'payMethod'       => ['type' => 'multilist', 'table'=>'d',],
        ];

        $this->setMultiValues($filters_array, $qb);

        //apply filters
        $this->buildFilters($filters_array, $filters, $qb);

        $qb->select('COUNT(d.id) filtered');
        $count = $qb->getQuery()->getScalarResult()[0]['filtered'];

        $this->setPagination($params, $qb);

        $qb->select(
            'd.id',
            'IDENTITY(d.paymentSystem) paymentSystem',
            'd.clientId',
            'c.firstname',
            'c.lastname',
            'c.managerId',
            'c.departmentId',
            'd.amount',
            'd.currency',
            // 'a.externalId AS accountExtId',
            'd.status',
            'd.comment',
            'd.message',
            'd.walletData',
            'd.payMethod',
            'd.lastDigits AS cardLastNumbers',
            "DATE_FORMAT(d.created, '%Y-%m-%d %H:%i:%s') created"
        );


        $sort_array = $filters_array;
        $sort_array['country'] = ['type' => 'string', 'table' => 'co', 'field' => 'name',];
        $sort_array['saleStatus'] = ['type' => 'string', 'table' => 'ss', 'field' => 'statusName',];
        $sort_array['clientId'] = ['type' => 'int', 'table' => 'd'];
        $this->setOrder($order, $sort_array, $qb);

//         Debug::$messages[] = $qb->getQuery()->getSQL();
        $data = $qb->getQuery()->getResult();
        $columns = $this->getRepositoryHelper()::prepareColumns($columns, 'DEPOSIT');
        return [
            'total' => (int)$total,
            'filtered' => (int)$count,
            'filters' => $this->genFilters($filters_array),
            'rows' => $data,
            'columns' => $columns,
        ];
    }

    public function getCardNumbers($clientId = false){
        $data = [];
        $w = 'd.`last_digits` IS NOT NULL AND d.`last_digits` <> ""';
        if($clientId){
            $w.= ' AND d.`client_id` = '.$clientId;
        }
        $q = "
            SELECT
                DISTINCT(d.`last_digits`) AS last_digits
            FROM `deposits` AS d
            WHERE {$w}
        ";
        $this->setSql($q);
        $res = $this->fetchAll();
        foreach ($res as $v) {
            $data[]=[
                'id' => $v['last_digits'],
                'cardLastNumbers' => $v['last_digits'],
            ];
        };
        return $data;
    }

    public function getCurrencies(){
        $data = [];
        $q = "
            SELECT
                DISTINCT(d.`currency`) AS currency
            FROM `deposits` AS d
        ";
        $this->setSql($q);
        $res = $this->fetchAll();
        foreach ($res as $v) {
            $data[]=[
                'id' => $v['currency'],
                'alias' => empty($v['currency'])?'Unknown':$v['currency'],
            ];
        };
        return $data;
    }
}
