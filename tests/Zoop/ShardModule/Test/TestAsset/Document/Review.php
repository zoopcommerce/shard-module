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
class Review {

    /**
     * @ODM\Id(strategy="NONE")
     */
    protected $title;

    /**
     * @ODM\ReferenceOne(targetDocument="Game", simple="true", inversedBy="reviews", cascade="all")
     */
    protected $game;

    /**
     * @ODM\ReferenceOne(targetDocument="Author", simple="true", inversedBy="games", cascade="all")
     */
    protected $author;

    /**
     *
     * @ODM\String
     */
    protected $content;

    public function getTitle() {
        return $this->title;
    }

    public function setTitle($title) {
        $this->title = $title;
    }

    public function getGame() {
        return $this->game;
    }

    public function setGame($game) {
        $this->game = $game;
    }

    public function getAuthor() {
        return $this->author;
    }

    public function setAuthor($author) {
        $this->author = $author;
    }

    public function getContent() {
        return $this->content;
    }

    public function setContent($content) {
        $this->content = $content;
    }
}
