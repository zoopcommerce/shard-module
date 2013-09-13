<?php

namespace Zoop\ShardModule\Test\TestAsset;

use Zoop\ShardModule\Test\TestAsset\Document;

class TestData
{
    public static function create($documentManager)
    {
        //Create data in the db to query against
        $documentManager->getConnection()->selectDatabase('shardModuleTest');

        $country1 = new Document\Country;
        $country1->setName('us');
        $country2 = new Document\Country;
        $country2->setName('germany');
        $country3 = new Document\Country;
        $country3->setName('belgum');

        $publisher1 = new Document\Publisher;
        $publisher1->setName('gamewright');
        $publisher1->setCountry($country1);
        $publisher1->setCity('Little Rock');

        $publisher2 = new Document\Publisher;
        $publisher2->setName('three-amigos');
        $publisher2->setCountry($country3);

        $m1 = new Document\Manufacturer;
        $m1->setName('Ludo Fact');
        $m2 = new Document\Manufacturer;
        $m2->setName('Panda');

        $c1 = new Document\Component;
        $c1->setName('Action Dice');
        $c1->setType('die');
        $c1->setManufacturers([$m1, $m2]);
        $c2 = new Document\Component;
        $c2->setName('Kitty Bowl');
        $c2->setType('bowl');
        $c3 = new Document\Component;
        $c3->setType('mice');
        $components1 = ['action-dice' => $c1, 'kitty-bowl' => $c2, 'mice' => $c3];

        $c4 = new Document\Component;
        $c4->setName('Wonders');
        $c4->setType('board');
        $c5 = new Document\Component;
        $c5->setName('Money');
        $c5->setType('coin');
        $components2 = ['wonders' => $c4, 'money' => $c5];

        $author1 = new Document\Author;
        $author1->setName('harry');
        $author1->setCountry($country1);

        $author2 = new Document\Author;
        $author2->setName('thomas');
        $author2->setCountry($country2);
        $author2->setNickname('tommy');

        $author3 = new Document\Author;
        $author3->setName('james');
        $author3->setCountry($country2);
        $author3->setSecret($country2);

        $author4 = new Document\Author;
        $author4->setName('bill');
        $author4->setCountry($country2);

        $game1 = new Document\Game;
        $game1->setName('feed-the-kitty');
        $game1->setType('dice');
        $game1->setPublisher($publisher1);
        $game1->setComponents($components1);
        $game1->setAuthor($author3);

        $game2 = new Document\Game;
        $game2->setName('seven-wonders');
        $game2->setType('card');
        $game2->setComponents($components2);
        $game2->setPublisher($publisher2);

        $r1 = new Document\Review;
        $r1->setTitle('great-review');
        $r1->setAuthor($author1);
        $r1->setGame($game1);

        $r2 = new Document\Review;
        $r2->setTitle('bad-review');
        $r2->setAuthor($author2);
        $r2->setGame($game1);

        $r3 = new Document\Review;
        $r3->setTitle('happy-review');
        $r3->setAuthor($author4);
        $r3->setGame($game2);

        $documentManager->persist($country1);
        $documentManager->persist($country2);
        $documentManager->persist($country3);
        $documentManager->persist($author1);
        $documentManager->persist($author2);
        $documentManager->persist($author3);
        $documentManager->persist($author4);
        $documentManager->persist($game1);
        $documentManager->persist($game2);
        $documentManager->persist($r1);
        $documentManager->persist($r2);
        $documentManager->persist($r3);

        $documentManager->flush();
        $documentManager->clear();
    }

    public static function remove($documentManager)
    {
        //Cleanup db after all tests have run
        $collections = $documentManager->getConnection()->selectDatabase('shardModuleTest')->listCollections();
        foreach ($collections as $collection) {
            $collection->remove(array(), array('safe' => true));
        }
    }
}
