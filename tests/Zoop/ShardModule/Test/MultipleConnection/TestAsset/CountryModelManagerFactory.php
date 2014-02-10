<?php

namespace Zoop\ShardModule\Test\MultipleConnection\TestAsset;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class CountryModelManagerFactory implements FactoryInterface
{
    /**
     * {@inheritDoc}
     * @return Application
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $name = $serviceLocator->get('config')['zoop']['shard']['manifest']['country']['model_manager'];

        return $serviceLocator->get($name);
    }
}
