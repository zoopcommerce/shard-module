<?php

namespace Zoop\ShardModule\Test;

use Zoop\ShardModule\Test\TestAsset\TestData;
use Zend\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;

abstract class BaseTest extends AbstractHttpControllerTestCase
{

    protected static $staticDcumentManager;
    protected static $dbDataCreated = false;

    public static function setUpBeforeClass()
    {
        static::$dbDataCreated = false;
    }

    public function setUp()
    {
        $this->setApplicationConfig(
            include __DIR__ . '/../../../test.application.config.php'
        );

        parent::setUp();

        $this->documentManager = $this->getApplicationServiceLocator()->get('doctrine.odm.documentmanager.default');
        $this->documentManager->getConnection()->selectDatabase(TestData::DB);

        if (!static::$dbDataCreated) {
            //Create data in the db to query against
            static::$staticDcumentManager = $this->documentManager;
            TestData::create($this->documentManager);
            static::$dbDataCreated = true;
        }
    }

    public static function tearDownAfterClass()
    {
        if (static::$staticDcumentManager) {
            TestData::remove(static::$staticDcumentManager);
        }
    }
}
