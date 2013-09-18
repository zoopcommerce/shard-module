<?php
/**
 * @package    Zoop
 * @license    MIT
 */
namespace Zoop\ShardModule\Options;

use Doctrine\Common\EventSubscriber;
use Zend\Http\Header\CacheControl;
use Zoop\ShardModule\Controller\Listener\DoctrineSubscriber;

/**
 *
 * @since   1.0
 * @version $Revision$
 * @author  Tim Roediger <superdweebie@gmail.com>
 */
class RestfulControllerOptions extends AbstractControllerOptions
{

    protected $acceptCriteria = [
        'Zend\View\Model\JsonModel' => [
            'application/json',
        ],
        'Zend\View\Model\ViewModel' => [
            '*/*',
        ],
    ];

    protected $referenceMap = 'referenceMap';

    protected $endpoint;

    protected $class;

    protected $property;

    protected $cacheControl;

    protected $rest;

    protected $limit = '30';

    protected $exceptionSerializer = 'Zoop\MaggottModule\JsonExceptionStrategy';

    protected $surpressFlush;

    protected $templates = [
        'get'         => 'zoop/rest/get',
        'getList'     => 'zoop/rest/get-list',
        'create'      => 'zoop/rest/create',
        'delete'      => 'zoop/rest/delete',
        'deleteList'  => 'zoop/rest/delete-list',
        'patch'       => 'zoop/rest/patch',
        'patchList'   => 'zoop/rest/patch-list',
        'update'      => 'zoop/rest/update',
        'replaceList' => 'zoop/rest/replace-list',
    ];

    protected $queryDotPlaceholder = '_';

    protected $doctrineSubscriber;

    protected $listeners = [
        'serialize'        => ['zoop.shardmodule.listener.serialize'],
        'serializeList'    => ['zoop.shardmodule.listener.serialize'],
        'unserialize'      => ['zoop.shardmodule.listener.unserialize'],
        'flush'            => ['zoop.shardmodule.listener.flush'],
        'prepareViewModel' => ['zoop.shardmodule.listener.prepareviewmodel'],
        'create'           => ['zoop.shardmodule.listener.create'],
        'delete'           => ['zoop.shardmodule.listener.delete'],
        'deleteList'       => ['zoop.shardmodule.listener.deletelist'],
        'get'              => ['zoop.shardmodule.listener.get'],
        'getList'          => ['zoop.shardmodule.listener.getlist'],
        'patch'            => ['zoop.shardmodule.listener.patchlist'],
        'replaceList'      => ['zoop.shardmodule.listener.replacelist'],
        'update'           => ['zoop.shardmodule.listener.update'],
    ];

    public function getAcceptCriteria()
    {
        return $this->acceptCriteria;
    }

    public function setAcceptCriteria(array $acceptCriteria)
    {
        $this->acceptCriteria = $acceptCriteria;
    }

    public function setReferenceMap($referenceMap)
    {
        $this->referenceMap = $referenceMap;
    }

    public function getReferenceMap()
    {
        if (is_string($this->referenceMap)) {
            $this->referenceMap = $this->serviceLocator->get($this->referenceMap);
        }

        return $this->referenceMap;
    }

    public function getEndpoint() {
        return $this->endpoint;
    }

    public function setEndpoint($endpoint) {
        $this->endpoint = $endpoint;
    }

    public function getDocumentClass()
    {
        return $this->documentClass;
    }

    public function setDocumentClass($documentClass)
    {
        $this->documentClass = (string) $documentClass;
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

    public function getClass() {
        return $this->class;
    }

    public function setClass($class) {
        $this->class = $class;
    }

    public function getProperty() {
        return $this->property;
    }

    public function setProperty($property) {
        $this->property = $property;
    }

    public function getCacheControl() {
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

    public function setCacheControl($cacheControl) {
        $this->cacheControl = $cacheControl;
    }

    public function getRest() {
        return $this->rest;
    }

    public function setRest($rest) {
        $this->rest = $rest;
    }

    public function getTemplates() {
        return $this->templates;
    }

    public function setTemplates($templates) {
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

    public function getDoctrineSubscriber()
    {
        if (!isset($this->doctrineSubscriber)) {
            $this->doctrineSubscriber = new DoctrineSubscriber;
        }
        return $this->doctrineSubscriber;
    }

    public function setDoctrineSubscriber(EventSubscriber $doctrineSubscriber)
    {
        $this->doctrineSubscriber = $doctrineSubscriber;
    }

    public function getListeners() {
        return $this->listeners;
    }

    public function setListeners($listeners) {
        $this->listeners = $listeners;
    }

    public function getListenersForEvent($event) {
        $result = [];
        foreach ($this->listeners[$event] as $listener) {
            $result[] = $this->serviceLocator->get($listener);
        }
        return $result;
    }
}
