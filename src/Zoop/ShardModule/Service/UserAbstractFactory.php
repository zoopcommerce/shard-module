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

    protected $activeCall = false;

    public function canCreateServiceWithName(ServiceLocatorInterface $serviceLocator, $name, $requestedName){

        //this if is to stop recursion. There can be only one user per manifest.
        if ($this->activeCall){
            return false;
        }

        $this->activeCall = true;
        if ($name == 'user' &&
            $serviceLocator->has('Zend\Authentication\AuthenticationService') &&
            $serviceLocator->get('Zend\Authentication\AuthenticationService')->hasIdentity()
        ){
            $this->activeCall = false;
            return true;
        }
        $this->activeCall = false;
    }

    public function createServiceWithName(ServiceLocatorInterface $serviceLocator, $name, $requestedName){

        return $serviceLocator->get('Zend\Authentication\AuthenticationService')->getIdentity();
    }
}