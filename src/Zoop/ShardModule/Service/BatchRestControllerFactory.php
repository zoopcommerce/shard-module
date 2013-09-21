<?php
/**
 * @package    Zoop
 * @license    MIT
 */
namespace Zoop\ShardModule\Service;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zoop\ShardModule\Controller\BatchRestfulController;
use Zoop\ShardModule\Options\BatchRestfulControllerOptions;

/**
 *
 * @since   1.0
 * @version $Revision$
 * @author  Tim Roediger <superdweebie@gmail.com>
 */
class BatchRestControllerFactory implements FactoryInterface
{

    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $appServiceLocator = $serviceLocator->getServiceLocator();

        $options = new BatchRestfulControllerOptions(
            [
                'service_locator' => $appServiceLocator
            ]
        );

        return new BatchRestfulController($options);
    }
}
