<?php

namespace Zoop\ShardModule\Service;

use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Mvc\Controller\Plugin\Service\ForwardFactory as ZendForwardFactory;
use Zoop\ShardModule\Controller\Plugin\Forward;

class ForwardFactory extends ZendForwardFactory
{
    /**
     * {@inheritDoc}
     *
     * @return Forward
     * @throws ServiceNotCreatedException if ControllerLoader service is not found in application service locator
     */
    public function createService(ServiceLocatorInterface $plugins)
    {
        $services = $plugins->getServiceLocator();
        if (!$services instanceof ServiceLocatorInterface) {
            throw new ServiceNotCreatedException(sprintf(
                '%s requires that the application service manager has been injected; none found',
                __CLASS__
            ));
        }

        if (!$services->has('ControllerLoader')) {
            throw new ServiceNotCreatedException(sprintf(
                '%s requires that the application service manager contains a "%s" service; none found',
                __CLASS__,
                'ControllerLoader'
            ));
        }
        $controllers = $services->get('ControllerLoader');

        $instance = new Forward($controllers);
        $instance->setMaxNestedForwards(5);

        return $instance;
    }
}
