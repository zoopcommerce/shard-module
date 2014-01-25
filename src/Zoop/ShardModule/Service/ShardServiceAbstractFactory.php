<?php
/**
 * @package    Zoop
 * @license    MIT
 */
namespace Zoop\ShardModule\Service;

use Zoop\Shard\Manifest;
use Zoop\ShardModule\ManifestAwareInterface;
use Zend\ServiceManager\AbstractFactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 *
 * @since   1.0
 * @version $Revision$
 * @author  Tim Roediger <superdweebie@gmail.com>
 */
class ShardServiceAbstractFactory implements AbstractFactoryInterface
{

    protected $manifestSLs = [];

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function canCreateServiceWithName(ServiceLocatorInterface $serviceLocator, $name, $requestedName)
    {
        if ($factoryMapping = $this->getFactoryMapping($name)) {
            if ($manifestSL = $this->getManifestServiceLocator(
                $factoryMapping['manifestName'],
                $serviceLocator
            )
            ) {
                if ($factoryMapping['serviceName'] == 'servicemanager') {
                    return true;
                }

                return $manifestSL->has($factoryMapping['serviceName']);
            }
        }

        return false;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function createServiceWithName(ServiceLocatorInterface $serviceLocator, $name, $requestedName)
    {
        $factoryMapping = $this->getFactoryMapping($name);

        $manifestSL = $this->getManifestServiceLocator($factoryMapping['manifestName'], $serviceLocator);
        if ($factoryMapping['serviceName'] == 'servicemanager') {
            return $manifestSL;
        }
        return $manifestSL->get($factoryMapping['serviceName']);
    }

    protected function getFactoryMapping($name)
    {
        $matches = [];

        if (! preg_match('/^shard\.(?<manifestName>[a-z0-9_]+)\.(?<serviceName>[a-z0-9_.]+)$/', $name, $matches)) {
            return false;
        }

        return [
            'manifestName' => $matches['manifestName'],
            'serviceName' => $matches['serviceName']
        ];
    }

    protected function getManifestServiceLocator($manifestName, $serviceLocator)
    {
        if (!isset($this->manifestSLs[$manifestName])) {
            $config = $serviceLocator->get('config')['zoop']['shard']['manifest'];
            if (isset($config[$manifestName])) {
                $manifestSL = Manifest::createServiceManager(
                    $config[$manifestName]['service_manager_config']
                );
                $manifest = new Manifest($config[$manifestName]);
                $manifest->setName($manifestName);
                $manifest->setServiceManager($manifestSL);
                $manifestSL->setService('manifest', $manifest);
                $manifestSL->addPeeringServiceManager($serviceLocator);
                $this->manifestSLs[$manifestName] = $manifestSL;
            } else {
                return null;
            }
        }

        return $this->manifestSLs[$manifestName];
    }
}
