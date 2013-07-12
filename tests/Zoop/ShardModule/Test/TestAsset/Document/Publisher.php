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
class Publisher {

    /**
     * @ODM\String
     * @ODM\UniqueIndex
     */
    protected $name;

    /**
     * @ODM\ReferenceOne(targetDocument="Country", simple="true", inversedBy="publishers", cascade="all")
     */
    protected $country;

    /**
     *
     * @ODM\String
     */
    protected $city;

    public function getName() {
        return $this->name;
    }

    public function setName($name) {
        $this->name = $name;
    }

    public function getCountry() {
        return $this->country;
    }

    public function setCountry($country) {
        $this->country = $country;
    }

    public function getCity() {
        return $this->city;
    }

    public function setCity($city) {
        $this->city = $city;
    }
}
