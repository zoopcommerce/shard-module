<?php

namespace Zoop\ShardModule\Test\TestAsset\Document;

use \DateTime;
//Annotation imports
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Zoop\Shard\Annotation\Annotations as Shard;

/**
 * @ODM\Document
 * @Shard\AccessControl({
 *     @Shard\Permission\Basic(roles="*", allow="*")
 * })
 */
class Review
{
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

    /**
     *
     * @ODM\Date
     */
    protected $date;

    /**
     * Score out of 100
     *
     * @ODM\Float
     */
    protected $score;

    /**
     * Number of comments
     *
     * @ODM\Int
     */
    protected $numComments;

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getGame()
    {
        return $this->game;
    }

    public function setGame($game)
    {
        $this->game = $game;
    }

    public function getAuthor()
    {
        return $this->author;
    }

    public function setAuthor($author)
    {
        $this->author = $author;
    }

    public function getContent()
    {
        return $this->content;
    }

    public function setContent($content)
    {
        $this->content = $content;
    }

    /**
     *
     * @return DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     *
     * @param DateTime $date
     */
    public function setDate(DateTime $date)
    {
        $this->date = $date;
    }

    /**
     * @return float
     */
    public function getScore()
    {
        return $this->score;
    }

    /**
     * @param float $score
     */
    public function setScore($score)
    {
        $this->score = (float) $score;
    }

    /**
     * @return int
     */
    public function getNumComments()
    {
        return $this->numComments;
    }

    /**
     * @param int $numComments
     */
    public function setNumComments($numComments)
    {
        $this->numComments = (int) $numComments;
    }
}
