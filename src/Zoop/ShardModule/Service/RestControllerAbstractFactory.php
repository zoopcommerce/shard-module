<?php
/**
 * @package    Zoop
 * @license    MIT
 */
namespace Zoop\ShardModule\Service;

use Zoop\ShardModule\Controller\RestfulController;
use Zoop\ShardModule\Options\RestfulControllerOptions;
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

    public function canCreateServiceWithName(ServiceLocatorInterface $serviceLocator, $name, $requestedName)
    {
        if ($factoryMapping = $this->getFactoryMapping($name)) {
            return $this->getEndpointMap($factoryMapping['manifestName'], $serviceLocator)
                ->hasEndpoint($factoryMapping['endpoint']);
        }

        return false;
    }

    public function createServiceWithName(ServiceLocatorInterface $serviceLocator, $name, $requestedName)
    {
        $factoryMapping = $this->getFactoryMapping($name);

        $endpointMap = $this->getEndpointMap($factoryMapping['manifestName'], $serviceLocator);
        $endpoint = $endpointMap->getEndpoint($factoryMapping['endpoint']);

        $options = [
            'end_point'        => $endpoint,
            'endpoint_map'     => $endpointMap,
            'document_class'   => $endpoint->getClass(),
            'manifest_name'    => $factoryMapping['manifestName'],
            'service_locator'  => $serviceLocator
                ->getServiceLocator()->get('shard.' . $factoryMapping['manifestName'] . '.serviceManager')
        ];

        //load any custom option overrides from config
        $config = $serviceLocator->getServiceLocator()->get('config')['zoop']['shard'];
        if (isset($config['controllers']) &&
            isset($config['controllers']['rest']) &&
            isset($config['controllers']['rest'][$factoryMapping['manifestName']]) &&
            isset($config['controllers']['rest'][$factoryMapping['manifestName']][$factoryMapping['endpoint']])
        ) {
            $options = array_merge(
                $options,
                $config['controllers']['rest'][$factoryMapping['manifestName']][$factoryMapping['endpoint']]
            );
        }

        return new RestfulController(new RestfulControllerOptions($options));
    }

    protected function getEndpointMap($manifestName, $serviceLocator)
    {
        if (!isset($this->endpointMap)) {
            $this->endpointMap = $serviceLocator->getServiceLocator()->get('shard.' . $manifestName . '.endpointMap');
        }

        return $this->endpointMap;
    }

    protected function getFactoryMapping($name)
    {
        $matches = [];

        if (! preg_match('/^rest\.(?<manifestName>[a-z0-9_]+)\.(?<endpoint>[a-z0-9_]+)$/', $name, $matches)) {
            return false;
        }

        return $matches;
    }
}
