<?php
/**
 * @package    Zoop
 * @license    MIT
 */
namespace Zoop\ShardModule\Options;

use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Stdlib\AbstractOptions;

/**
 *
 * @since   1.0
 * @version $Revision$
 * @author  Tim Roediger <superdweebie@gmail.com>
 */
class AbstractControllerOptions extends AbstractOptions
{

    protected $serviceLocator;

    protected $objectManager;

    protected $manifestName;

    /**
     *
     * @return ServiceLocatorInterface
     */
    public function getServiceLocator()
    {
        return $this->serviceLocator;
    }

    /**
     *
     * @param \Zend\ServiceManager\ServiceLocatorInterface $serviceLocator
     */
    public function setServiceLocator(ServiceLocatorInterface $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
    }

    /**
     *
     * @return \Doctrine\ODM\Mongo\DocumentManager
     */
    public function getObjectManager()
    {
        if (! isset($this->objectManager)) {
            $this->objectManager = $this->serviceLocator->get('objectmanager');
        }

        return $this->objectManager;
    }

    /**
     *
     * @param string | \Doctrine\ODM\Mongo\DocumentManager $documentManager
     */
    public function setObjectManager($objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     *
     * @return string
     */
    public function getManifestName()
    {
        return $this->manifestName;
    }

    /**
     *
     * @param string $manifestName
     */
    public function setManifestName($manifestName)
    {
        $this->manifestName = (string) $manifestName;
    }
}
