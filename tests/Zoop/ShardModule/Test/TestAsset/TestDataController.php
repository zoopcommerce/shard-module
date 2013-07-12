<?php

namespace Zoop\ShardModule\Test\TestAsset;

use Zend\Mvc\Controller\AbstractActionController;

class TestDataController extends AbstractActionController
{
    public function createAction()
    {
        TestData::create(
            $this->serviceLocator->get('doctrine.odm.documentmanager.default')
        );

        return;
    }

    public function removeAction()
    {
        TestData::remove(
            $this->serviceLocator->get('doctrine.odm.documentmanager.default')
        );

        return;
    }
}
