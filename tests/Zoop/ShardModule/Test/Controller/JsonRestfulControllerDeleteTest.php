<?php

namespace Zoop\ShardModule\Test\Controller;

use Zoop\ShardModule\Test\TestAsset\TestData;
use Zend\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;
use Zend\Http\Header\Accept;

class JsonRestfulControllerDeleteTest extends AbstractHttpControllerTestCase{

    protected static $staticDcumentManager;

    protected static $dbDataCreated = false;

    public static function tearDownAfterClass(){
        TestData::remove(static::$staticDcumentManager);
    }

    public function setUp(){

        $this->setApplicationConfig(
            include __DIR__ . '/../../../../test.application.config.php'
        );

        parent::setUp();

        $this->documentManager = $this->getApplicationServiceLocator()->get('doctrine.odm.documentmanager.default');
        static::$staticDcumentManager = $this->documentManager;

        if ( ! static::$dbDataCreated){
            //Create data in the db to query against
            TestData::create($this->documentManager);
            static::$dbDataCreated = true;
        }
    }

    public function testDelete(){

        $accept = new Accept;
        $accept->addMediaType('application/json');

        $this->getRequest()
            ->setMethod('DELETE')
            ->getHeaders()->addHeader($accept);

        $this->dispatch('/rest/author/harry', 'DELETE');

        $result = json_decode($this->getResponse()->getContent(), true);
        $this->assertFalse(isset($result));

        $this->assertResponseStatusCode(204);
        $this->assertControllerName('rest.default.author');
        $this->assertControllerClass('JsonRestfulController');
        $this->assertMatchedRouteName('rest.default');

        $author = $this->documentManager->getRepository('Zoop\ShardModule\Test\TestAsset\Document\Author')->find('harry');
        $this->assertFalse(isset($author));
    }

    public function testDeleteDoesNotExist(){

        $accept = new Accept;
        $accept->addMediaType('application/json');

        $this->getRequest()
            ->setMethod('DELETE')
            ->getHeaders()->addHeader($accept);

        $this->dispatch('/rest/author/billy', 'DELETE');

        $result = json_decode($this->getResponse()->getContent(), true);
        $this->assertFalse(isset($result));

        $this->assertResponseStatusCode(204);
        $this->assertControllerName('rest.default.author');
        $this->assertControllerClass('JsonRestfulController');
        $this->assertMatchedRouteName('rest.default');

        $author = $this->documentManager->getRepository('Zoop\ShardModule\Test\TestAsset\Document\Author')->find('billy');
        $this->assertFalse(isset($author));
    }

    public function testDelete404(){

        $accept = new Accept;
        $accept->addMediaType('application/json');

        $this->getRequest()
            ->setMethod('DELETE')
            ->getHeaders()->addHeader($accept);

        $this->dispatch('/rest/game/does-not-exist/author', 'DELETE');

        $result = json_decode($this->getResponse()->getContent(), true);
        $this->assertTrue(isset($result));

        $this->assertResponseStatusCode(404);
    }

    public function testDeleteDeepEmbeddedOne(){

        $accept = new Accept;
        $accept->addMediaType('application/json');

        $this->getRequest()
            ->setMethod('DELETE')
            ->getHeaders()->addHeader($accept);

        $this->dispatch('/rest/game/feed-the-kitty/publisher/country', 'DELETE');

        $result = json_decode($this->getResponse()->getContent(), true);
        $this->assertFalse(isset($result));

        $this->assertResponseStatusCode(204);
        $this->assertControllerName('rest.default.game');
        $this->assertControllerClass('JsonRestfulController');
        $this->assertMatchedRouteName('rest.default');

        $game = $this->documentManager->getRepository('Zoop\ShardModule\Test\TestAsset\Document\Game')->find('feed-the-kitty');
        $country = $game->getPublisher()->getCountry();
        $this->assertFalse(isset($country));
    }

