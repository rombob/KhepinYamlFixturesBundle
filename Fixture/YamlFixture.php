<?php

namespace Khepin\YamlFixturesBundle\Fixture;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Symfony\Component\Yaml\Yaml;
use Doctrine\Common\Util\Inflector;

class YamlFixture implements FixtureInterface {

    private $file;
    
    private $loader;

    public function __construct($file, $loader) {
        $this->file = $file;
        $this->loader = $loader;
    }

    public function load(ObjectManager $manager) {
        $cmf = $manager->getMetadataFactory();
        $file = Yaml::parse($this->file);
        // The model class for all fixtures defined in this file
        $class = $file['model'];
        // Get the fields that are not "associations"
        $metadata = $cmf->getMetaDataFor($class);
        $mapping = array_keys($metadata->fieldMappings);
        $associations = array_keys($metadata->associationMappings);

        foreach ($file['fixtures'] as $reference => $fixture) {
            // Instantiate new object
            $object = new $class;
            foreach ($fixture as $field => $value) {
                // Add the fields defined in the fistures file
                $method = Inflector::camelize('set_' . $field);
                // 
                if (in_array($field, $mapping)) {
                    // Dates need to be converted to DateTime objects
                    $type = $metadata->fieldMappings[$field]['type'];
                    if ($type == 'datetime' OR $type == 'date') {
                        $value = new \DateTime($value);
                    }
                    $object->$method($value);
                } else if (in_array($field, $associations)) { // This field is an association, we load it from the references
                    $object->$method($this->loader->getReference($value));
                } else {
                    // It's a method call that will set a field named differently
                    // eg: FOSUserBundle ->setPlainPassword sets the password after
                    // Encrypting it
                    $object->$method($value);
                }
            }
            // Save a reference to the current object
            $this->loader->setReference($reference, $object);
            $manager->persist($object);
        }
    }
}