<?php

namespace Khepin\YamlFixturesBundle\Loader;

use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Symfony\Bundle\DoctrineFixturesBundle\Common\DataFixtures\Loader as DataFixturesLoader;

class YamlLoader {

    private $bundles;

    /**
     *
     * @var type 
     */
    private $container;

    /**
     * Array of all yml files containing fixtures that should be loaded
     * @var type 
     */
    private $fixture_files = array();

    /**
     * Maintains references to already created objects
     * @var type 
     */
    private $references = array();

    public function __construct($container, $bundles) {
        $this->bundles = $bundles;
        $this->container = $container;
    }
    
    public function getReference($reference_name){
        return $this->references[$reference_name];
    }
    
    public function setReference($name, $object){
        $this->references[$name] = $object;
    }

    /**
     * Gets all fixtures files
     */
    protected function loadFixtureFiles($context = null) {
        foreach ($this->bundles as $bundle) {
            $path = $this->container->get('kernel')->locateResource('@' . $bundle);
            $files = glob($path . 'DataFixtures/*.yml');
            $this->fixture_files = array_merge($this->fixture_files, $files);
            if (!is_null($context)) {
                $files = glob($path . 'DataFixtures/' . $context . '/*.yml');
                $this->fixture_files = array_merge($this->fixture_files, $files);
            }
        }
    }

    /**
     * Loads the fixtures file by file and saves them to the database 
     */
    public function loadFixtures($context = null) {
        $this->loadFixtureFiles($context);
        $loader = new DataFixturesLoader($this->container);
        foreach ($this->fixture_files as $file) {
            $loader->addFixture(new \Khepin\YamlFixturesBundle\Fixture\YamlFixture($file, $this));
        }
        $em = $this->container->get('doctrine')->getEntityManager();
        $purger = new ORMPurger($em);
        $purger->setPurgeMode(ORMPurger::PURGE_MODE_DELETE);
        $executor = new ORMExecutor($em, $purger);
        $executor->execute($loader->getFixtures());
    }
    
    public function purgeDatabase(){
//        $purger = new ORMPurger($this->object_manager);
//        $purger->setPurgeMode(ORMPurger::PURGE_MODE_DELETE);
//        $executor = new ORMExecutor($this->object_manager, $purger);
//        $executor->execute(array());
    }

}