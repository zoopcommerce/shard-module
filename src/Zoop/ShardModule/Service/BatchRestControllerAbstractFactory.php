<?php
/**
 * @package    Zoop
 * @license    MIT
 */
namespace Zoop\ShardModule\Service;

use Zoop\ShardModule\Controller\BatchJsonRestfulController;
use Zoop\ShardModule\Options\BatchJsonRestfulControllerOptions;
use Zend\ServiceManager\AbstractFactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 *
 * @since   1.0
 * @version $Revision$
 * @author  Tim Roediger <superdweebie@gmail.com>
 */
class BatchRestControllerAbstractFactory implements AbstractFactoryInterface
{

    public function canCreateServiceWithName(ServiceLocatorInterface $serviceLocator, $name, $requestedName)
    {
        return (bool) $this->getManifestName($name);
    }

    public function createServiceWithName(ServiceLocatorInterface $serviceLocator, $name, $requestedName)
    {
        $manifestName = $this->getManifestName($name);

        $appServiceLocator = $serviceLocator->getServiceLocator();

        $options = new BatchJsonRestfulControllerOptions(
            [
                'documentManager' => $appServiceLocator
                    ->get('config')['zoop']['shard']['manifest'][$manifestName]['document_manager'],
                'manifestName' => $manifestName,
                'serviceLocator' => $appServiceLocator
                    ->get('shard.' . $manifestName . '.serviceManager')
            ]
        );

        return new BatchJsonRestfulController($options);
    }

    protected function getManifestName($name)
    {
        $matches = [];

        if (! preg_match('/^rest\.(?<manifestName>[a-z0-9_]+)\.batch$/', $name, $matches)) {
            return false;
        }

        return $matches['manifestName'];
    }
}
