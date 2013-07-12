<?php

namespace Zoop\ShardModule\Test\TestAsset\Document;

use Doctrine\Common\Collections\ArrayCollection;

//Annotation imports
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Zoop\Shard\Annotation\Annotations as Shard;

/**
 * @ODM\EmbeddedDocument
 * @Shard\AccessControl({
 *     @Shard\Permission\Basic(roles="*", allow="*")
 * })
 */
class Component {

    /**
     * @ODM\String
     * @ODM\UniqueIndex
     */
    protected $name;

    /**
     * @ODM\String
     * @Shard\Validator\Required
     */
    protected $type;

    /**
     * @ODM\EmbedMany(targetDocument="Manufacturer")
     */
    protected $manufacturers;

    public function getName() {
        return $this->name;
    }

    public function setName($name) {
        $this->name = $name;
    }

    public function getType() {
        return $this->type;
    }

    public function setType($type) {
        $this->type = $type;
    }

    public function getManufacturers() {
        return $this->manufacturers;
    }

    public function setManufacturers($manufacturers) {
        $this->manufacturers = $manufacturers;
    }

    public function __construct() {
        $this->manufacturers = new ArrayCollection();
    }
}
