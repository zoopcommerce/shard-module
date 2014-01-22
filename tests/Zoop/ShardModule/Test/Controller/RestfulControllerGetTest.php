<?php

namespace Zoop\ShardModule\Test\Controller;

use Zoop\ShardModule\Test\BaseTest;
use Zend\Http\Header\Accept;

class RestfulControllerGetTest extends BaseTest
{
    public function testGet()
    {
        $accept = new Accept;
        $accept->addMediaType('application/json');

        $this->getRequest()
            ->setMethod('GET')
            ->getHeaders()->addHeader($accept);

        $this->dispatch('/rest/game/feed-the-kitty');

        $result = json_decode($this->getResponse()->getContent(), true);

        $this->assertResponseStatusCode(200);
        $this->assertControllerName('shard.rest.game');
        $this->assertControllerClass('RestfulController');
        $this->assertMatchedRouteName('rest');

        $this->assertEquals(
            'Cache-Control: no-cache',
            $this->getResponse()->getHeaders()->get('Cache-Control')->toString()
        );
        $this->assertEquals('feed-the-kitty', $result['name']);
        $this->assertEquals('dice', $result['type']);
    }

    public function testGetHTML()
    {
        $this->getRequest()
            ->setMethod('GET');

        $this->dispatch('/rest/game/feed-the-kitty');

        $this->assertResponseStatusCode(200);
        $this->assertControllerName('shard.rest.game');
        $this->assertControllerClass('RestfulController');
        $this->assertMatchedRouteName('rest');
        $this->assertTemplateName('zoop/rest/get');
        $this->assertEquals(
            'Cache-Control: no-cache',
            $this->getResponse()->getHeaders()->get('Cache-Control')->toString()
        );
    }

    public function testGet404()
    {
        $accept = new Accept;
        $accept->addMediaType('application/json');

        $this->getRequest()
            ->setMethod('GET')
            ->getHeaders()->addHeader($accept);

        $this->dispatch('/rest/game/does-not-exist');

        $result = json_decode($this->getResponse()->getContent(), true);

        $this->assertResponseStatusCode(404);
        $this->assertEquals(
            'Content-Type: application/api-problem+json',
            $this->getResponse()->getHeaders()->get('Content-Type')->toString()
        );

        $this->assertEquals('/exception/document-not-found', $result['describedBy']);
        $this->assertEquals('Document not found', $result['title']);
    }

    public function testGetDeep404()
    {
        $accept = new Accept;
        $accept->addMediaType('application/json');

        $this->getRequest()
            ->setMethod('GET')
            ->getHeaders()->addHeader($accept);

        $this->dispatch('/rest/game/does-not-exist/publisher');

        $result = json_decode($this->getResponse()->getContent(), true);

        $this->assertResponseStatusCode(404);
        $this->assertEquals(
            'Content-Type: application/api-problem+json',
            $this->getResponse()->getHeaders()->get('Content-Type')->toString()
        );

        $this->assertEquals('/exception/document-not-found', $result['describedBy']);
        $this->assertEquals('Document not found', $result['title']);
    }

    public function testGetProperty404()
    {
        $accept = new Accept;
        $accept->addMediaType('application/json');

        $this->getRequest()
            ->setMethod('GET')
            ->getHeaders()->addHeader($accept);

        $this->dispatch('/rest/game/seven-wonders/author');

        $result = json_decode($this->getResponse()->getContent(), true);
        $this->assertResponseStatusCode(404);
        $this->assertEquals(
            'Content-Type: application/api-problem+json',
            $this->getResponse()->getHeaders()->get('Content-Type')->toString()
        );

        $this->assertEquals('/exception/document-not-found', $result['describedBy']);
        $this->assertEquals('Document not found', $result['title']);
    }

