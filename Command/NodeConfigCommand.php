<?php
namespace NaxCrmBundle\Command;

use Symfony\Component\Yaml\Yaml;

class NodeConfigCommand extends BaseCommand
{
    protected function configure()
    {
        $this
            // the name of the command (the part after "app/console")
            ->setName('app:node-config')

            // the short description shown while running "php app/console list"
            ->setDescription('Update integration/config.json')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('Run it after deploy for update integration/config.json')
        ;
    }

    protected function exec()
    {
        $this->printTitle('Updating integration/config.json ...');

        $root_path = dirname(dirname(dirname(__DIR__))).'/';
        $updates = $update_node_config = [];

        $node_config_path = $root_path.'integration/config.json';
        if(!file_exists($node_config_path)){
            $this->out([
                '<comment> WARN: config.json not exists</comment>',
                '',
            ]);
            return;
        }
        // $update_node_config = ($this->hasParameter('node_config')) ? $this->getParameter('node_config'): [];
        $brand = $this->getParameter('brand');
        // $env = $this->getParameter("kernel.environment");
        $env = $this->getParameter('env');
        $cofig_paramters_path = $root_path.'src/'.ucfirst($brand).'CrmBundle/Resources/config/node_config.yml';
        if(!file_exists($cofig_paramters_path)){
            $this->out('<comment> INFO: node_config.yml not exists</comment>');
        }
        else{
            $parameters = Yaml::parse(file_get_contents($cofig_paramters_path));
            $update_node_config = (!empty($parameters['config']))? $parameters['config']: [];

            if(!empty($parameters[$env.'_config'])){
                $this->out('INFO: use '.$env.'_config.yml also');
                $update_node_config = array_merge($update_node_config, $parameters[$env.'_config']);
            }
        }

        $node_config = json_decode(file_get_contents($node_config_path), 1);
        foreach ($update_node_config as $k => $v) {
            if(!isset($node_config[$k])){
                $node_config[$k] = '';
            }
            if($node_config[$k]!=$v){
                $updates[$k] = json_encode($node_config[$k]).' => '.json_encode($v);
                $node_config[$k] = $v;
            }
        }
        if(!count($updates)){
            $this->info('INFO: config.json already up to date');
            return;
        }
        $data = json_encode($node_config, JSON_PRETTY_PRINT);
        $data = str_replace('\\','', $data);
        if(!file_put_contents($node_config_path, $data)){
            $this->error('ERR: can not write config.json');
            return;
        }
        $this->out('<comment> INFO: config.json have some changes:</comment>');
        foreach ($updates as $k => $v) {
           $this->out('    '.$k.': '.$v);
        }

        $this->info('INFO: config.json updated');
        $this->out([
            '<comment> PLEASE DON`T FORGET TO RESTART INTEGRATION NODEJS<comment>',
            '',
        ]);
    }
}