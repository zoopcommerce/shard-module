<?php

namespace Zoop\ShardModule\Test\TestAsset\Document;

use Doctrine\Common\Collections\ArrayCollection;

//Annotation imports
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Zoop\Shard\Annotation\Annotations as Shard;

/**
 * @ODM\Document
 * @Shard\AccessControl({
 *     @Shard\Permission\Basic(roles="*", allow="*")
 * })
 */
class Country {

    /**
     * @ODM\Id(strategy="NONE")
     */
    protected $name;

    /**
     * @ODM\ReferenceMany(targetDocument="Author", mappedBy="country")
     */
    protected $authors;

    public function __construct() {
        $this->authors = new ArrayCollection();
    }

    public function getName() {
        return $this->name;
    }

    public function setName($name) {
        $this->name = $name;
    }

    public function getAuthors() {
        return $this->authors;
    }

    public function setAuthors($authors) {
        $this->authors = $authors;
    }
}
