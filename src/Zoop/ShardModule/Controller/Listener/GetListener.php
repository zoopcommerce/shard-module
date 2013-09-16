<?php
/**
 * @package    Zoop
 * @license    MIT
 */
namespace Zoop\ShardModule\Controller\Listener;

use Doctrine\ODM\MongoDB\Proxy\Proxy;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;
use Zend\Http\Header\CacheControl;
use Zend\Http\Header\LastModified;
use Zend\Mvc\MvcEvent;
use Zoop\ShardModule\Exception;
use Zoop\ShardModule\Controller\Event;

/**
 *
 * @since   1.0
 * @version $Revision$
 * @author  Tim Roediger <superdweebie@gmail.com>
 */
class GetListener implements ListenerAggregateInterface
{
    use SelectTrait;

    protected $listeners = array();

    /**
     * Attach to an event manager
     *
     * @param  EventManagerInterface $events
     * @return void
     */
    public function attach(EventManagerInterface $events)
    {
        $this->listeners[] = $events->attach(Event::GET, [$this, 'onGet']);
    }

    /**
     * Detach all our listeners from the event manager
     *
     * @param  EventManagerInterface $events
     * @return void
     */
    public function detach(EventManagerInterface $events)
    {
        foreach ($this->listeners as $index => $listener) {
            if ($events->detach($listener)) {
                unset($this->listeners[$index]);
            }
        }
    }

    public function onGet(MvcEvent $event)
    {
        $deeperResource = $event->getParam('deeperResource');
        $options = $event->getTarget()->getOptions();
        $documentManager = $options->getObjectManager();

        if (! ($endpoint = $event->getParam('endpoint'))) {
            $endpoint = $options->getEndpoint();
        }

        if ($document = $event->getParam('document')) {
            $metadata = $documentManager->getClassMetadata(get_class($document));
        } else {
            $metadata = $documentManager->getClassMetadata($options->getDocumentClass());
        }

        if (count($deeperResource) > 0) {
            //a deeper resource is requested
            $field = $deeperResource[0];
            array_shift($deeperResource);

            $event->setParam('deeperResource', $deeperResource);
            $mapping = $metadata->fieldMappings[$field];

            if (isset($mapping['type']) && $mapping['type'] == 'one') {
                return $this->getSingleObject($field, $metadata, $documentManager, $endpoint, $event);
            } else if (isset($mapping['type']) && $mapping['type'] == 'many') {
                return $this->getCollection($field, $metadata, $documentManager, $endpoint, $event);
            }

            throw new Exception\DocumentNotFoundException();
        }

        if (! isset($document)) {
            // document not set, so load it
            $document = $documentManager
                ->createQueryBuilder()
                ->find($metadata->name)
                ->field($endpoint->getProperty())->equals($event->getParam('id'))
                ->getQuery()
                ->getSingleResult();

            if (! $document) {
                throw new Exception\DocumentNotFoundException();
            }
            $event->setParam('document', $document);
        }

        if (isset($metadata->stamp['updatedOn'])) {
            $lastModified = new LastModified;
            $lastModified->setDate($metadata->getFieldValue($document, $metadata->stamp['updatedOn']));
            $event->getResponse()->getHeaders()->addHeader($lastModified);
        }

        $cacheControlOptions = $endpoint->getCacheControl();
        $cacheControl = new CacheControl;
        if ($cacheControlOptions->getPublic()) {
            $cacheControl->addDirective('public', true);
        }
        if ($cacheControlOptions->getPrivate()) {
            $cacheControl->addDirective('private', true);
        }
        if ($cacheControlOptions->getNoCache()) {
            $cacheControl->addDirective('no-cache', true);
        }
        if ($cacheControlOptions->getMaxAge()) {
            $cacheControl->addDirective('max-age', $cacheControlOptions->getMaxAge());
        }
        $event->getResponse()->getHeaders()->addHeader($cacheControl);

        $result = $event->getTarget()->trigger(Event::SERIALIZE, $event)->last();

        if ($select = $this->getSelect($event)) {
            $result = array_intersect_key($result, array_fill_keys($select, 0));
        }

        return $result;
    }

