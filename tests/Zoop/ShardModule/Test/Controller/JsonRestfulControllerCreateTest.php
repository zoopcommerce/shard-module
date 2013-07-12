<?php

namespace Zoop\ShardModule\Test\Controller;

use Zoop\ShardModule\Test\TestAsset\TestData;
use Zend\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;
use Zend\Http\Header\Accept;
use Zend\Http\Header\ContentType;

class JsonRestfulControllerCreateTest extends AbstractHttpControllerTestCase{

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

    public function testCreate(){

        $accept = new Accept;
        $accept->addMediaType('application/json');

        $this->getRequest()
            ->setMethod('POST')
            ->setContent('{"name": "forbidden-island", "type": "co-op"}')
            ->getHeaders()->addHeaders([$accept, ContentType::fromString('Content-type: application/json')]);

        $this->dispatch('/rest/game');

        $response = $this->getResponse();
        $result = json_decode($response->getContent(), true);

        $this->assertResponseStatusCode(201);
        $this->assertEquals('Location: /rest/game/forbidden-island', $response->getHeaders()->get('Location')->toString());
        $this->assertFalse(isset($result));

        $game = $this->documentManager->getRepository('Zoop\ShardModule\Test\TestAsset\Document\Game')->find('forbidden-island');
        $this->assertEquals('co-op', $game->getType());
    }

    public function testCreateDeep404(){

        $accept = new Accept;
        $accept->addMediaType('application/json');

        $this->getRequest()
            ->setMethod('POST')
            ->setContent('{"name": "nathan"}')
            ->getHeaders()->addHeaders([$accept, ContentType::fromString('Content-type: application/json')]);

        $this->dispatch('/rest/game/does-not-exist/author');

        $response = $this->getResponse();
        $result = json_decode($response->getContent(), true);

        $this->assertResponseStatusCode(404);
    }

    public function testCreateValidationFail(){

        $accept = new Accept;
        $accept->addMediaType('application/json');

        $this->getRequest()
            ->setMethod('POST')
            ->setContent('{"name": "missingType"}')
            ->getHeaders()->addHeaders([$accept, ContentType::fromString('Content-type: application/json')]);

        $this->dispatch('/rest/game');

        $this->assertResponseStatusCode(500);
        $this->assertEquals('Content-Type: application/api-problem+json', $this->getResponse()->getHeaders()->get('Content-Type')->toString());

        $result = json_decode($this->getResponse()->getContent(), true);

        $this->assertEquals('/exception/document-validation-failed', $result['describedBy']);
        $this->assertEquals('Document validation failed', $result['title']);
        $this->assertEquals('type: This value is required', $result['validatorMessages'][0]);
    }

    public function testCreateAlreadyExistsFail(){

        $accept = new Accept;
        $accept->addMediaType('application/json');

        $this->getRequest()
            ->setMethod('POST')
            ->setContent('{"name": "seven-wonders", "type": "card"}')
            ->getHeaders()->addHeaders([$accept, ContentType::fromString('Content-type: application/json')]);

        $this->dispatch('/rest/game', 'POST');

        $result = json_decode($this->getResponse()->getContent(), true);

        $this->assertResponseStatusCode(500);
        $this->assertEquals('Content-Type: application/api-problem+json', $this->getResponse()->getHeaders()->get('Content-Type')->toString());
        $this->assertEquals('/exception/document-already-exists', $result['describedBy']);
        $this->assertEquals('Document already exists', $result['title']);
    }

