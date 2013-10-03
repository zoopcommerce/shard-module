<?php
/**
 * @package    Zoop
 * @license    MIT
 */
namespace Zoop\ShardModule\Controller\Listener;

use Zend\Mvc\MvcEvent;

/**
 *
 * @since   1.0
 * @version $Revision$
 * @author  Tim Roediger <superdweebie@gmail.com>
 */
class UpdateListener extends AbstractActionListener
{
    public function update(MvcEvent $event)
    {
        return $this->route($event);
    }

    protected function doAction(MvcEvent $event, $metadata, $documentManager)
    {
        $result = $event->getResult();
        $updatedDocument = $result->getModel();

        if (!$documentManager->contains($updatedDocument) && !$metadata->isEmbeddedDocument) {
            $documentManager->persist($updatedDocument);
        }

        $result->setStatusCode(204);

        return $result;
    }

    protected function handleAssociatedSingle(MvcEvent $event, $metadata, $documentManager, $field)
    {
        $document = $this->loadDocument($event, $metadata, $documentManager, $field);
        $result = parent::handleAssociatedSingle($event, $metadata, $documentManager, $field);
        $metadata->setFieldValue($document, $field, $result->getModel());

        return $result;
    }

    protected function handleAssociatedCollection(MvcEvent $event, $metadata, $documentManager, $field)
    {
        $document = $this->loadDocument($event, $metadata, $documentManager, $field);

        $targetMetadata = $documentManager
            ->getClassMetadata($metadata->fieldMappings[$field]['targetDocument']);

        $deeperResource = $event->getParam('deeperResource');
        $restControllerMap = $this->getRestControllerMap($event);
        $collection = $metadata->reflFields[$field]->getValue($document);
        $targetOptions = $restControllerMap->getOptionsFromEndpoint($event->getTarget()->getOptions()->getEndpoint() . '.' . $field);

        if (isset($metadata->fieldMappings[$field]['reference'])) {
            $event->getRequest()->getQuery()->set($metadata->fieldMappings[$field]['mappedBy'], $event->getParam('id'));
        }

        if (count($deeperResource) == 0) {
            $event->setParam('deeperResource', $deeperResource);
            $event->setParam('list', $collection);

            return $event->getTarget()->forward()->dispatch(
                'shard.rest.' . $targetOptions->getEndpoint(),
                ['id' => false]
            );
        }

        $id = array_shift($deeperResource);
        $event->setParam('id', $id);
        $event->setParam('deeperResource', $deeperResource);

        if (isset($metadata->fieldMappings[$field]['embedded'])) {
            $targetDocument = $this->selectItemFromCollection(
                $collection,
                $id,
                $targetOptions->getProperty()
            );

            $event->setParam('document', $targetDocument);
            $result = $event->getTarget()->forward()->dispatch(
                'shard.rest.' . $targetOptions->getEndpoint()
            );

            if (!isset($targetDocument)) {
                $collection[$id] = $result->getModel();
            }
        } elseif (isset($metadata->fieldMappings[$field]['reference'])) {
            $event->setParam('document', null);

            $result = $event->getTarget()->forward()->dispatch(
                'shard.rest.' . $targetOptions->getEndpoint(),
                ['id' => $id]
            );

            $updatedDocument = $result->getModel();

            if (isset($metadata->fieldMappings[$field]['mappedBy'])) {
                $targetMetadata->setFieldValue($updatedDocument, $metadata->fieldMappings[$field]['mappedBy'], $document);
            } else {
                $collection[$id] = $updatedDocument;
            }
        }

        return $result;
    }
}
