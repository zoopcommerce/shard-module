<?php

namespace Zoop\ShardModule\Test\Controller;

use Zoop\ShardModule\Test\BaseTest;
use Zend\Http\Header\Accept;
use Zend\Http\Header\Range;

class RestfulControllerGetListTest extends BaseTest
{
    public function testGetList()
    {
        $accept = new Accept;
        $accept->addMediaType('application/json');

        $this->getRequest()
            ->setMethod('GET')
            ->getHeaders()->addHeader($accept);

        $this->dispatch('/rest/author');

        $result = json_decode($this->getResponse()->getContent(), true);

        $this->assertResponseStatusCode(200);
        $this->assertControllerName('shard.rest.author');
        $this->assertControllerClass('RestfulController');
        $this->assertMatchedRouteName('rest');

        $this->assertCount(4, $result);
        $this->assertEquals(
            'Content-Range: 0-3/4',
            $this->getResponse()->getHeaders()->get('Content-Range')->toString()
        );
    }

    public function testRootGetList()
    {
        $accept = new Accept;
        $accept->addMediaType('application/json');

        $this->getRequest()
            ->setMethod('GET')
            ->getHeaders()->addHeader($accept);

        $this->dispatch('/rest');

        $result = json_decode($this->getResponse()->getContent(), true);

        $this->assertResponseStatusCode(200);
        $this->assertControllerName('shard.rest');
        $this->assertControllerClass('RestfulController');
        $this->assertMatchedRouteName('rest');

        $this->assertCount(4, $result);
        $this->assertEquals(
            'Content-Range: 0-3/4',
            $this->getResponse()->getHeaders()->get('Content-Range')->toString()
        );
    }

    public function testGetListOfPartials()
    {
        $accept = new Accept;
        $accept->addMediaType('application/json');

        $this->getRequest()
            ->setMethod('GET')
            ->getHeaders()->addHeader($accept);

        $this->dispatch('/rest/author?select(name)');

        $result = json_decode($this->getResponse()->getContent(), true);

        $this->assertResponseStatusCode(200);
        $this->assertControllerName('shard.rest.author');
        $this->assertControllerClass('RestfulController');
        $this->assertMatchedRouteName('rest');

        $this->assertCount(4, $result);
        $this->assertFalse(isset($result[0]['country']));
        $this->assertEquals(
            'Content-Range: 0-3/4',
            $this->getResponse()->getHeaders()->get('Content-Range')->toString()
        );
    }

    public function testGetFilteredList()
    {
        $accept = new Accept;
        $accept->addMediaType('application/json');

        $this->getRequest()
            ->setMethod('GET')
            ->getHeaders()->addHeader($accept);

        $this->dispatch('/rest/author?country=germany');

        $this->assertResponseStatusCode(200);

        $result = json_decode($this->getResponse()->getContent(), true);

        $this->assertCount(3, $result);
        $this->assertEquals(
            'Content-Range: 0-2/3',
            $this->getResponse()->getHeaders()->get('Content-Range')->toString()
        );
    }

    public function testGetFilteredListWithFilterOnEmbeddedDoc()
    {
        $accept = new Accept;
        $accept->addMediaType('application/json');

        $this->getRequest()
            ->setMethod('GET')
            ->getHeaders()->addHeader($accept);

        $this->dispatch('/rest/game?publisher.city=' . urlencode('Little Rock'));

        $this->assertResponseStatusCode(200);

        $result = json_decode($this->getResponse()->getContent(), true);

        $this->assertCount(1, $result);
        $this->assertEquals(
            'Content-Range: 0-0/1',
            $this->getResponse()->getHeaders()->get('Content-Range')->toString()
        );
        $this->assertEquals('feed-the-kitty', $result[0]['name']);
    }

    public function testGetOrFilteredList()
    {
        $accept = new Accept;
        $accept->addMediaType('application/json');

        $this->getRequest()
            ->setMethod('GET')
            ->getHeaders()->addHeader($accept);

        $this->dispatch('/rest/author?name=[harry,thomas]');

        $this->assertResponseStatusCode(200);

        $result = json_decode($this->getResponse()->getContent(), true);

        $this->assertCount(2, $result);
        $this->assertEquals(
            'Content-Range: 0-1/2',
            $this->getResponse()->getHeaders()->get('Content-Range')->toString()
        );
        $this->assertEquals('harry', $result[0]['name']);
        $this->assertEquals('thomas', $result[1]['name']);
    }

