<?php
/**
 * @package    Zoop
 * @license    MIT
 */
namespace Zoop\ShardModule\Service;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zoop\ShardModule\Controller\Listener\BatchListener;

/**
 *
 * @since   1.0
 * @version $Revision$
 * @author  Tim Roediger <superdweebie@gmail.com>
 */
class BatchListenerFactory implements FactoryInterface
{

    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $instance = new BatchListener;
        $instance->setExceptionViewModelPreparer($serviceLocator->get('Zoop\MaggottModule\JsonExceptionStrategy'));
        $instance->setRouter($serviceLocator->get('router'));
        $instance->setControllerLoader($serviceLocator->get('controllerLoader'));
        return $instance;
    }
}
