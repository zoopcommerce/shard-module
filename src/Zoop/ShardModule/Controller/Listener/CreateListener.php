<?php
/**
 * @package    Zoop
 * @license    MIT
 */
namespace Zoop\ShardModule\Controller\Listener;

use Doctrine\ODM\MongoDB\Proxy\Proxy;
use Zend\EventManager\EventManagerInterface;
use Zend\Http\Header\CacheControl;
use Zend\Http\Header\LastModified;
use Zend\Mvc\MvcEvent;
use Zoop\ShardModule\Exception;
use Zoop\ShardModule\Controller\Event;
use Zoop\Shard\Serializer\Unserializer;

/**
 *
 * @since   1.0
 * @version $Revision$
 * @author  Tim Roediger <superdweebie@gmail.com>
 */
class CreateListener extends AbstractListener
{
    use LoadDocumentTrait;

    /**
     * Attach to an event manager
     *
     * @param  EventManagerInterface $events
     * @return void
     */
    public function attach(EventManagerInterface $events)
    {
        $this->listeners[] = $events->attach(Event::CREATE, [$this, 'onCreate']);
    }

    public function onCreate(MvcEvent $event)
    {
        $deeperResource = $event->getParam('deeperResource');
        $options = $event->getTarget()->getOptions();
        $documentManager = $options->getModelManager();

        if (! ($endpoint = $event->getParam('endpoint'))) {
            $endpoint = $options->getEndpoint();
        }

        if (count($deeperResource) == 0) {
            $event->setParam('document', null);
            $event->setParam('mode', Unserializer::UNSERIALIZE_PATCH);
            $createdDocument = $event->getTarget()->trigger(Event::UNSERIALIZE, $event)->last();

            if ($documentManager->contains($createdDocument)) {
                $exception = new Exception\DocumentAlreadyExistsException;
                $exception->setDocument($createdDocument);
                throw $exception;
            }
            if (! $documentManager->getClassMetadata(get_class($createdDocument))->isEmbeddedDocument) {
                $documentManager->persist($createdDocument);
            }

            return $createdDocument;
        }

        if ($document = $event->getParam('document')) {
            $metadata = $documentManager->getClassMetadata(get_class($document));
        } else {
            $metadata = $documentManager->getClassMetadata($options->getDocumentClass());
        }

        //a deeper resource is requested
        $field = $deeperResource[0];
        array_shift($deeperResource);

        $event->setParam('deeperResource', $deeperResource);
        $mapping = $metadata->fieldMappings[$field];

        if (isset($mapping['type']) && $mapping['type'] == 'one') {
            return $this->createSingleModel($field, $metadata, $documentManager, $endpoint, $event);
        } else if (isset($mapping['type']) && $mapping['type'] == 'many') {
            return $this->createCollection($field, $metadata, $documentManager, $endpoint, $event);
        }

        throw new Exception\DocumentNotFoundException();
    }

    protected function createSingleModel($field, $metadata, $documentManager, $endpoint, $event)
    {
        $document = $this->loadDocument($event, $documentManager, $metadata, $endpoint, $field);

        $targetMetadata = $documentManager
            ->getClassMetadata($metadata->fieldMappings[$field]['targetDocument']);

        if (isset($metadata->fieldMappings[$field]['embedded'])) {
            $event->setParam('document', $metadata->getFieldValue($document, $field));
            return $this->onCreate($event);
        }

        $targetEndpoint = $event->getTarget()->getOptions()
            ->getEndpointMap()
            ->getEndpointsFromMetadata($targetMetadata)[0];

        $targetDocument = $metadata->getFieldValue($document, $field);

        if (is_string($targetDocument)) {
            $targetDocument = $documentManager->getRepository($targetMetadata->name)->find($targetDocument);
        }
        if ($targetDocument instanceof Proxy) {
            $targetDocument->__load();
        }

        $id = $targetMetadata->reflFields[$targetEndpoint->getProperty()]->getValue($targetDocument);
        $event->setParam('document', $targetDocument);
        $event->setParam('endpoint', $targetEndpoint);
        $event->setParam('surpressResponse', true);

        return $event->getTarget()->forward()->dispatch(
            'rest.' . $event->getTarget()->getOptions()->getManifestName() . '.' . $targetEndpoint->getName(),
            ['id' => $id]
        );
    }

    protected function createCollection($field, $metadata, $documentManager, $endpoint, $event)
    {
        $document = $this->loadDocument($event, $documentManager, $metadata, $endpoint, $field);

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
            $event->setParam('surpressResponse', true);

            try {
                $createdDocument = $event->getTarget()->forward()->dispatch(
                    'rest.' . $event->getTarget()->getOptions()->getManifestName() . '.' . $targetEndpoint->getName(),
                    ['id' => $id]
                );
            } catch (Exception\DocumentAlreadyExistsException $exception) {
                $createdDocument = $exception->getDocument();
            }

            $event->setParam('surpressResponse', false);

            $collection = $metadata->getFieldValue($document, $field);
            if ($collection->contains($createdDocument)) {
                throw new Exception\DocumentAlreadyExistsException();
            }
            if (isset($metadata->fieldMappings[$field]['mappedBy'])) {
                if ($createdDocument instanceof Proxy) {
                    $createdDocument->__load();
                }
                $targetMetadata->setFieldValue($createdDocument, $metadata->fieldMappings[$field]['mappedBy'], $document);
            }
        } else {
            //embedded
            $targetEndpoint = $endpoint->getEmbeddedLists()[$field];
            $collection = $metadata->reflFields[$field]->getValue($document);

            if (!($targetEndpointProperty = $targetEndpoint->getProperty())) {
                $set = array_shift($deeperResource);
            }

            if (count($deeperResource) > 0) {
                if (isset($set) && isset($collection[$set])) {
                    $targetDocument = $collection[$set];
                } else if (!isset($set)) {
                    foreach ($collection as $targetDocument) {
                        if ($targetMetadata->getFieldValue($targetDocument, $targetEndpointProperty) == $deeperResource[0]) {
                            break;
                        }
                    }
                }

                if (!isset($targetDocument)) {
                    //embedded document not found in collection
                    throw new Exception\DocumentNotFoundException();
                }

                $event->setParam('endpoint', $targetEndpoint);
                $event->setParam('deeperResource', $deeperResource);
                $event->setParam('document', $targetDocument);
                return $this->onCreate($event);
            } else {
                $event->setParam('endpoint', $targetEndpoint);
                $createdDocument = $this->onCreate($event);

                if (isset($set)) {
                    $collection[$set] = $createdDocument;
                } else {
                    foreach ($collection as $targetDocument) {
                        if ($targetMetadata->getFieldValue($targetDocument, $targetEndpointProperty) == $targetMetadata->getFieldValue($createdDocument, $targetEndpointProperty)) {
                            throw new Exception\DocumentAlreadyExistsException();
                        }
                    }
                    $collection[] = $createdDocument;
                }

                return $createdDocument;
            }
        }

        return $createdDocument;
    }
}