    public function testGetFilteredCollectionList1()
    {
        $accept = new Accept;
        $accept->addMediaType('application/json');

        $this->getRequest()
            ->setMethod('GET')
            ->getHeaders()->addHeader($accept);

        $this->dispatch('/rest/game?awards=mensa');

        $result = json_decode($this->getResponse()->getContent(), true);
        $this->assertResponseStatusCode(200);

        $this->assertCount(1, $result);
        $this->assertEquals(
            'Content-Range: 0-0/1',
            $this->getResponse()->getHeaders()->get('Content-Range')->toString()
        );
        $this->assertEquals('feed-the-kitty', $result[0]['name']);
    }

    public function testGetFilteredCollectionList2()
    {
        $accept = new Accept;
        $accept->addMediaType('application/json');

        $this->getRequest()
            ->setMethod('GET')
            ->getHeaders()->addHeader($accept);

        $this->dispatch('/rest/game?awards=[games100]');

        $this->assertResponseStatusCode(200);

        $result = json_decode($this->getResponse()->getContent(), true);

        $this->assertCount(2, $result);
        $this->assertEquals(
            'Content-Range: 0-1/2',
            $this->getResponse()->getHeaders()->get('Content-Range')->toString()
        );
    }

    public function testGetSortedListAsc()
    {
        $accept = new Accept;
        $accept->addMediaType('application/json');

        $this->getRequest()
            ->setMethod('GET')
            ->getHeaders()->addHeader($accept);

        $this->dispatch('/rest/author?' . urlencode('sort(+country,+name)'));

        $this->assertResponseStatusCode(200);

        $response = $this->getResponse();
        $result = json_decode($response->getContent(), true);

        $this->assertCount(4, $result);
        $this->assertEquals('Content-Range: 0-3/4', $response->getHeaders()->get('Content-Range')->toString());
        $this->assertEquals('bill', $result[0]['name']);
        $this->assertEquals('james', $result[1]['name']);
        $this->assertEquals('thomas', $result[2]['name']);
        $this->assertEquals('harry', $result[3]['name']);
    }

    public function testGetSortedListDesc()
    {
        $accept = new Accept;
        $accept->addMediaType('application/json');

        $this->getRequest()
            ->setMethod('GET')
            ->getHeaders()->addHeader($accept);

        $this->dispatch('/rest/author?' . urlencode('sort(-country,-name)'));

        $this->assertResponseStatusCode(200);

        $response = $this->getResponse();
        $result = json_decode($response->getContent(), true);

        $this->assertCount(4, $result);
        $this->assertEquals('Content-Range: 0-3/4', $response->getHeaders()->get('Content-Range')->toString());
        $this->assertEquals('bill', $result[3]['name']);
        $this->assertEquals('james', $result[2]['name']);
        $this->assertEquals('thomas', $result[1]['name']);
        $this->assertEquals('harry', $result[0]['name']);
    }

    public function testGetOffsetList()
    {
        $accept = new Accept;
        $accept->addMediaType('application/json');

        $this->getRequest()
            ->setMethod('GET')
            ->getHeaders()->addHeaders([$accept, Range::fromString('Range: items=2-100')]);

        $this->dispatch('/rest/author');

        $this->assertResponseStatusCode(200);

        $response = $this->getResponse();
        $result = json_decode($response->getContent(), true);

        $this->assertCount(2, $result);
        $this->assertEquals('Content-Range: 2-3/4', $response->getHeaders()->get('Content-Range')->toString());
    }

    public function testGetOffsetListReverseRange()
    {
        $accept = new Accept;
        $accept->addMediaType('application/json');

        $this->getRequest()
            ->setMethod('GET')
            ->getHeaders()->addHeaders([$accept, Range::fromString('Range: items=2-0')]);

        $this->dispatch('/rest/author');

        $this->assertResponseStatusCode(416);
        $this->assertEquals(
            'Content-Type: application/api-problem+json',
            $this->getResponse()->getHeaders()->get('Content-Type')->toString()
        );

        $result = json_decode($this->getResponse()->getContent(), true);
        $this->assertEquals('/exception/bad-range', $result['describedBy']);
        $this->assertEquals('Requested range cannot be returned', $result['title']);
    }

