<?php
/**
 * @package    Zoop
 * @license    MIT
 */
namespace Zoop\ShardModule\Options;

use Zend\Http\Header\CacheControl;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Stdlib\AbstractOptions;

/**
 *
 * @since   1.0
 * @version $Revision$
 * @author  Tim Roediger <superdweebie@gmail.com>
 */
class RestfulControllerOptions extends AbstractOptions
{
    protected $serviceLocator;

    protected $modelManager;

    protected $manifest;

    protected $acceptCriteria;

    protected $endpoint;

    protected $class;

    protected $property;

    protected $cacheControl;

    protected $limit;

    protected $exceptionSerializer;

    protected $surpressFlush;

    protected $templates;

    protected $queryDotPlaceholder;

    protected $exceptionSubscriber;

    protected $listeners;

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

    public function getAcceptCriteria()
    {
        return $this->acceptCriteria;
    }

    public function setAcceptCriteria(array $acceptCriteria)
    {
        $this->acceptCriteria = $acceptCriteria;
    }

    public function getEndpoint()
    {
        return $this->endpoint;
    }

    public function setEndpoint($endpoint)
    {
        $this->endpoint = $endpoint;
    }

    public function getLimit()
    {
        return $this->limit;
    }

    public function setLimit($limit)
    {
        $this->limit = (int) $limit;
    }

    public function getExceptionSerializer()
    {
        if (is_string($this->exceptionSerializer)) {
            $this->exceptionSerializer = $this->serviceLocator->get($this->exceptionSerializer);
        }

        return $this->exceptionSerializer;
    }

    public function setExceptionSerializer($exceptionSerializer)
    {
        $this->exceptionSerializer = $exceptionSerializer;
    }

    public function getSurpressFlush()
    {
        return $this->surpressFlush;
    }

    public function setSurpressFlush($surpressFlush)
    {
        $this->surpressFlush = (boolean) $surpressFlush;
    }

    public function getClass()
    {
        return $this->class;
    }

    public function setClass($class)
    {
        $this->class = $class;
    }

    public function getProperty()
    {
        return $this->property;
    }

    public function setProperty($property)
    {
        $this->property = $property;
    }

    public function getCacheControl()
    {
        if (! $this->cacheControl instanceof CacheControl) {
            $cacheControl = new CacheControl;
            if (isset($this->cacheControl['public'])) {
                $cacheControl->addDirective('public', true);
            }
            if (isset($this->cacheControl['private'])) {
                $cacheControl->addDirective('private', true);
            }
            if (isset($this->cacheControl['no_cache'])) {
                $cacheControl->addDirective('no-cache', true);
            }
            if (isset($this->cacheControl['max_age'])) {
                $cacheControl->addDirective('max-age', $this->cacheControl['max_age']);
            }
            $this->cacheControl = $cacheControl;
        }

        return $this->cacheControl;
    }

    public function setCacheControl($cacheControl)
    {
        $this->cacheControl = $cacheControl;
    }

    public function getTemplates()
    {
        return $this->templates;
    }

    public function setTemplates($templates)
    {
        $this->templates = $templates;
    }

    public function getQueryDotPlaceholder()
    {
        return $this->queryDotPlaceholder;
    }

    public function setQueryDotPlaceholder($queryDotPlaceholder)
    {
        $this->queryDotPlaceholder = $queryDotPlaceholder;
    }

    public function getExceptionSubscriber()
    {
        if (is_string($this->exceptionSubscriber)) {
            $this->exceptionSubscriber = $this->serviceLocator->get($this->exceptionSubscriber);
        }

        return $this->exceptionSubscriber;
    }

    public function setExceptionSubscriber($exceptionSubscriber)
    {
        $this->exceptionSubscriber = $exceptionSubscriber;
    }

    public function getListeners()
    {
        return $this->listeners;
    }

    public function setListeners($listeners)
    {
        $this->listeners = $listeners;
    }

    public function getListenersForEvent($event)
    {
        $result = [];
        foreach ($this->listeners[$event] as $listener) {
            $result[] = $this->serviceLocator->get($listener);
        }

        return $result;
    }
}