    protected function getSingleObject($field, $metadata, $documentManager, $endpoint, $event)
    {
        $document = $this->loadDocument($event, $documentManager, $metadata, $endpoint, $field);

        if (! $targetDocument = $metadata->getFieldValue($document, $field)) {
            throw new Exception\DocumentNotFoundException;
        }

        $targetMetadata = $documentManager
            ->getClassMetadata($metadata->fieldMappings[$field]['targetDocument']);

        if (isset($metadata->fieldMappings[$field]['embedded'])) {
            $event->setParam('document', $metadata->getFieldValue($document, $field));
            return $this->onGet($event);
        }

        $targetEndpoint = $event->getTarget()->getOptions()
            ->getEndpointMap()
            ->getEndpointsFromMetadata($targetMetadata)[0];

        if (is_string($targetDocument)) {
            $targetDocument = $documentManager->getRepository($targetMetadata->name)->find($targetDocument);
        }
        if ($targetDocument instanceof Proxy) {
            $targetDocument->__load();
        }

        $id = $targetMetadata->reflFields[$targetEndpoint->getProperty()]->getValue($targetDocument);
        $event->setParam('document', $targetDocument);
        $event->setParam('endpoint', $targetEndpoint);

        return $event->getTarget()->forward()->dispatch(
            'rest.' . $event->getTarget()->getOptions()->getManifestName() . '.' . $targetEndpoint->getName(),
            ['id' => $id]
        );
    }

    protected function getCollection($field, $metadata, $documentManager, $endpoint, $event)
    {
        $targetMetadata = $documentManager
            ->getClassMetadata($metadata->fieldMappings[$field]['targetDocument']);

        $deeperResource = $event->getParam('deeperResource');

        if (isset($metadata->fieldMappings[$field]['reference'])) {
            $event->getRequest()->getQuery()->set($metadata->fieldMappings[$field]['mappedBy'], $event->getParam('id'));

            $targetEndpoint = $event->getTarget()->getOptions()
                ->getEndpointMap()
                ->getEndpointsFromMetadata($targetMetadata)[0];

            $id = array_shift($deeperResource);
            $event->setParam('id', $id);
            $event->setParam('deeperResource', $deeperResource);
            $event->setParam('endpoint', $targetEndpoint);
            $event->setParam('document', null);

            return $event->getTarget()->forward()->dispatch(
                'rest.' . $event->getTarget()->getOptions()->getManifestName() . '.' . $targetEndpoint->getName(),
                ['id' => $id]
            );
        }

        $document = $this->loadDocument($event, $documentManager, $metadata, $endpoint, $field);

        if (count($deeperResource) > 0) {
            $collection = $metadata->getFieldValue($document, $field);

            $targetEndpoint = $endpoint->getEmbeddedLists()[$field];
            $targetEndpointProperty = $targetEndpoint->getProperty();

            if ($targetEndpointProperty == '$set') {
                if (isset($collection[$deeperResource[0]])) {
                    $targetDocument = $collection[$deeperResource[0]];
                }
            } else {
                foreach ($collection as $targetDocument) {
                    //this iteration is slow. Should be replaced when upgrade to new version of mongo happens
                    if ($targetDocument[$targetEndpointProperty] == $deeperResource[0]) {
                        break;
                    }
                }
            }

            if (!isset($targetDocument)) {
                //embedded document not found in collection
                throw new Exception\DocumentNotFoundException();
            }

            array_shift($deeperResource);
            $event->setParam('endpoint', $targetEndpoint);
            $event->setParam('deeperResource', $deeperResource);
            $event->setParam('document', $targetDocument);
            return $this->onGet($event);
        } else {
            $event->setParam('list', $metadata->getFieldValue($document, $field));
            return $event->getTarget()->getList($event);
        }
    }

    protected function loadDocument($event, $documentManager, $metadata, $endpoint, $field)
    {
        if (! ($document = $event->getParam('document'))) {
            // document not set, so load it
            $document = $documentManager
                ->createQueryBuilder()
                ->find($metadata->name)
                ->field($endpoint->getProperty())->equals($event->getParam('id'))
                ->select($field)
                ->getQuery()
                ->getSingleResult();

            if (! $document) {
                throw new Exception\DocumentNotFoundException();
            }
        }
        return $document;
    }
}
