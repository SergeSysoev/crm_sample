<?php

namespace NaxCrmBundle\Entity;
use Symfony\Component\Yaml\Yaml;

abstract class BaseEntity
{
    /*
     * Get entity class name with inheritance
     * Use instead of property '::class'
     * @param string|null $brand set brand which full name need to get
     * @param string|null $entityName entity name which full name need to get
     * @return string of full class name
     */
    public static function class($brand = null, $entityName = null, $class_path = 'Entity')
    {
        // exit;
        if (!$brand){
            $params = Yaml::parse(file_get_contents(dirname(__FILE__) . '/../../../app/config/parameters.yml'));
            $brand = ucfirst($params['parameters']['brand']);
        }
        if (!$entityName){
            $entityName = get_called_class();
        }

        if (($sep_pos = strrpos($entityName, '\\')) !== false){
            $entityName = substr($entityName, $sep_pos+1);
        }
        if (($sep_pos = strrpos($entityName, ':')) !== false){
            $entityName = substr($entityName, $sep_pos+1);
        }

        $class_name = "{$brand}CrmBundle\\{$class_path}\\{$entityName}";
        if (class_exists($class_name)){
            return $class_name;
        }

        $bundle = $brand.'CrmBundle';
        do {
            $bundle = $bundle.'\\'.$bundle;
            $bundle = new $bundle();
            $bundle = $bundle->getParent();
            $class_name = "{$bundle}\\{$class_path}\\{$entityName}";
        } while (!class_exists($class_name));
        return $class_name;
    }

    /*
     * Get public static var with entity inheritance
     * Use instead of direct call
     * @param string $var name of static variable
     * @return variable value
     */
    public static function get($var){
        $class = self::class();
        return $class::$$var;
    }

    /*
     * Create new entity instance with inheritance
     * Use instead of direct creation `new Class()`
     * @return new class instance
     */
    public static function createInst()
    {
        $class_name = self::class();
        return new $class_name();
    }

    public function hardRemove($em)
    {
        // initiate an array for the removed listeners
        $originalEventListeners = [];
        // cycle through all registered event listeners
        foreach ($em->getEventManager()->getListeners() as $eventName => $listeners) {
            foreach ($listeners as $listener) {
                if ($listener instanceof \Gedmo\SoftDeleteable\SoftDeleteableListener) {

                    // store the event listener, that gets removed
                    $originalEventListeners[$eventName] = $listener;

                    // remove the SoftDeletableSubscriber event listener
                    $em->getEventManager()->removeEventListener($eventName, $listener);
                }
            }
        }
        // remove the entity
        $em->remove($this);
        $em->flush($this);

        // re-add the removed listener back to the event-manager
        foreach ($originalEventListeners as $eventName => $listener) {
            $em->getEventManager()->addEventListener($eventName, $listener);
        }
    }

    public function set($fields, $params)
    {
        foreach ($fields as $k => $v) {
            if (!is_array($v)) {
                $k = $v;
                $v = [];
            }

            if (!isset($params[$k])) {
                if (!isset($v['def'])) {
                    continue;
                }
                $params[$k] = $v['def'];
            }

            if (empty($v['field'])) {
                $v['field'] = $k;
            }

            $setter = (empty($v['setter'])) ?
                'set' . ucfirst($v['field']) :
                $v['setter'];


            if (!method_exists($this, $setter)) {
                \NaxCrmBundle\Debug::$messages[] = 'WARN: Entity(' . get_called_class() . ") can`t get seter for field '{$v['field']}'";
                continue;
            }
            if(is_string($params[$k])){
                $params[$k] = trim($params[$k]);
                $params[$k] = iconv('utf-8', 'windows-1251//IGNORE', $params[$k]);
                $params[$k] = iconv('windows-1251', 'utf-8//IGNORE', $params[$k]);
            }

            $this->$setter($params[$k]);
        }
        return $this;
    }
}