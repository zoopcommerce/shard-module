<?php

namespace Zoop\ShardModule\Test;

use Zoop\ShardModule\Test\TestAsset\TestData;
use Zend\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;

abstract class BaseTest extends AbstractHttpControllerTestCase
{
    const DB = 'shard-module-phpunit';
    protected static $staticDcumentManager;
    protected static $dbDataCreated = false;

    public function setUp()
    {
        $this->setApplicationConfig(
                include __DIR__ . '/../../../test.application.config.php'
        );

        parent::setUp();

        $this->documentManager = $this->getApplicationServiceLocator()->get('doctrine.odm.documentmanager.default');
        $this->documentManager->getConnection()->selectDatabase(self::DB);
        static::$staticDcumentManager = $this->documentManager;
        
        if (!static::$dbDataCreated) {
            //Create data in the db to query against
            TestData::create($this->documentManager);
            static::$dbDataCreated = true;
        }
    }

    public function tearDown()
    {
        $this->clearDatabase();
    }

    public function clearDatabase()
    {
        if (static::$staticDcumentManager) {
            $collections = static::$staticDcumentManager
                ->getConnection()
                ->selectDatabase(self::DB)->listCollections();

            foreach ($collections as $collection) {
                /* @var $collection \MongoCollection */
                $collection->remove();
                $collection->drop();
            }
        }
    }
}
