<?php
namespace NaxCrmBundle\Modules;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use NaxCrmBundle\NaxCrmBundle;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
abstract class MigrationWrapper extends AbstractMigration implements ContainerAwareInterface
{
    /*
     * List of bundles which apply migration
     * Migration applies with bundle children
     */
    private $bundles = [];

    /*
     * Exclude bundles from migration
     * Helpfull for exclude some child
     */
    private $exclude = [];

    protected $container;

    protected $canMigrate = false;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function setBundles($bundles)
    {
        if (!is_array($bundles)) $bundles = [$bundles];
        $this->bundles = array_unique(array_merge($this->bundles, $bundles));
        return $this;
    }

    public function setExclude($exclude)
    {
        if (!is_array($exclude)) $exclude = [$exclude];
        $this->exclude = $exclude;
        return $this;
    }

    private function getBrand($bundleName)
    {
        return strtolower(str_replace('CrmBundle', '', $bundleName));
    }

    protected function canMigrate()
    {
        //if empty that means migration for all brands
        if (empty($this->bundles)) return true;

        $kernel = $this->container->get('kernel');

        //if current bundle in acception list - apply
        $bundle = $kernel->getBundle('NaxCrmBundle');
        $brand = $this->getBrand($bundle->getName());
        if (in_array($brand, $this->bundles) && !in_array($brand, $this->exclude)) return true;

        //find all parents without root NaxCrmBundle and check it
        $father = $bundle->getParent();
        while ($father != 'NaxCrmBundle') {
            $father = $father . '\\' . $father;
            if (class_exists($father)) {
                $father = new $father();
                $brand = $this->getBrand($father->getName());
                if (in_array($brand, $this->bundles) && !in_array($brand, $this->exclude)) return true;
                $father = $father->getParent();

            }
        }

        //and check nax nundle
        if ($father == 'NaxCrmBundle') {
            $father = $father . '\\' . $father;
            if (class_exists($father)) {
                $father = new $father();
                $brand = $this->getBrand($father->getName());
                if (in_array($brand, $this->bundles) && !in_array($brand, $this->exclude)) return true;
            }
        }

        return false;
    }
}