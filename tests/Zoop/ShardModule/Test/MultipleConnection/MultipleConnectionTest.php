<?php

namespace Zoop\ShardModule\Test;

use Zend\Http\Header\Accept;
use Zend\Http\Header\ContentType;
use Zend\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;

class MultipleConnectionTest extends AbstractHttpControllerTestCase
{
    protected static $staticServiceManager;

    public function setUp()
    {
        $this->setApplicationConfig(
            include __DIR__ . '/TestAsset/test.application.config.php'
        );

        parent::setUp();

        static::$staticServiceManager = $this->getApplicationServiceLocator();
    }

    public static function tearDownAfterClass()
    {
        //tidy up
        $documentManagerCountry = static::$staticServiceManager->get('doctrine.odm.documentmanager.country');
        $collections = $documentManagerCountry->getConnection()
            ->selectDatabase('shard-module-phpunit-country')->listCollections();
        foreach ($collections as $collection) {
            $collection->remove(array(), array('safe' => true));
        }

        $documentManagerUser = static::$staticServiceManager->get('doctrine.odm.documentmanager.user');
        $collections = $documentManagerUser->getConnection()
            ->selectDatabase('shard-module-phpunit-user')->listCollections();
        foreach ($collections as $collection) {
            $collection->remove(array(), array('safe' => true));
        }
    }

    public function testCreateCountry()
    {
        //create a country
        $accept = new Accept;
        $accept->addMediaType('application/json');

        $this->getRequest()
            ->setMethod('POST')
            ->setContent('{"name": "australia"}')
            ->getHeaders()->addHeaders([$accept, ContentType::fromString('Content-type: application/json')]);

        $this->dispatch('/rest/country');

        $response = $this->getResponse();
        $result = json_decode($response->getContent(), true);

        $this->assertResponseStatusCode(201);
        $this->assertEquals(
            'Location: /rest/country/australia',
            $response->getHeaders()->get('Location')->toString()
        );
        $this->assertFalse(isset($result));

        //look into the db and check the results
        $documentManagerCountry = static::$staticServiceManager->get('doctrine.odm.documentmanager.country');
        $countries = $documentManagerCountry
            ->getRepository('Zoop\ShardModule\Test\MultipleConnection\TestAsset\Document1\Country')
            ->findAll();
        $this->assertCount(1, $countries);
        $this->assertEquals('australia', $countries[0]->getName());
    }

    public function testCreateUser()
    {
        //create a user
        $accept = new Accept;
        $accept->addMediaType('application/json');

        $this->getRequest()
            ->setMethod('POST')
            ->setContent('{"username": "paddington"}')
            ->getHeaders()->addHeaders([$accept, ContentType::fromString('Content-type: application/json')]);

        $this->dispatch('/rest/user');

        $response = $this->getResponse();
        $result = json_decode($response->getContent(), true);

        $this->assertResponseStatusCode(201);
        $this->assertEquals(
            'Location: /rest/user/paddington',
            $response->getHeaders()->get('Location')->toString()
        );
        $this->assertFalse(isset($result));

        //look into the db and check the results
        $documentManagerUser = static::$staticServiceManager->get('doctrine.odm.documentmanager.user');
        $users = $documentManagerUser
            ->getRepository('Zoop\ShardModule\Test\MultipleConnection\TestAsset\Document2\User')
            ->findAll();
        $this->assertCount(1, $users);
        $this->assertEquals('paddington', $users[0]->getUsername());
    }
}
