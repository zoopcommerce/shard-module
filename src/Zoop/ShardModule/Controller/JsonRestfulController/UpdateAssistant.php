<?php
/**
 * @package    Zoop
 * @license    MIT
 */
namespace Zoop\ShardModule\Controller\JsonRestfulController;

use Doctrine\ODM\MongoDB\Proxy\Proxy;
use Zoop\Shard\Serializer\Unserializer;
use Zoop\ShardModule\Exception;

/**
 *
 * @since   1.0
 * @version $Revision$
 * @author  Tim Roediger <superdweebie@gmail.com>
 */
class UpdateAssistant extends AbstractAssistant
{

    public function doUpdate(
        array $data,
        $document,
        array $deeperResource
    ) {
        $documentManager = $this->options->getDocumentManager();
        $metadata = $this->metadata;
        $endpoint = $this->endpoint;

        if (count($deeperResource) == 0) {

            if (isset($data[$metadata->identifier])) {
                //Remember data id for possible id update
                $dataId = $data[$metadata->identifier];
            }
            if (is_string($document)) {
                $data[$endpoint->getProperty()] = $document;
                $document = $documentManager
                    ->createQueryBuilder()
                    ->find($metadata->name)
                    ->field($endpoint->getProperty())->equals($document)
                    ->getQuery()
                    ->getSingleResult();
            }
            if (isset($document) && isset($metadata->identifier)) {
                $documentId = $metadata->reflFields[$metadata->identifier]->getValue($document);
                $data[$metadata->identifier] = $documentId;
                if (isset($dataId) && $dataId != $documentId) {
                    $newId = $dataId;
                }
            }

            $document = $this->unserialize($data, $document, $metadata, Unserializer::UNSERIALIZE_UPDATE);
            if (! $documentManager->contains($document) && ! $metadata->isEmbeddedDocument) {
                $createAssistant = $this->options->getCreateAssistant();
                $createAssistant->setController($this->controller);

                return $createAssistant->doCreate([], $document, []);
            }

            if (isset($newId)) {
                $deleteAssistant = $this->options->getDeleteAssistant();
                $deleteAssistant->setController($this->controller);
                $deleteAssistant->doDelete($document, []);

                //clone the document
                $newDocument = $metadata->newInstance();
                foreach ($metadata->reflFields as $field => $refl) {
                    $refl->setValue($newDocument, $refl->getValue($document));
                }
                $metadata->reflFields[$metadata->identifier]->setValue($newDocument, $newId);

                //update references
                $referenceMap = $this->options->getReferenceMap()->getMap();
                if (isset($referenceMap[$metadata->name])) {
                    $identityMap = $documentManager->getUnitOfWork()->getIdentityMap();
                    foreach ($referenceMap[$metadata->name] as $mapping) {
                        //update all references in the db
                        $documentManager
                            ->createQueryBuilder($mapping['class'])
                            ->update()
                            ->multiple(true)
                            ->field($mapping['field'])->equals($documentId)
                            ->field($mapping['field'])->set($newId)
                            ->getQuery()
                            ->execute();
                    }

                    //update all references for docs currently loaded in the uow
                    if (isset($identityMap[$mapping['class']])) {
                        $doucmentUsingRefMetadata = $documentManager->getClassMetadata($mapping['class']);
                        foreach ($identityMap[$mapping['class']] as $documentUsingRef) {
                            if ($mapping['type'] == 'one' &&
                                $documentId == $metadata->reflFields[$metadata->identifier]->getValue(
                                    $doucmentUsingRefMetadata->reflFields[$mapping['field']]->getValue(
                                        $documentUsingRef
                                    )
                                )
                            ) {
                                $doucmentUsingRefMetadata
                                    ->reflFields[$mapping['field']]
                                    ->setValue($documentUsingRef, $newDocument);
                            } else {
                                //TODO: mapping type == many
                            }
                        }
                    }
                }

                $createAssistant = $this->options->getCreateAssistant();
                $createAssistant->setController($this->controller);

                return $createAssistant->doCreate([], $newDocument, []);
            }

            return $document;
        }

        if (is_string($document)) {
            $document = $documentManager
                ->createQueryBuilder()
                ->find($metadata->name)
                ->field($endpoint->getProperty())->equals($document)
                ->getQuery()
                ->getSingleResult();

            if (! $document) {
                throw new Exception\DocumentNotFoundException();
            }
        }

        $field = $deeperResource[0];
        array_shift($deeperResource);
        if (! isset($metadata->fieldMappings[$field])) {
            throw new Exception\DocumentNotFoundException();
        }
        $mapping = $metadata->fieldMappings[$field];

        if (isset($mapping['reference']) && $mapping['reference'] && $mapping['type'] == 'many') {
            $referenceMetadata = $this->options
                ->getDocumentManager()
                ->getClassMetadata($metadata->fieldMappings[$field]['targetDocument']);
            $referenceEndpoint = $this->options
                ->getEndpointMap()
                ->getEndpointsFromMetadata($referenceMetadata)[0];
            $referencedDocuments = $this->forward()->dispatch(
                'rest.' . $this->options->getManifestName() . '.' . $referenceEndpoint->getName(),
                [
                    'id' => implode('/', $deeperResource),
                    'surpressResponse' => true
                ]
            );
            if (! is_array($referencedDocuments)) {
                $referencedDocuments = [$referencedDocuments];
            }
            foreach ($referencedDocuments as $referencedDocument) {
                $referenceMetadata->reflFields[$metadata->fieldMappings[$field]['mappedBy']]->setValue(
                    $referencedDocument,
                    $document
                );
            }

            return $document;
        }

        if (isset($mapping['reference']) && $mapping['reference'] && $mapping['type'] == 'one') {

            if (! $referencedDocument = $metadata->reflFields[$field]->getValue($document)) {
                throw new Exception\DocumentNotFoundException;
            }
            $referencedMetadata = $documentManager
                ->getClassMetadata($metadata->fieldMappings[$field]['targetDocument']);

            $referencedEndpoint = $this->options
                ->getEndpointMap()
                ->getEndpointsFromMetadata($referencedMetadata)[0];
            if (is_string($referencedDocument)) {
                $referencedDocument = $this->options
                    ->getDocumentManager()->getRepository($referencedMetadata->name)->find($referencedDocument);
            }
            if ($referencedDocument instanceof Proxy) {
                $referencedDocument->__load();
            }
            array_unshift(
                $deeperResource,
                $referencedMetadata->reflFields[$referencedEndpoint->getProperty()]->getValue($referencedDocument)
            );

            $updatedDocument = $this->forward()->dispatch(
                'rest.' . $this->options->getManifestName() . '.' . $referencedEndpoint->getName(),
                ['id' => implode('/', $deeperResource), 'surpressResponse' => true]
            );

            if (count($deeperResource) == 1) {
                $metadata->reflFields[$field]->setValue($document, $updatedDocument);
            }

            return $document;
        }

        if (isset($mapping['embedded']) && $mapping['embedded'] && $mapping['type'] == 'many') {
            $embeddedMetadata = $this->options
                ->getDocumentManager()
                ->getClassMetadata($metadata->fieldMappings[$field]['targetDocument']);
            $embeddedEndpoint = $endpoint->getEmbeddedLists()[$field];
            $embeddedEndpointProperty = $embeddedEndpoint->getProperty();
            $this->metadata = $embeddedMetadata;
            $collection = $metadata->reflFields[$field]->getValue($document);

            if (count($deeperResource) > 0) {

                if ($embeddedEndpointProperty == '$set') {
                    if (isset($collection[$deeperResource[0]])) {
                        $embeddedDocument = $collection[$deeperResource[0]];
                        $set = array_shift($deeperResource);
                        $this->endpoint = $embeddedEndpoint;
                        $updatedDocument = $this->doUpdate($data, $embeddedDocument, $deeperResource);
                        $collection[$set] = $updatedDocument;
                        return $document;
                    }

                    $set = array_shift($deeperResource);
                    $updatedDocument = $this->doUpdate($data, $set, $deeperResource);
                    $collection[$set] = $updatedDocument;

                    return $document;
                } else {
                    $reflField = $embeddedMetadata->reflFields[$embeddedEndpointProperty];
                    foreach ($collection as $key => $embeddedDocument) {
                        //this iteration is slow. Should be replaced when upgrade to new version of mongo happens
                        if ($reflField->getValue($embeddedDocument) == $deeperResource[0]) {
                            array_shift($deeperResource);
                            $this->endpoint = $embeddedEndpoint;
                            $updatedDocument = $this->doUpdate($data, $embeddedDocument, $deeperResource);
                            $collection[$key] = $updatedDocument;

                            return $document;
                        }
                    }
                    $updatedDocument = $this->doUpdate($data, array_shift($deeperResource), $deeperResource);
                    $collection[] = $updatedDocument;

                    return $document;
                }
            } else {
                $replaceListAssistant = $this->options->getReplaceListAssistant();
                $replaceListAssistant->setController($this->controller);
                $replaceListAssistant->doReplaceList($data, $collection);

                return $document;
            }
        }

        if (isset($mapping['embedded']) && $mapping['embedded'] && $mapping['type'] == 'one') {
            $this->metadata = $this->options
                ->getDocumentManager()
                ->getClassMetadata($metadata->fieldMappings[$field]['targetDocument']);
            $updatedDocument = $this->doUpdate(
                $data,
                $metadata->reflFields[$field]->getValue($document),
                $deeperResource
            );
            $metadata->reflFields[$field]->setValue($document, $updatedDocument);

            return $document;
        }
        throw new Exception\DocumentNotFoundException();
    }
}
