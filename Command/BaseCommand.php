<?php
namespace NaxCrmBundle\Command;

use NaxCrmBundle\Doctrine\NaxEntityManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

abstract class BaseCommand extends ContainerAwareCommand
{
    protected $in = null;
    protected $out = null;

    protected $em = null;

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $_SERVER['HTTP_REFERER'] = $this->getName();
        $this->in = $input;
        $this->out = $output;
        $this->exec();
    }
    protected function exec(){

    }

    protected function getEm()
    {
        if (!$this->em) {
            $this->em = new NaxEntityManager($this->getContainer()->get('doctrine')->getManager());
        }
        if (!$this->em->isOpen()) {//maybe not need
            $this->resetEm();
        }
        return $this->em;
    }
    protected function resetEm()//not work
    {
        $this->getDoctrine()->resetManager();
        $this->em = new NaxEntityManager($this->getContainer()->get('doctrine')->getManager());
        return $this->em;
    }

    protected function getRepository($name)
    {
        return $this->getEm()->getRepository($name);
    }

    protected function getReference($class, $id)
    {
        return $this->getEm()->getReference($class, $id);
    }

    protected function getParameter($param)
    {
        return $this->getContainer()->getParameter($param);
    }

    protected function hasParameter($param)
    {
        return $this->getContainer()->hasParameter($param);
    }

    protected function out($text){
        $this->out->writeln($text);
    }

    protected function printTitle($title = '')
    {
        $this->out("<question>\n\n {$title} \n</question>");
    }
    protected function error($mes = '')
    {
        $this->out("<error> {$mes} </error>");
    }
    protected function info($mes = '')
    {
        $this->out("<info> {$mes} </info>");
    }

    protected function setLog(){
        //TODO
    }
}