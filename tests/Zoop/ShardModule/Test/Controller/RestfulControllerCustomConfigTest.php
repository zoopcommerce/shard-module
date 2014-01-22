<?php

namespace Zoop\ShardModule\Test\Controller;

use Zoop\ShardModule\Test\BaseTest;
use Zend\Http\Header\Accept;

class RestfulControllerCustomConfigTest extends BaseTest
{
    public function testGetLimitedList()
    {
        $accept = new Accept;
        $accept->addMediaType('application/json');

        $this->getRequest()
            ->setMethod('GET')
            ->getHeaders()->addHeader($accept);

        $this->dispatch('/rest/review');

        $result = json_decode($this->getResponse()->getContent(), true);

        $this->assertResponseStatusCode(200);
        $this->assertControllerName('shard.rest.review');
        $this->assertControllerClass('RestfulController');
        $this->assertMatchedRouteName('rest');

        $this->assertCount(2, $result);
        $this->assertEquals(
            'Content-Range: 0-1/3',
            $this->getResponse()->getHeaders()->get('Content-Range')->toString()
        );
    }
}
