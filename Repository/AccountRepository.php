<?php

namespace NaxCrmBundle\Repository;

class AccountRepository extends AbstractEntityRepository
{

    public function getClientsAccounts($filters) //TODO rewrite with buildFilters
    {
        $w = empty($filters) ? '1=1' : $this->where($filters);

        $q = "
            SELECT
            c.email,
            c.id as clientId,
            a.id as accountId,
            a.platform,
            a.type,
            a.currency,
            a.external_id as externalId
            FROM clients c
            LEFT JOIN accounts a ON a.client_id = c.id
            WHERE $w
            ORDER BY a.`type` DESC
        ";

        $this->setSql($q);
        $rows = $this->fetchAll();

        $data = [];
        foreach ($rows as $item) {
            $data[$item['email']][] = $item;
        }
        return $data;
    }

    public function getOperationsTotal($currency = 'USD')
    {
        //TODO need check currency
        $q = "
            SELECT
            `a`.external_id,
            SUM(ROUND(`t`.deposits, 2)) AS `deposits`,
            SUM(ROUND(`t`.withdrawals,2)) AS `withdrawals`
            FROM (
                    SELECT
                        `d`.account_id AS `account_id`,
                        SUM(`d`.amount) AS `deposits`,
                        0 AS `withdrawals`
                    FROM
                        `deposits` AS `d`
                    WHERE d.`status` = 2
                    GROUP BY `account_id`
                UNION
                    SELECT
                        `w`.account_id AS `account_id`,
                        0 AS `deposits`,
                        SUM(`w`.amount) AS `withdrawals`
                    FROM
                        `withdrawals` AS `w`
                    WHERE w.`status` = 2
                    GROUP BY `account_id`
            ) AS `t`
            LEFT JOIN `accounts` AS `a` ON t.`account_id` = `a`.id
            WHERE
                a.`type` = 2
            GROUP BY `external_id`
        ";

        $this->setSql($q);
        $rows = $this->fetchAll();

        $data = [];
        foreach ($rows as $item) {
            $data[$item['external_id']] = $item;
        }
        return $data;
    }

}
