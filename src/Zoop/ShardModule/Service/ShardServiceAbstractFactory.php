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

    protected $manifestServiceManagers = [];

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function canCreateServiceWithName(ServiceLocatorInterface $serviceLocator, $name, $requestedName)
    {
        if ($factoryMapping = $this->getFactoryMapping($name)) {
            if ($manifestServiceManager = $this->getManifestServiceManager(
                $factoryMapping['manifestName'],
                $serviceLocator
            )
            ) {
                if ($factoryMapping['serviceName'] == 'servicemanager') {
                    return true;
                }

                return $manifestServiceManager->has($factoryMapping['serviceName']);
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

        $manifestServiceManager = $this->getManifestServiceManager($factoryMapping['manifestName'], $serviceLocator);
        if ($factoryMapping['serviceName'] == 'servicemanager') {
            return $manifestServiceManager;
        }
        $instance = $manifestServiceManager->get($factoryMapping['serviceName']);
        if ($instance instanceof ManifestAwareInterface) {
            $instance->setManifest($manifestServiceManager->get('manifest'));
        }

        return $instance;
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

    protected function getManifestServiceManager($manifestName, $serviceLocator)
    {
        if (!isset($this->manifestServiceManagers[$manifestName])) {
            $config = $serviceLocator->get('config')['zoop']['shard']['manifest'];
            if (isset($config[$manifestName])) {
                $manifestServiceManager = Manifest::createServiceManager(
                    $config[$manifestName]['service_manager_config']
                );
                $manifest = new Manifest($config[$manifestName]);
                $manifest->setName($manifestName);
                $manifest->setServiceManager($manifestServiceManager);
                $manifestServiceManager->setService('manifest', $manifest);
                $manifestServiceManager->addPeeringServiceManager($serviceLocator);
                $this->manifestServiceManagers[$manifestName] = $manifestServiceManager;
            } else {
                return null;
            }
        }

        return $this->manifestServiceManagers[$manifestName];
    }
}
