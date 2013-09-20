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
class GetListener extends AbstractActionListener
{
    public function get(MvcEvent $event)
    {
        return $this->route($event);
    }

    protected function doAction(MvcEvent $event, $metadata, $documentManager)
    {
        $options = $event->getTarget()->getOptions();

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

    protected function handleAssociatedCollection(MvcEvent $event, $metadata, $documentManager, $field)
    {
        $targetMetadata = $documentManager
            ->getClassMetadata($metadata->fieldMappings[$field]['targetDocument']);

        $deeperResource = $event->getParam('deeperResource');
        $restControllerMap = $this->getRestControllerMap($event);
        $routeMatchParams = null;

        if (isset($metadata->fieldMappings[$field]['embedded'])) {
            $document = $this->loadDocument($event, $metadata, $documentManager, $field);
            $targetOptions = $restControllerMap->getOptionsFromEndpoint($event->getTarget()->getOptions()->getEndpoint() . '.' . $field);

            if (count($deeperResource) > 0) {
                if (!($targetDocument = $this->selectItemFromCollection(
                    $metadata->getFieldValue($document, $field),
                    array_shift($deeperResource),
                    $targetOptions->getProperty()))
                ) {
                    //embedded document not found in collection
                    throw new Exception\DocumentNotFoundException();
                }
                $event->setParam('document', $targetDocument);
            } else {
                $routeMatchParams = ['id' => false];
                $event->setParam('list', $metadata->getFieldValue($document, $field));
            }
        } else if (isset($metadata->fieldMappings[$field]['reference'])) {
            $targetOptions = $restControllerMap->getOptionsFromClass($targetMetadata->name);
            $event->getRequest()->getQuery()->set($metadata->fieldMappings[$field]['mappedBy'], $event->getParam('id'));

            if (!($id = array_shift($deeperResource))) {
                $id = false;
            }
            $routeMatchParams = ['id' => $id];
            $event->setParam('id', $id);
            $event->setParam('document', null);
        }

        $event->setParam('deeperResource', $deeperResource);
        return $event->getTarget()->forward()->dispatch(
            'shard.rest.' . $targetOptions->getEndpoint(),
            $routeMatchParams
        );
    }
}
