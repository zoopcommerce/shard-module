<?php

namespace Zoop\ShardModule\Service;

use Zoop\Juggernaut\Adapter\FileSystem;
use Zoop\ShardModule\Cache\JuggernautCache;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class JuggernautFileSystemCacheFactory implements FactoryInterface
{
    /**
     * {@inheritDoc}
     * @return Application
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $config = $serviceLocator->get('Config');
        $juggernautInstance = new FileSystem($config['zoop']['juggernaut']['file_system']['directory']);
        $juggernautInstance->setTtl(2419200); //one month
        return new JuggernautCache($juggernautInstance);
    }
}
