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
class User {

    /**
     * @ODM\Id(strategy="NONE")
     */
    protected $id;

    /**
     * @ODM\String
     * @ODM\UniqueIndex
     */
    protected $username;

    /**
     * @ODM\String
     * @Shard\Serializer\Ignore
     */
    protected $password;


    /** @ODM\EmbedMany(targetDocument="Group") */
    protected $groups;

    /** @ODM\EmbedOne(targetDocument="Profile") */
    protected $profile;

    /**
     * @ODM\Field(type="string")
     */
    protected $location;

    public function __construct($id = null)
    {
        $this->id = $id;
        $this->groups = new ArrayCollection();
    }

    public function getId() {
        return $this->id;
    }

    public function setId($id) {
        $this->id = $id;
    }

    public function getUsername() {
        return $this->username;
    }

    public function setUsername($username) {
        $this->username = $username;
    }

    public function getPassword() {
        return $this->password;
    }

    public function setPassword($password) {
        $this->password = $password;
    }

    public function location() {
        return $this->location;
    }

    public function defineLocation($location) {
        $this->location = $location;
    }

    public function getGroups()
    {
        return $this->groups;
    }

    public function setGroups(array $groups){
        $this->groups = $groups;
    }

    public function addGroup(Group $group)
    {
        $this->groups[] = $group;
    }

    public function getProfile() {
        return $this->profile;
    }

    public function setProfile(Profile $profile) {
        $this->profile = $profile;
    }
}
