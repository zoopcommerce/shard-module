<?php
/**
 * @package    Zoop
 * @license    MIT
 */
namespace Zoop\ShardModule\Controller\Listener;

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

        if ($event->getTarget()->forward()->getNumNestedForwards() == 0 &&
            $documentManager->contains($createdDocument)
        ) {
            $exception = new Exception\DocumentAlreadyExistsException;
            $exception->setDocument($createdDocument);
            throw $exception;
        }
        if (! $documentManager->getClassMetadata(get_class($createdDocument))->isEmbeddedDocument) {
            $documentManager->persist($createdDocument);
        }

        $result->setStatusCode(201);

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
        $targetOptions = $restControllerMap
            ->getOptionsFromEndpoint($event->getTarget()->getOptions()->getEndpoint() . '.' . $field);

        if (count($deeperResource) == 0 || isset($metadata->fieldMappings[$field]['reference'])) {
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
                    if ($targetMetadata->getFieldValue($targetDocument, $targetProperty) ==
                        $targetMetadata->getFieldValue($createdDocument, $targetProperty)
                    ) {
                        throw new Exception\DocumentAlreadyExistsException('Document already exists');
                    }
                }
            }

            $collection[] = $createdDocument;

            if (isset($metadata->fieldMappings[$field]['mappedBy'])) {
                $targetMetadata->setFieldValue(
                    $createdDocument,
                    $metadata->fieldMappings[$field]['mappedBy'],
                    $document
                );
            }

            return $result;
        }

        if (isset($metadata->fieldMappings[$field]['embedded'])) {
            if (!$targetDocument = $this->selectItemFromCollection(
                $collection,
                array_shift($deeperResource),
                $targetOptions->getProperty()
            )
            ) {
                //embedded document not found in collection
                throw new Exception\DocumentNotFoundException();
            }

            $event->setParam('deeperResource', $deeperResource);
            $event->setParam('document', $targetDocument);

            return $event->getTarget()->forward()->dispatch(
                'shard.rest.' . $targetOptions->getEndpoint()
            );
        }
    }
}
