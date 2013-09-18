<?php
/**
 * @package    Zoop
 * @license    MIT
 */
namespace Zoop\ShardModule\Controller\Listener;

use Doctrine\ODM\MongoDB\Proxy\Proxy;
use Zend\EventManager\EventManagerInterface;
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
class GetListener extends AbstractListener
{
    use LoadDocumentTrait;
    use SelectTrait;

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

    public function onGet(MvcEvent $event)
    {
        $deeperResource = $event->getParam('deeperResource');
        $options = $event->getTarget()->getOptions();
        $documentManager = $options->getModelManager();
        $metadata = $documentManager->getClassMetadata($options->getClass());

        if (count($deeperResource) > 0) {
            //a deeper resource is requested
            $field = $deeperResource[0];
            array_shift($deeperResource);

            $event->setParam('deeperResource', $deeperResource);
            $mapping = $metadata->fieldMappings[$field];

            if (isset($mapping['type']) && $mapping['type'] == 'one') {
                return $this->getSingleModel($field, $metadata, $documentManager, $event);
            } else if (isset($mapping['type']) && $mapping['type'] == 'many') {
                return $this->getCollection($field, $metadata, $documentManager, $event);
            }

            throw new Exception\DocumentNotFoundException();
        }

        if (!($document = $event->getParam('document'))) {
            // document not set, so load it
            $document = $documentManager
                ->createQueryBuilder()
                ->find($metadata->name)
                ->field($options->getProperty())->equals($event->getParam('id'))
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

        $event->getResponse()->getHeaders()->addHeader($options->getCacheControl());

        $result = $event->getTarget()->trigger(Event::SERIALIZE, $event)->last();

        if ($select = $this->getSelect($event)) {
            $result = array_intersect_key($result, array_fill_keys($select, 0));
        }

        return $result;
    }

    protected function getSingleModel($field, $metadata, $documentManager, $event)
    {
        $document = $this->loadDocument($event, $documentManager, $metadata, $field);

        $targetMetadata = $documentManager
            ->getClassMetadata($metadata->fieldMappings[$field]['targetDocument']);

        if (isset($metadata->fieldMappings[$field]['embedded'])) {
            $options = $event->getTarget()->getOptions();
            $event->setParam('document', $metadata->getFieldValue($document, $field));
            return $event->getTarget()->forward()->dispatch(
                'shard.rest.' . $options->getEndpoint() . '.' . $field
            );
        }

        foreach ($event->getTarget()->getOptions()->getServiceLocator()->get('config')['zoop']['shard']['rest'] as $endpoint => $config) {
            if ($config['class'] == $targetMetadata->name) {
                $targetEndpoint = $endpoint;
                break;
            }
        }

        if (! ($targetDocument = $metadata->getFieldValue($document, $field))) {
            //associated document is null
            throw new Exception\DocumentNotFoundException();
        }

        if (is_string($targetDocument)) {
            $targetDocument = $documentManager->getRepository($targetMetadata->name)->find($targetDocument);
        }
        if ($targetDocument instanceof Proxy) {
            $targetDocument->__load();
        }

        $id = $targetMetadata->getFieldValue($targetDocument, $targetEndpoint->getProperty());
        $event->setParam('document', $targetDocument);
        $event->setParam('endpoint', $targetEndpoint);

        return $event->getTarget()->forward()->dispatch(
            'shard.rest.' . $targetEndpoint->getName(),
            ['id' => $id]
        );
    }

    protected function getCollection($field, $metadata, $documentManager, $event)
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

        $document = $this->loadDocument($event, $documentManager, $metadata, $field);

        if (count($deeperResource) > 0) {
            $collection = $metadata->getFieldValue($document, $field);

            $targetEndpoint = $endpoint->getEmbeddedLists()[$field];

            foreach ($event->getTarget()->getOptions()->getServiceLocator()->get('config')['zoop']['shard']['rest'] as $endpoint => $config) {
                if ($config['class'] == $targetMetadata->name) {
                    $targetEndpoint = $endpoint;
                    break;
                }
            }

            if ($targetEndpointProperty = $targetEndpoint->getProperty()) {
                foreach ($collection as $targetDocument) {
                    //this iteration is slow. Should be replaced when upgrade to new version of mongo happens
                    if ($targetDocument[$targetEndpointProperty] == $deeperResource[0]) {
                        break;
                    }
                }
            } else {
                //if endpoint property is not set, then a strategy=set must be used
                if (isset($collection[$deeperResource[0]])) {
                    $targetDocument = $collection[$deeperResource[0]];
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
}
