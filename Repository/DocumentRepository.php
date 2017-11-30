<?php

namespace NaxCrmBundle\Repository;

use NaxCrmBundle\Entity\Document;

class DocumentRepository extends AbstractEntityRepository
{
    public function getList($params)
    {
        $filters = [];
        $order = [];
        if (isset($params['filters'])) $filters = $params['filters'];
        if (isset($params['order'])) $order = $params['order'];

        $qb = $this->createQueryBuilder('d')
            ->join('d.client', 'c')
            ->leftJoin('d.documentType', 'dt')
            ->leftJoin('d.manager', 'm');

        $pre_filters = [
            'statusIds'     => ['table' => 'd', 'field' => 'status'],
            'clientIds'     => ['table' => 'd', 'field' => 'client'],
        ];

        $this->chkDepAccess($qb, 'm');
        $this->setupPreFilters($pre_filters, $qb);

        $qb->select('COUNT(d.id)');

        $total = $qb->getQuery()->getSingleScalarResult();

        $filters_array = [
            'id'             => ['type' => 'integer',   'table' => 'd',],
            'time'           => ['type' => 'daterange', 'table' => 'd',],
            'updated'        => ['type' => 'daterange', 'table' => 'd',],
            'mimeType'       => ['type' => 'string',    'table' => 'd',],
            'firstname'      => ['type' => 'string',    'table' => 'c',],
            'lastname'       => ['type' => 'string',    'table' => 'c',],
            'path'           => ['type' => 'string',    'table' => 'd',],
            'declineReason'  => ['type' => 'string',    'table' => 'd', 'field' => 'comment',],
            'managerName'    => ['type' => 'string',    'table' => 'm', 'field' => 'name',],
            'clientId'       => ['type' => 'multilist', 'table' => 'd', 'field' => 'client', 'skipNoSelect' => true,],
            'documentTypeId' => ['type' => 'multilist', 'table' => 'd', 'field' => 'documentType', 'skipNoSelect' => true,],
            'managerId'      => ['type' => 'multilist', 'table' => 'd', 'field' => 'manager', 'skipNoSelect' => true,],
            'departmentId'   => ['type' => 'multilist', 'table' => 'c', 'field' => 'departmentId',],
            'status'         => ['type' => 'multilist', 'table' => 'd', 'field' => 'status',],
        ];

        $this->setMultiValues($filters_array, $qb);

        //apply filters
        $this->buildFilters($filters_array, $filters, $qb);

        $qb->select('COUNT(d.id) filtered');
        $count = $qb->getQuery()->getScalarResult()[0]['filtered'];

        $this->setPagination($params, $qb);


        $qb->select(
            'd.id',
            "c.id clientId",
            'c.firstname',
            'c.lastname',
            "COALESCE(d.comment, '') declineReason",
            "dt.id documentTypeId",
            "m.id managerId",
            "m.name managerName",
            'd.mimeType',
            'd.path',
            'd.status',
            "DATE_FORMAT(d.time, '%Y-%m-%d %H:%i:%s') time",
            "DATE_FORMAT(d.updated, '%Y-%m-%d %H:%i:%s') updated"
        );

        $this->setOrder($order, $filters_array, $qb);


        $data = $qb->getQuery()->getResult();

        return [
            'total'     => (int)$total,
            'filtered'  => (int)$count,
            'filters'   => $this->genFilters($filters_array),
            'rows'      => $data,
        ];
    }
}
