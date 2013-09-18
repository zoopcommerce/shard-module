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

    protected $modelManager;

    protected $manifest;

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
    public function getModelManager()
    {
        if (! isset($this->modelManager)) {
            $this->modelManager = $this->getManifest()->getServiceManager()->get('modelmanager');
        }

        return $this->modelManager;
    }

    /**
     *
     * @param string | \Doctrine\ODM\Mongo\DocumentManager $documentManager
     */
    public function setModelManager($modelManager)
    {
        $this->modelManager = $modelManager;
    }

    /**
     *
     * @return string
     */
    public function getManifest()
    {
        if (is_string($this->manifest)) {
            $this->manifest = $this->serviceLocator->get('shard.' . $this->manifest . '.manifest');
        }
        return $this->manifest;
    }

    /**
     *
     * @param string $manifestName
     */
    public function setManifest($manifest)
    {
        $this->manifest = $manifest;
    }
}