    public function testGetPartial()
    {
        $accept = new Accept;
        $accept->addMediaType('application/json');

        $this->getRequest()
            ->setMethod('GET')
            ->getHeaders()->addHeader($accept);

        $this->dispatch('/rest/game/feed-the-kitty?select(publisher,type)');

        $result = json_decode($this->getResponse()->getContent(), true);

        $this->assertResponseStatusCode(200);
        $this->assertControllerName('shard.rest.game');
        $this->assertControllerClass('RestfulController');
        $this->assertMatchedRouteName('rest');

        $this->assertFalse(isset($result['name']));
        $this->assertTrue(isset($result['publisher']));
        $this->assertTrue(isset($result['type']));
        $this->assertFalse(isset($result['components']));
        $this->assertFalse(isset($result['author']));
    }

    public function testGetEmbedded()
    {
        $accept = new Accept;
        $accept->addMediaType('application/json');

        $this->getRequest()
            ->setMethod('GET')
            ->getHeaders()->addHeader($accept);

        $this->dispatch('/rest/game/feed-the-kitty/publisher');

        $result = json_decode($this->getResponse()->getContent(), true);

        $this->assertResponseStatusCode(200);
        $this->assertControllerName('shard.rest.game');
        $this->assertControllerClass('RestfulController');
        $this->assertMatchedRouteName('rest');

        $this->assertEquals('gamewright', $result['name']);
    }

    public function testGetEmbeddedListItem()
    {
        $accept = new Accept;
        $accept->addMediaType('application/json');

        $this->getRequest()
            ->setMethod('GET')
            ->getHeaders()->addHeader($accept);

        $this->dispatch('/rest/game/feed-the-kitty/components/action-dice');

        $result = json_decode($this->getResponse()->getContent(), true);

        $this->assertResponseStatusCode(200);
        $this->assertControllerName('shard.rest.game');
        $this->assertControllerClass('RestfulController');
        $this->assertMatchedRouteName('rest');

        $this->assertEquals('die', $result['type']);
    }

    public function testGetReference()
    {
        $accept = new Accept;
        $accept->addMediaType('application/json');

        $this->getRequest()
            ->setMethod('GET')
            ->getHeaders()->addHeader($accept);

        $this->dispatch('/rest/game/feed-the-kitty/author');

        $result = json_decode($this->getResponse()->getContent(), true);

        $this->assertResponseStatusCode(200);
        $this->assertControllerName('shard.rest.game');
        $this->assertControllerClass('RestfulController');
        $this->assertMatchedRouteName('rest');
        $this->assertEquals('james', $result['name']);
    }

    public function testGetReferenceListItem()
    {
        $accept = new Accept;
        $accept->addMediaType('application/json');

        $this->getRequest()
            ->setMethod('GET')
            ->getHeaders()->addHeader($accept);

        $this->dispatch('/rest/game/feed-the-kitty/reviews/great-review');

        $result = json_decode($this->getResponse()->getContent(), true);

        $this->assertResponseStatusCode(200);
        $this->assertControllerName('shard.rest.game');
        $this->assertControllerClass('RestfulController');
        $this->assertMatchedRouteName('rest');

        $this->assertEquals('great-review', $result['title']);
    }

    public function testGetEmbeddedDeep()
    {
        $accept = new Accept;
        $accept->addMediaType('application/json');

        $this->getRequest()
            ->setMethod('GET')
            ->getHeaders()->addHeader($accept);

        $this->dispatch('/rest/game/feed-the-kitty/publisher/country');

        $result = json_decode($this->getResponse()->getContent(), true);

        $this->assertResponseStatusCode(200);
        $this->assertControllerName('shard.rest.game');
        $this->assertControllerClass('RestfulController');
        $this->assertMatchedRouteName('rest');

        $this->assertEquals('us', $result['name']);
    }

    public function testGetReallyDeep()
    {
        $accept = new Accept;
        $accept->addMediaType('application/json');

        $this->getRequest()
            ->setMethod('GET')
            ->getHeaders()->addHeader($accept);

        $this->dispatch('/rest/review/great-review/game/author/country/authors/thomas');

        $result = json_decode($this->getResponse()->getContent(), true);

        $this->assertResponseStatusCode(200);
        $this->assertControllerClass('RestfulController');
        $this->assertMatchedRouteName('rest');

        $this->assertEquals('thomas', $result['name']);
    }
}