    public function testDeleteEmbeddedOne(){

        $accept = new Accept;
        $accept->addMediaType('application/json');

        $this->getRequest()
            ->setMethod('DELETE')
            ->getHeaders()->addHeader($accept);

        $this->dispatch('/rest/game/feed-the-kitty/publisher', 'DELETE');

        $result = json_decode($this->getResponse()->getContent(), true);
        $this->assertFalse(isset($result));

        $this->assertResponseStatusCode(204);
        $this->assertControllerName('rest.default.game');
        $this->assertControllerClass('JsonRestfulController');
        $this->assertMatchedRouteName('rest.default');

        $game = $this->documentManager->getRepository('Zoop\ShardModule\Test\TestAsset\Document\Game')->find('feed-the-kitty');
        $publisher = $game->getPublisher();
        $this->assertFalse(isset($publisher));
    }

    public function testDeleteEmbeddedListItem(){

        $accept = new Accept;
        $accept->addMediaType('application/json');

        $this->getRequest()
            ->setMethod('DELETE')
            ->getHeaders()->addHeader($accept);

        $this->dispatch('/rest/game/feed-the-kitty/components/action-dice', 'DELETE');

        $result = json_decode($this->getResponse()->getContent(), true);
        $this->assertFalse(isset($result));

        $this->assertResponseStatusCode(204);
        $this->assertControllerName('rest.default.game');
        $this->assertControllerClass('JsonRestfulController');
        $this->assertMatchedRouteName('rest.default');

        $this->documentManager->clear();
        $game = $this->documentManager->getRepository('Zoop\ShardModule\Test\TestAsset\Document\Game')->find('feed-the-kitty');
        $compoents = $game->getComponents();
        $this->assertEquals('kitty-bowl', $compoents[0]->getName());
    }

    public function testDeleteEmbeddedList(){

        $accept = new Accept;
        $accept->addMediaType('application/json');

        $this->getRequest()
            ->setMethod('DELETE')
            ->getHeaders()->addHeader($accept);

        $this->dispatch('/rest/game/feed-the-kitty/components', 'DELETE');

        $result = json_decode($this->getResponse()->getContent(), true);
        $this->assertFalse(isset($result));

        $this->assertResponseStatusCode(204);
        $this->assertControllerName('rest.default.game');
        $this->assertControllerClass('JsonRestfulController');
        $this->assertMatchedRouteName('rest.default');

        $this->documentManager->clear();
        $game = $this->documentManager->getRepository('Zoop\ShardModule\Test\TestAsset\Document\Game')->find('feed-the-kitty');
        $compoents = $game->getComponents();
        $this->assertCount(0, $compoents);
    }

    public function testDeleteDeepReferenceOne(){

        $accept = new Accept;
        $accept->addMediaType('application/json');

        $this->getRequest()
            ->setMethod('DELETE')
            ->getHeaders()->addHeader($accept);

        $this->dispatch('/rest/game/feed-the-kitty/author/country', 'DELETE');

        $result = json_decode($this->getResponse()->getContent(), true);
        $this->assertFalse(isset($result));

        $this->assertResponseStatusCode(204);
        $this->assertControllerName('rest.default.game');
        $this->assertControllerClass('JsonRestfulController');
        $this->assertMatchedRouteName('rest.default');

        $game = $this->documentManager->getRepository('Zoop\ShardModule\Test\TestAsset\Document\Game')->find('feed-the-kitty');
        $country = $game->getAuthor()->getCountry();
        $this->assertFalse(isset($country));
    }