    public function testGetOffsetListBeyondRange()
    {
        $accept = new Accept;
        $accept->addMediaType('application/json');

        $this->getRequest()
            ->setMethod('GET')
            ->getHeaders()->addHeaders([$accept, Range::fromString('Range: items=100-102')]);

        $this->dispatch('/rest/author');

        $this->assertResponseStatusCode(416);
        $this->assertEquals(
            'Content-Type: application/api-problem+json',
            $this->getResponse()->getHeaders()->get('Content-Type')->toString()
        );

        $result = json_decode($this->getResponse()->getContent(), true);
        $this->assertEquals('/exception/bad-range', $result['describedBy']);
        $this->assertEquals('Requested range cannot be returned', $result['title']);
    }

    public function testGetEmbeddedList()
    {
        $accept = new Accept;
        $accept->addMediaType('application/json');

        $this->getRequest()
            ->setMethod('GET')
            ->getHeaders()->addHeader($accept);

        $this->dispatch('/rest/game/feed-the-kitty/components');

        $result = json_decode($this->getResponse()->getContent(), true);

        $this->assertResponseStatusCode(200);

        $this->assertCount(3, $result);
        $this->assertEquals('die', $result[0]['type']);
        $this->assertEquals('bowl', $result[1]['type']);
        $this->assertEquals('mice', $result[2]['type']);

        $this->assertEquals(
            'Content-Range: 0-2/3',
            $this->getResponse()->getHeaders()->get('Content-Range')->toString()
        );
    }

    public function testGetEmbeddedListWithFilter()
    {
        $accept = new Accept;
        $accept->addMediaType('application/json');

        $this->getRequest()
            ->setMethod('GET')
            ->getHeaders()->addHeader($accept);

        $this->dispatch('/rest/game/feed-the-kitty/components?type=[die]');

        $result = json_decode($this->getResponse()->getContent(), true);

        $this->assertResponseStatusCode(200);

        $this->assertCount(1, $result);
        $this->assertEquals('die', $result[0]['type']);
        $this->assertEquals(
            'Content-Range: 0-0/1',
            $this->getResponse()->getHeaders()->get('Content-Range')->toString()
        );
    }

    public function testGetEmbeddedListWithSortAndRange()
    {
        $accept = new Accept;
        $accept->addMediaType('application/json');

        $this->getRequest()
            ->setMethod('GET')
            ->getHeaders()->addHeaders([$accept, Range::fromString('Range: items=1-2')]);

        $this->dispatch('/rest/game/feed-the-kitty/components?' . urlencode('sort(-type)'));

        $result = json_decode($this->getResponse()->getContent(), true);

        $this->assertResponseStatusCode(200);
        $this->assertCount(2, $result);
        $this->assertEquals('die', $result[0]['type']);
        $this->assertEquals('bowl', $result[1]['type']);

        $this->assertEquals(
            'Content-Range: 1-2/3',
            $this->getResponse()->getHeaders()->get('Content-Range')->toString()
        );
    }

    public function testGetReferencedList()
    {
        $accept = new Accept;
        $accept->addMediaType('application/json');

        $this->getRequest()
            ->setMethod('GET')
            ->getHeaders()->addHeader($accept);

        $this->dispatch('/rest/game/feed-the-kitty/reviews');

        $result = json_decode($this->getResponse()->getContent(), true);

        $this->assertResponseStatusCode(200);

        $this->assertCount(2, $result);

        $this->assertCount(
            1,
            array_filter(
                $result,
                function ($item) {
                return ($item['title'] == 'great-review');
                }
            )
        );
        $this->assertCount(
            1,
            array_filter(
                $result,
                function ($item) {
                return ($item['title'] == 'bad-review');
                }
            )
        );

        $this->assertEquals(
            'Content-Range: 0-1/2',
            $this->getResponse()->getHeaders()->get('Content-Range')->toString()
        );
    }

    public function testGetEmptyList()
    {
        self::tearDownAfterClass();

        $accept = new Accept;
        $accept->addMediaType('application/json');

        $this->getRequest()
            ->setMethod('GET')
            ->getHeaders()->addHeader($accept);

        $this->dispatch('/rest/author');

        $result = json_decode($this->getResponse()->getContent(), true);

        $this->assertResponseStatusCode(204);
        $this->assertControllerName('shard.rest.author');
        $this->assertControllerClass('RestfulController');
        $this->assertMatchedRouteName('rest');

        $this->assertFalse(isset($result));
    }
}
