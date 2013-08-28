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

    protected $documentManager;

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
    public function getDocumentManager()
    {
        if (is_string($this->documentManager)) {
            $this->documentManager = $this->serviceLocator->get($this->documentManager);
        }

        return $this->documentManager;
    }

    /**
     *
     * @param string | \Doctrine\ODM\Mongo\DocumentManager $documentManager
     */
    public function setDocumentManager($documentManager)
    {
        $this->documentManager = $documentManager;
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
