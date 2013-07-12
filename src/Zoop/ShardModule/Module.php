<?php
/**
 * @link       http://zoopcommerce.github.io/shard-module
 * @package    Zoop
 * @license    MIT
 */
namespace Zoop\ShardModule;

use Zend\ModuleManager\ModuleManager;
use Zend\Mvc\MvcEvent;

/**
 *
 * @since   1.0
 * @author  Tim Roediger <superdweebie@gmail.com>
 */
class Module
{

    public function init(ModuleManager $moduleManager) {

        $eventManager = $moduleManager->getEventManager();
        $sharedEventManager = $eventManager->getSharedManager();

        $eventManager->attachAggregate(new ConfigMergeListener);
        $sharedEventManager->attachAggregate(new InitalizeConsoleListener);
    }

    public function onBootstrap(MvcEvent $event)
    {
        $eventManager = $event->getApplication()->getEventManager();
        $eventManager->attachAggregate(new RouteListener);
    }

    public function getConfig()
    {
        return include __DIR__ . '/../../../config/module.config.php';
    }

    /**
     * {@inheritDoc}
     */
    public function getModuleDependencies()
    {
        return [
            'Zoop\MaggottModule',
            'DoctrineMongoODMModule'
        ];
    }
}
