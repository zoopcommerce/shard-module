<?php
/**
 * @package    Zoop
 * @license    MIT
 */
namespace Zoop\ShardModule\Service;

use Zoop\ShardModule\Controller\RestfulController;
use Zend\ServiceManager\AbstractFactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 *
 * @since   1.0
 * @version $Revision$
 * @author  Tim Roediger <superdweebie@gmail.com>
 */
class RestControllerAbstractFactory implements AbstractFactoryInterface
{
    protected $restControllerMap;

    protected function getRestControllerMap($serviceLocator){
        if (!isset($this->restControllerMap)) {
            $this->restControllerMap = $serviceLocator->get('zoop.shardmodule.restcontrollermap');
        }
        return $this->restControllerMap;
    }

    public function canCreateServiceWithName(ServiceLocatorInterface $serviceLocator, $name, $requestedName)
    {
        return $this->getOptions($name, $serviceLocator->getServiceLocator());
    }

    public function createServiceWithName(ServiceLocatorInterface $serviceLocator, $name, $requestedName)
    {
        return new RestfulController($this->getOptions($name, $serviceLocator->getServiceLocator()));
    }

    protected function getOptions($name, $serviceLocator)
    {
        $pieces = explode('.', $name);
        if (array_shift($pieces) == 'shard' && array_shift($pieces) == 'rest') {
            $endpoint = implode('.', $pieces);
            return $this->getRestControllerMap($serviceLocator)->getOptionsFromEndpoint($endpoint);
        }

        return false;
    }
}
