<?php
/**
 * @package    Zoop
 * @license    MIT
 */
namespace Zoop\ShardModule\Controller\Listener;

use Doctrine\ODM\MongoDB\Proxy\Proxy;
use Zend\Mvc\MvcEvent;
use Zoop\ShardModule\Exception;

/**
 *
 * @since   1.0
 * @version $Revision$
 * @author  Tim Roediger <superdweebie@gmail.com>
 */
class UpdateListener
{
    use LoadDocumentTrait;
    use RestControllerMapTrait;

    public function update(MvcEvent $event)
    {
        $deeperResource = $event->getParam('deeperResource');
        $options = $event->getTarget()->getOptions();
        $documentManager = $options->getModelManager();
        $metadata = $documentManager->getClassMetadata($options->getClass());

        if (count($deeperResource) == 0) {
            $result = $event->getResult();
            $updatedDocument = $result->getModel();

            if (!$documentManager->contains($updatedDocument) && !$metadata->isEmbeddedDocument) {
                return $event->getTarget()->create([]);
            }

            $result->setStatusCode(204);

            return $result;
        }

        //a deeper resource is requested
        $field = $deeperResource[0];
        array_shift($deeperResource);

        $event->setParam('deeperResource', $deeperResource);
        if (! isset($metadata->fieldMappings[$field])) {
            throw new Exception\DocumentNotFoundException();
        }
        $mapping = $metadata->fieldMappings[$field];

        if (isset($mapping['type']) && $mapping['type'] == 'one') {
            return $this->updateSingleModel($field, $metadata, $documentManager, $event);
        } else if (isset($mapping['type']) && $mapping['type'] == 'many') {
            return $this->updateCollection($field, $metadata, $documentManager, $event);
        }

        throw new Exception\DocumentNotFoundException();
    }

    protected function updateSingleModel($field, $metadata, $documentManager, $event)
    {
        $document = $this->loadDocument($event, $documentManager, $metadata, $field);

        $targetMetadata = $documentManager
            ->getClassMetadata($metadata->fieldMappings[$field]['targetDocument']);

        if (isset($metadata->fieldMappings[$field]['embedded'])) {
            $event->setParam('document', $metadata->getFieldValue($document, $field));
            $result = $event->getTarget()->forward()->dispatch(
                'shard.rest.' . $event->getTarget()->getOptions()->getEndpoint() . '.' . $field
            );
            $metadata->setFieldValue($document, $result->getModel(), $field);
            return $result;
        }

        $targetOptions = $this->getRestControllerMap($event)->getOptionsFromClass($targetMetadata->name);

        $targetDocument = $metadata->getFieldValue($document, $field);

        if (is_string($targetDocument)) {
            $targetDocument = $documentManager->getRepository($targetMetadata->name)->find($targetDocument);
        }
        if ($targetDocument instanceof Proxy) {
            $targetDocument->__load();
        }

        $id = $targetMetadata->reflFields[$targetOptions->getProperty()]->getValue($targetDocument);
        $event->setParam('document', $targetDocument);

        return $event->getTarget()->forward()->dispatch(
            'shard.rest.' . $targetOptions->getEndpoint(),
            ['id' => $id]
        );
    }

    protected function updateCollection($field, $metadata, $documentManager, $event)
    {
        $document = $this->loadDocument($event, $documentManager, $metadata, $field);

        $targetMetadata = $documentManager
            ->getClassMetadata($metadata->fieldMappings[$field]['targetDocument']);

        $deeperResource = $event->getParam('deeperResource');

        if (isset($metadata->fieldMappings[$field]['reference'])) {
            $event->getRequest()->getQuery()->set($metadata->fieldMappings[$field]['mappedBy'], $event->getParam('id'));

            $targetOptions = $this->getRestControllerMap($event)->getOptionsFromClass($targetMetadata->name);

            $id = array_shift($deeperResource);
            $event->setParam('id', $id);
            $event->setParam('deeperResource', $deeperResource);
            $event->setParam('document', null);

            try {
                $result = $event->getTarget()->forward()->dispatch(
                    'shard.rest.' . $targetOptions->getEndpoint(),
                    ['id' => $id]
                );
            } catch (Exception\DocumentAlreadyExistsException $exception) {
                $result = $event->getResult();
                $result->setModel($exception->getDocument());
                $result->setStatusCode(201);
                $result->addHeader($exception->getLocation());
            }

            $createdDocument = $result->getModel();

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

            return $result;

        } else {
            //embedded
            $collection = $metadata->reflFields[$field]->getValue($document);
            $endpoint = $event->getTarget()->getOptions()->getEndpoint();

            if (!($targetEndpointProperty = $this->getRestControllerMap($event)->getOptionsFromEndpoint($endpoint . '.' . $field)->getProperty())) {
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

                $event->setParam('deeperResource', $deeperResource);
                $event->setParam('document', $targetDocument);
                return $event->getTarget()->forward()->dispatch(
                    'shard.rest.' . $event->getTarget()->getOptions()->getEndpoint() . '.' . $field
                );
            } else {
                $result = $event->getTarget()->forward()->dispatch(
                    'shard.rest.' . $event->getTarget()->getOptions()->getEndpoint() . '.' . $field
                );
                $createdDocument = $result->getModel();

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
                return $result;
            }
        }
    }
}