    public function testEmbeddedCreateListItem(){

        $accept = new Accept;
        $accept->addMediaType('application/json');

        $this->getRequest()
            ->setMethod('POST')
            ->setContent('{
                "name": "age-I",
                "type": "card"
            }')
            ->getHeaders()->addHeaders([$accept, ContentType::fromString('Content-type: application/json')]);

        $this->dispatch('/rest/game/seven-wonders/components');

        $response = $this->getResponse();
        $result = json_decode($response->getContent(), true);

        $this->assertResponseStatusCode(201);
        $this->assertEquals('Location: /rest/game/seven-wonders/components/age-I', $response->getHeaders()->get('Location')->toString());
        $this->assertFalse(isset($result));

        $game = $this->documentManager->getRepository('Zoop\ShardModule\Test\TestAsset\Document\Game')->find('seven-wonders');
        $this->assertEquals('card', $game->getComponents()[2]->getType());
        $game->getComponents()->remove(2);
        $this->documentManager->flush();
    }

    public function testEmbeddedCreateListItemParentDoesNotExistFail(){

        $accept = new Accept;
        $accept->addMediaType('application/json');

        $this->getRequest()
            ->setMethod('POST')
            ->setContent('{"name": "box", "type": "box"}')
            ->getHeaders()->addHeaders([$accept, ContentType::fromString('Content-type: application/json')]);

        $this->dispatch('/rest/game/not-seven-wonders/components');

        $this->assertResponseStatusCode(404);
        $this->assertEquals('Content-Type: application/api-problem+json', $this->getResponse()->getHeaders()->get('Content-Type')->toString());
        $result = json_decode($this->getResponse()->getContent(), true);
        $this->assertEquals('/exception/document-not-found', $result['describedBy']);
        $this->assertEquals('Document not found', $result['title']);
    }

    public function testEmbeddedCreateListItemAlreadyExistsFail(){

        $accept = new Accept;
        $accept->addMediaType('application/json');

        $this->getRequest()
            ->setMethod('POST')
            ->setContent('{"name": "wonders", "type": "board"}')
            ->getHeaders()->addHeaders([$accept, ContentType::fromString('Content-type: application/json')]);

        $this->dispatch('/rest/game/seven-wonders/components');

        $result = json_decode($this->getResponse()->getContent(), true);

        $this->assertResponseStatusCode(500);
        $this->assertEquals('Content-Type: application/api-problem+json', $this->getResponse()->getHeaders()->get('Content-Type')->toString());

        $this->assertEquals('/exception/document-already-exists', $result['describedBy']);
        $this->assertEquals('Document already exists', $result['title']);
    }

    public function testReferencedCreateWithNewDocument(){

        $accept = new Accept;
        $accept->addMediaType('application/json');

        $this->getRequest()
            ->setMethod('POST')
            ->setContent('{
                "title": "good-review"
            }')
            ->getHeaders()->addHeaders([$accept, ContentType::fromString('Content-type: application/json')]);

        $this->dispatch('/rest/game/seven-wonders/reviews');

        $this->documentManager->clear();

        $response = $this->getResponse();
        $result = json_decode($response->getContent(), true);

        $this->assertResponseStatusCode(201);
        $this->assertEquals('Location: /rest/game/seven-wonders/reviews/good-review', $response->getHeaders()->get('Location')->toString());
        $this->assertFalse(isset($result));

        $game = $this->documentManager->getRepository('Zoop\ShardModule\Test\TestAsset\Document\Game')->find('seven-wonders');
        $this->assertCount(2, $game->getReviews());
    }

    public function testReferencedCreateWithExistingDocument(){

        $accept = new Accept;
        $accept->addMediaType('application/json');

        $this->getRequest()
            ->setMethod('POST')
            ->setContent('{
                "$ref": "review/bad-review"
            }')
            ->getHeaders()->addHeaders([$accept, ContentType::fromString('Content-type: application/json')]);

        $this->dispatch('/rest/game/seven-wonders/reviews');

        $this->documentManager->clear();

        $response = $this->getResponse();
        $result = json_decode($response->getContent(), true);

        $this->assertResponseStatusCode(201);
        $this->assertEquals('Location: /rest/game/seven-wonders/reviews/bad-review', $response->getHeaders()->get('Location')->toString());
        $this->assertFalse(isset($result));

        $game = $this->documentManager->getRepository('Zoop\ShardModule\Test\TestAsset\Document\Game')->find('seven-wonders');
        $this->assertCount(3, $game->getReviews());
    }

