<?php
/**
 * @package    Zoop
 * @license    MIT
 */
namespace Zoop\ShardModule\Options;

use Doctrine\Common\EventSubscriber;
use Zoop\Shard\Rest\EndpointMap;
use Zoop\ShardModule\Controller\Listener\DoctrineSubscriber;
use Zoop\ShardModule\Controller\Listener\ZfLazyListener;

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

    protected $endpoint;

    protected $referenceMap = 'referenceMap';

    protected $documentClass;

    protected $limit = '30';

    protected $exceptionSerializer = 'Zoop\MaggottModule\JsonExceptionStrategy';

    protected $surpressFlush;

    protected $endpointMap;

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
        'serialize'        => 'zoop.shardmodule.listener.serialize',
        'serializeList'    => 'zoop.shardmodule.listener.serialize',
        'unserialize'      => 'zoop.shardmodule.listener.unserialize',
        'prepareViewModel' => 'zoop.shardmodule.listener.prepareviewmodel',
        'create'           => 'zoop.shardmodule.listener.create',
        'delete'           => 'zoop.shardmodule.listener.delete',
        'deleteList'       => 'zoop.shardmodule.listener.deletelist',
        'get'              => 'zoop.shardmodule.listener.get',
        'getList'          => 'zoop.shardmodule.listener.getlist',
        'patch'            => 'zoop.shardmodule.listener.patchlist',
        'replaceList'      => 'zoop.shardmodule.listener.replacelist',
        'update'           => 'zoop.shardmodule.listener.update',
    ];

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

    public function getEndpointMap()
    {
        return $this->endpointMap;
    }

    public function setEndpointMap(EndpointMap $endpointMap)
    {
        $this->endpointMap = $endpointMap;
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

    public function getListener($event) {
        if (isset($this->listeners[$event])) {
            return $this->serviceLocator->get($this->listeners[$event]);
        }
    }
}
