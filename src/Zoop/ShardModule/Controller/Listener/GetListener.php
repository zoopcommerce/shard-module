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
class GetListener
{
    use LoadDocumentTrait;
    use RestControllerMapTrait;

    public function get(MvcEvent $event)
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
        }

        $result = new Result($document);

        if (isset($metadata->stamp['updatedOn'])) {
            $lastModified = new LastModified;
            $lastModified->setDate($metadata->getFieldValue($document, $metadata->stamp['updatedOn']));
            $result->addHeader($lastModified);
        }
        $result->addHeader($options->getCacheControl());

        $event->setResult($result);
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

    protected function getCollection($field, $metadata, $documentManager, $event)
    {
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

            return $event->getTarget()->forward()->dispatch(
                'shard.rest.' . $targetOptions->getEndpoint(),
                ['id' => $id]
            );
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

            array_shift($deeperResource);
            $event->setParam('deeperResource', $deeperResource);
            $event->setParam('document', $targetDocument);
            return $event->getTarget()->forward()->dispatch(
                'shard.rest.' . $endpoint . '.' . $field
            );
        } else {
            $event->setParam('list', $metadata->getFieldValue($document, $field));
            return $event->getTarget()->forward()->dispatch(
                'shard.rest.' . $endpoint . '.' . $field,
                ['id' => null]
            );
        }
    }
}
