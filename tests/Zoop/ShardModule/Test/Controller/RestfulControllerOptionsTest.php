<?php

namespace Zoop\ShardModule\Test\Controller;

use Zoop\ShardModule\Test\BaseTest;

class RestfulControllerOptionsTest extends BaseTest
{
    public function testOptions()
    {
        $this->getRequest()
            ->setMethod('OPTIONS');

        $this->dispatch('/rest/game/feed-the-kitty');

        $this->assertResponseStatusCode(405);
    }
}
