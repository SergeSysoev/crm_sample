<?php
namespace NaxCrmBundle\Command;

class BuildRoutesCommand extends BaseCommand
{
    protected function configure()
    {
        $this
            // the name of the command (the part after "app/console")
            ->setName('app:build-routes')

            // the short description shown while running "php app/console list"
            ->setDescription('Build routing_auto.yml by controllers')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('Run before deploy')
        ;
    }

    protected function exec()
    {
        $this->printTitle('Scan Bundles ...');
        $src_path = dirname(dirname(__DIR__)).'/';
        $bundles = glob($src_path.'*', GLOB_ONLYDIR|GLOB_NOSORT);
        $this->out($bundles);
        $this->out('');

        $controller_path = 'NaxCrmBundle/Controller/';
        $this->out('Scan Controller dir of parrent bundle(NaxCrmBundle)...');
        $controllers = $this->rglob($src_path.$controller_path.'*.php');
        $this->out('Controllers found:'.count($controllers));

        $exclude_controllers = [
            'ExceptionController.php',
            'v1/BaseController.php',
            'v1/ApiDocController.php',
        ];

        $routes = $catergory = $prefix = '';
        foreach ($controllers as $controller) {
            $resource = str_replace($src_path, '', $controller);
            $route_name = str_replace($controller_path, '', $resource);
            if(in_array($route_name, $exclude_controllers)){
                continue;
            }
            $prefix = dirname($route_name);
            if($prefix!=$catergory){
                $catergory = $prefix;
                $routes.="\n\n## /Controller/{$catergory}";
                $this->out("## {$catergory}");
            }
            $route_name = str_replace('/','_', strtolower($route_name));
            $route_name = str_replace('controller.php','', $route_name);
            $prefix = explode('/', $prefix);
            $routes.= "\n{$route_name}:\n    resource: \"@{$resource}\"\n    type:     annotation\n    prefix:   /api";
            while(count($prefix)>0){
                $routes.= '/'.array_pop($prefix);
            }
        }

        //TODO add diff of bundles
        // $routes.="\n\n##import from child bundles\nbrand_import:\n    resource: \"@NaxCrmBundle/Resources/routing.yml\"";

        $res = file_put_contents(dirname($src_path).'/app/config/routing_auto.yml', ltrim($routes));
        if($res){
            $this->info('Save routing_auto success');
        }
        else{
            $this->error('Save routing_auto failed');
        }
    }
    public function rglob($pattern, $flags = 0) {
        $files = glob($pattern, $flags);
        foreach (glob(dirname($pattern).'/*', GLOB_ONLYDIR) as $dir) {
            $files = array_merge($files, $this->rglob($dir.'/'.basename($pattern), $flags));
        }
        return $files;
    }
}