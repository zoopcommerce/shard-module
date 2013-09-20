<?php

namespace Zoop\ShardModule\Test\Controller;

use Zoop\ShardModule\Test\TestAsset\TestData;
use Zend\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;
use Zend\Http\Header\Accept;
use Zend\Http\Header\ContentType;

class RestfulControllerUpdateTest extends AbstractHttpControllerTestCase
{
    protected static $staticDcumentManager;

    protected static $dbDataCreated = false;

    public static function tearDownAfterClass()
    {
        //Cleanup db after all tests have run
        TestData::remove(static::$staticDcumentManager);
    }

    public function setUp()
    {
        $this->setApplicationConfig(
            include __DIR__ . '/../../../../test.application.config.php'
        );

        parent::setUp();

        $this->documentManager = $this->getApplicationServiceLocator()->get('doctrine.odm.documentmanager.default');
        static::$staticDcumentManager = $this->documentManager;

        if (! static::$dbDataCreated) {
            //Create data in the db to query against
            TestData::create($this->documentManager);
            static::$dbDataCreated = true;
        }
    }
/*
    public function testCreateViaUpdate()
    {
        $accept = new Accept;
        $accept->addMediaType('application/json');

        $this->getRequest()
            ->setMethod('PUT')
            ->setContent('{"type": "card"}')
            ->getHeaders()->addHeaders([$accept, ContentType::fromString('Content-type: application/json')]);

        $this->dispatch('/rest/game/uno');

        $response = $this->getResponse();
        $result = json_decode($response->getContent(), true);

        $this->assertResponseStatusCode(201);
        $this->assertFalse(isset($result));

        $game = $this->documentManager->getRepository('Zoop\ShardModule\Test\TestAsset\Document\Game')->find('uno');
        $this->assertEquals('card', $game->getType());
    }

    public function testUpdateValidationFail()
    {
        $accept = new Accept;
        $accept->addMediaType('application/json');

        $this->getRequest()
            ->setMethod('PUT')
            ->setContent('{"author": {"$ref": "author/thomas"}}')
            ->getHeaders()->addHeaders([$accept, ContentType::fromString('Content-type: application/json')]);

        $this->dispatch('/rest/game/feed-the-kitty');

        $this->assertResponseStatusCode(500);
        $this->assertEquals(
            'Content-Type: application/api-problem+json',
            $this->getResponse()->getHeaders()->get('Content-Type')->toString()
        );

        $result = json_decode($this->getResponse()->getContent(), true);

        $this->assertEquals('/exception/document-validation-failed', $result['describedBy']);
        $this->assertEquals('Document validation failed', $result['title']);
        $this->assertEquals('type: This value is required', $result['validatorMessages'][0]);

        $game = $this->documentManager
            ->getRepository('Zoop\ShardModule\Test\TestAsset\Document\Game')->find('feed-the-kitty');
        $components = $game->getComponents();
        $this->assertCount(3, $components);
    }

    public function testUpdateEmbedded404()
    {
        $accept = new Accept;
        $accept->addMediaType('application/json');

        $this->getRequest()
            ->setMethod('PUT')
            ->setContent('{"country": {"$ref": "country/us"}}')
            ->getHeaders()->addHeaders([$accept, ContentType::fromString('Content-type: application/json')]);

        $this->dispatch('/rest/game/feed-the-kitty/does-not-exist');

        $result = json_decode($this->getResponse()->getContent(), true);
        $this->assertResponseStatusCode(404);
        $this->assertEquals(
            'Content-Type: application/api-problem+json',
            $this->getResponse()->getHeaders()->get('Content-Type')->toString()
        );
        $this->assertEquals('/exception/document-not-found', $result['describedBy']);
        $this->assertEquals('Document not found', $result['title']);
    }

    public function testUpdateEmbeddedOne()
    {
        $accept = new Accept;
        $accept->addMediaType('application/json');

        $this->getRequest()
            ->setMethod('PUT')
            ->setContent('{"name": "gamewright", "country": {"$ref": "country/germany"}}')
            ->getHeaders()->addHeaders([$accept, ContentType::fromString('Content-type: application/json')]);

        $this->dispatch('/rest/game/feed-the-kitty/publisher');

        $response = $this->getResponse();
        $result = json_decode($response->getContent(), true);

        $this->assertResponseStatusCode(204);
        $this->assertFalse(isset($result));

        $game = $this->documentManager
            ->getRepository('Zoop\ShardModule\Test\TestAsset\Document\Game')
            ->find('feed-the-kitty');

        $publisher = $game->getPublisher();
        $this->assertEquals('gamewright', $publisher->getName());
        $this->assertEquals('germany', $publisher->getCountry()->getName());
        $this->assertNull($publisher->getCity());
    }

    public function testUpdateEmbeddedListItem()
    {
        $accept = new Accept;
        $accept->addMediaType('application/json');

        $this->getRequest()
            ->setMethod('PUT')
            ->setContent('{"type": "custom"}')
            ->getHeaders()->addHeaders([$accept, ContentType::fromString('Content-type: application/json')]);

        $this->dispatch('/rest/game/feed-the-kitty/components/action-dice');

        $response = $this->getResponse();
        $result = json_decode($response->getContent(), true);

        $this->assertResponseStatusCode(204);
        $this->assertFalse(isset($result));

        $game = $this->documentManager
             ->getRepository('Zoop\ShardModule\Test\TestAsset\Document\Game')
             ->find('feed-the-kitty');

        $components = $game->getComponents();
        $this->assertEquals('custom', $components['action-dice']->getType());
        $this->assertCount(0, $components['action-dice']->getManufacturers());
    }

    public function testUpdateEmbeddedListItemWithNew()
    {
        $accept = new Accept;
        $accept->addMediaType('application/json');

        $this->getRequest()
            ->setMethod('PUT')
            ->setContent('{"type": "paper"}')
            ->getHeaders()->addHeaders([$accept, ContentType::fromString('Content-type: application/json')]);

        $this->dispatch('/rest/game/feed-the-kitty/components/feedback-form');

        $response = $this->getResponse();
        $result = json_decode($response->getContent(), true);

        $this->assertResponseStatusCode(204);
        $this->assertFalse(isset($result));

        $game = $this->documentManager
            ->getRepository('Zoop\ShardModule\Test\TestAsset\Document\Game')
            ->find('feed-the-kitty');

        $components = $game->getComponents();
        $this->assertEquals('paper', $components['feedback-form']->getType());
    }

    public function testReplaceEmbeddedList()
    {
        $accept = new Accept;
        $accept->addMediaType('application/json');

        $this->getRequest()
            ->setMethod('PUT')
            ->setContent(
                '[
                    {"name": "instructions", "type": "paper"},
                    {"name": "game-box", "type": "telescoping"}
                ]'
            )
            ->getHeaders()->addHeaders([$accept, ContentType::fromString('Content-type: application/json')]);

        $this->dispatch('/rest/game/feed-the-kitty/components');

        $response = $this->getResponse();
        $result = json_decode($response->getContent(), true);

        $this->assertResponseStatusCode(204);
        $this->assertFalse(isset($result));

        $game = $this->documentManager
             ->getRepository('Zoop\ShardModule\Test\TestAsset\Document\Game')
             ->find('feed-the-kitty');

        $components = $game->getComponents();
        $this->assertEquals('paper', $components[0]->getType());
        $this->assertEquals('telescoping', $components[1]->getType());
        $this->assertCount(2, $components);
    }

    public function testUpdateReferencedOne()
    {
        $accept = new Accept;
        $accept->addMediaType('application/json');

        $this->getRequest()
            ->setMethod('PUT')
            ->setContent('{"country": {"$ref": "country/us"}}')
            ->getHeaders()->addHeaders([$accept, ContentType::fromString('Content-type: application/json')]);

        $this->dispatch('/rest/game/feed-the-kitty/author');

        $response = $this->getResponse();
        $result = json_decode($response->getContent(), true);

        $this->assertResponseStatusCode(204);
        $this->assertFalse(isset($result));

        $game = $this->documentManager
            ->getRepository('Zoop\ShardModule\Test\TestAsset\Document\Game')
            ->find('feed-the-kitty');

        $author = $game->getAuthor();
        $this->assertEquals('james', $author->getName());
        $this->assertEquals('us', $author->getCountry()->getName());
    }

    public function testUpdateReferencedOneWithReference()
    {
        $accept = new Accept;
        $accept->addMediaType('application/json');

        $this->getRequest()
            ->setMethod('PUT')
            ->setContent('{"$ref": "author/bill"}')
            ->getHeaders()->addHeaders([$accept, ContentType::fromString('Content-type: application/json')]);

        $this->dispatch('/rest/game/feed-the-kitty/author');

        $response = $this->getResponse();
        $result = json_decode($response->getContent(), true);

        $this->assertResponseStatusCode(204);
        $this->assertFalse(isset($result));

        $game = $this->documentManager
            ->getRepository('Zoop\ShardModule\Test\TestAsset\Document\Game')
            ->find('feed-the-kitty');

        $author = $game->getAuthor();
        $this->assertEquals('bill', $author->getName());
        $this->assertEquals('germany', $author->getCountry()->getName());
    }

    public function testUpdateReferencedOneWithNew()
    {
        $accept = new Accept;
        $accept->addMediaType('application/json');

        $this->getRequest()
            ->setMethod('PUT')
            ->setContent('{"name": "oscar"}')
            ->getHeaders()->addHeaders([$accept, ContentType::fromString('Content-type: application/json')]);

        $this->dispatch('/rest/game/feed-the-kitty/author');

        $response = $this->getResponse();
        $result = json_decode($response->getContent(), true);

        $this->assertResponseStatusCode(201);
        $this->assertFalse(isset($result));

        $this->documentManager->clear();
        $game = $this->documentManager
             ->getRepository('Zoop\ShardModule\Test\TestAsset\Document\Game')
             ->find('feed-the-kitty');

        $author = $game->getAuthor();
        $this->assertEquals('oscar', $author->getName());

        $author = $this->documentManager
            ->getRepository('Zoop\ShardModule\Test\TestAsset\Document\Author')
            ->find('oscar');

        $this->assertTrue(isset($author));
    }

    public function testUpdateReferencedListItem()
    {
        $accept = new Accept;
        $accept->addMediaType('application/json');

        $this->getRequest()
            ->setMethod('PUT')
            ->setContent('{"author": {"$ref" : "author/james"}}')
            ->getHeaders()->addHeaders([$accept, ContentType::fromString('Content-type: application/json')]);

        $this->dispatch('/rest/game/feed-the-kitty/reviews/great-review');

        $response = $this->getResponse();
        $result = json_decode($response->getContent(), true);

        $this->assertResponseStatusCode(204);
        $this->assertFalse(isset($result));

        $game = $this->documentManager
            ->getRepository('Zoop\ShardModule\Test\TestAsset\Document\Game')
             ->find('feed-the-kitty');

        $review = $game->getReviews()[0];
        $this->assertEquals('great-review', $review->getTitle());
        $this->assertEquals('james', $review->getAuthor()->getName());

    }

    public function testUpdateReferencedListItemWithNew()
    {
        $accept = new Accept;
        $accept->addMediaType('application/json');

        $this->getRequest()
            ->setMethod('PUT')
            ->setContent('{"content" : "blah blah blah"}')
            ->getHeaders()->addHeaders([$accept, ContentType::fromString('Content-type: application/json')]);

        $this->dispatch('/rest/game/feed-the-kitty/reviews/another-review');

        $response = $this->getResponse();
        $result = json_decode($response->getContent(), true);

        $this->assertResponseStatusCode(201);
        $this->assertFalse(isset($result));

        $game = $this->documentManager
            ->getRepository('Zoop\ShardModule\Test\TestAsset\Document\Game')
            ->find('feed-the-kitty');

        $review = $game->getReviews()[2];
        $this->assertEquals('another-review', $review->getTitle());

    }

    public function testUpdateExistingDocument()
    {
        $accept = new Accept;
        $accept->addMediaType('application/json');

        $this->getRequest()
            ->setMethod('PUT')
            ->setContent('{"type": "childrens", "author": {"$ref": "author/harry"}}')
            ->getHeaders()->addHeaders([$accept, ContentType::fromString('Content-type: application/json')]);

        $this->dispatch('/rest/game/feed-the-kitty', 'PUT');

        $response = $this->getResponse();
        $result = json_decode($response->getContent(), true);

        $this->assertResponseStatusCode(204);
        $this->assertFalse(isset($result));

        $game = $this->documentManager
             ->getRepository('Zoop\ShardModule\Test\TestAsset\Document\Game')
             ->find('feed-the-kitty');

        $this->assertEquals('childrens', $game->getType());
        $this->assertEquals('harry', $game->getAuthor()->getName());
        $this->assertEquals(null, $game->getPublisher());
        $this->assertCount(0, $game->getReviews());
    }
*/
    public function testUpdateExistingDocumentId()
    {
        $accept = new Accept;
        $accept->addMediaType('application/json');

        $this->getRequest()
            ->setMethod('PUT')
            ->setContent('{"name": "thomas-dean", "nickname": "deano"}')
            ->getHeaders()->addHeaders([$accept, ContentType::fromString('Content-type: application/json')]);

        $this->dispatch('/rest/author/thomas');

        $response = $this->getResponse();
        $result = json_decode($response->getContent(), true);

        $this->assertResponseStatusCode(204);
        $this->assertFalse(isset($result));

        $this->assertEquals(
            'Location: /rest/author/thomas-dean',
            $response->getHeaders()->get('Location')->toString()
        );

        $this->documentManager->clear();
        $author = $this->documentManager
            ->getRepository('Zoop\ShardModule\Test\TestAsset\Document\Author')->find('thomas');

        $this->assertFalse(isset($author));

        $author = $this->documentManager
            ->getRepository('Zoop\ShardModule\Test\TestAsset\Document\Author')->find('thomas-dean');

        $this->assertTrue(isset($author));
        $this->assertEquals('deano', $author->getNickname());

        $review = $this->documentManager
            ->getRepository('Zoop\ShardModule\Test\TestAsset\Document\Review')->find('bad-review');

        $this->assertEquals('thomas-dean', $review->getAuthor()->getName());
    }
/*
    public function testReplaceReferencedList()
    {
        $accept = new Accept;
        $accept->addMediaType('application/json');

        $this->getRequest()
            ->setMethod('PUT')
            ->setContent(
                '[
                    {"title" : "new-review-1"},
                    {"title" : "new-review-2"}
                ]'
            )
            ->getHeaders()->addHeaders([$accept, ContentType::fromString('Content-type: application/json')]);

        $this->dispatch('/rest/game/feed-the-kitty/reviews', 'PUT');

        $response = $this->getResponse();
        $result = json_decode($response->getContent(), true);

        $this->assertResponseStatusCode(204);
        $this->assertFalse(isset($result));

        $game = $this->documentManager
            ->getRepository('Zoop\ShardModule\Test\TestAsset\Document\Game')->find('feed-the-kitty');

        $review = $game->getReviews()[0];
        $this->assertEquals('new-review-1', $review->getTitle());
        $review = $game->getReviews()[1];
        $this->assertEquals('new-review-2', $review->getTitle());
    }

    public function testReplaceList()
    {
        $accept = new Accept;
        $accept->addMediaType('application/json');

        $this->getRequest()
            ->setMethod('PUT')
            ->setContent(
                '[
                    {"name": "dweebies", "type": "card"},
                    {"name": "exploding-chicken", "type": "dice"},
                    {"name": "kings-at-arms", "type": "card"}
                ]'
            )
            ->getHeaders()->addHeaders([$accept, ContentType::fromString('Content-type: application/json')]);

        $this->dispatch('/rest/game', 'PUT');

        $response = $this->getResponse();
        $result = json_decode($response->getContent(), true);

        $this->assertResponseStatusCode(204);
        $this->assertFalse(isset($result));

        $repository = $this->documentManager->getRepository('Zoop\ShardModule\Test\TestAsset\Document\Game');
        $game = $repository->find('dweebies');
        $this->assertTrue(isset($game));
        $game = $repository->find('exploding-chicken');
        $this->assertTrue(isset($game));
        $game = $repository->find('kings-at-arms');
        $this->assertTrue(isset($game));
        $game = $repository->find('feed-the-kitty');
        $this->assertFalse(isset($game));
    }
*/
}
