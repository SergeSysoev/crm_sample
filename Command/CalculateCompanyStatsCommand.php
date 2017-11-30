<?php
namespace NaxCrmBundle\Command;

use NaxCrmBundle\Entity\Client;
use NaxCrmBundle\Entity\CompanyStatistic;
use NaxCrmBundle\Entity\Department;

class CalculateCompanyStatsCommand extends BaseCommand
{
    protected function configure()
    {
        $this
            // the name of the command (the part after "app/console")
            ->setName('app:calculate-profit')

            // the short description shown while running "php app/console list"
            ->setDescription('Calculate company statistics for yesterday.')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('This command helps you to fill company statistics for yesterday.')
        ;
    }

    protected function exec()
    {
        $this->printTitle('Filling company statistic for yesterday ...');

        $dateFrom = new \DateTime();
        $dateFrom->add(\DateInterval::createFromDateString('yesterday'));
        $dateFrom = $dateFrom->setTime(0, 0, 0);
        $dateTo = new \DateTime();
        $dateTo->add(\DateInterval::createFromDateString('yesterday'));
        $dateTo = $dateTo->setTime(23, 59, 59);

        $repo = $this->getRepository(Client::class());
        $deps = $repo->getCompanyStatistic($dateFrom, $dateTo);
        foreach ($deps as $dep) {
            $department = $this->getReference(Department::class(), $dep['departmentId']);
            $companyStatistic = $this->getRepository(CompanyStatistic::class())->findOneBy([
                'department' => $department,
                'date'       => $dateFrom
            ]);
            if (!$companyStatistic) {
                $companyStatistic = CompanyStatistic::createInst();
            }
            $companyStatistic->setDate($dateFrom);
            $companyStatistic->setDepartment($department);
            $companyStatistic->setNumberOfTraders($dep['numberOfTraders']);
            $companyStatistic->setCompanyProfit($dep['companyProfit']);
            $companyStatistic->setOrdersOpened($dep['ordersOpened']);
            $companyStatistic->setOrdersClosed($dep['ordersClosed']);
            $this->getEm()->persist($companyStatistic);
        }
        $this->getEm()->flush();

        $this->info('Statistic was calculated');
    }
}