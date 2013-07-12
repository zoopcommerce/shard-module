<?php
/**
 * @package    Zoop
 * @license    MIT
 */
namespace Zoop\ShardModule\Controller\JsonRestfulController;

use Doctrine\ODM\MongoDB\Proxy\Proxy;
use Zoop\Shard\Serializer\Serializer;
use Zoop\ShardModule\Exception;

/**
 *
 * @since   1.0
 * @version $Revision$
 * @author  Tim Roediger <superdweebie@gmail.com>
 */
class CreateAssistant extends AbstractAssistant
{

    public function doCreate(
        array $data,
        $document,
        array $deeperResource
    ){

        $metadata = $this->metadata;
        $documentManager = $this->options->getDocumentManager();

        if (count($deeperResource) == 0){
            $createdDocument = $this->unserialize($data, $document, $metadata, Serializer::UNSERIALIZE_PATCH);
            if ($documentManager->contains($createdDocument)){
                $exception = new Exception\DocumentAlreadyExistsException();
                $exception->setDocument($createdDocument);
                throw $exception;
            }
            if ( ! $metadata->isEmbeddedDocument){
                $documentManager->persist($createdDocument);
            }
            return $createdDocument;
        }

        $field = $deeperResource[0];
        array_shift($deeperResource);

        $mapping = $metadata->fieldMappings[$field];

        if (is_string($document)){
            $document = $documentManager
                ->createQueryBuilder()
                ->find($metadata->name)
                ->field($this->endpoint->getProperty())->equals($document)
                ->hydrate(true)
                ->getQuery()
                ->getSingleResult();

            if ( ! $document){
                throw new Exception\DocumentNotFoundException();
            }
        }

        if (isset($mapping['reference']) && $mapping['reference'] && $mapping['type'] == 'many'){
            $referencedMetadata = $this
                ->options
                ->getDocumentManager()
                ->getClassMetadata($metadata->fieldMappings[$field]['targetDocument']);
            try {
                $endpoint = $this->options
                    ->getEndpointMap()
                    ->getEndpointsFromClass($referencedMetadata->name)[0];
                $createdDocument = $this->forward()->dispatch(
                    'rest.' . $this->options->getManifestName() . '.' . $endpoint->getName(),
                    [
                        'id' => implode('/', $deeperResource),
                        'surpressResponse' => true
                    ]
                );
            } catch (Exception\DocumentAlreadyExistsException $exception){
                $createdDocument = $exception->getDocument();
            }
            $collection = $metadata->reflFields[$field]->getValue($document);
            if ($collection->contains($createdDocument)){
                throw new Exception\DocumentAlreadyExistsException();
            }
            if (isset($mapping['mappedBy'])){
                if ($createdDocument instanceof Proxy){
                    $createdDocument->__load();
                }
                $referencedMetadata->reflFields[$mapping['mappedBy']]->setValue($createdDocument, $document);
            }
            return $createdDocument;
        }

        if (isset($mapping['reference']) && $mapping['reference'] && $mapping['type'] == 'one'){
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
        }

        if (isset($mapping['embedded']) && $mapping['embedded'] && $mapping['type'] == 'many'){
            $embeddedMetadata = $this
                ->options
                ->getDocumentManager()
                ->getClassMetadata($metadata->fieldMappings[$field]['targetDocument']);
            $this->metadata = $embeddedMetadata;
            $embeddedEndpoint = $this->endpoint->getEmbeddedLists()[$field];
            $embeddedEndpointProperty = $embeddedEndpoint->getProperty();
            $reflField = $embeddedMetadata->reflFields[$embeddedEndpointProperty];
            $collection = $metadata->reflFields[$field]->getValue($document);
            if (count($deeperResource) > 0){
                foreach ($collection as $embeddedDocument){ //this iteration is slow. Should be replaced when upgrade to new version of mongo happens
                    if ($reflField->getValue($embeddedDocument) == $deeperResource[0]){
                        array_shift($deeperResource);
                        $this->endpoint = $embeddedEndpoint;
                        return $this->doCreate($data, $embeddedDocument, $deeperResource);
                    }
                }
                //embedded document not found in collection
                throw new Exception\DocumentNotFoundException();
            } else {
                $createdDocument = $this->doCreate(
                    $data,
                    null,
                    $deeperResource
                );
                foreach ($collection as $embeddedDocument){
                    if ($reflField->getValue($embeddedDocument) == $reflField->getValue($createdDocument)){
                        throw new Exception\DocumentAlreadyExistsException();
                    }
                }
                $collection[] = $createdDocument;
                return $createdDocument;
            }
        }

        if (isset($mapping['embedded']) && $mapping['embedded'] && $mapping['type'] == 'one'){
            $this->metadata = $this
                ->options
                ->getDocumentManager()
                ->getClassMetadata($metadata->fieldMappings[$field]['targetDocument']);
            return $this->doCreate(
                $data,
                $metadata->reflFields[$field]->getValue($document),
                $deeperResource
            );
        }
        throw new Exception\DocumentNotFoundException();
    }
}
