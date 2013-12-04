<?php

namespace Zoop\ShardModule\Test\Controller;

use Zoop\ShardModule\Test\TestAsset\TestData;
use Zend\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;

class RestfulControllerOptionsTest extends AbstractHttpControllerTestCase
{
    protected static $staticDcumentManager;

    protected static $dbDataCreated = false;

    public static function tearDownAfterClass()
    {
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
            TestData::create($this->documentManager);
            static::$dbDataCreated = true;
        }
    }

    public function testOptions()
    {
        $this->getRequest()
            ->setMethod('OPTIONS');

        $this->dispatch('/rest/game/feed-the-kitty');

        $this->assertResponseStatusCode(405);
    }
}