    public function testDeleteReferenceOne(){

        $accept = new Accept;
        $accept->addMediaType('application/json');

        $this->getRequest()
            ->setMethod('DELETE')
            ->getHeaders()->addHeader($accept);

        $this->dispatch('/rest/game/feed-the-kitty/author', 'DELETE');

        $result = json_decode($this->getResponse()->getContent(), true);
        $this->assertFalse(isset($result));

        $this->assertResponseStatusCode(204);
        $this->assertControllerName('rest.default.game');
        $this->assertControllerClass('JsonRestfulController');
        $this->assertMatchedRouteName('rest.default');

        $game = $this->documentManager->getRepository('Zoop\ShardModule\Test\TestAsset\Document\Game')->find('feed-the-kitty');
        $author = $game->getAuthor();
        $this->assertFalse(isset($author));
    }

    public function testDeleteDeepReferenceListItem(){

        $accept = new Accept;
        $accept->addMediaType('application/json');

        $this->getRequest()
            ->setMethod('DELETE')
            ->getHeaders()->addHeader($accept);

        $this->dispatch('/rest/game/feed-the-kitty/reviews/great-review/author', 'DELETE');

        $result = json_decode($this->getResponse()->getContent(), true);
        $this->assertFalse(isset($result));

        $this->assertResponseStatusCode(204);
        $this->assertControllerName('rest.default.game');
        $this->assertControllerClass('JsonRestfulController');
        $this->assertMatchedRouteName('rest.default');

        $game = $this->documentManager->getRepository('Zoop\ShardModule\Test\TestAsset\Document\Game')->find('feed-the-kitty');
        foreach($game->getReviews() as $review){
            if ($review->getTitle() == 'great-review'){
                break;
            }
        }
        $author = $review->getAuthor();
        $this->assertFalse(isset($author));
    }

    public function testDeleteReferenceListItem(){

        $accept = new Accept;
        $accept->addMediaType('application/json');

        $this->getRequest()
            ->setMethod('DELETE')
            ->getHeaders()->addHeader($accept);

        $this->dispatch('/rest/game/feed-the-kitty/reviews/great-review', 'DELETE');

        $result = json_decode($this->getResponse()->getContent(), true);
        $this->assertFalse(isset($result));

        $this->assertResponseStatusCode(204);
        $this->assertControllerName('rest.default.game');
        $this->assertControllerClass('JsonRestfulController');
        $this->assertMatchedRouteName('rest.default');

        $game = $this->documentManager->getRepository('Zoop\ShardModule\Test\TestAsset\Document\Game')->find('feed-the-kitty');
        $reviews = $game->getReviews();
        $this->assertCount(1, $reviews);

    }

    public function testDeleteReferenceList(){

        $accept = new Accept;
        $accept->addMediaType('application/json');

        $this->getRequest()
            ->setMethod('DELETE')
            ->getHeaders()->addHeader($accept);

        $this->dispatch('/rest/game/feed-the-kitty/reviews', 'DELETE');

        $result = json_decode($this->getResponse()->getContent(), true);
        $this->assertFalse(isset($result));

        $this->assertResponseStatusCode(204);
        $this->assertControllerName('rest.default.game');
        $this->assertControllerClass('JsonRestfulController');
        $this->assertMatchedRouteName('rest.default');

        $game = $this->documentManager->getRepository('Zoop\ShardModule\Test\TestAsset\Document\Game')->find('feed-the-kitty');
        $reviews = $game->getReviews();
        $this->assertCount(0, $reviews);
    }

    public function testDeleteList(){

        $accept = new Accept;
        $accept->addMediaType('application/json');

        $this->getRequest()
            ->setMethod('DELETE')
            ->getHeaders()->addHeader($accept);

        $this->dispatch('/rest/author', 'DELETE');

        $result = json_decode($this->getResponse()->getContent(), true);
        $this->assertFalse(isset($result));

        $this->assertResponseStatusCode(204);
        $this->assertControllerName('rest.default.author');
        $this->assertControllerClass('JsonRestfulController');
        $this->assertMatchedRouteName('rest.default');

        $cursor = $this->documentManager->getRepository('Zoop\ShardModule\Test\TestAsset\Document\Author')->findAll();

        $this->assertCount(0, $cursor);
    }
}
