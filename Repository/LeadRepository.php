<?php

namespace NaxCrmBundle\Repository;


class LeadRepository extends AbstractEntityRepository
{
    public function getList($params = [])
    {
        $filters = [];
        $order = [];
        if (isset($params['filters'])) $filters = $params['filters'];
        if (isset($params['order'])) $order = $params['order'];

        $qb = $this->createQueryBuilder('l');

        if (isset($this->preFilters['assignmentIds'])) $qb->leftJoin('l.assignment', 'a');
        if (isset($this->preFilters['leadUploadIds'])) $qb->leftJoin('l.upload', 'u');

        $pre_filters = [
            // 'ids'           => ['table' => 'l', 'field' => 'id',],
            'statusIds'     => ['table' => 'l', 'field' => 'status',],
            'showDeleted'   => ['table' => 'l', 'field' => 'deletedAt', 'op' => '<=', 'value' => new \DateTime(),],
            'assignmentIds' => ['table' => 'a', 'field' => 'id',],
            'leadUploadIds' => ['table' => 'u', 'field' => 'id',],
            'platform'      => ['table' => 'l',  'type' => 'multilist',],
        ];

        $this->chkDepAccess($qb, 'l');
        $this->setupPreFilters($pre_filters, $qb);

        $qb->select('COUNT(l.id)');

        $total = $qb->getQuery()->getSingleScalarResult();

        //apply filters
        $qb->leftJoin('l.country', 'co')
           ->leftJoin('l.saleStatus', 'ss');

        $filters_array = [
            'id'            => ['table' => 'l',  'type' => 'integer',],
            'country'       => ['table' => 'co', 'type' => 'multilist', 'field' => 'id',],
            'managerId'     => ['table' => 'l',  'type' => 'multilist',],
            'departmentId'  => ['table' => 'l',  'type' => 'multilist',],
            'saleStatus'    => ['table' => 'ss', 'type' => 'multilist', 'field' => 'id',],
            'langcode'      => ['table' => 'l',  'type' => 'multilist',],
            'firstname'     => ['table' => 'l',  'type' => 'string',],
            'lastname'      => ['table' => 'l',  'type' => 'string',],
            'email'         => ['table' => 'l',  'type' => 'string',],
            'phone'         => ['table' => 'l',  'type' => 'string',],
            'note1'         => ['table' => 'l',  'type' => 'string',],
            'note2'         => ['table' => 'l',  'type' => 'string',],
            'source'        => ['table' => 'l',  'type' => 'string',],
            'platform'      => ['table' => 'l',  'type' => 'multilist',],
            'externalId'    => ['table' => 'l',  'type' => 'integer',],
            'created'       => ['table' => 'l',  'type' => 'daterange',],
        ];

        $this->setMultiValues($filters_array, $qb);
        $this->buildFilters($filters_array, $filters, $qb);

        $qb->select('COUNT(l.id) filtered');
        $count = $qb->getQuery()->getScalarResult()[0]['filtered'];

        $this->setPagination($params, $qb);

        $qb->select(
            'l.id',
            'l.firstname',
            'l.lastname',
            'l.email',
            'l.phone',
            'co.name country',
            'l.langcode',
            "COALESCE(l.managerId, '') managerId",
            'l.departmentId',
            'ss.id saleStatus',
            'l.note1',
            'l.note2',
            'l.source',
            'l.platform',
            'l.externalId',
            "DATE_FORMAT(l.created, '%Y-%m-%d %H:%i:%s') created",
            "DATE_FORMAT(l.saleStatusUpdate, '%Y-%m-%d %H:%i:%s') saleStatusUpdate",
            'l.deletedAt'
        );

        $sort_array                = $filters_array;
        $sort_array['country']     = ['table' => 'co', 'field' => 'name',];
        $sort_array['saleStatus']  = ['table' => 'ss', 'field' => 'statusName',];
        $this->setOrder($order, $sort_array, $qb);

        // \NaxCrmBundle\Debug::$messages[] = $qb->getQuery()->getSQL();
        $data = $qb->getQuery()->getResult();

        return [
            'total'     => (int) $total,
            'filtered'  => (int) $count,
            'filters'   => $this->genFilters($filters_array),
            'rows'      => $data,
        ];
    }

    public function batchUpdate($ids, $fields){
        $set = [];
        foreach ($fields as $k => $v) {
            $set[]= "{$k} = '{$v}'";
        }
        if(empty($set)){
            throw new HttpException(400, 'Fields for change not found');
        }
        $set = implode(',',$set);
        $w = "l.`id` IN ({$ids})";

        $q = "UPDATE leads l
            SET {$set}
            WHERE {$w}";

        $res = $this->setSql($q);
        return $res;
    }
}
