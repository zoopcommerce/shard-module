<?php
/**
 * @package    Zoop
 * @license    MIT
 */
namespace Zoop\ShardModule\Controller\JsonRestfulController;

use Doctrine\ODM\MongoDB\Proxy\Proxy;
use Zoop\ShardModule\Exception;

/**
 *
 * @since   1.0
 * @version $Revision$
 * @author  Tim Roediger <superdweebie@gmail.com>
 */
class DeleteAssistant extends AbstractAssistant
{

    public function doDelete(
        $document,
        array $deeperResource
    ){
        $documentManager = $this->options->getDocumentManager();
        $metadata = $this->metadata;
        $endpoint = $this->endpoint;

        if (count($deeperResource) == 0 ){
            if (is_string($document)){
                $documentManager
                    ->createQueryBuilder($metadata->name)
                    ->remove()
                    ->field($endpoint->getProperty())->equals($document)
                    ->getQuery()
                    ->execute();
            } else {
                $documentManager->remove($document);
            }
            return;
        }

        $field = $deeperResource[0];
        array_shift($deeperResource);
        $mapping = $metadata->fieldMappings[$field];

        if (isset($mapping['reference']) && $mapping['reference'] && $mapping['type'] == 'many'){
            $this->controller->getRequest()->getQuery()->set($metadata->fieldMappings[$field]['mappedBy'], $document);
            $referenceMetadata = $this->options
                ->getDocumentManager()
                ->getClassMetadata($metadata->fieldMappings[$field]['targetDocument']);
            $referenceEndpoint = $this->options
                ->getEndpointMap()
                ->getEndpointsFromClass($referenceMetadata->name)[0];
            return $this->forward()->dispatch(
                'rest.' . $this->options->getManifestName() . '.' . $referenceEndpoint->getName(),
                ['id' => implode('/', $deeperResource), 'surpressResponse' => true]
            );
        }

        if (is_string($document)){
            $document = $documentManager
                ->createQueryBuilder()
                ->find($metadata->name)
                ->field($this->endpoint->getProperty())->equals($document)
                ->getQuery()
                ->getSingleResult();

            if ( ! $document){
                throw new Exception\DocumentNotFoundException();
            }
        }

        if (isset($mapping['reference']) && $mapping['reference'] && $mapping['type'] == 'one'){
            if (count($deeperResource) > 0){
                if ( ! $referencedDocument = $metadata->reflFields[$field]->getValue($document)){
                    throw new Exception\DocumentNotFoundException;
                }
                $referencedMetadata = $documentManager->getClassMetadata($metadata->fieldMappings[$field]['targetDocument']);
                $referencedEndpoint = $this->options
                    ->getEndpointMap()
                    ->getEndpointsFromClass($referencedMetadata->name)[0];
                if (is_string($referencedDocument)){
                    $referencedDocument = $this->options->getDocumentManager()->getRepository($referencedMetadata->name)->find($referencedDocument);
                }
                if ($referencedDocument instanceof Proxy){
                    $referencedDocument->__load();
                }
                array_unshift($deeperResource, $referencedMetadata->reflFields[$referencedEndpoint->getProperty()]->getValue($referencedDocument));

                return $this->forward()->dispatch(
                    'rest.' . $this->options->getManifestName() . '.' . $referencedEndpoint->getName(),
                    [
                        'id' => implode('/', $deeperResource),
                        'surpressResponse' => true
                    ]
                );
            } else {
                $metadata->reflFields[$field]->setValue($document, null);
                return;
            }
        }

        if (isset($mapping['embedded']) && $mapping['embedded'] && $mapping['type'] == 'many'){
            $embeddedMetadata = $this->options
                ->getDocumentManager()
                ->getClassMetadata($metadata->fieldMappings[$field]['targetDocument']);
            $embeddedEndpoint = $endpoint->getEmbeddedLists()[$field];
            $embeddedEndpointProperty = $embeddedEndpoint->getProperty();
            $this->metadata = $embeddedMetadata;
            $collection = $metadata->reflFields[$field]->getValue($document);
            if (count($deeperResource) > 0){
                $embeddedId = $deeperResource[0];
                array_shift($deeperResource);
                if( ! ($embeddedDocument = $collection->filter(function($item) use ($embeddedId, $embeddedMetadata, $embeddedEndpointProperty){
                    if ($embeddedMetadata->reflFields[$embeddedEndpointProperty]->getValue($item) == $embeddedId){
                        return true;
                    }
                })[0])){
                    throw new Exception\DocumentNotFoundException;
                };
                if (count($deeperResource) == 0){
                    $collection->removeElement($embeddedDocument);
                    return;
                } else {
                    return $this->doDelete(
                        $embeddedDocument,
                        $embeddedMetadata,
                        $deeperResource
                    );
                }
            } else {
                $deleteListAssistant = $this->options->getDeleteListAssistant();
                $deleteListAssistant->setController($this->controller);
                return $deleteListAssistant->doDeleteList($collection);
            }
        }

        if (isset($mapping['embedded']) && $mapping['embedded'] && $mapping['type'] == 'one'){
            if (count($deeperResource) > 0){
                $this->metadata = $this->options
                    ->getDocumentManager()
                    ->getClassMetadata($metadata->fieldMappings[$field]['targetDocument']);
                $embeddedDocument = $metadata->reflFields[$field]->getValue($document);
                if (!isset($embeddedDocument)){
                    throw new Exception\DocumentNotFoundException();
                }
                return $this->doDelete($embeddedDocument, $deeperResource);
            } else {
                $metadata->reflFields[$field]->setValue($document, null);
                return;
            }
        }

        throw new Exception\DocumentNotFoundException();
    }
}
