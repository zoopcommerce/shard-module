<?php
/**
 * @package    Zoop
 * @license    MIT
 */
namespace Zoop\ShardModule\Controller\Listener;

use Doctrine\ODM\MongoDB\Proxy\Proxy;
use Zend\Http\Header\LastModified;
use Zend\Mvc\MvcEvent;
use Zoop\ShardModule\Exception;
use Zoop\ShardModule\Controller\Result;

/**
 *
 * @since   1.0
 * @version $Revision$
 * @author  Tim Roediger <superdweebie@gmail.com>
 */
class DeleteListener
{
    use LoadDocumentTrait;
    use RestControllerMapTrait;

    public function delete(MvcEvent $event)
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
                return $this->deleteSingleModel($field, $metadata, $documentManager, $event);
            } else if (isset($mapping['type']) && $mapping['type'] == 'many') {
                return $this->deleteCollection($field, $metadata, $documentManager, $event);
            }

            throw new Exception\DocumentNotFoundException();
        }

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

    protected function deleteSingleModel($field, $metadata, $documentManager, $event)
    {
        $document = $this->loadDocument($event, $documentManager, $metadata, $field);

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

        $targetOptions = $this->getRestControllerMap($event)->getOptionsFromClass($targetMetadata->name);

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

        $id = $targetMetadata->getFieldValue($targetDocument, $targetOptions->getProperty());
        $event->setParam('document', $targetDocument);

        return $event->getTarget()->forward()->dispatch(
            'shard.rest.' . $targetOptions->getEndpoint(),
            ['id' => $id]
        );
    }

    protected function deleteCollection($field, $metadata, $documentManager, $event)
    {
        $targetMetadata = $documentManager
            ->getClassMetadata($metadata->fieldMappings[$field]['targetDocument']);

        $deeperResource = $event->getParam('deeperResource');

        if (isset($metadata->fieldMappings[$field]['reference'])) {
            if (count($deeperResource) > 0) {
                $event->getRequest()->getQuery()->set($metadata->fieldMappings[$field]['mappedBy'], $event->getParam('id'));

                $targetOptions = $this->getRestControllerMap($event)->getOptionsFromClass($targetMetadata->name);

                $id = array_shift($deeperResource);
                $event->setParam('id', $id);
                $event->setParam('deeperResource', $deeperResource);
                $event->setParam('document', null);

                return $event->getTarget()->forward()->dispatch(
                    'shard.rest.' . $targetOptions->getEndpoint(),
                    ['id' => $id]
                );
            }
            $document = $this->loadDocument($event, $documentManager, $metadata, $field);
            $collection = $metadata->getFieldValue($document, $field);
            foreach ($collection as $key => $item) {
                $collection->remove($key);
            }
            $result = new Result([]);
            $result->setStatusCode(204);

            $event->setResult($result);
            return $result;
        }

        $document = $this->loadDocument($event, $documentManager, $metadata, $field);
        $endpoint = $event->getTarget()->getOptions()->getEndpoint();

        if (count($deeperResource) > 0) {
            $collection = $metadata->getFieldValue($document, $field);

            if ($targetEndpointProperty = $this->getRestControllerMap($event)->getOptionsFromEndpoint($endpoint . '.' . $field)->getProperty()) {
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
        } else {
            $collection = $metadata->getFieldValue($document, $field);
            foreach ($collection as $key => $item) {
                $collection->remove($key);
            }
            $result = new Result([]);
            $result->setStatusCode(204);

            $event->setResult($result);
            return $result;
        }
    }
}
