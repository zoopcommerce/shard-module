<?php
/**
 * @package    Zoop
 * @license    MIT
 */
namespace Zoop\ShardModule\Controller\Listener;

use Zend\Mvc\MvcEvent;
use Zoop\ShardModule\Exception;
use Zoop\ShardModule\Controller\Result;

/**
 *
 * @since   1.0
 * @version $Revision$
 * @author  Tim Roediger <superdweebie@gmail.com>
 */
class DeleteListener extends AbstractActionListener
{
    public function delete(MvcEvent $event)
    {
        return $this->route($event);
    }

    protected function doAction(MvcEvent $event, $metadata, $documentManager)
    {
        $options = $event->getTarget()->getOptions();

        if ($document = $event->getParam('document')) {
            // document already loaded, so just remove it
            $documentManager->remove($document);
        } else {
            // use query to remove the doc
            $documentManager
                ->createQueryBuilder($metadata->name)
                ->remove()
                ->field($options->getProperty())->equals($event->getParam('id'))
                ->getQuery()
                ->execute();
        }

        $result = new Result([]);
        $result->setStatusCode(204);

        $event->setResult($result);

        return $result;
    }

    protected function handleAssociatedSingle(MvcEvent $event, $metadata, $documentManager, $field)
    {
        $document = $this->loadDocument($event, $metadata, $documentManager, $field);

        $targetMetadata = $documentManager
            ->getClassMetadata($metadata->fieldMappings[$field]['targetDocument']);

        if (isset($metadata->fieldMappings[$field]['embedded'])) {
            $deeperResource = $event->getParam('deeperResource');

            if (count($deeperResource) == 0) {
                $metadata->setFieldValue($document, $field, null);

                $result = new Result([]);
                $result->setStatusCode(204);

                $event->setResult($result);

                return $result;
            } else {
                $options = $event->getTarget()->getOptions();
                $event->setParam('document', $metadata->getFieldValue($document, $field));

                return $event->getTarget()->forward()->dispatch(
                    'shard.rest.' . $options->getEndpoint() . '.' . $field
                );
            }
        }

        $deeperResource = $event->getParam('deeperResource');

        if (count($deeperResource) == 0) {
            $metadata->setFieldValue($document, $field, null);
            $result = new Result([]);
            $result->setStatusCode(204);

            $event->setResult($result);

            return $result;
        }

        $targetOptions =
            $this->getRestControllerMap($event)
                ->getOptionsFromEndpoint($event->getTarget()->getOptions()->getEndpoint() . '.' . $field);

        if (! ($targetDocument = $metadata->getFieldValue($document, $field))) {
            //associated document is null
            throw new Exception\DocumentNotFoundException();
        }

        if (is_string($targetDocument)) {
            $targetDocument = $documentManager->getRepository($targetMetadata->name)->find($targetDocument);
        }

        $id = $targetMetadata->getFieldValue($targetDocument, $targetOptions->getProperty());
        $event->setParam('document', $targetDocument);

        return $event->getTarget()->forward()->dispatch(
            'shard.rest.' . $targetOptions->getEndpoint(),
            ['id' => $id]
        );
    }

    protected function handleAssociatedCollection(MvcEvent $event, $metadata, $documentManager, $field)
    {
        $deeperResource = $event->getParam('deeperResource');

        if (count($deeperResource) == 0) {
            $document = $this->loadDocument($event, $metadata, $documentManager, $field);
            $collection = $metadata->getFieldValue($document, $field);
            foreach ($collection->getKeys() as $key) {
                $collection->remove($key);
            }
            $result = new Result([]);
            $result->setStatusCode(204);

            $event->setResult($result);

            return $result;
        }

        if (isset($metadata->fieldMappings[$field]['reference'])) {
            $event->getRequest()->getQuery()->set($metadata->fieldMappings[$field]['mappedBy'], $event->getParam('id'));
            $targetOptions = $this->getRestControllerMap($event)
                ->getOptionsFromEndpoint($event->getTarget()->getOptions()->getEndpoint() . '.' . $field);

            $id = array_shift($deeperResource);
            $event->setParam('id', $id);
            $event->setParam('deeperResource', $deeperResource);
            $event->setParam('document', null);

            return $event->getTarget()->forward()->dispatch(
                'shard.rest.' . $targetOptions->getEndpoint(),
                ['id' => $id]
            );
        }

        $document = $this->loadDocument($event, $metadata, $documentManager, $field);
        $endpoint = $event->getTarget()->getOptions()->getEndpoint();
        $collection = $metadata->getFieldValue($document, $field);

        if (!$targetDocument = $this->selectItemFromCollection(
            $collection,
            $deeperResource[0],
            $this->getRestControllerMap($event)->getOptionsFromEndpoint($endpoint . '.' . $field)->getProperty()
        )
        ) {
            //embedded document not found in collection
            throw new Exception\DocumentNotFoundException();
        }

        if (!isset($targetDocument)) {
            //embedded document not found in collection
            throw new Exception\DocumentNotFoundException();
        }

        $id = array_shift($deeperResource);
        $event->setParam('id', $id);

        if (count($deeperResource) == 0) {
            $collection->removeElement($targetDocument);
            $result = new Result([]);
            $result->setStatusCode(204);

            $event->setResult($result);

            return $result;
        } else {
            array_shift($deeperResource);
            $event->setParam('deeperResource', $deeperResource);
            $event->setParam('document', $targetDocument);

            return $event->getTarget()->forward()->dispatch(
                'shard.rest.' . $endpoint . '.' . $field
            );
        }
    }
}