    public function testReferencedCreateAlreadyExistsFail(){

        $accept = new Accept;
        $accept->addMediaType('application/json');

        $this->getRequest()
            ->setMethod('POST')
            ->setContent('{"title": "happy-review"}')
            ->getHeaders()->addHeaders([$accept, ContentType::fromString('Content-type: application/json')]);

        $this->dispatch('/rest/game/seven-wonders/reviews');

        $result = json_decode($this->getResponse()->getContent(), true);

        $this->assertResponseStatusCode(500);
        $this->assertEquals('Content-Type: application/api-problem+json', $this->getResponse()->getHeaders()->get('Content-Type')->toString());
        $this->assertEquals('/exception/document-already-exists', $result['describedBy']);
        $this->assertEquals('Document already exists', $result['title']);
    }

    public function testDeedNestedReferencedCreate(){

        $accept = new Accept;
        $accept->addMediaType('application/json');

        $this->getRequest()
            ->setMethod('POST')
            ->setContent('{"name": "new", "type": "test"}')
            ->getHeaders()->addHeaders([$accept, ContentType::fromString('Content-type: application/json')]);

        $this->dispatch('/rest/author/harry/reviews/happy-review/game/components');

        $response = $this->getResponse();
        $result = json_decode($response->getContent(), true);

        $this->assertResponseStatusCode(201);
        $this->assertEquals('Location: /rest/author/harry/reviews/happy-review/game/components/new', $response->getHeaders()->get('Location')->toString());
        $this->assertFalse(isset($result));

        $game = $this->documentManager->getRepository('Zoop\ShardModule\Test\TestAsset\Document\Game')->find('seven-wonders');
        $this->assertEquals('test', $game->getComponents()[2]->getType());
    }

    public function testDeedNestedEmbeddedCreate(){

        $accept = new Accept;
        $accept->addMediaType('application/json');

        $this->getRequest()
            ->setMethod('POST')
            ->setContent('{"name": "A1-boards", "email": "contact@here.com"}')
            ->getHeaders()->addHeaders([$accept, ContentType::fromString('Content-type: application/json')]);

        $this->dispatch('/rest/game/seven-wonders/components/wonders/manufacturers');

        $response = $this->getResponse();
        $result = json_decode($response->getContent(), true);

        $this->assertResponseStatusCode(201);
        $this->assertEquals('Location: /rest/game/seven-wonders/components/wonders/manufacturers/A1-boards', $response->getHeaders()->get('Location')->toString());
        $this->assertFalse(isset($result));

        $game = $this->documentManager->getRepository('Zoop\ShardModule\Test\TestAsset\Document\Game')->find('seven-wonders');
        $this->assertEquals('contact@here.com', $game->getComponents()[0]->getManufacturers()[0]->getEmail());
    }

    public function testDeedNestedEmbeddedOneCreate(){

        //I something is wrong in AbstractControllerTestCase. The documentManager shouldn't have to be cleared here.
        $this->documentManager->clear();

        $accept = new Accept;
        $accept->addMediaType('application/json');

        $this->getRequest()
            ->setMethod('POST')
            ->setContent('{"name": "samson"}')
            ->getHeaders()->addHeaders([$accept, ContentType::fromString('Content-type: application/json')]);

        $this->dispatch('/rest/game/seven-wonders/publisher/country/authors');

        $response = $this->getResponse();
        $result = json_decode($response->getContent(), true);

        $this->assertResponseStatusCode(201);
        $this->assertEquals('Location: /rest/game/seven-wonders/publisher/country/authors/samson', $response->getHeaders()->get('Location')->toString());
        $this->assertFalse(isset($result));

        $this->documentManager->clear();
        $country = $this->documentManager->getRepository('Zoop\ShardModule\Test\TestAsset\Document\Country')->find('belgum');
        $this->assertEquals('samson', $country->getAuthors()[0]->getName());
    }
}
