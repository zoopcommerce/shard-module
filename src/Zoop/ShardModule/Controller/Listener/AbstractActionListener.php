<?php
/**
 * @package    Zoop
 * @license    MIT
 */
namespace Zoop\ShardModule\Controller\Listener;

use Zend\Mvc\MvcEvent;
use Zoop\ShardModule\Exception;

/**
 *
 * @since   1.0
 * @version $Revision$
 * @author  Tim Roediger <superdweebie@gmail.com>
 */
abstract class AbstractActionListener
{
    protected $restControllerMap;

    public function route(MvcEvent $event)
    {
        $deeperResource = $event->getParam('deeperResource');
        $options = $event->getTarget()->getOptions();
        $documentManager = $options->getModelManager();
        $metadata = $documentManager->getClassMetadata($options->getClass());

        if (count($deeperResource) == 0) {
            return $this->doAction($event, $metadata, $documentManager);
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
            return $this->handleAssociatedSingle($event, $metadata, $documentManager, $field);
        } else if (isset($mapping['type']) && $mapping['type'] == 'many') {
            return $this->handleAssociatedCollection($event, $metadata, $documentManager, $field);
        }

        throw new Exception\DocumentNotFoundException();
    }

    abstract protected function doAction(MvcEvent $event, $metadata, $documentManager);

    /**
     * This default handler is used by get, create and update
     * other listeners override
     *
     * @param \Zend\Mvc\MvcEvent $event
     * @param type $metadata
     * @param type $documentManager
     * @param type $field
     * @return type
     * @throws Exception\DocumentNotFoundException
     */
    protected function handleAssociatedSingle(MvcEvent $event, $metadata, $documentManager, $field)
    {
        $document = $this->loadDocument($event, $metadata, $documentManager, $field);

        $targetMetadata = $documentManager
            ->getClassMetadata($metadata->fieldMappings[$field]['targetDocument']);

        if (! ($targetDocument = $metadata->getFieldValue($document, $field))) {
            //associated document is null
            throw new Exception\DocumentNotFoundException();
        }

        $restControllerMap = $this->getRestControllerMap($event);
        if (isset($metadata->fieldMappings[$field]['embedded'])) {
            $targetOptions = $restControllerMap->getOptionsFromEndpoint($event->getTarget()->getOptions()->getEndpoint() . '.' . $field);
            $targetDocument = $metadata->getFieldValue($document, $field);
            $routeMatchArgs = [];
        } else if ($metadata->fieldMappings[$field]['reference']) {
            $targetOptions = $restControllerMap->getOptionsFromClass($targetMetadata->name);
            if (is_string($targetDocument)) {
                $targetDocument = $documentManager->getRepository($targetMetadata->name)->find($targetDocument);
            }
            $id = $targetMetadata->getFieldValue($targetDocument, $targetOptions->getProperty());
            $event->setParam('id', $id);
            $routeMatchArgs = ['id' => $id];
        }

        $event->setParam('document', $targetDocument);

        return $event->getTarget()->forward()->dispatch(
            'shard.rest.' . $targetOptions->getEndpoint(),
            $routeMatchArgs
        );
    }

    protected function handleAssociatedCollection(MvcEvent $event, $metadata, $documentManager, $field)
    {

    }

    protected function loadDocument(MvcEvent $event, $metadata, $documentManager, $field)
    {
        if (! ($document = $event->getParam('document'))) {
            // document not set, so load it
            $document = $documentManager
                ->createQueryBuilder()
                ->find($metadata->name)
                ->field($event->getTarget()->getOptions()->getProperty())->equals($event->getParam('id'))
                ->select($field)
                ->getQuery()
                ->getSingleResult();

            if (! $document) {
                throw new Exception\DocumentNotFoundException();
            }
        }
        return $document;
    }

    protected function selectItemFromCollection($collection, $key, $keyProperty = null) {
        if ($keyProperty) {
            foreach ($collection as $item) {
                //this iteration is slow. Should be replaced when upgrade to new version of mongo happens
                if ($item[$keyProperty] == $key) {
                    return $item;
                }
            }
        } else {
            //if endpoint property is not set, then a strategy=set must be used
            if (isset($collection[$key])) {
                return $collection[$key];
            }
        }
    }

    protected function getRestControllerMap(MvcEvent $event){
        if (!isset($this->restControllerMap)) {
            $this->restControllerMap = $event->getTarget()->getOptions()->getServiceLocator()->get('zoop.shardmodule.restcontrollermap');
        }
        return $this->restControllerMap;
    }
}
