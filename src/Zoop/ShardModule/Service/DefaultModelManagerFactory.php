<?php

namespace Zoop\ShardModule\Service;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class DefaultModelManagerFactory implements FactoryInterface
{
    /**
     * {@inheritDoc}
     * @return Application
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $name = $serviceLocator->get('config')['zoop']['shard']['manifest']['default']['model_manager'];
        return $serviceLocator->get($name);
    }
}
