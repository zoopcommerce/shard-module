<?php
/**
 * @package    Zoop
 * @license    MIT
 */
namespace Zoop\ShardModule\Service;

use Zend\ServiceManager\AbstractFactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 *
 * @since   1.0
 * @version $Revision$
 * @author  Tim Roediger <superdweebie@gmail.com>
 */
class UserAbstractFactory implements AbstractFactoryInterface
{

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function canCreateServiceWithName(ServiceLocatorInterface $serviceLocator, $name, $requestedName)
    {
        if ($name == 'user' &&
            $serviceLocator->has('Zend\Authentication\AuthenticationService')
        ) {
            $authenticationService = $serviceLocator->get('Zend\Authentication\AuthenticationService');
            if ($authenticationService->hasIdentity() || $authenticationService->authenticate()->isValid()) {
                return true;
            }
        }
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function createServiceWithName(ServiceLocatorInterface $serviceLocator, $name, $requestedName)
    {
        return $serviceLocator->get('Zend\Authentication\AuthenticationService')->getIdentity();
    }
}
