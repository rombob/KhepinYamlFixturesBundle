<?php

namespace Khepin\YamlFixturesBundle\Loader;

use Khepin\YamlFixturesBundle\Fixture\YamlFixture;
use Khepin\YamlFixturesBundle\Fixture\YamlAclFixture;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;

class YamlLoader {

    private $bundles;

    /**
     *
     * @var type 
     */
    private $kernel;

    /**
     * Doctrine entity manager
     * @var type 
     */
    private $object_manager;
    
    private $acl_manager = null;

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

    public function __construct(\AppKernel $kernel, $doctrine, $bundles) {
        $this->object_manager = $doctrine->getEntityManager();
        $this->bundles = $bundles;
        $this->kernel = $kernel;
    }

    /**
     * 
     * @param type $manager 
     */
    public function setAclManager($manager = null) {
        $this->acl_manager = $manager;
    }

    /**
     * Returns a previously saved reference
     * @param type $reference_name
     * @return type 
     */
    public function getReference($reference_name) {
        return $this->references[$reference_name];
    }

    /**
     * Sets a reference to an object
     * @param type $name
     * @param type $object 
     */
    public function setReference($name, $object) {
        $this->references[$name] = $object;
    }

    /**
     * Gets all fixtures files
     */
    protected function loadFixtureFiles() {
        foreach ($this->bundles as $bundle) {
          $path = $this->kernel->locateResource('@' . $bundle);
          $files = glob($path . 'DataFixtures/*.yml');
          $this->fixture_files = array_merge($this->fixture_files, $files);

          //sort by index
          $sortedFiles = array();
          $sortingIndex = @file_get_contents($path . 'DataFixtures/fixturesOrder.txt');

          if (!empty($sortingIndex)) {
            $sortingIndex = explode("\n", $sortingIndex);

            // pick indexed
            foreach ($sortingIndex as $name) {
              foreach ($this->fixture_files as $key => $fixture_file) {
                if (strcmp(basename($fixture_file), $name . '.yml') === 0) {
                  $sortedFiles[] = $fixture_file;
                  unset($this->fixture_files[$key]);
                }
              }
            }
            // append the rest
            if (!empty($this->fixture_files)) {
              foreach ($this->fixture_files as $fixture_file) {
                $sortedFiles[] = $fixture_file;
              }
            }
            $this->fixture_files = $sortedFiles;
          }
        }
    }

    /**
     * Loads the fixtures file by file and saves them to the database 
     */
    public function loadFixtures() {
        $this->loadFixtureFiles();
        foreach ($this->fixture_files as $file) {
            $fixture = new YamlFixture($file, $this);
            $fixture->load($this->object_manager, func_get_args());
        }

        if (!is_null($this->acl_manager)) {
            foreach ($this->fixture_files as $file) {
                $fixture = new YamlAclFixture($file, $this);
                $fixture->load($this->acl_manager, func_get_args());
            }
        }
    }

    /**
     * Remove all fixtures from the database 
     */
    public function purgeDatabase() {
        $purger = new ORMPurger($this->object_manager);
        $executor = new ORMExecutor($this->object_manager, $purger);
        $executor->purge();
    }

}