<?php

namespace NaxCrmBundle\Repository;

class CommentRepository extends AbstractEntityRepository
{
    public function getList($params)
    {
        $filters = [];
        $order = [];
        if (isset($params['filters'])) $filters = $params['filters'];
        if (isset($params['order'])) $order = $params['order'];

        $qb = $this->createQueryBuilder('com')
            ->join('com.manager', 'm');

        // $this->chkDepAccess($qb);
        if (isset($this->preFilters['departmentIds'])) {
            if(isset($filters['clientId']) || isset($this->preFilters['clientId'])){
                $qb->leftJoin('com.client', 'c');
                $qb->andWhere('c.departmentId IN (:departmentIds)')->setParameter('departmentIds', $this->preFilters['departmentIds']);
            }
            if(isset($filters['leadId']) || isset($this->preFilters['leadId'])){
                $qb->leftJoin('com.lead', 'l');
                $qb->andWhere('l.departmentId IN (:departmentIds)')->setParameter('departmentIds', $this->preFilters['departmentIds']);
            }
        }

        $pre_filters = [
            'managerId' => ['table' => 'com', 'field' => 'manager',],
            'clientId'  => ['table' => 'com', 'field' => 'client',],
            'leadId'    => ['table' => 'com', 'field' => 'lead',],
        ];

        $this->setupPreFilters($pre_filters, $qb);

        $qb->select('COUNT(com.id)');

        $total = $qb->getQuery()->getSingleScalarResult();

        $filters_array = [
            'id'                => ['type' => 'integer',   'table' => 'com',],
            'text'              => ['type' => 'string',    'table' => 'com',],
            'created'           => ['type' => 'daterange', 'table' => 'com',],
            'managerId'         => ['type' => 'multilist', 'table' => 'com', 'field' => 'manager', 'skipNoSelect' => true,],
            'clientId'          => ['type' => 'multilist', 'table' => 'com', 'field' => 'client', 'skipNoSelect' => true,],
            'leadId'            => ['type' => 'multilist', 'table' => 'com', 'field' => 'lead', 'skipNoSelect' => true,],
        ];

        $this->setMultiValues($filters_array, $qb);

        $this->buildFilters($filters_array, $filters, $qb);

        $qb->select('COUNT(com.id) filtered');

        $count = $qb->getQuery()->getScalarResult()[0]['filtered'];
        $this->setPagination($params, $qb);

        $qb->select([
            'com.id',
            'IDENTITY(com.manager) managerId',
            'm.name managerName',
            'com.text',
            "DATE_FORMAT(com.created, '%Y-%m-%d %H:%i:%s') created"
        ]);

        if (empty($order)){
            $order = ['column' => 'created', 'dir' => 'DESC'];
        }
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
