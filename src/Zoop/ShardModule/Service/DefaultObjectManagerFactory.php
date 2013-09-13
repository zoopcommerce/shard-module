<?php

namespace Zoop\ShardModule\Service;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class DefaultObjectManagerFactory implements FactoryInterface
{
    /**
     * {@inheritDoc}
     * @return Application
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $name = $serviceLocator->get('config')['zoop']['shard']['manifest']['default']['object_manager'];
        return $serviceLocator->get($name);
    }
}
