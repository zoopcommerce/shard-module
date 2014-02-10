<?php

namespace Zoop\ShardModule\Test\MultipleConnection\TestAsset\Document1;

//Annotation imports
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document
 */
class Country
{
    /**
     * @ODM\Id(strategy="NONE")
     */
    protected $name;

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }
}
