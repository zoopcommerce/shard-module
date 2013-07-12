<?php
/**
 * @package    Zoop
 * @license    MIT
 */
namespace Zoop\ShardModule\Service;

use Zoop\ShardModule\Controller\DojoController;
use Zoop\ShardModule\Options\DojoControllerOptions;
use Zend\ServiceManager\AbstractFactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 *
 * @since   1.0
 * @version $Revision$
 * @author  Tim Roediger <superdweebie@gmail.com>
 */
class DojoControllerAbstractFactory implements AbstractFactoryInterface
{

    public function canCreateServiceWithName(ServiceLocatorInterface $serviceLocator, $name, $requestedName){
        if ($manifestName = $this->getManifestName($name)){
            return true;
        }
        return false;
    }

    /**
     *
     * @param \Zend\ServiceManager\ServiceLocatorInterface $serviceLocator
     * @return object
     */
    public function createServiceWithName(ServiceLocatorInterface $serviceLocator, $name, $requestedName)
    {
        $manifestName = $this->getManifestName($name);

        $options = new DojoControllerOptions([
            'resource_map' => 'resourcemap',
            'document_manager' => $serviceLocator->getServiceLocator()->get('config')['zoop']['shard']['manifest'][$manifestName]['document_manager'],
            'service_locator' => $serviceLocator->getServiceLocator()->get('shard.' . $manifestName . '.serviceManager')
        ]);

        return new DojoController($options);
    }

    protected function getManifestName($name){

        $matches = [];

        if (! preg_match('/^dojo\.(?<manifestName>[a-z0-9_]+)$/', $name, $matches)) {
            return false;
        }

        return $matches['manifestName'];
    }
}
