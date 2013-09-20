<?php
/**
 * @package    Zoop
 * @license    MIT
 */
namespace Zoop\ShardModule\Controller\Listener;

use Doctrine\ODM\MongoDB\Proxy\Proxy;
use Zend\Http\Header\Location;
use Zend\Mvc\MvcEvent;
use Zoop\ShardModule\Exception;

/**
 *
 * @since   1.0
 * @version $Revision$
 * @author  Tim Roediger <superdweebie@gmail.com>
 */
class CreateListener extends AbstractActionListener
{
    public function create(MvcEvent $event)
    {
        return $this->route($event);
    }

    protected function doAction(MvcEvent $event, $metadata, $documentManager)
    {
        $result = $event->getResult();
        $createdDocument = $result->getModel();

        if ($event->getTarget()->forward()->getNumNestedForwards() == 0 && $documentManager->contains($createdDocument)) {
            $exception = new Exception\DocumentAlreadyExistsException;
            $exception->setDocument($createdDocument);
            throw $exception;
        }
        if (! $documentManager->getClassMetadata(get_class($createdDocument))->isEmbeddedDocument) {
            $documentManager->persist($createdDocument);
        }

        $result->setStatusCode(201);
        $result->addHeader($this->getLocationHeader($event, $metadata, $createdDocument));

        return $result;
    }

    protected function handleAssociatedCollection(MvcEvent $event, $metadata, $documentManager, $field)
    {
        $document = $this->loadDocument($event, $metadata, $documentManager, $field);

        $targetMetadata = $documentManager
            ->getClassMetadata($metadata->fieldMappings[$field]['targetDocument']);

        $deeperResource = $event->getParam('deeperResource');
        $restControllerMap = $this->getRestControllerMap($event);
        $collection = $metadata->getFieldValue($document, $field);

        if (isset($metadata->fieldMappings[$field]['embedded'])) {
            $targetOptions = $restControllerMap->getOptionsFromEndpoint($event->getTarget()->getOptions()->getEndpoint() . '.' . $field);

            if (count($deeperResource) > 0) {
                if (!($targetDocument = $this->selectItemFromCollection(
                    $collection,
                    array_shift($deeperResource),
                    $targetOptions->getProperty()))
                ) {
                    //embedded document not found in collection
                    throw new Exception\DocumentNotFoundException();
                }

                $event->setParam('deeperResource', $deeperResource);
                $event->setParam('document', $targetDocument);
                return $event->getTarget()->forward()->dispatch(
                    'shard.rest.' . $targetOptions->getEndpoint()
                );
            } else {
                $result = $event->getTarget()->forward()->dispatch(
                    'shard.rest.' . $targetOptions->getEndpoint()
                );
                $createdDocument = $result->getModel();

                if ($targetProperty = $targetOptions->getProperty()) {
                    foreach ($collection as $targetDocument) {
                        if ($targetMetadata->getFieldValue($targetDocument, $targetProperty) == $targetMetadata->getFieldValue($createdDocument, $targetProperty)) {
                            throw new Exception\DocumentAlreadyExistsException();
                        }
                    }
                }

                $collection[] = $createdDocument;
                return $result;
            }
        } else if (isset($metadata->fieldMappings[$field]['reference'])) {
            $targetOptions = $restControllerMap->getOptionsFromClass($targetMetadata->name);

            $id = array_shift($deeperResource);
            $event->setParam('id', $id);
            $event->setParam('deeperResource', $deeperResource);
            $event->setParam('document', null);

            $result = $event->getTarget()->forward()->dispatch(
                'shard.rest.' . $targetOptions->getEndpoint(),
                ['id' => $id]
            );

            $createdDocument = $result->getModel();

            if ($targetProperty = $targetOptions->getProperty()) {
                foreach ($collection as $targetDocument) {
                    if ($targetMetadata->getFieldValue($targetDocument, $targetProperty) == $targetMetadata->getFieldValue($createdDocument, $targetProperty)) {
                        throw new Exception\DocumentAlreadyExistsException();
                    }
                }
            }

            $collection[] = $createdDocument;

            if (isset($metadata->fieldMappings[$field]['mappedBy'])) {
                if ($createdDocument instanceof Proxy) {
                    $createdDocument->__load();
                }
                $targetMetadata->setFieldValue($createdDocument, $metadata->fieldMappings[$field]['mappedBy'], $document);
            }

            return $result;
        }
    }

    protected function getLocationHeader($event, $metadata, $createdDocument){

        if ($property = $event->getTarget()->getOptions()->getProperty()) {
            return Location::fromString(
                'Location: ' .
                $event->getRequest()->getUri()->getPath() .
                '/' .
                $metadata->getFieldValue($createdDocument, $property)
            );
        }
    }
}