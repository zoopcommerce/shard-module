<?php

namespace Zoop\ShardModule\Builder;

use DoctrineModule\Exception;
use DoctrineMongoODMModule\Builder\DocumentManagerBuilder;
use DoctrineMongoODMModule\Options\DocumentManagerOptions;
use Zoop\Shard\ODMCore\ModelManager;

/**
 * Builder creates a mongo document manager
 */
class ModelManagerBuilder extends DocumentManagerBuilder
{
    /**
     * @param \Zend\ServiceManager\ServiceLocatorInterface $serviceLocator
     * @return \Doctrine\ODM\MongoDB\DocumentManager
     */
    public function build($options)
    {
        if (is_array($options) || $options instanceof \Traversable) {
            $options = new DocumentManagerOptions($options);
        } elseif (! $options instanceof DocumentManagerOptions) {
            throw new Exception\InvalidArgumentException();
        }

        return ModelManager::create(
            /* @var $connection \Doctrine\MongoDB\Connection */
            $this->serviceLocator->get($options->getConnection()),
            /* @var $config \Doctrine\ODM\MongoDB\Configuration */
            $this->serviceLocator->get($options->getConfiguration()),
            /* @var $eventManager \Doctrine\Common\EventManager */
            $this->serviceLocator->get($options->getEventManager())
        );
    }
}
