<?php
/**
 * @package    Zoop
 * @license    MIT
 */
namespace Zoop\ShardModule\Service;

use Zoop\ShardModule\Controller\JsonRestfulController;
use Zoop\ShardModule\Controller\JsonRestfulController\DoctrineSubscriber;
use Zoop\ShardModule\Options\JsonRestfulControllerOptions;
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
    protected $endpointMap;

    public function canCreateServiceWithName(ServiceLocatorInterface $serviceLocator, $name, $requestedName){
        if ($factoryMapping = $this->getFactoryMapping($name)){
            return $this->getEndpointMap($factoryMapping['manifestName'], $serviceLocator)->hasEndpoint($factoryMapping['endpoint']);
        }
        return false;
    }

    public function createServiceWithName(ServiceLocatorInterface $serviceLocator, $name, $requestedName){

        $factoryMapping = $this->getFactoryMapping($name);

        $endpointMap = $this->getEndpointMap($factoryMapping['manifestName'], $serviceLocator);
        $endpoint = $endpointMap->getEndpoint($factoryMapping['endpoint']);

        $options = new JsonRestfulControllerOptions([
            'end_point'        => $endpoint,
            'endpoint_map'     => $endpointMap,
            'document_class'   => $endpoint->getClass(),
            'document_manager' => $serviceLocator->getServiceLocator()->get('config')['zoop']['shard']['manifest'][$factoryMapping['manifestName']]['document_manager'],
            'manifest_name'    => $factoryMapping['manifestName'],
            'service_locator'  => $serviceLocator->getServiceLocator()->get('shard.' . $factoryMapping['manifestName'] . '.serviceManager')
        ]);
        $instance = new JsonRestfulController($options);
        $instance->setDoctrineSubscriber(new DoctrineSubscriber);

        return $instance;
    }

    protected function getEndpointMap($manifestName, $serviceLocator){
        if (!isset($this->endpointMap)){
            $this->endpointMap = $serviceLocator->getServiceLocator()->get('shard.' . $manifestName . '.endpointMap');
        }
        return $this->endpointMap;
    }

    protected function getFactoryMapping($name){

        $matches = [];

        if (! preg_match('/^rest\.(?<manifestName>[a-z0-9_]+)\.(?<endpoint>[a-z0-9_]+)$/', $name, $matches)) {
            return false;
        }

        return $matches;
    }
}
