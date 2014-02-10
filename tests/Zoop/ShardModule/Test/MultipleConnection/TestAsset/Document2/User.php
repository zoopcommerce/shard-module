<?php

namespace Zoop\ShardModule\Test\MultipleConnection\TestAsset\Document2;

//Annotation imports
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document
 */
class User
{
    /**
     * @ODM\Id(strategy="NONE")
     */
    protected $username;

    public function getUsername()
    {
        return $this->username;
    }

    public function setUsername($username)
    {
        $this->username = $username;
    }
}
