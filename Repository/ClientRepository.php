<?php

namespace NaxCrmBundle\Repository;

use NaxCrmBundle\Debug;
use NaxCrmBundle\Entity\Account;
use NaxCrmBundle\Entity\Deposit;
use NaxCrmBundle\Entity\Document;
use NaxCrmBundle\Entity\Withdrawal;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ClientRepository extends AbstractEntityRepository
{
    public function getComplianceReportData($params)
    {
        $filters = [];
        $order = $limit = '';
        if (isset($params['filters'])) $filters = $params['filters'];
        $preWhereCondition = ' c.deleted_at IS NULL ';
        $preWhereCondition .= 'AND c.verified = 0 ';
        $this->chkDepAccess($preWhereCondition, 'c');

        $filters_array = [
            'clientId' => ['type' => 'integer', 'table' => 'c', 'field' => 'id'],
            'firstname' => ['type' => 'string', 'table' => 'c'],
            'lastname' => ['type' => 'string', 'table' => 'c'],
            'email' => ['type' => 'string', 'table' => 'c'],
            'lastDocumentTime' => ['type' => 'string', 'table' => 'd', 'field' => 'time'],
        ];

        $whereCondition = $this->buildFilters($filters_array, $filters);

        $need_doc_types = implode('\',\'', [
            'Passport',
            'Proof of residence',
            'Credit card front',
            'Credit card back',
        ]);
        $q = "SELECT `id`, CONCAT('doc_type_',`type`) AS `type`
            FROM `document_types`
            WHERE `type` IN ('{$need_doc_types}')";
        $this->setSql($q);
        $doc_types = $this->fetchAll();

        $doc_types_s = [];
        $doc_types_w = [];
        $status_approved = Document::STATUS_APPROVED;
        foreach ($doc_types as $doc) {
            $doc['type'] = str_replace(' ', '_', $doc['type']);
            $doc_types_s[] = "MAX(IF(d.`document_type_id` = {$doc['id']} AND IFNULL(d.`status`, 0) = {$status_approved}, 1,0)) AS `{$doc['type']}`";
            $doc_types_w[] = "`{$doc['type']}` = 0";
        }
        $doc_types_s = implode(",\n", $doc_types_s);
        $doc_types_w = implode(' OR ', $doc_types_w);
        if (!empty($doc_types_s)) $doc_types_s = ',' . $doc_types_s;
        if (!empty($doc_types_w)) $doc_types_w = "HAVING  ({$doc_types_w})";

        $whereCondition = str_replace('%', '%%', $whereCondition);
        $q = "SELECT
                %s
                {$doc_types_s}
            FROM clients AS c
            LEFT JOIN documents d ON d.`client_id`=c.`id`
            WHERE {$whereCondition} AND {$preWhereCondition}
            GROUP BY c.`id`
            {$doc_types_w}
            %s
            %s";

//        Debug::$messages['q_demo'] = $q;
        $sql = sprintf($q,
            'c.`id` AS clientId,
            c.`email`,
            c.`firstname`,
            c.`lastname`,
            MAX(IFNULL(d.`time`, "")) AS `lastDocumentTime`', //,GROUP_CONCAT(DISTINCT status) status
            '',
            $limit
        );
        $sql = "SELECT COUNT(0) total FROM ({$sql}) as t";

        $this->setSql($sql);
        $filteredData = $this->fetch();
        $totalCount = $count = $filteredData['total'];

        if (!empty($params['limit'])) {
            $limit = "LIMIT {$params['limit']}";
            if (!empty($params['offset'])) {
                $limit = "LIMIT {$params['offset']}, {$params['limit']}";
            }
        }
        if (!empty($params['order'])) {
            $order = $params['order'];
            $order['dir'] = (!empty($order['dir'])) ? strtoupper($order['dir']) : 'ASC';
            $dir = in_array($order['dir'], ['ASC', 'DESC']) ? $order['dir'] : 'ASC';

            if (!empty($order['column'])) {
                // $order['column'] = str_replace('clientId', 'id', $order['column']);
                if (!in_array($order['column'], [
                    'lastDocumentTime',
                    'accountType',
                    'clientId',
                ])
                ) {
                    $order = "ORDER BY c.`{$order['column']}` {$dir}";
                } else {
                    $order = "ORDER BY `{$order['column']}` {$dir}";
                }
            }
        }
        $sql = sprintf($q,
            'c.`id` AS clientId,
            c.`email`,
            c.`firstname`,
            c.`lastname`,
            MAX(IFNULL(d.`time`, "")) AS `lastDocumentTime`',
            $order,
            $limit
        );
        $this->setSql($sql);
        $rows = $this->fetchAll();

        return [
            'total' => $totalCount,
            'filtered' => $count,
            'filters' => $this->genFilters($filters_array),
            'rows' => $rows,
        ];
    }

    public function getList($params)
    {
        $filters = [];
        $order = [];
        if (isset($params['filters'])) $filters = $params['filters'];
        if (isset($params['order'])) $order = $params['order'];
        if(empty($filters['currency'])) $filters['currency'] = ['USD'];

        $qb = $this->createQueryBuilder('c')
            ->select('COUNT(DISTINCT c.id)');
        if (!empty($this->preFilters)) {
            $this->chkDepAccess($qb);

            if (isset($this->preFilters['platform'])) {
                $qb->andWhere('c.platform IN (:platform)')->setParameter('platform', $this->preFilters['platform']);
            }
            if (isset($this->preFilters['statusIds'])) {
                $qb->andWhere('c.ctatusId IN (:statusIds)')->setParameter('statusIds', $this->preFilters['statusIds']);
            }
            if (isset($this->preFilters['showDeleted'])) {
                if ($this->_em->getFilters()->isEnabled('softdeleteable')) {
                    $this->_em->getFilters()->disable('softdeleteable');
                }
                $qb->where('c.deletedAt <= :timeNow')
                    ->setParameter(':timeNow', new \DateTime());
            }
            if (isset($this->preFilters['assignmentIds'])) {
                $qb->leftJoin('c.assignment', 'a')
                    ->andWhere('a.id IN (:assignmentIds)')->setParameter('assignmentIds', $this->preFilters['assignmentIds']);
            }
            if (isset($this->preFilters['cpi'])) {
                $qb->join('c.accounts', 'ac')->andWhere('ac.type = :typeDemo')->setParameter('typeDemo', Account::TYPE_DEMO)
                    ->leftJoin('c.accounts', 'ac2', 'WITH', 'ac2.type = :typeLive')->setParameter('typeLive', Account::TYPE_LIVE) //to skip client with live accs
                    ->andWhere('ac2.id IS NULL');
            }
            if (isset($this->preFilters['cpa'])) {
                $qb->join('c.accounts', 'ac')->andWhere('ac.type = :typeLive')->setParameter('typeLive', Account::TYPE_LIVE);
            }
        }
        $total = $qb->getQuery()->getSingleScalarResult();

        //apply filters
        $qb->leftJoin('c.country', 'co')
           ->leftJoin('c.saleStatus', 'ss');
        $qb->leftJoin('c.deposits', 'd', 'WITH', 'd.status = :status AND d.currency = :currency')
                ->setParameter('status', Deposit::STATUS_PROCESSED)
                ->setParameter('currency', $filters['currency']);// pzdc


        $filters_array = [
            'id'            => ['type' => 'integer', 'table' => 'c',],
            'country'       => ['type' => 'multilist', 'table' => 'co', 'field' => 'id',],
            'status'        => ['type' => 'multilist', 'table' => 'c', 'field' => 'statusId',],
            'saleStatus'    => ['type' => 'multilist', 'table' => 'ss', 'field' => 'id',],
            'langcode'      => ['type' => 'multilist', 'table' => 'c', 'field' => 'langcode',],
            'managerId'     => ['type' => 'multilist', 'table' => 'c',],
            'departmentId'  => ['type' => 'multilist', 'table' => 'c',],
            'isEmailConfirmed' => ['type' => 'multilist', 'table' => 'c',],
            'isOnline'      => ['type' => 'multilist', 'table' => 'c',],
            'firstname'     => ['type' => 'string', 'table' => 'c',],
            'lastname'      => ['type' => 'string', 'table' => 'c',],
            'email'         => ['type' => 'string', 'table' => 'c',],
            'phone'         => ['type' => 'string', 'table' => 'c',],
            'note1'         => ['type' => 'string', 'table' => 'c',],
            'note2'         => ['type' => 'string', 'table' => 'c',],
            'source'        => ['type' => 'string', 'table' => 'c',],
            'platform'      => ['type' => 'multilist', 'table' => 'c',],
            'externalId'    => ['type' => 'integer', 'table' => 'c',],
            'created'       => ['type' => 'daterange', 'table' => 'c',],

            'lastNoteUpdate'=> ['type' => 'daterange', 'table' => 'c',],
        ];

        $this->setMultiValues($filters_array, $qb);

        ////apply common(standard behaviour) filters
        $this->buildFilters($filters_array, $filters, $qb);

        $filters_array['totalDepositsAmount'] = ['type' => 'integer',];
        $filters_array['currency'] = ['type' => 'string', 'table' => 'd',];

        //handle uncommon filters
        if (isset($filters['totalDepositsAmountFrom'])) $qb->andHaving($qb->expr()->gte('SUM(d.amount)', ':totalDepositsAmountFrom'))->setParameter(
            'totalDepositsAmountFrom', $filters['totalDepositsAmountFrom']
        );
        if (isset($filters['totalDepositsAmountTo'])) $qb->andHaving($qb->expr()->lte('SUM(d.amount)', ':totalDepositsAmountTo'))->setParameter(
            'totalDepositsAmountTo', $filters['totalDepositsAmountTo']
        );

        $qb->select('COUNT(DISTINCT c.id) filtered');

        $count = $qb->getQuery()->getScalarResult()[0]['filtered'] ??  0;

        $this->setPagination($params, $qb);


        $qb->select(
            'c.id',
            'c.firstname',
            'c.lastname',
            'c.email',
            'c.phone',
            'co.name country',
            'c.langcode',
            "COALESCE(c.managerId, '') managerId",
            'c.departmentId',
            'c.statusId status',
            'ss.id saleStatus',
            "DATE_FORMAT(c.created, '%Y-%m-%d %H:%i:%s') created",
            'c.isEmailConfirmed',
            'COALESCE(SUM(d.amount), 0) as totalDepositsAmount',
            'c.isOnline',
            'c.note1',
            'c.note2',
            'c.source',
            'c.platform',
            'c.externalId',
            "DATE_FORMAT(c.saleStatusUpdate, '%Y-%m-%d %H:%i:%s') saleStatusUpdate",
            "DATE_FORMAT(c.lastNoteUpdate, '%Y-%m-%d %H:%i:%s') lastNoteUpdate"
        )->groupBy('c.id');

        $sort_array = $filters_array;
        $sort_array['country'] = ['table' => 'co', 'field' => 'name'];
        $sort_array['saleStatus'] = ['table' => 'ss', 'field' => 'statusName'];

        $this->setOrder($order, $sort_array, $qb);
        \NaxCrmBundle\Debug::$messages[]=$qb->getQuery()->getSQL();
        $data = $qb->getQuery()->getResult();

        return [
            'total' => (int)$total,
            'filtered' => (int)$count,
            'filters' => $this->genFilters($filters_array),
            'rows' => $data,
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
        $w = "c.`id` IN ({$ids})";

        $q = "UPDATE clients c
            SET {$set}
            WHERE {$w}";

        $res = $this->setSql($q);
        return $res;
    }


    public function getClientList($params)
    {
        $filters = [];
        if (isset($params['filters'])) $filters = $params['filters'];

        $qb = $this->createQueryBuilder('c');
        if (!empty($this->preFilters)) {
            $this->chkDepAccess($qb);
            if (isset($this->preFilters['live'])) {
                $qb->join('c.accounts', 'ac')->andWhere('ac.type = :typeLive')->setParameter('typeLive', Account::TYPE_LIVE);
            }
        }

        $filters_array = [
            'id' => ['type' => 'integer', 'table' => 'c',],
            'firstname' => ['type' => 'string', 'table' => 'c',],
            'lastname' => ['type' => 'string', 'table' => 'c',],
            'statusId' => ['type' => 'integer', 'table' => 'c',],
        ];

        if(!empty($filters['name'])){
            $name = explode(' ', $filters['name']);
            if(count($name)==1){
                $filters['name'] = $filters['name'].'%';
                $qb->andWhere('(c.firstname LIKE :name OR c.lastname LIKE :name)')->setParameter('name', $filters['name']);
            }
            else{
                $firstname = array_shift($name);
                $lastname = implode(' ', $name).'%';
                $qb->andWhere('c.firstname LIKE :firstname')->setParameter('firstname', $firstname);
                $qb->andWhere('c.lastname LIKE :lastname')->setParameter('lastname', $lastname);
            }
        }
        else{
            $this->buildFilters($filters_array, $filters, $qb);
        }
        $filters_array['name'] = ['type' => 'string'];

        $qb->select(
            'c.id',
            'c.firstname',
            'c.lastname',
            'c.statusId'
        );
        $qb->orderBy('c.id', 'DESC');
        $data = $qb->getQuery()->getResult();

        return [
            'filters' => $this->genFilters($filters_array),
            'rows' => $data,
        ];
    }

    public function getClientInfo($clientId)
    {
        $data = [];

        $sql = "
            SELECT
             a.`id`,
             a.`type`,
             a.`currency`

            FROM accounts a
            WHERE a.`client_id` = '{$clientId}'
            GROUP BY  a.`id`
        ";

        $this->setSql($sql);
        $data['accounts'] = $this->fetchAll();

        return $data;
    }

    public function getCompanyBalanceSheet($departmentId)
    {
        if (!is_array($departmentId)) $departmentId = [$departmentId];
        $departmentId = array_map(function ($item) {
            return preg_replace('@[^\d]+@', '', $item);
        }, $departmentId);
        $departmentId = implode(',', $departmentId);
        $r = [];

        $q = "SELECT
                COALESCE(SUM(d.`amnt`), 0) AS amount,
                d.`currency`
            FROM
                clients c
            LEFT JOIN
                (SELECT d.`client_id`,  d.`currency`,  COALESCE(SUM(amount), 0) AS amnt
                    FROM deposits AS d WHERE `status` = %d GROUP BY client_id
                )AS d ON d.`client_id` = c.`id`
            WHERE
                c.`department_id` IN (%s) AND c.`deleted_at` IS NULL
            GROUP BY d.`currency`";
        $this->setSql(sprintf($q, Deposit::STATUS_PROCESSED, $departmentId));
        $d = $this->fetchAll();
        foreach ($d as $v) {
            if(empty($v['amount'])) continue;
            if(empty($v['currency'])){
                $v['currency'] = 'Unknown';
            }
            $r[$v['currency']] = [
                'companyBalanceSheet' => $v['amount'],
                'deposits' => $v['amount'],
                'withdrawals' => 0,
            ];
        }

        $q = "SELECT
                COALESCE(SUM(w.`amnt`), 0) AS amount,
                w.`currency`
            FROM
                clients c
            LEFT JOIN
                (SELECT w.`client_id`,  a.`currency`,  COALESCE(SUM(amount), 0) AS amnt
                    FROM withdrawals AS w LEFT JOIN accounts AS a ON a.`id` = w.`account_id`
                    WHERE `status` = %d GROUP BY client_id
                ) AS w ON c.`id` = w.`client_id`
            WHERE
                c.`department_id` IN (%s) AND c.`deleted_at` IS NULL
            GROUP BY w.`currency`";
        $this->setSql(sprintf($q, Withdrawal::STATUS_APPROVED, $departmentId));
        $w = $this->fetchAll();
        foreach ($w as $v) {
            if(empty($v['amount'])) continue;
            if(empty($v['currency'])){
                $v['currency'] = 'Unknown';
            }
            if(empty($r[$v['currency']])){
                $r[$v['currency']] = [
                    'companyBalanceSheet' => -$v['amount'],
                    'deposits' => 0,
                    'withdrawals' => $v['amount'],
                ];
            }
            else{
                $r[$v['currency']]['companyBalanceSheet']-= $v['amount'];
                $r[$v['currency']]['withdrawals'] = $v['amount'];
            }
        }
        \NaxCrmBundle\Debug::$messages[]=$w;

        return $r;
    }

    public function getCompanyStatistic(\DateTime $dateFrom, \DateTime $dateTo)
    {
        if ($this->_em->getFilters()->isEnabled('softdeleteable')) {
            $this->_em->getFilters()->disable('softdeleteable');
        }
        $qb = $this->createQueryBuilder('c')
            ->select(
                'c.departmentId departmentId',
                'COUNT(DISTINCT c.id) numberOfTraders',
                'COUNT(DISTINCT o.id) ordersClosed',
                'COUNT(DISTINCT o2.id) ordersOpened',
                'COALESCE(SUM(-o.profit), 0) companyProfit'
            )
            ->leftJoin('c.orders', 'o', 'WITH', 'o.status = 2 AND o.timeOpen >= :from AND o.timeOpen <= :to')
            ->leftJoin('c.orders', 'o2', 'WITH', 'o2.status = 1 AND o2.timeOpen >= :from AND o2.timeOpen <= :to')
            ->setParameters([
                'from'  => $dateFrom->format('Y-m-d H:i:s'),
                'to'    => $dateTo->format('Y-m-d H:i:s'),
            ])
            ->andWhere('o.id IS NOT NULL OR o2.id IS NOT NULL')
            ->where('c.departmentId IS NOT NULL')
            ->groupBy('c.departmentId');
        return $qb->getQuery()->getResult();
    }
}
