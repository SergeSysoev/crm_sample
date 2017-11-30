<?php
namespace NaxCrmBundle\Command;

use NaxCrmBundle\Entity\Client;

class ChkOnlineCommand extends BaseCommand
{
    protected function configure()
    {
        $this
            // the name of the command (the part after "app/console")
            ->setName('app:chk-online')

            // the short description shown while running "php app/console list"
            ->setDescription('Checking online clients.')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp("")
        ;
    }

    protected function exec()
    {
        $this->printTitle('Checking clients online status ...');

        $repo = $this->getRepository(Client::class());
        $repo->updateOnlineStatus();

        $this->info('Clients online status updated successfully');
    }
}