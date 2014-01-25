<?php

namespace Zoop\ShardModule\Test\Controller;

use Zoop\ShardModule\Test\BaseTest;
use Zend\Http\Header\Accept;
use Zend\Http\Header\ContentType;

class BatchRestfulControllerTest extends BaseTest
{
    public function testBatchGet()
    {
        $accept = new Accept;
        $accept->addMediaType('application/json');

        $this->getRequest()
            ->setMethod('POST')
            ->setContent(
                '{
                    "request1": {
                        "uri": "/rest/game/feed-the-kitty",
                        "method": "GET"
                    },
                    "request2": {
                        "uri": "/rest/game/seven-wonders",
                        "method": "GET"
                    },
                    "request3": {
                        "uri": "/rest/game/does-not-extist",
                        "method": "GET"
                    },
                    "request4": {
                        "uri": "/rest/does-not-extist",
                        "method": "GET"
                    }
                }'
            )
            ->getHeaders()->addHeaders([$accept, ContentType::fromString('Content-type: application/json')]);

        $this->dispatch('/rest/batch');

        $response = $this->getResponse();
        $result = json_decode($response->getContent(), true);
        $this->assertResponseStatusCode(200);

        $this->assertEquals(200, $result['request1']['status']);
        $this->assertEquals('no-cache', $result['request1']['headers']['Cache-Control']);
        $this->assertEquals('feed-the-kitty', $result['request1']['content']['name']);
        $this->assertEquals('dice', $result['request1']['content']['type']);

        $this->assertEquals(200, $result['request2']['status']);
        $this->assertEquals('no-cache', $result['request2']['headers']['Cache-Control']);
        $this->assertEquals('seven-wonders', $result['request2']['content']['name']);

        $this->assertEquals(404, $result['request3']['status']);
        $this->assertEquals('application/api-problem+json', $result['request3']['headers']['Content-Type']);
        $this->assertEquals('Document not found', $result['request3']['content']['title']);

        $this->assertEquals(404, $result['request4']['status']);
        $this->assertEquals('application/api-problem+json', $result['request4']['headers']['Content-Type']);
        $this->assertEquals('Application Exception', $result['request4']['content']['title']);
    }

    public function testBatchGetList()
    {
        $accept = new Accept;
        $accept->addMediaType('application/json');

        $this->getRequest()
            ->setMethod('POST')
            ->setContent(
                '{
                    "request1": {
                        "uri": "/rest/author",
                        "method": "GET"
                    },
                    "request2": {
                        "uri": "/rest/author?select(name)",
                        "method": "GET"
                    },
                    "request3": {
                        "uri": "/rest/author?country=germany",
                        "method": "GET"
                    },
                    "request4": {
                        "uri": "/rest/author?' . urlencode('sort(+country,+name)') . '",
                        "method": "GET"
                    },
                    "request5": {
                        "uri": "/rest/author",
                        "method": "GET",
                        "headers": {
                            "Range": "items=2-100"
                        }
                    }
                }'
            )
            ->getHeaders()->addHeaders([$accept, ContentType::fromString('Content-type: application/json')]);

        $this->dispatch('/rest/batch');

        $response = $this->getResponse();
        $result = json_decode($response->getContent(), true);
        $this->assertResponseStatusCode(200);

        $this->assertEquals(200, $result['request1']['status']);
        $this->assertCount(4, $result['request1']['content']);
        $this->assertEquals('0-3/4', $result['request1']['headers']['Content-Range']);

        $this->assertEquals(200, $result['request2']['status']);
        $this->assertCount(4, $result['request2']['content']);
        $this->assertFalse(isset($result['request2']['content'][0]['country']));
        $this->assertEquals('0-3/4', $result['request2']['headers']['Content-Range']);

        $this->assertEquals(200, $result['request3']['status']);
        $this->assertCount(3, $result['request3']['content']);
        $this->assertEquals('0-2/3', $result['request3']['headers']['Content-Range']);

        $this->assertEquals(200, $result['request4']['status']);
        $this->assertCount(4, $result['request4']['content']);
        $this->assertEquals('0-3/4', $result['request4']['headers']['Content-Range']);
        $this->assertEquals('bill', $result['request4']['content'][0]['name']);
        $this->assertEquals('james', $result['request4']['content'][1]['name']);
        $this->assertEquals('thomas', $result['request4']['content'][2]['name']);
        $this->assertEquals('harry', $result['request4']['content'][3]['name']);

        $this->assertEquals(200, $result['request5']['status']);
        $this->assertCount(2, $result['request5']['content']);
        $this->assertEquals('2-3/4', $result['request5']['headers']['Content-Range']);
    }

    public function testBatchMixed()
    {
        $accept = new Accept;
        $accept->addMediaType('application/json');

        $this->getRequest()
            ->setMethod('POST')
            ->setContent(
                '{
                    "request1": {
                        "uri": "/rest/game",
                        "method": "POST",
                        "content": {"name": "forbidden-island", "type": "co-op"}
                    },
                    "request2": {
                        "uri": "/rest/author/harry",
                        "method": "DELETE"
                    },
                    "request3": {
                        "uri": "/rest/game/feed-the-kitty",
                        "method": "PUT",
                        "content": {"type": "childrens", "author": {"$ref": "author/harry"}}
                    },
                    "request4": {
                        "uri": "/rest/game/feed-the-kitty",
                        "method": "PATCH",
                        "content": {"type": "kids"}
                    }
                }'
            )
            ->getHeaders()->addHeaders([$accept, ContentType::fromString('Content-type: application/json')]);

        $this->dispatch('/rest/batch');

        $response = $this->getResponse();
        $result = json_decode($response->getContent(), true);
        $this->assertResponseStatusCode(200);

        $this->assertEquals(201, $result['request1']['status']);
        $this->assertFalse(isset($result['request1']['content']));
        $this->assertEquals('/rest/game/forbidden-island', $result['request1']['headers']['Location']);

        $this->assertEquals(204, $result['request2']['status']);

        $this->assertEquals(204, $result['request3']['status']);
        $this->assertFalse(isset($result['request3']['content']));

        $this->assertEquals(204, $result['request4']['status']);
        $this->assertFalse(isset($result['request4']['content']));
    }

    public function testGet405()
    {
        $accept = new Accept;
        $accept->addMediaType('application/json');

        $this->getRequest()
            ->setMethod('GET')
            ->getHeaders()->addHeaders([$accept, ContentType::fromString('Content-type: application/json')]);

        $this->dispatch('/rest/batch');

        $response = $this->getResponse();
        $result = json_decode($response->getContent(), true);

        $this->assertResponseStatusCode(405);
    }

    public function testPut405()
    {
        $accept = new Accept;
        $accept->addMediaType('application/json');

        $this->getRequest()
            ->setMethod('PUT')
            ->getHeaders()->addHeaders([$accept, ContentType::fromString('Content-type: application/json')]);

        $this->dispatch('/rest/batch');

        $response = $this->getResponse();
        $result = json_decode($response->getContent(), true);

        $this->assertResponseStatusCode(405);
    }

    public function testPatch405()
    {
        $accept = new Accept;
        $accept->addMediaType('application/json');

        $this->getRequest()
            ->setMethod('PATCH')
            ->getHeaders()->addHeaders([$accept, ContentType::fromString('Content-type: application/json')]);

        $this->dispatch('/rest/batch');

        $response = $this->getResponse();
        $result = json_decode($response->getContent(), true);

        $this->assertResponseStatusCode(405);
    }
}
