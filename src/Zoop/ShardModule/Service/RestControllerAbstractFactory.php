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
        return $this->getOptions($name, $serviceLocator->getServiceLocator());
    }

    public function createServiceWithName(ServiceLocatorInterface $serviceLocator, $name, $requestedName)
    {
        $options = $this->getOptions($name, $serviceLocator->getServiceLocator());
        $options['service_locator'] = $serviceLocator->getServiceLocator();

        return new RestfulController(new RestfulControllerOptions($options));
    }

    protected function getOptions($name, $serviceLocator)
    {
        $pieces = explode('.', $name);
        if (array_shift($pieces) == 'shard' && array_shift($pieces) == 'rest') {
            $endpoint = implode('.', $pieces);
            if (!($options = $serviceLocator->get('config')['zoop']['shard']['rest'][array_shift($pieces)])){
                return false;
            }
            foreach ($pieces as $piece) {
                $metadata = $serviceLocator->get('shard.' . $options['manifest'] . '.manifest')->getServiceManager()->get('modelmanager')->getClassMetadata($options['class']);
                if ($metadata->fieldMappings[$piece]['targetDocument']) {
                    $options['class'] = $metadata->fieldMappings[$piece]['targetDocument'];
                }
                if (isset($options['rest'][$piece])) {
                    $options = array_merge($options, $options['rest'][$piece]);
                } else {
                    unset($options['rest']);
                }
            }
            $options['endpoint'] = $endpoint;
            return $options;
        }

        return false;
    }
}
