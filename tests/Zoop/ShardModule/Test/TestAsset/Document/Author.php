<?php

namespace Zoop\ShardModule\Test\TestAsset\Document;

use Doctrine\Common\Collections\ArrayCollection;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Zoop\Shard\Annotation\Annotations as Shard;

/**
 * @ODM\Document
 * @Shard\AccessControl({
 *     @Shard\Permission\Basic(roles="*", allow="*")
 * })
 */
class Author
{
    /**
     * @ODM\Id(strategy="none")
     * @Shard\Validator\Slug
     */
    protected $name;

    /**
     * @ODM\ReferenceOne(targetDocument="Country", simple="true", inversedBy="authors", cascade="persist")
     */
    protected $country;

    /**
     * @ODM\ReferenceMany(targetDocument="Game", mappedBy="author")
     */
    protected $games;

    /**
     * @ODM\ReferenceMany(targetDocument="Review", mappedBy="author")
     */
    protected $reviews;

    /**
     * @ODM\String
     * @Shard\Validator\Chain({
     *     @Shard\Validator\NotRequired,
     *     @Shard\Validator\Slug
     * })
     */
    protected $nickname;

    public function __construct()
    {
        $this->games = new ArrayCollection();
        $this->reviews = new ArrayCollection();
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getCountry()
    {
        return $this->country;
    }

    public function setCountry($country)
    {
        $this->country = $country;
    }

    public function getGames()
    {
        return $this->games;
    }

    public function setGames($games)
    {
        $this->games = $games;
    }

    public function getReviews()
    {
        return $this->reviews;
    }

    public function setReviews($reviews)
    {
        $this->reviews = $reviews;
    }

    public function getNickname()
    {
        return $this->nickname;
    }

    public function setNickname($nickname)
    {
        $this->nickname = $nickname;
    }
}
