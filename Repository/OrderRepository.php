<?php

namespace NaxCrmBundle\Repository;

use NaxCrmBundle\Debug;
use NaxCrmBundle\Entity\Account;

class OrderRepository extends AbstractEntityRepository
{
    public function getList($params)
    {
        $filters = [];
        $order = [];
        if (isset($params['filters'])) $filters = $params['filters'];
        if (isset($params['order'])) $order = $params['order'];

        $qb = $this->createQueryBuilder('o')->select('COUNT(o.id)');

        if (!empty($this->preFilters)) {
            $qb->join('o.client', 'c');
        }

        if (isset($this->preFilters['cpi'])) {
            $qb->leftJoin('o.account', 'ac', 'WITH', 'ac.type = :typeLive')->setParameter('typeLive', Account::TYPE_LIVE)
                ->andWhere('ac.id IS NULL');
        } elseif (isset($this->preFilters['cpa'])) {
            $qb->join('o.account', 'ac')->andWhere('ac.type = :typeLive')->setParameter('typeLive', Account::TYPE_LIVE);
        }

        $pre_filters = [
            'clientId'      => ['table' => 'o', 'op' => '='],
        ];

        $this->chkDepAccess($qb);
        $this->setupPreFilters($pre_filters, $qb);

        Debug::$messages[] = $qb->getQuery()->getSQL();
        $count = $total = $qb->getQuery()->getSingleScalarResult();

        $filters_array = [
            'id'        => ['type' => 'integer',   'table' => 'o',],
            'firstname' => ['type' => 'string',    'table' => 'c',],
            'lastname'  => ['type' => 'string',    'table' => 'c',],
            'email'     => ['type' => 'string',    'table' => 'c',],
            'timeOpen'  => ['type' => 'daterange', 'table' => 'o', 'field' => 'timeOpen',],
            'time_open' => ['type' => 'daterange', 'table' => 'o', 'field' => 'timeOpen',],
        ];

        $this->buildFilters($filters_array, $filters, $qb);

        $qb->select('COUNT(o.id) filtered');
//            Debug::$messages[] = $qb->getQuery()->getSQL();
        $count = $qb->getQuery()->getScalarResult()[0]['filtered'];

        $this->setPagination($params, $qb);

        $qb->select(
            'o.id',
            'c.firstname',
            'c.lastname',
            'o.amount',
            'o.extId',
            'o.info',
            'o.payout',
            'o.payoutRate',
            'o.priceClose',
            'o.priceOpen',
            'o.priceStrike',
            'o.profit',
            'o.state',
            'o.symbol',
            "DATE_FORMAT(o.timeClose, '%Y-%m-%d %H:%i:%s') timeClose",
            "DATE_FORMAT(o.timeOpen, '%Y-%m-%d %H:%i:%s') timeOpen",
            "o.type",

            'c.email',
            'o.accountId AS login',
            '\'\' AS acc_group',
            '\'\' AS time',
            'o.priceStrike AS price_open1',
            'o.priceClose AS price_close',
            'o.payoutRate AS winrate',
            'o.flags',
            // 'o.priceStrike2 AS price_open2',
            '(UNIX_TIMESTAMP(o.timeExpiration) - UNIX_TIMESTAMP(o.timeOpen)) AS binary_option',
            'o.timeExpiration',
            "UNIX_TIMESTAMP(o.timeExpiration) AS expiration",
            "UNIX_TIMESTAMP(o.timeOpen) AS created",
            "DATE_FORMAT(o.timeOpen, '%Y-%m-%d %H:%i:%s') AS time_open"
        );

        if (isset($order['column'])) {
            $sort_array                 = $filters_array;
            $sort_array['login']        = ['table' => 'o', 'field' => 'extId'];
            $sort_array['created']      = ['table' => 'o', 'field' => 'timeOpen'];
            $sort_array['expiration']   = ['table' => 'o', 'field' => 'timeExpiration'];
            $sort_array['price_open1']  = ['table' => 'o', 'field' => 'priceStrike'];

            $this->setOrder($order, $sort_array, $qb);

        }
//        Debug::$messages[] = $qb->getQuery()->getSQL();
        $data = $qb->getQuery()->getResult();

        return [
            'total'     => (int) $total,
            'filtered'  => (int) $count,
            'filters'   => $this->genFilters($filters_array),
            'rows'      => $data,
        ];
    }

    public function getTotalOrders($params)
    {
        $handler = $params['handler'];
        return $handler->getTotalOrders($params);
    }

    public function getAllOrders($params)
    {
        $handler = $params['handler'];
        return $handler->getAllOrders($params);
    }


}
